<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Accueil';
$db = getDB();

// Get recent games
$games = $db->query("SELECT * FROM games ORDER BY id DESC LIMIT 6")->fetchAll();

// Get platform stats
$stats = [
    'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'games' => $db->query("SELECT COUNT(*) FROM games")->fetchColumn(),
    'achievements' => $db->query("SELECT COUNT(*) FROM achievements")->fetchColumn(),
];

include 'includes/header.php';
?>

<section class="hero">
    <div class="container">
        <h1 class="hero-title">⚡ GameVault</h1>
        <p class="hero-subtitle">
            La plateforme gaming qui centralise votre bibliothèque de jeux,
            suit vos succès et enrichit votre expérience.
        </p>
        <div class="hero-btns">
            <?php if (isLoggedIn()): ?>
                <a href="<?= BASE_URL ?>/games.php" class="btn btn-primary">🎮 Voir les jeux</a>
                <a href="<?= BASE_URL ?>/profile.php" class="btn btn-secondary">👤 Mon profil</a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">🚀 Créer un compte</a>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-secondary">🔑 Se connecter</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<div class="container">
    <!-- Stats bar -->
    <div class="stats-grid mb-3">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['games'] ?></div>
            <div class="stat-label">🎮 Jeux disponibles</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['users'] ?></div>
            <div class="stat-label">👥 Joueurs inscrits</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['achievements'] ?></div>
            <div class="stat-label">🏆 Succès à débloquer</div>
        </div>
    </div>

    <div class="neon-divider"></div>

    <!-- Games showcase -->
    <h2 class="section-title">🎯 Jeux disponibles</h2>
    <div class="games-grid">
        <?php foreach ($games as $game): ?>
        <div class="game-card">
            <div class="game-card-img">
                <?php if ($game['image'] && file_exists(UPLOAD_DIR . $game['image'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/games/<?= e($game['image']) ?>" alt="<?= e($game['name']) ?>">
                <?php else: ?>
                    🎮
                <?php endif; ?>
            </div>
            <div class="game-card-body">
                <div class="game-type"><?= e($game['type']) ?></div>
                <h3><?= e($game['name']) ?></h3>
                <p><?= e(substr($game['description'], 0, 100)) ?>...</p>
            </div>
            <div class="game-card-footer">
                <span class="text-muted" style="font-size:0.85rem"><?= e($game['developer']) ?></span>
                <a href="<?= BASE_URL ?>/games.php?id=<?= $game['id'] ?>" class="btn btn-sm btn-secondary">Voir →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
