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

function articleFormFromExisting(array $article): array
{
    return [
        'title' => $article['title'] ?? '',
        'meta_title' => $article['meta_title'] ?? '',
        'meta_description' => $article['meta_description'] ?? '',
        'content' => $article['content'] ?? '',
        'cover_alt' => $article['cover_alt'] ?? '',
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

function slugExists(mysqli $connection, string $slug, ?int $excludeId = null): bool
{
    if ($excludeId) {
        $query = 'SELECT id FROM articles WHERE slug = ? AND id != ? LIMIT 1';
        $stmt = $connection->prepare($query);
        $stmt->bind_param('si', $slug, $excludeId);
    } else {
        $query = 'SELECT id FROM articles WHERE slug = ? LIMIT 1';
        $stmt = $connection->prepare($query);
        $stmt->bind_param('s', $slug);
    }

    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

function ensureUniqueSlug(mysqli $connection, string $baseSlug, ?int $excludeId = null): string
{
    $slug = $baseSlug !== '' ? $baseSlug : 'article';
    $candidate = $slug;
    $suffix = 1;

    while (slugExists($connection, $candidate, $excludeId)) {
        $candidate = $slug . '-' . $suffix;
        $suffix++;
    }

    return $candidate;
}


function handleArticleCreation(array $input, array $author, array $files = []): array
{
    [$data, $errors] = validateArticleInput($input);
    $createdArticle = null;
    $coverImageFile = $files['cover_image'] ?? null;
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

function fetchArticleStats(): array
{
    $stats = [
        'total' => 0,
        'published' => 0,
        'draft' => 0,
        'delete' => 0,
    ];

    $connection = getDbConnection();
    $result = $connection->query("SELECT status, COUNT(*) AS total FROM articles GROUP BY status");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $status = $row['status'] ?? 'draft';
            $count = (int) ($row['total'] ?? 0);

            if (array_key_exists($status, $stats)) {
                $stats[$status] = $count;
            }
        }
        $result->free();
    }

    $stats['total'] = $stats['published'] + $stats['draft'] + $stats['delete'];

    return $stats;
}

function fetchAdminArticles(): array
{
    $connection = getDbConnection();
    $query = "SELECT a.id,
                     a.title,
                     a.slug,
                     a.status,
                     a.published_at,
                     a.updated_at,
                     a.created_at,
                     u.name AS author_name
              FROM articles a
              LEFT JOIN users u ON u.id = a.author_id
              ORDER BY a.updated_at DESC, a.id DESC";

    $result = $connection->query($query);
    $articles = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $row['url'] = getUrl($row['slug'], (int) $row['id'], 1);
            $articles[] = $row;
        }
        $result->free();
    }

    return $articles;
}

function findArticleForAdmin(int $articleId): ?array
{
    if ($articleId <= 0) {
        return null;
    }

    $connection = getDbConnection();
    $article = findArticleRecord($connection, $articleId);

    if ($article) {
        $article['url'] = getUrl($article['slug'], (int) $article['id'], 1);
    }

    return $article;
}

function handleArticleUpdate(int $articleId, array $input, array $files = []): array
{
    [$data, $errors] = validateArticleInput($input);
    $updatedArticle = null;

    if ($articleId <= 0) {
        $errors[] = 'Article à mettre à jour invalide.';
    }

    $coverImageFile = $files['cover_image'] ?? null;
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

            $existing = findArticleRecord($connection, $articleId);
            if (!$existing) {
                throw new RuntimeException('Article introuvable.');
            }

            $baseSlug = createSlug($data['title']);
            $slug = ensureUniqueSlug($connection, $baseSlug, $articleId);

            $metaTitle = $data['meta_title'] !== '' ? $data['meta_title'] : $data['title'];
            $metaDescription = $data['meta_description'] !== ''
                ? $data['meta_description']
                : mb_substr(trim(strip_tags($data['content'])), 0, 160);

            $stmt = $connection->prepare(
                'UPDATE articles SET title = ?, slug = ?, content = ?, meta_title = ?, meta_description = ? WHERE id = ?'
            );
            $stmt->bind_param(
                'sssssi',
                $data['title'],
                $slug,
                $data['content'],
                $metaTitle,
                $metaDescription,
                $articleId
            );
            $stmt->execute();
            $stmt->close();

            if ($coverImagePlan['status'] === 'ready') {
                $storedCover = persistCoverImage($coverImageFile, $coverImagePlan['extension']);
                deleteArticleMediaRecords($connection, $articleId);
                $coverAlt = resolveCoverAltText($data['cover_alt'], $data['title']);

                insertCoverImageRecord(
                    $connection,
                    $storedCover['public_path'],
                    $coverAlt,
                    $coverImagePlan['mime_type'],
                    $coverImagePlan['size'],
                    $articleId
                );
            } elseif ($existing && isset($existing['cover_media_id']) && $existing['cover_media_id']) {
                $coverAlt = resolveCoverAltText($data['cover_alt'], $data['title']);
                updateCoverAltText($connection, (int) $existing['cover_media_id'], $coverAlt);
            }

            $connection->commit();

            $updatedArticle = findArticleRecord($connection, $articleId);
            if ($updatedArticle) {
                $updatedArticle['url'] = getUrl($updatedArticle['slug'], $articleId, 1);
            }
        } catch (Throwable $e) {
            if ($connection instanceof mysqli) {
                $connection->rollback();
            }

            if ($storedCover && isset($storedCover['absolute_path']) && is_file($storedCover['absolute_path'])) {
                @unlink($storedCover['absolute_path']);
            }

            $errors[] = 'Erreur lors de la mise à jour : ' . $e->getMessage();
        }
    }

    return [$data, $errors, $updatedArticle];
}

