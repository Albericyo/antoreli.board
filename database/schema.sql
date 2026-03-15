-- Base de données Shooting Board
-- Exécuter ce script pour créer la base et la table users

CREATE DATABASE IF NOT EXISTS shooting_board
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE shooting_board;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS boards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL DEFAULT 'Shooting sans titre',
  finished TINYINT(1) NOT NULL DEFAULT 0,
  state LONGTEXT NULL COMMENT 'JSON: cats, clips',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_boards_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Créer un utilisateur de test (mot de passe: demo123)
-- En PHP : password_hash('demo123', PASSWORD_DEFAULT) puis insérer le hash ci-dessous.
-- INSERT INTO users (email, password_hash) VALUES ('demo@example.com', '$2y$10$...');
