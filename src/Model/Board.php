<?php

namespace App\Model;

use App\Core\Database;
use PDO;

class Board
{
    public static function listAll(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query('SELECT id, name, finished, created_at FROM boards ORDER BY created_at DESC');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, finished, state, created_at FROM boards WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(string $name = 'Shooting sans titre'): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO boards (name) VALUES (?)');
        $stmt->execute([$name]);
        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
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
        $sql = 'UPDATE boards SET ' . implode(', ', $set) . ' WHERE id = ?';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM boards WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public static function toggleFinished(int $id): bool
    {
        $board = self::find($id);
        if (!$board) {
            return false;
        }
        return self::update($id, ['finished' => !((bool) $board['finished'])]);
    }
}
