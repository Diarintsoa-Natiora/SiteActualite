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
$updatedArticle = null;
$tinyApiKey = getenv('TINYMCE_API_KEY') ?: 'no-api-key';
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$formMode = 'create';
$editingArticleId = 0;
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$requestedArticleId = $isAdmin ? (int) ($_GET['article_id'] ?? 0) : 0;
$articleStats = [
    'total' => 0,
    'published' => 0,
    'draft' => 0,
    'delete' => 0,
];

if ($isPost) {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'update') {
        if (!$isAdmin) {
            $errors[] = 'Seul un administrateur peut modifier un article.';
        } else {
            $editingArticleId = (int) ($_POST['article_id'] ?? 0);
            [$formData, $actionErrors, $updatedArticle] = handleArticleUpdate($editingArticleId, $_POST, $_FILES);
            $errors = array_merge($errors, $actionErrors);
            if ($updatedArticle) {
                $formMode = 'edit';
                $editingArticleId = (int) $updatedArticle['id'];
            }
        }
    } else {
        [$formData, $actionErrors, $createdArticle] = handleArticleCreation($_POST, $currentUser, $_FILES);
        $errors = array_merge($errors, $actionErrors);
    }
}

if ($isAdmin && !$isPost && $requestedArticleId > 0) {
    $selectedArticle = findArticleForAdmin($requestedArticleId);
    if ($selectedArticle) {
        $formData = articleFormFromExisting($selectedArticle);
        $formMode = 'edit';
        $editingArticleId = (int) $selectedArticle['id'];
    } else {
        $errors[] = 'Article introuvable ou déjà supprimé.';
    }
}

if ($isAdmin) {
    $articleStats = fetchArticleStats();
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Back-office · Administration</title>
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://cdn.tiny.cloud/1/<?= rawurlencode($tinyApiKey) ?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
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
        <?php if ($isAdmin): ?>
            <a href="/backoffice" class="nav__link nav__link--active">Gestion articles</a>
        <?php endif; ?>
        <a href="/deconnexion" class="nav__link nav__link--accent">Déconnexion</a>
    </nav>
</header>

<main class="dashboard">
    <section class="card hero-card">
        <p class="eyebrow">Back-office</p>
        <h1><?= $isAdmin ? 'Pilotage éditorial' : 'Nouvel article' ?></h1>
        <p class="card__subtitle">
            <?= $isAdmin
                ? 'Gérez la liste complète des articles, mettez-les à jour ou supprimez-les en un clic.'
                : 'Rédigez depuis TinyDocs, envoyez et publiez instantanément.' ?>
        </p>
        <p>Connecté en tant que <strong><?= htmlspecialchars($currentUser['name'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($currentUser['role'], ENT_QUOTES, 'UTF-8') ?>)</p>
        <?php if ($isAdmin): ?>
            <div class="metrics-grid">
                <div class="metric">
                    <p class="metric__label">Publiés</p>
                    <p class="metric__value"><?= (int) $articleStats['published'] ?></p>
                </div>
                <div class="metric">
                    <p class="metric__label">Brouillons</p>
                    <p class="metric__value"><?= (int) $articleStats['draft'] ?></p>
                </div>
                <div class="metric">
                    <p class="metric__label">Archivés</p>
                    <p class="metric__value"><?= (int) $articleStats['delete'] ?></p>
                </div>
            </div>
        <?php endif; ?>
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

    <?php if ($createdArticle || $updatedArticle): ?>
        <section class="card">
            <div class="alert alert--success">
                <?php if ($createdArticle): ?>
                    Article « <?= htmlspecialchars($createdArticle['title'], ENT_QUOTES, 'UTF-8') ?> » publié.
                    URL SEO : <a href="<?= htmlspecialchars($createdArticle['url'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($createdArticle['url'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php elseif ($updatedArticle): ?>
                    Article « <?= htmlspecialchars($updatedArticle['title'], ENT_QUOTES, 'UTF-8') ?> » mis à jour.
                    <a href="<?= htmlspecialchars($updatedArticle['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">Ouvrir l'article</a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
        <section class="card">
            <div class="form-header">
                <div>
                    <p class="eyebrow">Gestion avancée</p>
                    <h2><?= (int) $articleStats['total'] ?> articles en base</h2>
                </div>
                <a href="/backoffice" class="btn btn--ghost">Ouvrir la liste</a>
            </div>
            <p class="muted">Consultez, modifiez ou supprimez les articles existants depuis la liste dédiée.</p>
        </section>
    <?php endif; ?>

    <section class="card">
        <div class="form-header">
            <div>
                <p class="eyebrow"><?= $formMode === 'edit' ? 'Édition' : 'Création' ?></p>
                <h2><?= $formMode === 'edit' ? 'Modifier un article' : 'Nouvel article' ?></h2>
            </div>
            <?php if ($formMode === 'edit'): ?>
                <a href="/redaction" class="btn btn--ghost btn--small">Nouvel article</a>
            <?php endif; ?>
        </div>

        <?php if ($formMode === 'edit' && $isAdmin && $editingArticleId > 0): ?>
            <div class="alert alert--info">
                Vous éditez l'article #<?= (int) $editingArticleId ?>.
            </div>
        <?php endif; ?>

        <form method="post" class="form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="<?= $formMode === 'edit' ? 'update' : 'create' ?>">
            <?php if ($formMode === 'edit'): ?>
                <input type="hidden" name="article_id" value="<?= (int) $editingArticleId ?>">
            <?php endif; ?>

            <label for="title">Titre</label>
            <input type="text" id="title" name="title" value="<?= htmlspecialchars($formData['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>

            <div class="form__row">
                <div>
                    <label for="meta_title">Meta title</label>
                    <input type="text" id="meta_title" name="meta_title" value="<?= htmlspecialchars($formData['meta_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Optionnel">
                </div>
                <div>
                    <label for="meta_description">Meta description</label>
                    <input type="text" id="meta_description" name="meta_description" value="<?= htmlspecialchars($formData['meta_description'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Optionnel (160 caractères)">
                </div>
            </div>

            <div class="form__row">
                <div>
                    <label for="cover_image"><?= $formMode === 'edit' ? "Remplacer l'image de couverture" : 'Image de couverture' ?></label>
                    <input type="file" id="cover_image" name="cover_image" accept="image/*">
                </div>
                <div>
                    <label for="cover_alt">Texte alternatif</label>
                    <input type="text" id="cover_alt" name="cover_alt" value="<?= htmlspecialchars($formData['cover_alt'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Décrivez l'image">
                </div>
            </div>

            <label for="content">Contenu TinyDocs</label>
            <textarea id="content" name="content" rows="15"><?= htmlspecialchars($formData['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <button type="submit" class="btn btn--primary">
                <?= $formMode === 'edit' ? 'Mettre à jour' : 'Publier' ?>
            </button>
        </form>
    </section>
</main>
</body>
</html>
