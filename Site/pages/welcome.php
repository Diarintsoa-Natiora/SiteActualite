<?php
declare(strict_types=1);

session_start();

header('Content-Type: text/html; charset=utf-8');

$currentUser = $_SESSION['user'] ?? null;

if (!$currentUser) {
    $_SESSION['flash_error'] = 'Veuillez vous connecter pour accéder à votre espace.';
    header('Location: /connexion?redirect=bienvenue');
    exit;
}

$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue <?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="page page--dashboard">
<header class="masthead masthead--solid">
    <div class="brand">Iran Focus</div>
    <nav class="nav">
        <a href="/site" class="nav__link">Site</a>
        <a href="/" class="nav__link">Accueil</a>
        <a href="/deconnexion" class="nav__link nav__link--accent">Déconnexion</a>
    </nav>
</header>

<main class="dashboard">
    <section class="card hero-card">
        <p class="eyebrow">Bienvenue</p>
        <h1><?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="card__subtitle">Rôle actuel : <strong><?= htmlspecialchars($currentUser['role'], ENT_QUOTES, 'UTF-8') ?></strong></p>
        <div class="hero-actions">
            <a class="btn btn--primary" href="/site">Voir le site</a>
            <a class="btn" href="/inscription">Inviter un collègue</a>
        </div>
    </section>

    <?php if ($flashSuccess || $flashError): ?>
        <section class="card">
            <?php if ($flashSuccess): ?>
                <div class="alert alert--success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="alert alert--error"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="card grid-card">
        <h2>Prochaines étapes</h2>
        <div class="grid grid--two">
            <article>
                <h3>1. Configurer TinyDocs</h3>
                <p>Branchez votre éditeur pour préparer les prochains articles sur la guerre en Iran.</p>
            </article>
            <article>
                <h3>2. Planifier vos catégories</h3>
                <p>Définissez vos rubriques (Politique, Diplomatie, Économie) pour alimenter le flux.</p>
            </article>
        </div>
    </section>
</main>
</body>
</html>
