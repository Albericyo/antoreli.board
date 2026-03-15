-- Base de données Shooting Board
-- Exécuter ce script pour créer la base et les tables.
-- Adapter le nom de la base (shooting_board) si besoin (ex. préfixe Hostinger).

CREATE DATABASE IF NOT EXISTS u584471040_board
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE u584471040_board;

-- Shooting boards : nom, statut terminé, état (catégories + plans en JSON).
CREATE TABLE IF NOT EXISTS boards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL DEFAULT 'Shooting sans titre',
  finished TINYINT(1) NOT NULL DEFAULT 0,
  state LONGTEXT NULL COMMENT 'JSON: cats, clips',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reels : vidéos associées à un board (contenu en BLOB, LONGBLOB jusqu'à 4 Go).
-- MySQL : max_allowed_packet doit être >= taille max d'une vidéo (ex. 256M ou 512M).
CREATE TABLE IF NOT EXISTS reels (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  board_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(64) NOT NULL DEFAULT 'video/mp4',
  content LONGBLOB NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reels_board (board_id),
  CONSTRAINT reels_board_fk FOREIGN KEY (board_id) REFERENCES boards (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
