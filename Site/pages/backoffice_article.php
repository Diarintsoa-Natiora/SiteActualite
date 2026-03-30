<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../services/article_service.php';

$currentUser = $_SESSION['user'] ?? null;

if (!$currentUser) {
    $_SESSION['flash_error'] = 'Veuillez vous connecter pour accéder au back-office.';
    header('Location: /connexion?redirect=redaction');
    exit;
}

header('Content-Type: text/html; charset=utf-8');

$formData = articleFormDefaults();
$errors = [];
$createdArticle = null;
$tinyApiKey = getenv('TINYMCE_API_KEY') ?: 'no-api-key';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    [$formData, $errors, $createdArticle] = handleArticleCreation($_POST, $currentUser);

    if ($createdArticle) {
        $_SESSION['flash_success'] = 'Article publié : ' . $createdArticle['title'];
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back-office · Création article</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://cdn.tiny.cloud/1/<?= rawurlencode($tinyApiKey) ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // TODO: brancher TinyDocs (ou TinyMCE self-hosted) avec la clé API réelle si nécessaire.
            tinymce.init({
                selector: '#content',
                height: 420,
                menubar: false,
                plugins: 'link lists image media table autoresize code',
                toolbar: 'undo redo | styleselect | bold italic underline | bullist numlist | link image media | code',
                content_style: 'body { font-family: Space Grotesk, sans-serif; }'
            });
        });
    </script>
</head>
<body class="page page--dashboard">
<header class="masthead masthead--solid">
    <div class="brand">Iran Focus · Back-office</div>
    <nav class="nav">
        <a href="/bienvenue" class="nav__link">Tableau de bord</a>
        <a href="/site" class="nav__link">Site public</a>
        <a href="/deconnexion" class="nav__link nav__link--accent">Déconnexion</a>
    </nav>
</header>

<main class="dashboard">
    <section class="card hero-card">
        <p class="eyebrow">Back-office</p>
        <h1>Nouvel article</h1>
        <p class="card__subtitle">Rédigez depuis TinyDocs, envoyez, c'est publié instantanément.</p>
        <p>Connecté en tant que <strong><?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($currentUser['role'], ENT_QUOTES, 'UTF-8') ?>)</p>
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

    <?php if ($createdArticle): ?>
        <section class="card">
            <div class="alert alert--success">
                Article « <?= htmlspecialchars($createdArticle['title'], ENT_QUOTES, 'UTF-8') ?> » publié.
                URL SEO : <a href="<?= htmlspecialchars($createdArticle['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($createdArticle['url'], ENT_QUOTES, 'UTF-8') ?></a>
            </div>
        </section>
    <?php endif; ?>

    <section class="card">
        <form method="post" class="form" enctype="multipart/form-data">
            <label for="title">Titre</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($formData['title'], ENT_QUOTES, 'UTF-8') ?>" required>

            <div class="form__row">
                <div>
                    <label for="meta_title">Meta title</label>
                    <input type="text" id="meta_title" name="meta_title" value="<?= htmlspecialchars($formData['meta_title'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Optionnel">
                </div>
                <div>
                    <label for="meta_description">Meta description</label>
                    <input type="text" id="meta_description" name="meta_description" value="<?= htmlspecialchars($formData['meta_description'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Optionnel (160 caractères)">
                </div>
                <div>
                    <label for="cover_image">Images de couverture</label>
                    <input type="file" id="cover_image" name="cover_image" value="<?= htmlspecialchars($formData['meta_description'], ENT_QUOTES, 'UTF-8') ?>" placeholder="optionelle">
                </div>
            </div>

            <label for="content">Contenu TinyDocs</label>
            <textarea id="content" name="content" rows="15"><?= htmlspecialchars($formData['content'], ENT_QUOTES, 'UTF-8') ?></textarea>

            <button type="submit" class="btn btn--primary">Publier</button>
        </form>
    </section>
</main>
</body>
</html>
