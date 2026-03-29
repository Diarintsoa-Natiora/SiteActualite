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
    <title>Iran Focus · Actualités</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="page page--site">
<header class="masthead masthead--solid">
    <div class="brand">Iran Focus</div>
    <nav class="nav">
        <a href="/" class="nav__link">Accueil</a>
        <a href="/site" class="nav__link nav__link--active">Site</a>
        <a href="/connexion" class="nav__link">Connexion</a>
        <?php if ($currentUser): ?>
            <a href="/bienvenue" class="nav__link">Espace <?= htmlspecialchars($currentUser['role'], ENT_QUOTES, 'UTF-8') ?></a>
        <?php else: ?>
            <a href="/inscription" class="nav__link nav__link--accent">Inscription</a>
        <?php endif; ?>
    </nav>
</header>

<main class="site-layout">
    <section class="hero hero--site">
        <div>
            <p class="eyebrow">Breaking News</p>
            <h1>Tensions croissantes à la frontière iranienne</h1>
            <p>Suivez nos envoyés pour décrypter les enjeux diplomatiques, économiques et humanitaires du conflit.</p>
            <div class="hero-actions">
                <a class="btn btn--primary" href="/connexion">Lire les analyses complètes</a>
                <a class="btn" href="/inscription">Devenir contributeur</a>
            </div>
        </div>
    </section>

    <section class="grid grid--three">
        <article class="card story">
            <p class="tag">Diplomatie</p>
            <h2>De nouvelles négociations à Genève</h2>
            <p>Les discussions reprennent entre les puissances régionales pour tenter d'établir un cessez-le-feu durable.</p>
        </article>
        <article class="card story">
            <p class="tag">Économie</p>
            <h2>Impact sur le baril de pétrole</h2>
            <p>Les marchés réagissent aux sanctions et aux coupures de production. Décryptage complet.</p>
        </article>
        <article class="card story">
            <p class="tag">Terrain</p>
            <h2>Couloirs humanitaires</h2>
            <p>Reporter spécial : comment la population s'organise face aux blocages des axes routiers.</p>
        </article>
    </section>

    <section class="card feed">
        <header class="feed__header">
            <h2>Chronologie</h2>
            <span><?= date('d M Y') ?></span>
        </header>
        <ul class="timeline">
            <li>
                <span>08:00</span>
                <p>Annonce d'une conférence de presse conjointe Iran / ONU.</p>
            </li>
            <li>
                <span>11:30</span>
                <p>Briefing militaire sur la sécurisation du détroit d'Ormuz.</p>
            </li>
            <li>
                <span>15:00</span>
                <p>Mobilisation des ONG pour acheminer des médicaments.</p>
            </li>
        </ul>
    </section>
</main>
</body>
</html>
