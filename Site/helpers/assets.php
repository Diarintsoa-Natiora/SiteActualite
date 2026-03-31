<?php

declare(strict_types=1);

function stylesheetHref(): string
{
    return assetFileUrl(
        __DIR__ . '/../assets/dist/app.min.css',
        '/assets/dist/app.min.css',
        __DIR__ . '/../assets/css/app.css',
        '/assets/css/app.css'
    );
}

function scriptSrc(): ?string
{
    return assetFileUrl(
        __DIR__ . '/../assets/dist/app.min.js',
        '/assets/dist/app.min.js',
        __DIR__ . '/../assets/js/app.js',
        '/assets/js/app.js',
        true
    );
}

function assetFileUrl(
    string $optimizedPath,
    string $optimizedPublicPath,
    string $fallbackPath,
    string $fallbackPublicPath,
    bool $allowMissingFallback = false
): ?string {
    if (is_file($optimizedPath)) {
        return $optimizedPublicPath . '?v=' . (string) filemtime($optimizedPath);
    }

    if (is_file($fallbackPath)) {
        return $fallbackPublicPath . '?v=' . (string) filemtime($fallbackPath);
    }

    return $allowMissingFallback ? null : $fallbackPublicPath;
}
