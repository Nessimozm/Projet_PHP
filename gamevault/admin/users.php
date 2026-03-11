<?php
require_once __DIR__ . '/../includes/config.php';
requireAdmin();
$pageTitle = 'Admin — Utilisateurs';
$db = getDB();

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// Handle edit submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'update_user') {
        $userId   = (int)$_POST['user_id'];
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = in_array($_POST['role'], ['user', 'admin']) ? $_POST['role'] : 'user';
        $errors   = [];

        if (strlen($username) < 3) $errors[] = "Nom d'utilisateur trop court.";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";

        // Check uniqueness (excluding current user)
        $exists = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $exists->execute([$username, $email, $userId]);
        if ($exists->fetch()) $errors[] = "Nom d'utilisateur ou e-mail déjà utilisé.";

        if (empty($errors)) {
            // Update password if provided
            if (!empty($_POST['new_password'])) {
                if (strlen($_POST['new_password']) < 8) {
                    $errors[] = "Nouveau mot de passe trop court.";
                } else {
                    $hash = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                    $db->prepare("UPDATE users SET username=?,email=?,role=?,password=? WHERE id=?")
                       ->execute([$username, $email, $role, $hash, $userId]);
                }
            } else {
                $db->prepare("UPDATE users SET username=?,email=?,role=? WHERE id=?")
                   ->execute([$username, $email, $role, $userId]);
            }

            if (empty($errors)) {
                setFlash('success', 'Utilisateur mis à jour.');
                redirect(BASE_URL . '/admin/users.php');
            }
        }
        if (!empty($errors)) {
            setFlash('error', implode(' ', $errors));
        }
    }

    if ($postAction === 'delete_user') {
        $userId = (int)$_POST['user_id'];
        if ($userId === $_SESSION['user_id']) {
            setFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        } else {
            $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
            setFlash('success', 'Utilisateur supprimé.');
        }
        redirect(BASE_URL . '/admin/users.php');
    }
}

// Fetch edit user
$editUser = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch();
    if (!$editUser) { setFlash('error', 'Utilisateur introuvable.'); redirect(BASE_URL . '/admin/users.php'); }
}

// Fetch all users
$users = $db->query("SELECT u.*, COUNT(ug.id) as games_count FROM users u LEFT JOIN user_games ug ON ug.user_id = u.id GROUP BY u.id ORDER BY u.created_at DESC")->fetchAll();

include '../includes/header.php';
?>

<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <div class="admin-content">
        <div class="flex-between mb-3">
            <h1 style="font-family:'Orbitron',monospace;color:var(--primary)">👥 Utilisateurs</h1>
        </div>

        <?php if ($editUser): ?>
        <!-- Edit form -->
        <div class="card" style="max-width:500px;margin-bottom:2rem">
            <h2 style="margin-bottom:1.5rem">✏️ Modifier #<?= $editUser['id'] ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" value="<?= $editUser['id'] ?>">
                <div class="form-group">
                    <label>Nom d'utilisateur</label>
                    <input type="text" name="username" value="<?= e($editUser['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" value="<?= e($editUser['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Rôle</label>
                    <select name="role">
                        <option value="user"  <?= $editUser['role'] === 'user'  ? 'selected' : '' ?>>Utilisateur</option>
                        <option value="admin" <?= $editUser['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nouveau mot de passe (laisser vide = inchangé)</label>
                    <input type="password" name="new_password" placeholder="Nouveau mot de passe...">
                </div>
                <div style="display:flex;gap:1rem">
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                    <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Users table -->
        <div class="card" style="padding:0;overflow:hidden">
            <table>
                <thead>
                    <tr><th>ID</th><th>Pseudo</th><th>E-mail</th><th>Rôle</th><th>Jeux</th><th>Inscrit le</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td style="color:var(--text-muted)">#<?= $u['id'] ?></td>
                    <td><strong><?= e($u['username']) ?></strong></td>
                    <td style="color:var(--text-muted);font-size:0.9rem"><?= e($u['email']) ?></td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                    <td style="font-family:'Orbitron',monospace;color:var(--primary)"><?= $u['games_count'] ?></td>
                    <td style="color:var(--text-muted);font-size:0.85rem"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div style="display:flex;gap:0.4rem">
                            <a href="?action=edit&id=<?= $u['id'] ?>" class="btn btn-sm btn-secondary">✏️</a>
                            <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger"
                                        data-confirm="Supprimer l'utilisateur <?= e($u['username']) ?> ?">🗑️</button>
                            </form>
                            <?php endif; ?>
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
