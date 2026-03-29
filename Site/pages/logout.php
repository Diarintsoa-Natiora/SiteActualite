<?php

declare(strict_types=1);

session_start();

$name = $_SESSION['user']['name'] ?? 'Utilisateur';

$_SESSION = [];
session_regenerate_id(true);
$_SESSION['flash_success'] = 'À bientôt ' . $name . '.';

header('Location: /connexion');
exit;
