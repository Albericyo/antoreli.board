<?php

/**
 * Chemin de base du stockage (fichiers uploadés, reels, etc.).
 * En hébergement partagé (ex. Hostinger), PHP ne peut souvent pas créer de dossiers :
 * définir STORAGE_PATH dans .env (chemin absolu ou relatif au projet) et créer
 * manuellement les dossiers storage/ et storage/reels/ via FTP ou Gestionnaire de fichiers.
 */

if (!function_exists('get_storage_path')) {
    function get_storage_path(): string
    {
        $root = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 2);
        $envPath = $_ENV['STORAGE_PATH'] ?? '';
        $envPath = trim($envPath);
        if ($envPath === '') {
            return $root . DIRECTORY_SEPARATOR . 'storage';
        }
        // Chemin absolu (Unix / ou Windows C:\)
        if ($envPath[0] === '/' || (strlen($envPath) > 1 && $envPath[1] === ':')) {
            return rtrim($envPath, DIRECTORY_SEPARATOR . '/');
        }
        return $root . DIRECTORY_SEPARATOR . trim(str_replace('/', DIRECTORY_SEPARATOR, $envPath), DIRECTORY_SEPARATOR . '/');
    }
}
