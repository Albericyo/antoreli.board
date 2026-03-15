-- Migration optionnelle : base déjà en production avec table users et boards.user_id
-- Exécuter uniquement si vous aviez l’ancien schéma. À adapter selon le nom de votre base.

USE shooting_board;

ALTER TABLE boards DROP FOREIGN KEY boards_ibfk_1;
ALTER TABLE boards DROP INDEX idx_boards_user;
ALTER TABLE boards DROP COLUMN user_id;
DROP TABLE IF EXISTS users;
