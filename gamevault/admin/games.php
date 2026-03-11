<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$pageTitle = 'Admin — Jeux';
$db = getDB();

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'save_game') {
        $gameId      = (int)($_POST['game_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $type        = trim($_POST['type'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $developer   = trim($_POST['developer'] ?? '');
        $year        = (int)($_POST['release_year'] ?? date('Y'));
        $imageName   = null;

        if (strlen($name) < 2)   $errors[] = "Le nom est requis.";
        if (strlen($type) < 2)   $errors[] = "Le type est requis.";

        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $file     = $_FILES['image'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed)) {
                $errors[] = "Format d'image non supporté (jpg, png, gif, webp).";
            } elseif ($file['size'] > 2 * 1024 * 1024) {
                $errors[] = "Image trop lourde (max 2 Mo).";
            } else {
                $imageName = uniqid('game_') . '.' . $ext;
                if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $imageName)) {
                    $errors[] = "Erreur lors de l'upload de l'image.";
                    $imageName = null;
                }
            }
        }

        if (empty($errors)) {
            if ($gameId) {
                // Update
                if ($imageName) {
                    // Delete old image
                    $old = $db->prepare("SELECT image FROM games WHERE id = ?"); $old->execute([$gameId]);
                    $oldImg = $old->fetchColumn();
                    if ($oldImg && file_exists(UPLOAD_DIR . $oldImg)) unlink(UPLOAD_DIR . $oldImg);
                    $db->prepare("UPDATE games SET name=?,type=?,description=?,developer=?,release_year=?,image=? WHERE id=?")
                       ->execute([$name, $type, $description, $developer, $year, $imageName, $gameId]);
                } else {
                    $db->prepare("UPDATE games SET name=?,type=?,description=?,developer=?,release_year=? WHERE id=?")
                       ->execute([$name, $type, $description, $developer, $year, $gameId]);
                }
                setFlash('success', 'Jeu mis à jour !');
            } else {
                // Insert
                $db->prepare("INSERT INTO games (name,type,description,developer,release_year,image) VALUES (?,?,?,?,?,?)")
                   ->execute([$name, $type, $description, $developer, $year, $imageName]);
                setFlash('success', 'Jeu ajouté !');
            }
            redirect(BASE_URL . '/admin/games.php');
        }
    }

    if ($postAction === 'delete_game') {
        $gameId = (int)$_POST['game_id'];
        $old = $db->prepare("SELECT image FROM games WHERE id = ?"); $old->execute([$gameId]);
        $oldImg = $old->fetchColumn();
        if ($oldImg && file_exists(UPLOAD_DIR . $oldImg)) unlink(UPLOAD_DIR . $oldImg);
        $db->prepare("DELETE FROM games WHERE id = ?")->execute([$gameId]);
        setFlash('success', 'Jeu supprimé.');
        redirect(BASE_URL . '/admin/games.php');
    }
}

// Fetch edit game
$editGame = null;
if (in_array($action, ['edit', 'add'])) {
    if ($id) {
        $stmt = $db->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$id]);
        $editGame = $stmt->fetch();
    } else {
        $editGame = ['id'=>0,'name'=>'','type'=>'','description'=>'','developer'=>'','release_year'=>date('Y'),'image'=>null];
    }
}

// Fetch all games
$games = $db->query("SELECT g.*, COUNT(ug.id) as players FROM games g LEFT JOIN user_games ug ON ug.game_id = g.id GROUP BY g.id ORDER BY g.name")->fetchAll();

include '../includes/header.php';
?>

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <div class="flex-between mb-3">
            <h1 style="font-family:'Orbitron',monospace;color:var(--primary)">🎮 Jeux</h1>
            <a href="?action=add" class="btn btn-primary btn-sm">+ Ajouter</a>
        </div>

        <?php if ($editGame !== null): ?>
        <!-- Add/Edit form -->
        <div class="card" style="margin-bottom:2rem">
            <h2 style="margin-bottom:1.5rem"><?= $editGame['id'] ? '✏️ Modifier' : '➕ Ajouter un jeu' ?></h2>
            <?php if (!empty($errors)): ?>
                <div class="flash flash-error" style="margin-bottom:1rem;border-radius:8px">
                    <span><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></span>
                </div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save_game">
                <input type="hidden" name="game_id" value="<?= $editGame['id'] ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                    <div class="form-group">
                        <label>Nom du jeu *</label>
                        <input type="text" name="name" value="<?= e($editGame['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Type / Genre *</label>
                        <input type="text" name="type" value="<?= e($editGame['type']) ?>" placeholder="RPG, FPS, Platformer..." required>
                    </div>
                    <div class="form-group">
                        <label>Développeur</label>
                        <input type="text" name="developer" value="<?= e($editGame['developer']) ?>">
                    </div>
                    <div class="form-group">
                        <label>Année de sortie</label>
                        <input type="number" name="release_year" value="<?= e($editGame['release_year']) ?>" min="1970" max="2030">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4"><?= e($editGame['description']) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Image (jpg, png, gif, webp — max 2 Mo)</label>
                    <?php if ($editGame['image'] && file_exists(UPLOAD_DIR . $editGame['image'])): ?>
                        <div style="margin-bottom:0.5rem">
                            <img src="<?= BASE_URL ?>/uploads/games/<?= e($editGame['image']) ?>" style="height:80px;border-radius:8px">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div style="display:flex;gap:1rem">
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                    <a href="<?= BASE_URL ?>/admin/games.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Games table -->
        <div class="card" style="padding:0;overflow:hidden">
            <table>
                <thead>
                    <tr><th>Jeu</th><th>Type</th><th>Développeur</th><th>Année</th><th>Joueurs</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($games as $g): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:0.8rem">
                            <div style="width:40px;height:40px;border-radius:6px;background:var(--bg-card2);display:flex;align-items:center;justify-content:center;font-size:1.2rem;overflow:hidden">
                                <?php if ($g['image'] && file_exists(UPLOAD_DIR . $g['image'])): ?>
                                    <img src="<?= BASE_URL ?>/uploads/games/<?= e($g['image']) ?>" style="width:100%;height:100%;object-fit:cover">
                                <?php else: ?>🎮<?php endif; ?>
                            </div>
                            <strong><?= e($g['name']) ?></strong>
                        </div>
                    </td>
                    <td style="color:var(--primary);font-size:0.85rem"><?= e($g['type']) ?></td>
                    <td style="color:var(--text-muted);font-size:0.85rem"><?= e($g['developer']) ?></td>
                    <td style="color:var(--text-muted)"><?= $g['release_year'] ?></td>
                    <td style="font-family:'Orbitron',monospace;color:var(--accent)"><?= $g['players'] ?></td>
                    <td>
                        <div style="display:flex;gap:0.4rem">
                            <a href="?action=edit&id=<?= $g['id'] ?>" class="btn btn-sm btn-secondary">✏️</a>
                            <a href="<?= BASE_URL ?>/admin/levels.php?game_id=<?= $g['id'] ?>" class="btn btn-sm btn-secondary" title="Niveaux">📋</a>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="delete_game">
                                <input type="hidden" name="game_id" value="<?= $g['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger"
                                        data-confirm="Supprimer <?= e($g['name']) ?> ? Toutes les données liées seront perdues.">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
