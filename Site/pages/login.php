<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../services/login_service.php';

header('Content-Type: text/html; charset=utf-8');

$formData = loginFormDefaults();
$errors = [];
$successMessage = '';
$currentUser = $_SESSION['user'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$formData, $errors, $user] = handleLogin($_POST);

    if (!$errors && $user) {
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
        $currentUser = $_SESSION['user'];
        $successMessage = 'Connexion réussie. Vous êtes maintenant connecté.';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 2rem auto;
            padding: 1rem;
            background-color: #f5f5f5;
        }
        form {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        label {
            display: block;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 0.6rem 1.2rem;
            background-color: #111827;
            border: none;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }
        .messages {
            margin-bottom: 1rem;
        }
        .messages ul {
            margin: 0;
            padding-left: 1.25rem;
        }
        .error {
            color: #b91c1c;
        }
        .success {
            color: #15803d;
        }
        .links {
            margin-top: 1rem;
        }
        .links a {
            color: #2563eb;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            background-color: #e0e7ff;
            color: #1e3a8a;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <h1>Connexion</h1>

    <?php if ($currentUser): ?>
        <p class="badge">Connecté en tant que <?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="messages error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <p class="messages success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <form method="post" action="">
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

        <button type="submit">Se connecter</button>
    </form>

    <div class="links">
        <p><a href="/inscription">Créer un compte</a></p>
        <p><a href="/">Retour à l'accueil</a></p>
        <?php if ($currentUser): ?>
            <p><a href="/deconnexion">Se déconnecter</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
