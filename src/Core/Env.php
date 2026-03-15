<?php

namespace App\Core;

/**
 * Charge les variables d'un fichier .env dans $_ENV.
 * Les lignes vides et les commentaires (#) sont ignorés.
 */
class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            [$name, $value] = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if ($name === '') {
                continue;
            }
            $value = self::unquote($value);
            $_ENV[$name] = $value;
            putenv("$name=$value");
        }
    }

    private static function unquote(string $value): string
    {
        $len = strlen($value);
        if ($len >= 2 && (($value[0] === '"' && $value[$len - 1] === '"') || ($value[0] === "'" && $value[$len - 1] === "'"))) {
            return substr($value, 1, -1);
        }
        return $value;
    }
}
