-- Migration optionnelle : ajouter la table reels si la base existe déjà.
-- Exécuter après avoir appliqué schema.sql une première fois (sans la table reels).

USE shooting_board;

CREATE TABLE IF NOT EXISTS reels (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  board_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  file_path VARCHAR(512) NOT NULL,
  mime_type VARCHAR(64) NOT NULL DEFAULT 'video/mp4',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reels_board (board_id),
  CONSTRAINT reels_board_fk FOREIGN KEY (board_id) REFERENCES boards (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
