<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/slug.php';
require_once __DIR__ . '/../helpers/url.php';

function articleFormDefaults(): array
{
    return [
        'title' => '',
        'meta_title' => '',
        'meta_description' => '',
        'content' => '',
        'cover_alt' => '',
    ];
}

function validateArticleInput(array $input): array
{
    $data = [
        'title' => trim($input['title'] ?? ''),
        'meta_title' => trim($input['meta_title'] ?? ''),
        'meta_description' => trim($input['meta_description'] ?? ''),
        'content' => trim($input['content'] ?? ''),
        'cover_alt' => trim($input['cover_alt'] ?? ''),
    ];

    $errors = [];

    if ($data['title'] === '' || mb_strlen($data['title']) < 10) {
        $errors[] = 'Le titre doit contenir au moins 10 caractères.';
    }

    if ($data['content'] === '' || mb_strlen(strip_tags($data['content'])) < 50) {
        $errors[] = 'Le contenu TinyDocs doit contenir au moins 50 caractères.';
    }

    if (mb_strlen($data['meta_title']) > 255) {
        $errors[] = 'La balise meta title ne doit pas dépasser 255 caractères.';
    }

    if (mb_strlen($data['meta_description']) > 400) {
        $errors[] = 'La meta description est trop longue (400 caractères max).';
    }

    if (mb_strlen($data['cover_alt']) > 255) {
        $errors[] = 'Le texte alternatif de l\'image doit faire 255 caractères maximum.';
    }

    return [$data, $errors];
}

function slugExists(mysqli $connection, string $slug): bool
{
    $query = 'SELECT id FROM articles WHERE slug = ? LIMIT 1';
    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

function ensureUniqueSlug(mysqli $connection, string $baseSlug): string
{
    $slug = $baseSlug !== '' ? $baseSlug : 'article';
    $candidate = $slug;
    $suffix = 1;

    while (slugExists($connection, $candidate)) {
        $candidate = $slug . '-' . $suffix;
        $suffix++;
    }

    return $candidate;
}

function handleArticleCreation(array $input, array $author): array
{
    [$data, $errors] = validateArticleInput($input);
    $createdArticle = null;
    $coverImageFile = $_FILES['cover_image'] ?? null;
    $coverImagePlan = validateCoverImageUpload($coverImageFile);

    if ($coverImagePlan['status'] === 'error') {
        $errors[] = $coverImagePlan['message'];
    }

    if (!$errors) {
        $connection = null;
        $storedCover = null;

        try {
            $connection = getDbConnection();
            $connection->begin_transaction();
            $baseSlug = createSlug($data['title']);
            $slug = ensureUniqueSlug($connection, $baseSlug);



            $metaTitle = $data['meta_title'] !== '' ? $data['meta_title'] : $data['title'];
            $metaDescription = $data['meta_description'] !== ''
                ? $data['meta_description']
                : mb_substr(trim(strip_tags($data['content'])), 0, 160);

            $publishedAt = (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');

            $query = 'INSERT INTO articles (title, slug, content, meta_title, meta_description, status, author_id, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $connection->prepare($query);
            $status = 'published';
            $authorId = (int) $author['id'];
            $stmt->bind_param(
                'ssssssis',
                $data['title'],
                $slug,
                $data['content'],
                $metaTitle,
                $metaDescription,
                $status,
                $authorId,
                $publishedAt
            );
            $stmt->execute();
            $newId = $connection->insert_id;
            $stmt->close();

            if ($coverImagePlan['status'] === 'ready') {
                $storedCover = persistCoverImage($coverImageFile, $coverImagePlan['extension']);
                $coverAlt = resolveCoverAltText($data['cover_alt'], $data['title']);

                insertCoverImageRecord(
                    $connection,
                    $storedCover['public_path'],
                    $coverAlt,
                    $coverImagePlan['mime_type'],
                    $coverImagePlan['size'],
                    (int) $newId
                );
            }

            $connection->commit();

            $createdArticle = [
                'id' => (int) $newId,
                'title' => $data['title'],
                'slug' => $slug,
                'published_at' => $publishedAt,
                'url' => getUrl($slug, (int) $newId, 1),
            ];

            $data = articleFormDefaults();
        } catch (Throwable $e) {
            if ($connection instanceof mysqli) {
                $connection->rollback();
            }

            if ($storedCover && isset($storedCover['absolute_path']) && is_file($storedCover['absolute_path'])) {
                @unlink($storedCover['absolute_path']);
            }

            $errors[] = 'Erreur lors de la création : ' . $e->getMessage();
        }
    }

    return [$data, $errors, $createdArticle];
}

function validateCoverImageUpload(?array $file): array
{
    if ($file === null || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['status' => 'skip'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'status' => 'error',
            'message' => 'Téléversement de l\'image impossible (code ' . (int) $file['error'] . ').',
        ];
    }

    $maxSize = 5 * 1024 * 1024; // 5 Mo
    if ((int) ($file['size'] ?? 0) > $maxSize) {
        return [
            'status' => 'error',
            'message' => 'L\'image dépasse la taille maximale autorisée (5 Mo).',
        ];
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    $detectedMime = $file['type'] ?? '';
    if (extension_loaded('fileinfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $probe = $finfo->file($file['tmp_name']);
        if ($probe) {
            $detectedMime = $probe;
        }
    }

    if (!isset($allowed[$detectedMime])) {
        return [
            'status' => 'error',
            'message' => 'Format d\'image non supporté. Utilisez jpg, png, gif ou webp.',
        ];
    }

    return [
        'status' => 'ready',
        'extension' => $allowed[$detectedMime],
        'mime_type' => $detectedMime,
        'size' => (int) $file['size'],
    ];
}

function persistCoverImage(array $file, string $extension): array
{
    $uploadDir = __DIR__ . '/../assets/upload';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Impossible de créer le dossier des images.');
    }

    try {
        $token = bin2hex(random_bytes(6));
    } catch (Throwable $e) {
        throw new RuntimeException('Impossible de générer un nom de fichier pour l\'image.', 0, $e);
    }

    $filename = sprintf('%s-%s.%s', date('YmdHis'), $token, $extension);
    $destination = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Impossible de sauvegarder l\'image téléchargée.');
    }

    return [
        'public_path' => '/assets/upload/' . $filename,
        'absolute_path' => $destination,
    ];
}

function resolveCoverAltText(string $providedAlt, string $fallbackTitle): string
{
    $alt = $providedAlt !== '' ? $providedAlt : $fallbackTitle;

    return mb_substr($alt, 0, 255);
}

function insertCoverImageRecord(
    mysqli $connection,
    string $filePath,
    string $altText,
    string $mimeType,
    int $size,
    int $articleId
): void {
    $query = 'INSERT INTO media (file_path, alt_text, mime_type, size, id_article) VALUES (?, ?, ?, ?, ?)';
    $stmt = $connection->prepare($query);
    $stmt->bind_param(
        'sssii',
        $filePath,
        $altText,
        $mimeType,
        $size,
        $articleId
    );
    $stmt->execute();
    $stmt->close();
}
