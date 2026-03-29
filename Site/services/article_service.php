<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/slug.php';

function articleFormDefaults(): array
{
    return [
        'title' => '',
        'meta_title' => '',
        'meta_description' => '',
        'content' => '',
    ];
}

function validateArticleInput(array $input): array
{
    $data = [
        'title' => trim($input['title'] ?? ''),
        'meta_title' => trim($input['meta_title'] ?? ''),
        'meta_description' => trim($input['meta_description'] ?? ''),
        'content' => trim($input['content'] ?? ''),
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

    if (!$errors) {
        try {
            $connection = getDbConnection();
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

            $createdArticle = [
                'id' => (int) $newId,
                'title' => $data['title'],
                'slug' => $slug,
                'published_at' => $publishedAt,
            ];

            $data = articleFormDefaults();
        } catch (Throwable $e) {
            $errors[] = 'Erreur lors de la création : ' . $e->getMessage();
        }
    }

    return [$data, $errors, $createdArticle];
}
