<?php

session_start();
$currentUser = $_SESSION['user'] ?? null;

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Home test</title>
</head>
<body>
    <h1>home.php</h1>
    <p>Le conteneur PHP/Apache fonctionne.</p>
    <p><a href="/inscription">Accéder à la page d'inscription</a></p>
    <p><a href="/connexion">Aller sur la page de connexion</a></p>

    <?php if ($currentUser): ?>
        <p>Utilisateur connecté : <strong><?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?></strong> (<a href="/deconnexion">Se déconnecter</a>)</p>
    <?php else: ?>
        <p>Aucun utilisateur connecté actuellement.</p>
    <?php endif; ?>
</body>
</html>
