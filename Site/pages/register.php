<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../services/registration_service.php';

header('Content-Type: text/html; charset=utf-8');

$formData = registrationFormDefaults();
$errors = [];
$currentUser = $_SESSION['user'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$formData, $errors, $createdUser] = handleRegistration($_POST);

    if (!$errors && $createdUser) {
        $_SESSION['user'] = [
            'id' => (int) $createdUser['id'],
            'name' => $createdUser['name'],
            'email' => $createdUser['email'],
            'role' => $createdUser['role'],
        ];
        $_SESSION['flash_success'] = 'Bienvenue ' . $createdUser['name'] . ' ! Votre compte est actif.';
        header('Location: /bienvenue');
        exit;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="page page--gradient">
<header class="masthead">
    <div class="brand">Iran Focus</div>
    <nav class="nav">
        <a href="/" class="nav__link">Accueil</a>
        <a href="/site" class="nav__link">Site</a>
        <?php if ($currentUser): ?>
            <a href="/redaction" class="nav__link">Rédaction</a>
            <a href="/deconnexion" class="nav__link nav__link--accent">Déconnexion</a>
        <?php else: ?>
            <a href="/connexion" class="nav__link nav__link--accent">Connexion</a>
        <?php endif; ?>
    </nav>
</header>

<main class="auth-grid">
    <section class="card auth-card">
        <p class="eyebrow">Étape 1 · Création de compte</p>
        <h1 class="card__title">Rejoindre la rédaction</h1>
        <p class="card__subtitle">Accédez à l'espace de rédaction et publiez vos analyses.</p>

        <?php if ($currentUser): ?>
            <div class="alert alert--info">
                Vous êtes déjà connecté en tant que <?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?>.
                <a href="/bienvenue">Accéder au tableau de bord</a>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert--error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" class="form">
            <label for="name">Nom complet</label>
            <input
                type="text"
                id="name"
                name="name"
                value="<?= htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <label for="email">Email professionnel</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <div class="form__row">
                <div>
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div>
                    <label for="confirm_password">Confirmation</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
            </div>

            <button type="submit" class="btn btn--primary">Créer mon compte</button>
        </form>

        <p class="switch-link">Déjà enregistré ? <a href="/connexion">Passez à l'étape connexion</a></p>
    </section>

    <aside class="card auth-aside">
        <h2>Espace contributeur</h2>
        <ul>
            <li>Publiez via TinyDocs</li>
            <li>Générez des URLs SEO</li>
            <li>Suivez vos performances</li>
        </ul>
        <p class="muted">Votre rôle par défaut est <strong>auteur</strong>. Un admin peut ensuite vous promouvoir.</p>
    </aside>
</main>
</body>
</html>
