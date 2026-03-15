<?php

declare(strict_types=1);

namespace App\Model;

use App\Core\Database;
use PDO;

class Reel
{
    /**
     * Crée un reel avec le contenu vidéo en BLOB (stream pour limiter la mémoire).
     *
     * @param resource $contentStream Ressource de type stream (ex. fopen du fichier temporaire d'upload)
     */
    public static function create(int $boardId, string $name, string $mimeType, $contentStream): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO reels (board_id, name, mime_type, content) VALUES (?, ?, ?, ?)');
        $stmt->bindValue(1, $boardId, PDO::PARAM_INT);
        $stmt->bindValue(2, $name, PDO::PARAM_STR);
        $stmt->bindValue(3, $mimeType, PDO::PARAM_STR);
        $stmt->bindParam(4, $contentStream, PDO::PARAM_LOB);
        $stmt->execute();
        return (int) $pdo->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, board_id, name, mime_type, created_at FROM reels WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @return array<int, array{id: int, name: string, mime_type: string}>
     */
    public static function findByBoard(int $boardId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT id, name, mime_type FROM reels WHERE board_id = ? ORDER BY created_at ASC');
        $stmt->execute([$boardId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
        }
        return $rows;
    }

    /**
     * Récupère le flux de contenu (BLOB) pour le stream HTTP.
     * Utilise une requête non bufferisée + bindColumn LOB pour ne pas charger toute la vidéo en mémoire.
     *
     * @return array{stream: resource, mime_type: string, content_length: int}|null
     */
    public static function getContentStream(int $id): ?array
    {
        $pdo = Database::getConnection();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $stmt = $pdo->prepare('SELECT content, mime_type, LENGTH(content) AS content_length FROM reels WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $stmt->bindColumn(1, $stream, PDO::PARAM_LOB);
        $stmt->bindColumn(2, $mimeType, PDO::PARAM_STR);
        $stmt->bindColumn(3, $contentLength, PDO::PARAM_INT);
        $fetched = $stmt->fetch(PDO::FETCH_BOUND);
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        if (!$fetched || !is_resource($stream)) {
            return null;
        }
        return [
            'stream' => $stream,
            'mime_type' => $mimeType,
            'content_length' => (int) $contentLength,
        ];
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM reels WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Supprime tous les reels d'un board (lignes en base uniquement).
     */
    public static function deleteByBoardId(int $boardId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('DELETE FROM reels WHERE board_id = ?');
        $stmt->execute([$boardId]);
    }
}
