<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();

$pageTitle = 'Mon Profil';
$db   = getDB();
$uid  = $_SESSION['user_id'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update profile bio
    if ($action === 'update_profile') {
        $bio = trim($_POST['bio'] ?? '');
        $bio = substr($bio, 0, 500);
        $db->prepare("UPDATE users SET bio = ? WHERE id = ?")->execute([$bio, $uid]);
        setFlash('success', 'Profil mis à jour !');
        redirect(BASE_URL . '/profile.php');
    }

    // Update playtime for a game (simulate CRUD)
    if ($action === 'update_playtime') {
        $gameId   = (int)$_POST['game_id'];
        $playtime = max(0, (int)$_POST['playtime']);
        $db->prepare("UPDATE user_games SET playtime = ? WHERE user_id = ? AND game_id = ?")
           ->execute([$playtime, $uid, $gameId]);
        checkAchievements($uid);
        setFlash('success', 'Temps de jeu mis à jour !');
        redirect(BASE_URL . '/profile.php');
    }

    // Remove game from library
    if ($action === 'remove_game') {
        $gameId = (int)$_POST['game_id'];
        $db->prepare("DELETE FROM user_games WHERE user_id = ? AND game_id = ?")->execute([$uid, $gameId]);
        setFlash('info', 'Jeu retiré de votre bibliothèque.');
        redirect(BASE_URL . '/profile.php');
    }
}

// Fetch user data
$user = $db->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$uid]);
$user = $user->fetch();

