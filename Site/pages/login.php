<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../services/login_service.php';

header('Content-Type: text/html; charset=utf-8');

$formData = loginFormDefaults();
$errors = [];
$currentUser = $_SESSION['user'] ?? null;
$redirectParam = $_GET['redirect'] ?? ($_POST['redirect'] ?? '');
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
$redirectTarget = $redirectParam !== '' ? '/' . ltrim($redirectParam, '/') : '/bienvenue';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$formData, $errors, $user] = handleLogin($_POST);

    if (!$errors && $user) {
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        $_SESSION['flash_success'] = 'Ravi de vous revoir ' . $user['name'] . '.';
        header('Location: ' . $redirectTarget);
        exit;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
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
            <a href="/inscription" class="nav__link">Inscription</a>
        <?php endif; ?>
    </nav>
</header>

<main class="auth-grid">
    <section class="card auth-card">
        <p class="eyebrow">Étape 2 · Connexion</p>
        <h1 class="card__title">Se connecter</h1>
        <p class="card__subtitle">Accédez à l'espace rédacteur, aux brouillons et aux statistiques.</p>

        <?php if ($currentUser): ?>
            <div class="alert alert--info">
                Connecté en tant que <?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?>.
                <a href="<?= htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8') ?>">Continuer</a>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess): ?>
            <div class="alert alert--success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="alert alert--error"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
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
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectParam, ENT_QUOTES, 'UTF-8') ?>">
            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn btn--primary">Se connecter</button>
        </form>

        <p class="switch-link">Pas encore de compte ? <a href="/inscription">Créer mon accès</a></p>
    </section>

    <aside class="card auth-aside">
        <h2>Workflow</h2>
        <ol>
            <li>Saisie via TinyDocs</li>
            <li>Validation éditoriale</li>
            <li>Publication SEO</li>
        </ol>
        <p class="muted">Besoin d'un rôle supérieur ? Contactez l'admin.</p>
    </aside>
</main>
</body>
</html>
