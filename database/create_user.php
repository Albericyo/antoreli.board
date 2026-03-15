<?php

/**
 * Script CLI pour créer un utilisateur.
 * Usage : php database/create_user.php email@example.com motdepasse
 */

if (php_sapi_name() !== 'cli') {
    exit('Ce script doit être exécuté en ligne de commande.');
}

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

if (!$email || !$password) {
    echo "Usage: php database/create_user.php <email> <mot_de_passe>\n";
    exit(1);
}

$projectRoot = dirname(__DIR__);
require $projectRoot . '/src/Core/Env.php';
\App\Core\Env::load($projectRoot . '/.env');
require $projectRoot . '/src/config/database.php';
require $projectRoot . '/src/Core/Database.php';

$pdo = \App\Core\Database::getConnection();
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
$stmt->execute([$email, $hash]);
echo "Utilisateur créé : $email\n";
