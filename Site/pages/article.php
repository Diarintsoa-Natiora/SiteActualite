<?php

header('Content-Type: text/html; charset=utf-8');
$slug = $_GET['slug'] ?? '';
$id = $_GET['id'] ?? '';
$idcat = $_GET['idcat'] ?? '';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Article test</title>
</head>
<body>
    <h1>article.php</h1>
    <p>slug: <?= htmlspecialchars((string) $slug, ENT_QUOTES, 'UTF-8') ?></p>
    <p>id: <?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?></p>
    <p>idcat: <?= htmlspecialchars((string) $idcat, ENT_QUOTES, 'UTF-8') ?></p>
</body>
</html>
