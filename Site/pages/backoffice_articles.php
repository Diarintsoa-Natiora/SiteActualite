<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../services/article_service.php';

$currentUser = $_SESSION['user'] ?? null;

if (!$currentUser) {
    $_SESSION['flash_error'] = 'Veuillez vous connecter pour accéder au back-office.';
    header('Location: /connexion?redirect=backoffice');
    exit;
}

$isAdmin = ($currentUser['role'] ?? '') === 'admin';

if (!$isAdmin) {
    $_SESSION['flash_error'] = 'Accès réservé aux administrateurs.';
    header('Location: /redaction');
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$errors = [];
$deletedArticle = null;
$articles = [];
$articleStats = [
    'total' => 0,
    'published' => 0,
    'draft' => 0,
    'delete' => 0,
];
$statusLabels = [
    'published' => 'Publié',
    'draft' => 'Brouillon',
    'delete' => 'Archivé',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $targetId = (int) ($_POST['article_id'] ?? 0);
        [$actionErrors, $deletedArticle] = handleArticleDeletion($targetId);
        $errors = array_merge($errors, $actionErrors);
    } else {
        $errors[] = 'Action non supportée sur la liste.';
    }
}

$articles = fetchAdminArticles();
$articleStats = fetchArticleStats();

function formatAdminDate(?string $date): string
{
    if (!$date) {
        return '—';
    }

    try {
        return (new DateTimeImmutable($date))->format('d/m/Y H:i');
    } catch (Throwable $e) {
        return $date;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back-office · Articles</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="page page--dashboard">
<header class="masthead masthead--solid">
    <div class="brand">Iran Focus · Back-office</div>
    <nav class="nav">
        <a href="/bienvenue" class="nav__link">Tableau de bord</a>
        <a href="/site" class="nav__link">Site public</a>
        <a href="/redaction" class="nav__link">Créer un article</a>
        <a href="/backoffice" class="nav__link nav__link--active">Gestion articles</a>
        <a href="/deconnexion" class="nav__link nav__link--accent">Déconnexion</a>
    </nav>
</header>

<main class="dashboard">
    <section class="card hero-card">
        <p class="eyebrow">Inventaire éditorial</p>
        <h1>Liste des articles</h1>
        <p class="card__subtitle">Visualisez l'ensemble des publications, mettez-les à jour ou supprimez-les.</p>
        <p>Connecté en tant que <strong><?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?></strong> (admin)</p>
        <div class="metrics-grid">
            <div class="metric">
                <p class="metric__label">Publiés</p>
                <p class="metric__value"><?= (int) $articleStats['published'] ?></p>
            </div>
            <div class="metric">
                <p class="metric__label">Brouillons</p>
                <p class="metric__value"><?= (int) $articleStats['draft'] ?></p>
            </div>
            <div class="metric">
                <p class="metric__label">Archivés</p>
                <p class="metric__value"><?= (int) $articleStats['delete'] ?></p>
            </div>
        </div>
    </section>

    <?php if ($errors): ?>
        <section class="card">
            <div class="alert alert--error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($deletedArticle): ?>
        <section class="card">
            <div class="alert alert--success">
                Article « <?= htmlspecialchars($deletedArticle['title'], ENT_QUOTES, 'UTF-8') ?> » supprimé avec succès.
            </div>
        </section>
    <?php endif; ?>

    <section class="card backoffice-board">
        <div class="board__header">
            <div>
                <p class="eyebrow">Articles en base</p>
                <h2><?= (int) $articleStats['total'] ?> entrées</h2>
            </div>
            <a href="/redaction" class="btn btn--ghost">Créer un article</a>
        </div>

        <?php if ($articles): ?>
            <ul class="article-list">
                <?php foreach ($articles as $article): ?>
                    <?php $statusKey = $article['status'] ?? 'draft'; ?>
                    <li class="article-list__item">
                        <div>
                            <span class="status-pill status-pill--<?= htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($statusLabels[$statusKey] ?? ucfirst($statusKey), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <h3><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="muted">
                                Maj <?= htmlspecialchars(formatAdminDate($article['updated_at'] ?? null), ENT_QUOTES, 'UTF-8') ?> ·
                                <?= htmlspecialchars($article['author_name'] ?? 'Auteur inconnu', ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                        <div class="article-list__actions">
                            <a href="<?= htmlspecialchars($article['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn--ghost btn--small">Voir</a>
                            <a href="/redaction?article_id=<?= (int) $article['id'] ?>" class="btn btn--primary btn--small">Modifier</a>
                            <form method="post" onsubmit="return confirm('Supprimer \"<?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>\" ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="article_id" value="<?= (int) $article['id'] ?>">
                                <button type="submit" class="btn btn--danger btn--small">Supprimer</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">Aucun article n'est encore disponible.</p>
        <?php endif; ?>
    </section>
</main>
</body>
</html>
