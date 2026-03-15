<?php

/**
 * Front controller — point d'entrée unique de l'application.
 * Document root = public/
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

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

Session::start();

$router = new Router();
$router->dispatch();
