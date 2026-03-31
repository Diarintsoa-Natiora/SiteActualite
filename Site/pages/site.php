<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../services/site_feed_service.php';
require_once __DIR__ . '/../helpers/article_presenter.php';
require_once __DIR__ . '/../helpers/assets.php';

header('Content-Type: text/html; charset=utf-8');

$currentUser = $_SESSION['user'] ?? null;
$pageParam = isset($_GET['page']) ? (int) $_GET['page'] : 1;

$articlesData = getPublishedArticles($pageParam, 6);
$articles = $articlesData['items'];
$pagination = $articlesData['pagination'];
$featuredArticle = $articles[0] ?? null;
$listingArticles = $featuredArticle ? array_slice($articles, 1) : $articles;
$timelineArticles = array_slice($articles, 0, 3);
$lastUpdatedAt = latestArticleTimestamp($articles);

if ($lastUpdatedAt) {
    $lastModified = (new DateTimeImmutable($lastUpdatedAt))
        ->setTimezone(new DateTimeZone('UTC'))
        ->format('D, d M Y H:i:s') . ' GMT';
    header('Last-Modified: ' . $lastModified);
    header('ETag: "site-' . sha1($pageParam . '|' . $lastModified) . '"');
}

header('Cache-Control: public, max-age=60, s-maxage=180');
header('Vary: Accept-Encoding');

function latestArticleTimestamp(array $articles): ?string
{
    $latest = null;

    foreach ($articles as $article) {
        foreach (['updated_at', 'published_at'] as $key) {
            if (!empty($article[$key])) {
                $candidate = $article[$key];
                if ($latest === null || strtotime($candidate) > strtotime((string) $latest)) {
                    $latest = $candidate;
                }
            }
        }
    }

    return $latest;
}

$feed = getSiteFeed($pageParam, 6);
$featuredArticle = $feed['featured'];
$listingArticles = $feed['listing'];
$timelineArticles = $feed['timeline'];
$pagination = $feed['pagination'];

?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iran Focus · Actualités</title>
    <meta name="description" content="Articles analysant la guerre en Iran avec un angle diplomatique, économique et terrain.">
    <link rel="stylesheet" href="<?= htmlspecialchars(stylesheetHref(), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="page page--site">
<header class="masthead masthead--solid">
    <div class="brand">Iran Focus</div>
    <nav class="nav">
        <a href="/" class="nav__link">Accueil</a>
        <a href="/site" class="nav__link nav__link--active">Site</a>
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

<main class="site-layout">
    <?php if ($featuredArticle): ?>
        <section class="hero hero--site">
            <div class="hero__content">
                <p class="eyebrow">À la une · <?= formatArticleDate($featuredArticle['published_at']) ?></p>
                <h1><?= htmlspecialchars($featuredArticle['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars(formatArticlePreview($featuredArticle), ENT_QUOTES, 'UTF-8') ?></p>
                <div class="hero-actions">
                    <a class="btn btn--primary" href="<?= htmlspecialchars($featuredArticle['url'], ENT_QUOTES, 'UTF-8') ?>">Lire l'article</a>
                    <?php if ($currentUser): ?>
                        <a class="btn" href="/redaction">Écrire</a>
                    <?php else: ?>
                        <a class="btn" href="/inscription">Devenir contributeur</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($featuredArticle['cover_image_path'])): ?>
                <div class="hero__media">
                    <img
                        src="<?= htmlspecialchars($featuredArticle['cover_image_path'], ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars(articleCoverAlt($featuredArticle), ENT_QUOTES, 'UTF-8') ?>"
                        loading="eager"
                        fetchpriority="high"
                        decoding="async"
                    >
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <section class="hero hero--site">
            <div class="hero__content">
                <p class="eyebrow">Aucun article</p>
                <h1>Publiez votre première analyse</h1>
                <p>Les articles publiés apparaîtront ici automatiquement avec leur meta description.</p>
                <div class="hero-actions">
                    <a class="btn btn--primary" href="/redaction">Créer un article</a>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($listingArticles): ?>
        <section class="grid grid--three">
            <?php foreach ($listingArticles as $article): ?>
                <article class="card story">
                    <?php if (!empty($article['cover_image_path'])): ?>
                        <img
                            class="story__image"
                            src="<?= htmlspecialchars($article['cover_image_path'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars(articleCoverAlt($article), ENT_QUOTES, 'UTF-8') ?>"
                            loading="lazy"
                            decoding="async"
                        >
                    <?php endif; ?>
                    <p class="tag">Publié le <?= htmlspecialchars(formatArticleDate($article['published_at']), ENT_QUOTES, 'UTF-8') ?></p>
                    <h2><a href="<?= htmlspecialchars($article['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></a></h2>
                    <p><?= htmlspecialchars(formatArticlePreview($article), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="muted">Par <?= htmlspecialchars($article['author_name'] ?: 'Rédaction', ENT_QUOTES, 'UTF-8') ?></p>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <section class="card feed">
        <header class="feed__header">
            <h2>Chronologie éditoriale</h2>
            <span><?= date('d M Y') ?></span>
        </header>
        <?php if ($timelineArticles): ?>
            <ul class="timeline">
                <?php foreach ($timelineArticles as $article): ?>
                    <?php
                    $timelineImage = $article['cover_image_path'] ?? '';
                    $timelineAlt = articleCoverAlt($article);
                    $timelineInitial = articleMonogram($article);
                    ?>
                    <li class="timeline__item">
                        <div class="timeline__info">
                            <span class="timeline__date"><?= htmlspecialchars(formatArticleDate($article['published_at']), ENT_QUOTES, 'UTF-8') ?></span>
                            <p class="timeline__title"><a href="<?= htmlspecialchars($article['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></a></p>
                        </div>
                        <?php if ($timelineImage !== ''): ?>
                            <span class="timeline__thumb">
                                <img
                                    src="<?= htmlspecialchars($timelineImage, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="<?= htmlspecialchars($timelineAlt, ENT_QUOTES, 'UTF-8') ?>"
                                    loading="lazy"
                                    decoding="async"
                                >
                            </span>
                        <?php else: ?>
                            <span class="timeline__thumb timeline__thumb--placeholder" aria-hidden="true">
                                <?= htmlspecialchars($timelineInitial, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aucune publication pour le moment.</p>
        <?php endif; ?>
    </section>

    <?php if ($pagination['total_pages'] > 1): ?>
        <nav class="pagination">
            <?php if ($pagination['current_page'] > 1): ?>
                <a class="btn" href="/site?page=<?= $pagination['current_page'] - 1 ?>">← Précédent</a>
            <?php endif; ?>
            <span>Page <?= $pagination['current_page'] ?> / <?= $pagination['total_pages'] ?></span>
            <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a class="btn" href="/site?page=<?= $pagination['current_page'] + 1 ?>">Suivant →</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</main>
</body>
</html>
