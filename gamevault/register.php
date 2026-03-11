<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Inscription';

if (isLoggedIn()) redirect(BASE_URL . '/index.php');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    // Validation
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors['username'] = "Le nom d'utilisateur doit faire entre 3 et 50 caractères.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = "Uniquement lettres, chiffres et underscores.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Adresse e-mail invalide.";
    }

    if (strlen($password) < 8) {
        $errors['password'] = "Le mot de passe doit faire au moins 8 caractères.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = "Le mot de passe doit contenir au moins une majuscule et un chiffre.";
    }

    if ($password !== $confirm) {
        $errors['confirm'] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        $db = getDB();

        // Check uniqueness
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $errors['general'] = "Ce nom d'utilisateur ou cet e-mail est déjà utilisé.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)")
               ->execute([$username, $email, $hash]);

            $userId = $db->lastInsertId();

            // Set session
            $_SESSION['user_id']  = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['role']     = 'user';

            // Award 'Premier Pas' achievement
            checkAchievements($userId);

            setFlash('success', "Bienvenue sur GameVault, $username ! 🎮");
            redirect(BASE_URL . '/profile.php');
        }
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <div class="form-card">
        <h1 class="form-title">⚡ Créer un compte</h1>
        <p class="form-subtitle">Rejoignez la communauté GameVault</p>

        <?php if (!empty($errors['general'])): ?>
            <div class="flash flash-error" style="margin-bottom:1rem; border-radius:8px;">
                <span><?= e($errors['general']) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input type="text" id="username" name="username"
                       value="<?= e($_POST['username'] ?? '') ?>"
                       placeholder="ex: GamerPro42" required autocomplete="username">
                <?php if (!empty($errors['username'])): ?>
                    <div class="form-error"><?= e($errors['username']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email"
                       value="<?= e($_POST['email'] ?? '') ?>"
                       placeholder="vous@exemple.com" required autocomplete="email">
                <?php if (!empty($errors['email'])): ?>
                    <div class="form-error"><?= e($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password"
                       placeholder="Min 8 caractères, 1 maj, 1 chiffre" required autocomplete="new-password">
                <?php if (!empty($errors['password'])): ?>
                    <div class="form-error"><?= e($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="confirm">Confirmer le mot de passe</label>
                <input type="password" id="confirm" name="confirm"
                       placeholder="Retapez votre mot de passe" required autocomplete="new-password">
                <?php if (!empty($errors['confirm'])): ?>
                    <div class="form-error"><?= e($errors['confirm']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary form-btn">🚀 Créer mon compte</button>
        </form>

        <div class="form-footer">
            Déjà un compte ? <a href="<?= BASE_URL ?>/login.php">Se connecter →</a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
