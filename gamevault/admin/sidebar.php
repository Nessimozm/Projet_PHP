<?php
$currentAdmin = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <h3>⚙️ Admin</h3>
    <ul>
        <li><a href="<?= BASE_URL ?>/admin/index.php" class="<?= $currentAdmin === 'index.php' ? 'active' : '' ?>">📊 Dashboard</a></li>
        <li><a href="<?= BASE_URL ?>/admin/users.php" class="<?= $currentAdmin === 'users.php' ? 'active' : '' ?>">👥 Utilisateurs</a></li>
        <li><a href="<?= BASE_URL ?>/admin/games.php" class="<?= $currentAdmin === 'games.php' ? 'active' : '' ?>">🎮 Jeux</a></li>
        <li><a href="<?= BASE_URL ?>/admin/levels.php" class="<?= $currentAdmin === 'levels.php' ? 'active' : '' ?>">📋 Niveaux</a></li>
        <li><a href="<?= BASE_URL ?>/index.php" style="color:var(--text-muted)">← Retour au site</a></li>
    </ul>
</aside>
