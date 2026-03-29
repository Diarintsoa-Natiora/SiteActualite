<?php
require_once __DIR__ . '/config/db.php';

$connectionOk = false;
$errorMessage = '';

try {
    $mysqli = getDbConnection();
    $result = $mysqli->query('SELECT 1 AS ping');
    if ($result && $result->fetch_assoc()['ping'] == 1) {
        $connectionOk = true;
    }
} catch (Throwable $e) {
    $errorMessage = $e->getMessage();
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Docker + MySQL</title>
</head>
<body>
    <h1>Test environnement Docker</h1>

    <?php if ($connectionOk): ?>
        <p style="color: green;">Connexion MySQL OK</p>
    <?php else: ?>
        <p style="color: red;">Connexion MySQL KO</p>
        <pre><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></pre>
    <?php endif; ?>

    <p><a href="/pages/home.php">Aller sur home.php</a></p>
    <p><a href="/actualites/test-seo-1-1.html">Tester URL rewrite</a></p>
    <p><a href="/pages/register.php">Créer un compte</a> (URL SEO : <a href="/inscription">/inscription</a>)</p>
</body>
</html>
