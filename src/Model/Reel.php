<?php

declare(strict_types=1);

namespace App\Model;

use App\Core\Database;
use PDO;

class Reel
{
    public static function create(int $boardId, string $name, string $filePath, string $mimeType = 'video/mp4'): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO reels (board_id, name, file_path, mime_type) VALUES (?, ?, ?, ?)');
        $stmt->execute([$boardId, $name, $filePath, $mimeType]);
        return (int) $pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, board_id, name, file_path, mime_type, created_at FROM reels WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @return array<int, array{id: int, name: string, file_path: string, mime_type: string}>
     */
    public static function findByBoard(int $boardId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, file_path, mime_type FROM reels WHERE board_id = ? ORDER BY created_at ASC');
        $stmt->execute([$boardId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
        }
        return $rows;
    }

    public static function delete(int $id): bool
    {
        $reel = self::findById($id);
        if (!$reel) {
            return false;
        }
        self::deleteFileByPath($reel['file_path']);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM reels WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Supprime tous les reels d'un board (fichiers sur disque + lignes). À appeler avant Board::delete si pas de CASCADE.
     */
    public static function deleteByBoardId(int $boardId): void
    {
        $reels = self::findByBoard($boardId);
        foreach ($reels as $reel) {
            self::deleteFileByPath($reel['file_path']);
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM reels WHERE board_id = ?');
        $stmt->execute([$boardId]);
    }

    /**
     * Chemin absolu du fichier à partir du file_path relatif (stocké comme "reels/1/xxx.mp4" sous storage/).
     */
    public static function getAbsolutePath(string $filePath): string
    {
        $base = defined('PROJECT_ROOT') ? PROJECT_ROOT . '/storage' : dirname(__DIR__, 2) . '/storage';
        return $base . '/' . $filePath;
    }

    private static function deleteFileByPath(string $filePath): void
    {
        $abs = self::getAbsolutePath($filePath);
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}
