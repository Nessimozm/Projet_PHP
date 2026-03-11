<?php
// includes/header.php
$flash = getFlash();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — ' : '' ?><?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <a href="<?= BASE_URL ?>/index.php" class="nav-logo">
            <span class="logo-icon">⚡</span> <?= SITE_NAME ?>
        </a>
        <ul class="nav-links">
            <li><a href="<?= BASE_URL ?>/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Accueil</a></li>
            <li><a href="<?= BASE_URL ?>/games.php" class="<?= $currentPage === 'games.php' ? 'active' : '' ?>">Jeux</a></li>
            <?php if (isLoggedIn()): ?>
                <li><a href="<?= BASE_URL ?>/profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">Mon Profil</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="<?= BASE_URL ?>/admin/index.php" class="admin-link">Admin</a></li>
                <?php endif; ?>
                <li><a href="<?= BASE_URL ?>/logout.php" class="btn-nav">Déconnexion</a></li>
            <?php else: ?>
                <li><a href="<?= BASE_URL ?>/login.php" class="<?= $currentPage === 'login.php' ? 'active' : '' ?>">Connexion</a></li>
                <li><a href="<?= BASE_URL ?>/register.php" class="btn-nav">S'inscrire</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<?php if ($flash): ?>
<div class="flash flash-<?= e($flash['type']) ?>">
    <span><?= e($flash['message']) ?></span>
    <button onclick="this.parentElement.remove()">✕</button>
</div>
<?php endif; ?>

<main class="main-content">
