<?php

/**
 * Front controller — point d'entrée unique de l'application.
 * Document root = public/
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', $projectRoot);
}

// Charger .env puis la config DB
require $projectRoot . '/src/Core/Env.php';
\App\Core\Env::load($projectRoot . '/.env');
require $projectRoot . '/src/config/database.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Router;
use App\Core\Session;

try {
    Session::start();
    $router = new Router();
    $router->dispatch();
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $showDetails = ($_ENV['APP_DEBUG'] ?? '') === '1' || ($_ENV['APP_DEBUG'] ?? '') === 'true';
    $message = $showDetails
        ? '<p><strong>Erreur :</strong> ' . htmlspecialchars($e->getMessage()) . '</p><p><small>' . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . '</small></p>'
        : 'Erreur serveur. Vérifiez le fichier .env (DB_*, ADMIN_*) et les logs PHP.';
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Erreur</title></head><body><h1>Erreur 500</h1>' . $message . '</body></html>';
}
