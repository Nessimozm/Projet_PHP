<?php
/**
 * GameVault Setup Script
 * Run this once to initialize the database: http://localhost/gamevault/setup.php
 * DELETE this file after running it!
 */

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbname = 'gamevault';

try {
    // Connect without DB first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Read and execute schema
    $sql = file_get_contents(__DIR__ . '/database.sql');

    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            try { $pdo->exec($stmt); } catch (PDOException $e) { /* ignore duplicate key etc */ }
        }
    }

    // Update passwords with proper bcrypt hashes
    $pdo->exec("USE $dbname");
    $adminHash  = password_hash('Admin@123', PASSWORD_BCRYPT);
    $playerHash = password_hash('Player@123', PASSWORD_BCRYPT);

    $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'")->execute([$adminHash]);
    $pdo->prepare("UPDATE users SET password = ? WHERE username = 'player1'")->execute([$playerHash]);

    echo "✅ Base de données initialisée avec succès !<br>";
    echo "Comptes créés :<br>";
    echo "- <strong>admin</strong> / Admin@123 (administrateur)<br>";
    echo "- <strong>player1</strong> / Player@123 (utilisateur)<br><br>";
    echo "<strong>⚠️ Supprimez ce fichier setup.php après utilisation !</strong><br><br>";
    echo '<a href="index.php">→ Aller au site</a>';

} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
