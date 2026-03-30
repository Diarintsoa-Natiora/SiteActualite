<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../services/article_repository.php';

header('Content-Type: text/html; charset=utf-8');

$slug = $_GET['slug'] ?? '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$article = findPublishedArticle($id, $slug);
$currentUser = $_SESSION['user'] ?? null;

if (!$article) {
    http_response_code(404);
}

function articleMetaDescription(?array $article): string
{
    if (!$article) {
        return 'Article introuvable ou non publié.';
    }

    $candidate = $article['meta_description'] ?: strip_tags($article['content']);
    $candidate = preg_replace('/\s+/', ' ', $candidate);
    $candidate = trim((string) $candidate);

    if ($candidate === '') {
        return 'Analyse exclusive sur la guerre en Iran.';
    }

    if (mb_strlen($candidate) > 160) {
        $candidate = mb_substr($candidate, 0, 160) . '…';
    }

    return $candidate;
}

$pageTitle = $article ? ($article['meta_title'] ?: $article['title']) : 'Article introuvable';
$metaDescription = articleMetaDescription($article);
$articleDate = $article ? (new DateTimeImmutable($article['published_at']))->format('d M Y · H\hi') : '';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($article): ?>
        <link rel="canonical" href="<?= htmlspecialchars($article['url'], ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="page page--site">
<header class="masthead masthead--solid">
    <div class="brand">Iran Focus</div>
    <nav class="nav">
        <a href="/site" class="nav__link">Toutes les actus</a>
        <?php if ($currentUser): ?>
            <a href="/redaction" class="nav__link">Rédaction</a>
            <a href="/bienvenue" class="nav__link">Espace <?= htmlspecialchars($currentUser['role'], ENT_QUOTES, 'UTF-8') ?></a>
            <a href="/deconnexion" class="nav__link nav__link--accent">Déconnexion</a>
        <?php else: ?>
            <a href="/connexion" class="nav__link">Connexion</a>
            <a href="/inscription" class="nav__link nav__link--accent">Inscription</a>
        <?php endif; ?>
    </nav>
</header>

<main class="article-page">
    <?php if ($article): ?>
        <article class="card article-card">
            <p class="eyebrow">Publié le <?= htmlspecialchars($articleDate, ENT_QUOTES, 'UTF-8') ?></p>
            <h1><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="muted">Par <?= htmlspecialchars($article['author_name'] ?: 'Rédaction', ENT_QUOTES, 'UTF-8') ?></p>
            <?php if (!empty($article['cover_image_path'])): ?>
                <figure class="article-cover">
                    <img src="<?= htmlspecialchars($article['cover_image_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($article['cover_image_alt'] ?: $article['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php if (!empty($article['cover_image_alt'])): ?>
                        <figcaption><?= htmlspecialchars($article['cover_image_alt'], ENT_QUOTES, 'UTF-8') ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endif; ?>
            <div class="article-content">
                <?= $article['content'] ?>
            </div>
        </article>
    <?php else: ?>
        <section class="card">
            <h1>Article introuvable</h1>
            <p>Le contenu demandé n'est plus disponible ou n'a pas encore été publié.</p>
            <a class="btn btn--primary" href="/site">Retourner aux actualités</a>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
