<?php

namespace App\Model;

use App\Core\Database;
use PDO;

class Board
{
    public static function listByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, finished, created_at FROM boards WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id, int $userId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, finished, state, created_at FROM boards WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(int $userId, string $name = 'Shooting sans titre'): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO boards (user_id, name) VALUES (?, ?)');
        $stmt->execute([$userId, $name]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $allowed = ['name', 'finished', 'state'];
        $set = [];
        $params = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            if ($key === 'state') {
                $set[] = 'state = ?';
                $params[] = is_string($value) ? $value : json_encode($value);
            } elseif ($key === 'finished') {
                $set[] = 'finished = ?';
                $params[] = $value ? 1 : 0;
            } else {
                $set[] = $key . ' = ?';
                $params[] = $value;
            }
        }
        if ($set === []) {
            return false;
        }
        $params[] = $id;
        $params[] = $userId;
        $sql = 'UPDATE boards SET ' . implode(', ', $set) . ' WHERE id = ? AND user_id = ?';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id, int $userId): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM boards WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function toggleFinished(int $id, int $userId): bool
    {
        $board = self::find($id, $userId);
        if (!$board) {
            return false;
        }
        return self::update($id, $userId, ['finished' => !((bool) $board['finished'])]);
    }
}
