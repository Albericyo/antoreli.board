-- Migration : stockage des vidéos en BLOB (au lieu de fichiers sur disque).
-- Exécuter sur une base existante qui a déjà la table reels avec file_path.
-- Après bascule du code, optionnel : ALTER TABLE reels DROP COLUMN file_path;

-- Ajout de la colonne content (LONGBLOB jusqu'à 4 Go).
-- MySQL : max_allowed_packet doit être >= taille max d'une vidéo (ex. 256M ou 512M).
ALTER TABLE reels ADD COLUMN content LONGBLOB NULL AFTER mime_type;

-- Rendre file_path nullable pour la transition (nouveaux reels n'auront que content).
ALTER TABLE reels MODIFY COLUMN file_path VARCHAR(512) NULL;
