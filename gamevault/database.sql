-- GameVault Database Schema
CREATE DATABASE IF NOT EXISTS gamevault CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gamevault;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    avatar VARCHAR(255) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Games table
CREATE TABLE IF NOT EXISTS games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    image VARCHAR(255) DEFAULT NULL,
    release_year INT,
    developer VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Levels table
CREATE TABLE IF NOT EXISTS levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    difficulty ENUM('Facile', 'Moyen', 'Difficile', 'Expert') DEFAULT 'Moyen',
    description TEXT,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

-- User games (interactions with games)
CREATE TABLE IF NOT EXISTS user_games (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    added_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    playtime INT DEFAULT 0,  -- in minutes, random
    UNIQUE KEY unique_user_game (user_id, game_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
);

-- Achievements table
CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT '🏆',
    condition_type VARCHAR(50),  -- e.g. 'playtime', 'games_count', 'levels_completed'
    condition_value INT DEFAULT 0,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL
);

-- User achievements
CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievements(id) ON DELETE CASCADE
);

-- Insert default admin user (password: Admin@123)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@gamevault.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('player1', 'player1@gamevault.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Note: password hash above is for 'password' (Laravel default). We'll generate proper ones via PHP.
-- Run the setup.php file to insert users with proper hashes.

-- Insert sample games (5 minimum required)
INSERT INTO games (name, type, description, image, release_year, developer) VALUES
('Shadow Chronicles', 'RPG', 'Un RPG épique où vous incarnez un héros qui doit sauver le royaume des ténèbres. Explorez des donjons, combattez des monstres et découvrez un scénario haletant.', 'shadow_chronicles.jpg', 2021, 'DarkForge Studios'),
('Pixel Racers', 'Course', 'Un jeu de course rétro en pixel art avec des dizaines de circuits à débloquer. Personnalisez votre voiture et affrontez des adversaires du monde entier.', 'pixel_racers.jpg', 2020, 'RetroSpeed Games'),
('Galaxy Defender', 'Shoot em up', 'Défendez la galaxie contre des vagues infinies d''ennemis extraterrestres. Des power-ups, des boss épiques et un mode multijoueur vous attendent.', 'galaxy_defender.jpg', 2022, 'StarBlast Interactive'),
('Dungeon Builder', 'Stratégie', 'Construisez et gérez votre propre donjon. Recrutez des monstres, placez des pièges et défendez votre trésor contre des aventuriers intrépides.', 'dungeon_builder.jpg', 2019, 'CraftMind Studios'),
('NeoCity Runner', 'Platformer', 'Parcourez une ville futuriste à toute vitesse dans ce platformer dynamique. Évitez les obstacles, collectez des cristaux et battez vos records.', 'neocity_runner.jpg', 2023, 'NeonWave Dev'),
('Frost Tactics', 'Stratégie au tour par tour', 'Un jeu de stratégie au tour par tour se déroulant dans un monde de glace et de magie. Gérez vos ressources, formez vos troupes et conquérez des territoires.', 'frost_tactics.jpg', 2022, 'IceLogic Games');

-- Insert levels for each game
INSERT INTO levels (game_id, name, difficulty, description) VALUES
(1, 'La Forêt des Ombres', 'Facile', 'Introduction au gameplay, explorez la forêt mystérieuse.'),
(1, 'Les Catacombes Maudites', 'Moyen', 'Un donjon souterrain peuplé de morts-vivants.'),
(1, 'Le Château du Néant', 'Difficile', 'La forteresse du seigneur des ténèbres.'),
(1, 'L''Abîme Éternel', 'Expert', 'Le niveau final, affrontez le boss ultime.'),
(2, 'Circuit Débutant', 'Facile', 'Un circuit simple pour apprendre les bases.'),
(2, 'Route Montagneuse', 'Moyen', 'Des virages serrés en altitude.'),
(2, 'Piste Infernale', 'Difficile', 'La piste la plus rapide et dangereuse.'),
(3, 'Secteur Alpha', 'Facile', 'Les premiers vaisseaux ennemis.'),
(3, 'Nébuleuse Hostile', 'Moyen', 'Combattez dans un champ d''astéroïdes.'),
(3, 'Invasion Finale', 'Expert', 'Repoussez la flotte mère ennemie.'),
(4, 'Caverne de Départ', 'Facile', 'Apprenez à construire votre premier donjon.'),
(4, 'Dédale Piégé', 'Difficile', 'Un labyrinthe complexe avec de nombreux pièges.'),
(5, 'Banlieue Est', 'Facile', 'Quartiers résidentiels de NeoCity.'),
(5, 'Downtown Chaos', 'Moyen', 'Le centre-ville animé et dangereux.'),
(5, 'Zone Industrielle', 'Difficile', 'Des plateformes mobiles et des lasers.'),
(6, 'Plaines de Givre', 'Facile', 'Les premières batailles sur les plaines enneigées.'),
(6, 'Forteresse de Glace', 'Expert', 'Assiégez la forteresse imprenable.');

-- Insert achievements
INSERT INTO achievements (game_id, name, description, icon, condition_type, condition_value) VALUES
(NULL, 'Premier Pas', 'Créez votre compte sur GameVault', '🎮', 'register', 1),
(NULL, 'Collectionneur', 'Ajoutez 3 jeux à votre bibliothèque', '📚', 'games_count', 3),
(NULL, 'Passionné', 'Ajoutez 5 jeux à votre bibliothèque', '🔥', 'games_count', 5),
(NULL, 'Marathon', 'Accumulez 300 minutes de jeu total', '⏱️', 'total_playtime', 300),
(NULL, 'Vétéran', 'Accumulez 1000 minutes de jeu total', '⭐', 'total_playtime', 1000),
(1, 'Chasseur d''Ombres', 'Commencez à jouer à Shadow Chronicles', '🗡️', 'game_added', 1),
(2, 'Pilote en Herbe', 'Commencez à jouer à Pixel Racers', '🏎️', 'game_added', 2),
(3, 'Défenseur Galactique', 'Commencez à jouer à Galaxy Defender', '🚀', 'game_added', 3),
(4, 'Architecte du Mal', 'Commencez à jouer à Dungeon Builder', '🏰', 'game_added', 4),
(5, 'Sprinter Urbain', 'Commencez à jouer à NeoCity Runner', '🏃', 'game_added', 5);