function handleArticleDeletion(int $articleId): array
{
    $errors = [];
    $deletedArticle = null;

    if ($articleId <= 0) {
        $errors[] = 'Article à supprimer invalide.';

        return [$errors, $deletedArticle];
    }

    $connection = null;

    try {
        $connection = getDbConnection();
        $connection->begin_transaction();

        $article = findArticleRecord($connection, $articleId);
        if (!$article) {
            throw new RuntimeException('Article introuvable.');
        }

        $article['url'] = getUrl($article['slug'], $articleId, 1);

        deleteArticleMediaRecords($connection, $articleId);

        $stmt = $connection->prepare('DELETE FROM articles WHERE id = ?');
        $stmt->bind_param('i', $articleId);
        $stmt->execute();
        $stmt->close();

        $connection->commit();
        $deletedArticle = $article;
    } catch (Throwable $e) {
        if ($connection instanceof mysqli) {
            $connection->rollback();
        }

        $errors[] = 'Suppression impossible : ' . $e->getMessage();
    }

    return [$errors, $deletedArticle];
}

function deleteArticleMediaRecords(mysqli $connection, int $articleId): void
{
    $selectStmt = $connection->prepare('SELECT file_path FROM media WHERE id_article = ?');
    $selectStmt->bind_param('i', $articleId);
    $selectStmt->execute();
    $result = $selectStmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            removePhysicalMediaFile($row['file_path'] ?? null);
        }
        $result->free();
    }
    $selectStmt->close();

    $deleteStmt = $connection->prepare('DELETE FROM media WHERE id_article = ?');
    $deleteStmt->bind_param('i', $articleId);
    $deleteStmt->execute();
    $deleteStmt->close();
}

function removePhysicalMediaFile(?string $publicPath): void
{
    if (!$publicPath) {
        return;
    }

    $relativePath = ltrim($publicPath, '/');
    $absolute = realpath(__DIR__ . '/../' . $relativePath) ?: (__DIR__ . '/../' . $relativePath);

    if (is_file($absolute)) {
        @unlink($absolute);
    }
}

function findArticleRecord(mysqli $connection, int $articleId): ?array
{
    $stmt = $connection->prepare(
        "SELECT a.id,
                a.title,
                a.slug,
                a.content,
                a.meta_title,
                a.meta_description,
                a.status,
                a.author_id,
                a.published_at,
                a.created_at,
                a.updated_at,
                (SELECT m.id FROM media m WHERE m.id_article = a.id ORDER BY m.id DESC LIMIT 1) AS cover_media_id,
                (SELECT m.file_path FROM media m WHERE m.id_article = a.id ORDER BY m.id DESC LIMIT 1) AS cover_image_path,
                (SELECT m.alt_text FROM media m WHERE m.id_article = a.id ORDER BY m.id DESC LIMIT 1) AS cover_alt
         FROM articles a
         WHERE a.id = ?
         LIMIT 1"
    );
    $stmt->bind_param('i', $articleId);
    $stmt->execute();
    $result = $stmt->get_result();
    $article = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $article ?: null;
}

function updateCoverAltText(mysqli $connection, int $mediaId, string $altText): void
{
    if ($mediaId <= 0) {
        return;
    }

    $stmt = $connection->prepare('UPDATE media SET alt_text = ? WHERE id = ?');
    $stmt->bind_param('si', $altText, $mediaId);
    $stmt->execute();
    $stmt->close();
}
