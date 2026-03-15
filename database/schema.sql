-- Base de données Shooting Board
-- Exécuter ce script pour créer la base et les tables.
-- Adapter le nom de la base (shooting_board) si besoin (ex. préfixe Hostinger).

CREATE DATABASE IF NOT EXISTS shooting_board
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE shooting_board;

-- Shooting boards : nom, statut terminé, état (catégories + plans en JSON).
CREATE TABLE IF NOT EXISTS boards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL DEFAULT 'Shooting sans titre',
  finished TINYINT(1) NOT NULL DEFAULT 0,
  state LONGTEXT NULL COMMENT 'JSON: cats, clips',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