// Fetch user library (with game info)
$library = $db->prepare("
    SELECT g.*, ug.playtime, ug.added_at, ug.id as ug_id
    FROM user_games ug
    JOIN games g ON g.id = ug.game_id
    WHERE ug.user_id = ?
    ORDER BY ug.added_at DESC
");
$library->execute([$uid]);
$library = $library->fetchAll();

// Total playtime
$totalPlaytime = array_sum(array_column($library, 'playtime'));

// Achievements
$achievements = $db->prepare("
    SELECT a.*, CASE WHEN ua.id IS NOT NULL THEN 1 ELSE 0 END as earned, ua.earned_at
    FROM achievements a
    LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = ?
    ORDER BY earned DESC, a.id ASC
");
$achievements->execute([$uid]);
$achievements = $achievements->fetchAll();
$earnedCount = count(array_filter($achievements, fn($a) => $a['earned']));

include 'includes/header.php';
?>

<div class="container">
    <!-- Profile header -->
    <div class="profile-header">
        <div class="avatar glow-anim">
            <?= strtoupper(substr($user['username'], 0, 1)) ?>
        </div>
        <div class="profile-info" style="flex:1">
            <h2><?= e($user['username']) ?></h2>
            <div style="margin-bottom:0.5rem">
                <span class="badge badge-<?= $user['role'] ?>"><?= $user['role'] === 'admin' ? '⭐ Admin' : '🎮 Joueur' ?></span>
            </div>
            <p class="text-muted" style="font-size:0.9rem">Membre depuis le <?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
            <p class="text-muted" style="font-size:0.9rem"><?= e($user['email']) ?></p>
        </div>

        <!-- Edit bio -->
        <details style="max-width:300px">
            <summary style="cursor:pointer;color:var(--primary);font-size:0.9rem">✏️ Modifier le profil</summary>
            <form method="POST" style="margin-top:0.8rem">
                <input type="hidden" name="action" value="update_profile">
                <textarea name="bio" style="width:100%;background:var(--bg-dark);border:1px solid var(--border);color:var(--text);border-radius:8px;padding:0.6rem;font-family:inherit;resize:vertical" rows="3" placeholder="Votre bio..."><?= e($user['bio'] ?? '') ?></textarea>
                <button type="submit" class="btn btn-primary btn-sm" style="margin-top:0.5rem">Enregistrer</button>
            </form>
        </details>
    </div>

    <?php if ($user['bio']): ?>
    <div class="card mb-3" style="font-style:italic;color:var(--text-muted)">
        "<?= e($user['bio']) ?>"
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-grid mb-3">
        <div class="stat-card">
            <div class="stat-value"><?= count($library) ?></div>
            <div class="stat-label">🎮 Jeux dans la biblio</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= round($totalPlaytime / 60, 1) ?>h</div>
            <div class="stat-label">⏱️ Temps de jeu total</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $earnedCount ?>/<?= count($achievements) ?></div>
            <div class="stat-label">🏆 Succès débloqués</div>
        </div>
    </div>

    <div class="neon-divider"></div>

    <!-- Library -->
    <h2 class="section-title">📚 Ma Bibliothèque</h2>
    <?php if (empty($library)): ?>
        <div class="card text-center" style="color:var(--text-muted);padding:3rem">
            <p>Votre bibliothèque est vide.</p>
            <a href="<?= BASE_URL ?>/games.php" class="btn btn-primary" style="margin-top:1rem">🎮 Découvrir les jeux</a>
        </div>
    <?php else: ?>
    <div class="table-wrap card" style="padding:0;overflow:hidden;margin-bottom:2rem">
        <table>
            <thead>
                <tr>
                    <th>Jeu</th>
                    <th>Type</th>
                    <th>Temps de jeu</th>
                    <th>Ajouté le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($library as $entry): ?>
            <tr>
                <td>
                    <a href="<?= BASE_URL ?>/games.php?id=<?= $entry['id'] ?>" style="color:var(--text);font-weight:600">
                        <?= e($entry['name']) ?>
                    </a>
                </td>
                <td><span style="color:var(--primary);font-size:0.85rem"><?= e($entry['type']) ?></span></td>
                <td>
                    <form method="POST" style="display:flex;gap:0.5rem;align-items:center">
                        <input type="hidden" name="action" value="update_playtime">
                        <input type="hidden" name="game_id" value="<?= $entry['id'] ?>">
                        <input type="number" name="playtime" value="<?= $entry['playtime'] ?>"
                               min="0" max="99999"
                               style="width:80px;background:var(--bg-dark);border:1px solid var(--border);border-radius:6px;color:var(--accent);padding:0.3rem 0.5rem;font-family:'Orbitron',monospace;font-size:0.85rem">
                        <span style="color:var(--text-muted);font-size:0.8rem">min</span>
                        <button type="submit" class="btn btn-sm btn-secondary" style="padding:0.3rem 0.6rem">✓</button>
                    </form>
                </td>
                <td style="color:var(--text-muted);font-size:0.85rem"><?= date('d/m/Y', strtotime($entry['added_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:0.4rem">
                        <a href="<?= BASE_URL ?>/games.php?id=<?= $entry['id'] ?>" class="btn btn-sm btn-secondary">Voir</a>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="remove_game">
                            <input type="hidden" name="game_id" value="<?= $entry['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger"
                                    data-confirm="Retirer <?= e($entry['name']) ?> de votre bibliothèque ?">✕</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Achievements -->
    <h2 class="section-title">🏆 Succès</h2>
    <div class="achievements-grid">
        <?php foreach ($achievements as $ach): ?>
        <div class="achievement-card <?= $ach['earned'] ? 'earned' : 'locked' ?>">
            <div class="achievement-icon"><?= e($ach['icon']) ?></div>
            <div class="achievement-name"><?= e($ach['name']) ?></div>
            <div class="achievement-desc"><?= e($ach['description']) ?></div>
            <?php if ($ach['earned'] && $ach['earned_at']): ?>
                <div style="font-size:0.75rem;color:var(--accent);margin-top:0.5rem">
                    ✓ <?= date('d/m/Y', strtotime($ach['earned_at'])) ?>
                </div>
            <?php elseif (!$ach['earned']): ?>
                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.5rem">🔒 Verrouillé</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
