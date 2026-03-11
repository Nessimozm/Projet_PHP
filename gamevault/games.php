<?php
require_once __DIR__ . '/includes/config.php';
$db = getDB();

// Single game view
if (isset($_GET['id'])) {
    $gameId = (int)$_GET['id'];
    $game = $db->prepare("SELECT * FROM games WHERE id = ?");
    $game->execute([$gameId]);
    $game = $game->fetch();
    if (!$game) { setFlash('error', 'Jeu introuvable.'); redirect(BASE_URL . '/games.php'); }

    // Get levels
    $levels = $db->prepare("SELECT * FROM levels WHERE game_id = ? ORDER BY FIELD(difficulty,'Facile','Moyen','Difficile','Expert')");
    $levels->execute([$gameId]);
    $levels = $levels->fetchAll();

    // Get achievements for this game
    $achievements = $db->prepare("SELECT a.*, CASE WHEN ua.id IS NOT NULL THEN 1 ELSE 0 END as earned
        FROM achievements a
        LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = ?
        WHERE a.game_id = ? OR a.game_id IS NULL");
    $achievements->execute([($_SESSION['user_id'] ?? 0), $gameId]);

    // Handle add to library
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        requireLogin();
        $userId = $_SESSION['user_id'];

        if ($_POST['action'] === 'add') {
            $playtime = rand(30, 2000); // random playtime in minutes
            try {
                $db->prepare("INSERT INTO user_games (user_id, game_id, playtime) VALUES (?,?,?) ON DUPLICATE KEY UPDATE playtime = playtime")
                   ->execute([$userId, $gameId, $playtime]);
                checkAchievements($userId);
                setFlash('success', "🎮 {$game['name']} ajouté à votre bibliothèque !");
            } catch (PDOException $e) {
                setFlash('error', 'Erreur lors de l\'ajout.');
            }
        } elseif ($_POST['action'] === 'remove') {
            $db->prepare("DELETE FROM user_games WHERE user_id = ? AND game_id = ?")
               ->execute([$userId, $gameId]);
            setFlash('info', "Jeu retiré de votre bibliothèque.");
        }
        redirect(BASE_URL . '/games.php?id=' . $gameId);
    }

    // Check if already in library
    $inLibrary = false;
    if (isLoggedIn()) {
        $check = $db->prepare("SELECT id FROM user_games WHERE user_id = ? AND game_id = ?");
        $check->execute([$_SESSION['user_id'], $gameId]);
        $inLibrary = (bool)$check->fetch();
    }

    $pageTitle = $game['name'];
    include 'includes/header.php';
?>

<div class="container">
    <div style="margin-bottom:1.5rem">
        <a href="<?= BASE_URL ?>/games.php" style="color:var(--text-muted); font-size:0.9rem">← Retour aux jeux</a>
    </div>

    <div class="card" style="display:flex;gap:2rem;align-items:flex-start;flex-wrap:wrap;margin-bottom:2rem">
        <div class="game-card-img" style="width:220px;height:160px;border-radius:12px;flex-shrink:0;font-size:5rem">
            <?php if ($game['image'] && file_exists(UPLOAD_DIR . $game['image'])): ?>
                <img src="<?= BASE_URL ?>/uploads/games/<?= e($game['image']) ?>" alt="">
            <?php else: ?>🎮<?php endif; ?>
        </div>
        <div style="flex:1">
            <div class="game-type"><?= e($game['type']) ?></div>
            <h1 style="font-size:2rem;margin-bottom:0.5rem"><?= e($game['name']) ?></h1>
            <p style="color:var(--text-muted);margin-bottom:1rem"><?= e($game['developer']) ?> · <?= e($game['release_year']) ?></p>
            <p style="line-height:1.7;margin-bottom:1.5rem"><?= e($game['description']) ?></p>

            <?php if (isLoggedIn()): ?>
                <form method="POST">
                    <?php if ($inLibrary): ?>
                        <button name="action" value="remove" class="btn btn-danger btn-sm"
                                data-confirm="Retirer ce jeu de votre bibliothèque ?">✕ Retirer de ma bibliothèque</button>
                    <?php else: ?>
                        <button name="action" value="add" class="btn btn-primary">+ Ajouter à ma bibliothèque</button>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-secondary">🔑 Connectez-vous pour ajouter</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($levels)): ?>
    <h2 class="section-title">📋 Niveaux</h2>
    <div class="table-wrap card" style="padding:0;margin-bottom:2rem;overflow:hidden">
        <table>
            <thead><tr><th>Nom</th><th>Difficulté</th><th>Description</th></tr></thead>
            <tbody>
            <?php foreach ($levels as $lvl): ?>
                <tr>
                    <td><strong><?= e($lvl['name']) ?></strong></td>
                    <td><span class="difficulty diff-<?= e($lvl['difficulty']) ?>"><?= e($lvl['difficulty']) ?></span></td>
                    <td style="color:var(--text-muted)"><?= e($lvl['description']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php';
} else {
    // Game list page
    $pageTitle = 'Jeux';
    $games = $db->query("SELECT * FROM games ORDER BY name")->fetchAll();
    include 'includes/header.php';
?>

<div class="container">
    <div class="flex-between mb-3">
        <h1 class="section-title" style="margin-bottom:0">🎮 Catalogue de jeux</h1>
        <?php if (isAdmin()): ?>
            <a href="<?= BASE_URL ?>/admin/games.php?action=add" class="btn btn-primary btn-sm">+ Ajouter un jeu</a>
        <?php endif; ?>
    </div>

    <div class="games-grid">
        <?php foreach ($games as $game): ?>
        <div class="game-card">
            <div class="game-card-img">
                <?php if ($game['image'] && file_exists(UPLOAD_DIR . $game['image'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/games/<?= e($game['image']) ?>" alt="">
                <?php else: ?>🎮<?php endif; ?>
            </div>
            <div class="game-card-body">
                <div class="game-type"><?= e($game['type']) ?></div>
                <h3><?= e($game['name']) ?></h3>
                <p><?= e(substr($game['description'], 0, 90)) ?>...</p>
            </div>
            <div class="game-card-footer">
                <span class="text-muted" style="font-size:0.8rem"><?= e($game['release_year']) ?></span>
                <a href="?id=<?= $game['id'] ?>" class="btn btn-sm btn-secondary">Voir →</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include 'includes/footer.php';
} ?>
