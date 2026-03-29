<?php
declare(strict_types=1);

session_start();

header('Content-Type: text/html; charset=utf-8');

$currentUser = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iran Focus · Accueil</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="page page--gradient">
<header class="masthead">
    <div class="brand">Iran Focus</div>
    <nav class="nav">
        <a href="/" class="nav__link nav__link--active">Accueil</a>
        <a href="/site" class="nav__link">Site</a>
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

<main class="home">
    <section class="hero">
        <div>
            <p class="eyebrow">Projet étudiant · Guerre en Iran</p>
            <h1>Construisez un média SEO fiable</h1>
            <p>Workflow complet : TinyDocs, MySQL, URLs réécrites et hébergement Docker prêt pour la démo.</p>
            <div class="hero-actions">
                <?php if ($currentUser): ?>
                    <a class="btn btn--primary" href="/redaction">Écrire un article</a>
                    <a class="btn" href="/site">Voir le site</a>
                    <a class="btn btn--ghost" href="/bienvenue">Tableau de bord</a>
                <?php else: ?>
                    <a class="btn btn--primary" href="/connexion">Se connecter</a>
                    <a class="btn" href="/inscription">Créer un compte</a>
                    <a class="btn btn--ghost" href="/site">Voir le site</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero__status">
            <?php if ($currentUser): ?>
                <p>Connecté : <strong><?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
                <a href="/bienvenue" class="status-pill">Espace <?= htmlspecialchars($currentUser['role'], ENT_QUOTES, 'UTF-8') ?></a>
            <?php else: ?>
                <p>Aucun utilisateur connecté</p>
                <span class="status-pill">Mode invité</span>
            <?php endif; ?>
        </div>
    </section>

    <section class="grid grid--three">
        <article class="card">
            <h2>1. Inscription</h2>
            <p>Créez un compte contributeur et attribuez un rôle.</p>
        </article>
        <article class="card">
            <h2>2. Connexion</h2>
            <p>Accédez à l'espace auteur pour suivre vos contenus.</p>
        </article>
        <article class="card">
            <h2>3. Publication</h2>
            <p>Servez le contenu SEO via des URLs réécrites.</p>
        </article>
    </section>
</main>
</body>
</html>
