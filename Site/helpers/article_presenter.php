<?php

declare(strict_types=1);

// Fonctions utilitaires partagées pour la présentation des articles.

function formatArticlePreview(array $article): string
{
    $candidate = $article['meta_description'] ?? '';
    if ($candidate === '') {
        $candidate = strip_tags((string) ($article['content'] ?? ''));
    }

    $candidate = preg_replace('/\s+/', ' ', $candidate ?? '');
    $candidate = trim((string) $candidate);

    if ($candidate === '') {
        return 'Contenu en cours de préparation.';
    }

    if (mb_strlen($candidate) > 200) {
        $candidate = mb_substr($candidate, 0, 200) . '…';
    }

    return $candidate;
}

function formatArticleDate(?string $date): string
{
    if (!$date) {
        return '';
    }

    try {
        return (new DateTimeImmutable($date))->format('d M Y · H\hi');
    } catch (Throwable $e) {
        return $date;
    }
}

function articleCoverAlt(array $article): string
{
    $alt = trim((string) ($article['cover_image_alt'] ?? ''));

    if ($alt === '' && isset($article['title'])) {
        $alt = (string) $article['title'];
    }

    return $alt !== '' ? $alt : 'Illustration Iran Focus';
}

function articleMonogram(array $article): string
{
    $title = trim((string) ($article['title'] ?? ''));
    if ($title === '') {
        return 'IF';
    }

    $firstChar = mb_substr($title, 0, 1);

    return mb_strtoupper($firstChar ?? 'I');
}
