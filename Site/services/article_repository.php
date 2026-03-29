<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/url.php';

const DEFAULT_CATEGORY_ID = 1;

function getPublishedArticles(int $page, int $perPage = 6): array
{
    $page = max(1, $page);
    $perPage = max(1, min(12, $perPage));
    $offset = ($page - 1) * $perPage;

    $connection = getDbConnection();

    $totalResult = $connection->query("SELECT COUNT(*) AS total FROM articles WHERE status = 'published'");
    $totalRow = $totalResult ? $totalResult->fetch_assoc() : ['total' => 0];
    $totalItems = (int) ($totalRow['total'] ?? 0);

    $stmt = $connection->prepare(
        "SELECT a.id, a.title, a.slug, a.meta_title, a.meta_description, a.content, a.published_at, u.name AS author_name
         FROM articles a
         LEFT JOIN users u ON u.id = a.author_id
         WHERE a.status = 'published'
         ORDER BY a.published_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $articles = [];
    while ($row = $result->fetch_assoc()) {
        $row['url'] = getUrl($row['slug'], (int) $row['id'], DEFAULT_CATEGORY_ID);
        $articles[] = $row;
    }
    $stmt->close();

    $totalPages = max(1, (int) ceil($totalItems / $perPage));

    return [
        'items' => $articles,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
        ],
    ];
}

function findPublishedArticle(int $id, string $slug): ?array
{
    if ($id <= 0 || $slug === '') {
        return null;
    }

    $connection = getDbConnection();
    $stmt = $connection->prepare(
        "SELECT a.id, a.title, a.slug, a.meta_title, a.meta_description, a.content, a.published_at, u.name AS author_name
         FROM articles a
         LEFT JOIN users u ON u.id = a.author_id
         WHERE a.status = 'published' AND a.id = ? AND a.slug = ?
         LIMIT 1"
    );
    $stmt->bind_param('is', $id, $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $article = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if ($article) {
        $article['url'] = getUrl($article['slug'], (int) $article['id'], DEFAULT_CATEGORY_ID);
    }

    return $article ?: null;
}
