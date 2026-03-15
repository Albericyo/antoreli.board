<?php

namespace App\Model;

use App\Core\Database;
use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, email, password_hash FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function verifyPassword(string $email, string $password): ?array
    {
        $user = self::findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        return $user;
    }
}
