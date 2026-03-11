<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$pageTitle = 'Admin — Niveaux';
$db = getDB();

$gameId = (int)($_GET['game_id'] ?? 0);
$action = $_GET['action'] ?? 'list';
$lvlId  = (int)($_GET['id'] ?? 0);
$errors = [];

// Fetch game for context
$game = null;
if ($gameId) {
    $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->execute([$gameId]);
    $game = $stmt->fetch();
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save_level') {
        $levelId    = (int)$_POST['level_id'];
        $gId        = (int)$_POST['game_id'];
        $name       = trim($_POST['name'] ?? '');
        $difficulty = $_POST['difficulty'] ?? 'Moyen';
        $description = trim($_POST['description'] ?? '');

        if (!in_array($difficulty, ['Facile','Moyen','Difficile','Expert'])) $difficulty = 'Moyen';
        if (strlen($name) < 2) $errors[] = "Nom requis.";

        if (empty($errors)) {
            if ($levelId) {
                $db->prepare("UPDATE levels SET name=?,difficulty=?,description=? WHERE id=?")
                   ->execute([$name, $difficulty, $description, $levelId]);
            } else {
                $db->prepare("INSERT INTO levels (game_id,name,difficulty,description) VALUES (?,?,?,?)")
                   ->execute([$gId, $name, $difficulty, $description]);
            }
            setFlash('success', 'Niveau enregistré.');
            redirect(BASE_URL . '/admin/levels.php?game_id=' . $gId);
        }
    }

    if ($postAction === 'delete_level') {
        $levelId = (int)$_POST['level_id'];
        $gId     = (int)$_POST['game_id'];
        $db->prepare("DELETE FROM levels WHERE id = ?")->execute([$levelId]);
        setFlash('success', 'Niveau supprimé.');
        redirect(BASE_URL . '/admin/levels.php?game_id=' . $gId);
    }
}

// Fetch edit level
$editLevel = null;
if (in_array($action, ['edit','add'])) {
    if ($lvlId) {
        $stmt = $db->prepare("SELECT * FROM levels WHERE id = ?");
        $stmt->execute([$lvlId]);
        $editLevel = $stmt->fetch();
        $gameId    = $editLevel['game_id'];
    } else {
        $editLevel = ['id'=>0,'game_id'=>$gameId,'name'=>'','difficulty'=>'Moyen','description'=>''];
    }
}

// Fetch all games for selector
$allGames = $db->query("SELECT id, name FROM games ORDER BY name")->fetchAll();

// Fetch levels
$levels = [];
if ($gameId) {
    $stmt = $db->prepare("SELECT * FROM levels WHERE game_id = ? ORDER BY FIELD(difficulty,'Facile','Moyen','Difficile','Expert')");
    $stmt->execute([$gameId]);
    $levels = $stmt->fetchAll();
}

include '../includes/header.php';
?>

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <div class="flex-between mb-3">
            <h1 style="font-family:'Orbitron',monospace;color:var(--primary)">
                📋 Niveaux <?= $game ? '— ' . e($game['name']) : '' ?>
            </h1>
            <?php if ($gameId): ?>
            <a href="?action=add&game_id=<?= $gameId ?>" class="btn btn-primary btn-sm">+ Ajouter un niveau</a>
            <?php endif; ?>
        </div>

        <!-- Game selector -->
        <div class="card" style="margin-bottom:1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
            <label style="color:var(--text-muted);font-weight:600">Choisir un jeu :</label>
            <select onchange="location.href='?game_id='+this.value"
                    style="background:var(--bg-dark);border:1px solid var(--border);color:var(--text);padding:0.5rem 1rem;border-radius:8px;font-family:'Rajdhani',sans-serif">
                <option value="">-- Sélectionner --</option>
                <?php foreach ($allGames as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= $gameId === (int)$g['id'] ? 'selected' : '' ?>><?= e($g['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if ($editLevel !== null): ?>
        <div class="card" style="max-width:500px;margin-bottom:1.5rem">
            <h2 style="margin-bottom:1.2rem"><?= $editLevel['id'] ? '✏️ Modifier le niveau' : '➕ Nouveau niveau' ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="save_level">
                <input type="hidden" name="level_id" value="<?= $editLevel['id'] ?>">
                <input type="hidden" name="game_id" value="<?= $gameId ?>">
                <div class="form-group">
                    <label>Nom du niveau *</label>
                    <input type="text" name="name" value="<?= e($editLevel['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Difficulté</label>
                    <select name="difficulty">
                        <?php foreach (['Facile','Moyen','Difficile','Expert'] as $d): ?>
                            <option value="<?= $d ?>" <?= $editLevel['difficulty'] === $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?= e($editLevel['description']) ?></textarea>
                </div>
                <div style="display:flex;gap:1rem">
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                    <a href="<?= BASE_URL ?>/admin/levels.php?game_id=<?= $gameId ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($gameId && !empty($levels)): ?>
        <div class="card" style="padding:0;overflow:hidden">
            <table>
                <thead><tr><th>Nom</th><th>Difficulté</th><th>Description</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($levels as $lvl): ?>
                <tr>
                    <td><strong><?= e($lvl['name']) ?></strong></td>
                    <td><span class="difficulty diff-<?= e($lvl['difficulty']) ?>"><?= e($lvl['difficulty']) ?></span></td>
                    <td style="color:var(--text-muted);font-size:0.9rem"><?= e($lvl['description']) ?></td>
                    <td>
                        <div style="display:flex;gap:0.4rem">
                            <a href="?action=edit&id=<?= $lvl['id'] ?>&game_id=<?= $gameId ?>" class="btn btn-sm btn-secondary">✏️</a>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="delete_level">
                                <input type="hidden" name="level_id" value="<?= $lvl['id'] ?>">
                                <input type="hidden" name="game_id" value="<?= $gameId ?>">
                                <button type="submit" class="btn btn-sm btn-danger"
                                        data-confirm="Supprimer ce niveau ?">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($gameId): ?>
            <div class="card text-center" style="color:var(--text-muted)">Aucun niveau pour ce jeu.</div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
