<?php
// includes/config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gamevault');
define('SITE_NAME', 'GameVault');
define('BASE_URL', 'http://localhost/gamevault');
define('UPLOAD_DIR', __DIR__ . '/../uploads/games/');
define('UPLOAD_URL', BASE_URL . '/uploads/games/');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection using PDO
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
    return $pdo;
}

// Helper: Check if user is logged in
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

// Helper: Check if user is admin
function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Helper: Redirect
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// Helper: Sanitize output
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Helper: Flash messages
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Helper: Require login
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('error', 'Veuillez vous connecter pour accéder à cette page.');
        redirect(BASE_URL . '/login.php');
    }
}

// Helper: Require admin
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        setFlash('error', 'Accès réservé aux administrateurs.');
        redirect(BASE_URL . '/index.php');
    }
}

// Check and award achievements
function checkAchievements(int $userId): void {
    $db = getDB();

    // Get user stats
    $stmt = $db->prepare("SELECT COUNT(*) as games_count, SUM(playtime) as total_playtime FROM user_games WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    $gamesCount = (int)$stats['games_count'];
    $totalPlaytime = (int)$stats['total_playtime'];

    // Get all achievements not yet earned
    $stmt = $db->prepare("
        SELECT a.* FROM achievements a
        LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = ?
        WHERE ua.id IS NULL
    ");
    $stmt->execute([$userId]);
    $achievements = $stmt->fetchAll();

    foreach ($achievements as $ach) {
        $earned = false;
        switch ($ach['condition_type']) {
            case 'register':
                $earned = true;
                break;
            case 'games_count':
                $earned = $gamesCount >= $ach['condition_value'];
                break;
            case 'total_playtime':
                $earned = $totalPlaytime >= $ach['condition_value'];
                break;
            case 'game_added':
                $stmt2 = $db->prepare("SELECT 1 FROM user_games WHERE user_id = ? AND game_id = ?");
                $stmt2->execute([$userId, $ach['game_id']]);
                $earned = (bool)$stmt2->fetch();
                break;
        }
        if ($earned) {
            try {
                $db->prepare("INSERT IGNORE INTO user_achievements (user_id, achievement_id) VALUES (?, ?)")
                   ->execute([$userId, $ach['id']]);
            } catch (PDOException $e) { /* ignore duplicates */ }
        }
    }
}
