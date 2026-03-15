<?php

/**
 * Configuration base de données MySQL.
 * Les valeurs sont lues depuis $_ENV (chargé depuis .env) avec repli sur les défauts ci-dessous.
 */

defined('DB_HOST') || define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
defined('DB_NAME') || define('DB_NAME', $_ENV['DB_NAME'] ?? 'shooting_board');
defined('DB_USER') || define('DB_USER', $_ENV['DB_USER'] ?? 'root');
defined('DB_PASS') || define('DB_PASS', $_ENV['DB_PASS'] ?? '');
defined('DB_CHARSET') || define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');
