<?php

declare(strict_types=1);

require_once __DIR__ . '/article_repository.php';

function getSiteFeed(int $page = 1, int $perPage = 6): array
{
    $articlesData = getPublishedArticles($page, $perPage);
    $articles = $articlesData['items'];
    $pagination = $articlesData['pagination'];

    $featured = $articles[0] ?? null;
    $listing = $featured ? array_slice($articles, 1) : $articles;
    $timeline = array_slice($articles, 0, 3);

    return [
        'articles' => $articles,
        'featured' => $featured,
        'listing' => $listing,
        'timeline' => $timeline,
        'pagination' => $pagination,
    ];
}
