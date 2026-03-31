<?php

declare(strict_types=1);

$rootDir = dirname(__DIR__);
$cssDir = $rootDir . '/assets/css';
$jsDir = $rootDir . '/assets/js';
$distDir = $rootDir . '/assets/dist';

if (!is_dir($distDir) && !mkdir($distDir, 0775, true) && !is_dir($distDir)) {
    fwrite(STDERR, "Impossible de créer le dossier dist (" . $distDir . ")." . PHP_EOL);
    exit(1);
}

buildCssBundle($cssDir, $distDir);
buildJsBundle($jsDir, $distDir);

echo "Build terminé." . PHP_EOL;

function buildCssBundle(string $cssDir, string $distDir): void
{
    $cssFiles = glob(rtrim($cssDir, '/\\') . '/*.css') ?: [];

    if (!$cssFiles) {
        echo "Aucun fichier CSS à traiter." . PHP_EOL;
        return;
    }

    sort($cssFiles);
    $buffer = '';

    foreach ($cssFiles as $file) {
        $contents = file_get_contents($file);
        if ($contents === false) {
            fwrite(STDERR, "Lecture impossible : {$file}" . PHP_EOL);
            continue;
        }
        $buffer .= "/* Source: " . basename($file) . " */\n" . $contents . "\n";
    }

    $minified = minifyCss($buffer);
    $target = $distDir . '/app.min.css';
    file_put_contents($target, $minified);
    echo "CSS minifié → {$target} (" . strlen($minified) . " octets)." . PHP_EOL;
}

function buildJsBundle(string $jsDir, string $distDir): void
{
    $jsFiles = glob(rtrim($jsDir, '/\\') . '/*.js') ?: [];

    if (!$jsFiles) {
        echo "Aucun fichier JS à traiter." . PHP_EOL;
        return;
    }

    sort($jsFiles);
    $buffer = '';

    foreach ($jsFiles as $file) {
        $contents = file_get_contents($file);
        if ($contents === false) {
            fwrite(STDERR, "Lecture impossible : {$file}" . PHP_EOL);
            continue;
        }
        $buffer .= "// Source: " . basename($file) . "\n" . $contents . "\n";
    }

    $minified = minifyJs($buffer);
    $target = $distDir . '/app.min.js';
    file_put_contents($target, $minified);
    echo "JS minifié → {$target} (" . strlen($minified) . " octets)." . PHP_EOL;
}

function minifyCss(string $css): string
{
    $css = preg_replace('!/\*.*?\*/!s', '', $css) ?? $css;
    $css = preg_replace('/\s+/', ' ', $css) ?? $css;
    $css = preg_replace('/\s*([{};:,])\s*/', '$1', $css) ?? $css;
    $css = str_replace(';}', '}', $css);

    return trim($css);
}

function minifyJs(string $js): string
{
    $js = preg_replace('!/\*.*?\*/!s', '', $js) ?? $js;
    $js = preg_replace('/(^|[^:\\])\/\/.*(?=\n)/m', '$1', $js) ?? $js;
    $js = preg_replace('/\s+/', ' ', $js) ?? $js;
    $js = preg_replace('/\s*([=+\-*/{}();,:<>])\s*/', '$1', $js) ?? $js;

    return trim($js);
}
