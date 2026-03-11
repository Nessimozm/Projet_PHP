<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$pageTitle = 'Admin — Dashboard';
$db = getDB();

$stats = [
    'users'        => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'games'        => $db->query("SELECT COUNT(*) FROM games")->fetchColumn(),
    'user_games'   => $db->query("SELECT COUNT(*) FROM user_games")->fetchColumn(),
    'achievements' => $db->query("SELECT COUNT(*) FROM user_achievements")->fetchColumn(),
];

include '../includes/header.php';
?>

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <h1 style="font-family:'Orbitron',monospace;color:var(--primary);margin-bottom:2rem">⚙️ Tableau de bord</h1>

        <div class="stats-grid mb-3">
            <div class="stat-card">
                <div class="stat-value"><?= $stats['users'] ?></div>
                <div class="stat-label">👥 Utilisateurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['games'] ?></div>
                <div class="stat-label">🎮 Jeux</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['user_games'] ?></div>
                <div class="stat-label">📚 Jeux en biblio</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= $stats['achievements'] ?></div>
                <div class="stat-label">🏆 Succès obtenus</div>
            </div>
        </div>

        <div class="neon-divider"></div>

        <h2 class="section-title">👥 Derniers utilisateurs</h2>
        <div class="card" style="padding:0;overflow:hidden">
            <table>
                <thead><tr><th>ID</th><th>Pseudo</th><th>E-mail</th><th>Rôle</th><th>Inscrit le</th><th>Actions</th></tr></thead>
                <tbody>
                <?php
                $users = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
                foreach ($users as $u): ?>
                <tr>
                    <td style="color:var(--text-muted)">#<?= $u['id'] ?></td>
                    <td><strong><?= e($u['username']) ?></strong></td>
                    <td style="color:var(--text-muted)"><?= e($u['email']) ?></td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                    <td style="color:var(--text-muted)"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td><a href="users.php?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">Modifier</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:1rem"><a href="users.php" class="btn btn-secondary btn-sm">Voir tous →</a></div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
