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

try {
    require $projectRoot . '/src/Core/Env.php';
    \App\Core\Env::load($projectRoot . '/.env');
    require $projectRoot . '/src/config/database.php';
    require $projectRoot . '/src/config/storage.php';

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

    \App\Core\Session::start();
    $router = new \App\Core\Router();
    $router->dispatch();
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $msg = $e->getMessage();
    $showTrace = ($_ENV['APP_DEBUG'] ?? '') === '1' || ($_ENV['APP_DEBUG'] ?? '') === 'true';
    $safeMsg = htmlspecialchars($msg);
    $body = '<p><strong>Erreur :</strong> ' . $safeMsg . '</p>';
    if ($showTrace) {
        $body .= '<p><small>' . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . '</small></p>';
        $body .= '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        $body .= '<p>Vérifiez le fichier <code>.env</code> (DB_HOST, DB_NAME, DB_USER, DB_PASS, ADMIN_EMAIL, ADMIN_PASSWORD). Pour plus de détail, ajoutez <code>APP_DEBUG=1</code> dans <code>.env</code>.</p>';
    }
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Erreur</title></head><body><h1>Erreur 500</h1>' . $body . '</body></html>';
}
