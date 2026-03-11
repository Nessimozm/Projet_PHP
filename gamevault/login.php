<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Connexion';

if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $errors['general'] = "Veuillez remplir tous les champs.";
    } else {
        $db = getDB();
        // Allow login by username OR email
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$login, $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            setFlash('success', "Bienvenue, {$user['username']} ! 👾");
            redirect(BASE_URL . '/profile.php');
        } else {
            $errors['general'] = "Identifiants incorrects.";
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <div class="form-card">
        <h1 class="form-title">🔑 Connexion</h1>
        <p class="form-subtitle">Content de vous revoir, joueur !</p>

        <?php if (!empty($errors['general'])): ?>
            <div class="flash flash-error" style="margin-bottom:1rem; border-radius:8px;">
                <span><?= e($errors['general']) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label for="login">Nom d'utilisateur ou e-mail</label>
                <input type="text" id="login" name="login"
                       value="<?= e($_POST['login'] ?? '') ?>"
                       placeholder="Pseudo ou e-mail" required autofocus autocomplete="username">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password"
                       placeholder="Votre mot de passe" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary form-btn">🎮 Se connecter</button>
        </form>

        <div class="form-footer">
            Pas encore de compte ? <a href="<?= BASE_URL ?>/register.php">S'inscrire →</a>
        </div>

        <div class="form-footer mt-1" style="font-size:0.8rem; opacity:0.5;">
            Compte demo: <strong>admin</strong> / <strong>Admin@123</strong>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
