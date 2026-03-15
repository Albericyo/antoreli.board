<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Session;
use App\Model\Board;
use App\Model\Reel;

class BoardController
{
    private const MAX_REEL_SIZE = 100 * 1024 * 1024; // 100 Mo
    private const ALLOWED_MIMES = ['video/mp4', 'video/quicktime', 'video/webm'];

    public function uploadReel(): void
    {
        ob_start();
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendUploadError('Méthode non autorisée', 400);
            return;
        }
        if (!Session::isLoggedIn()) {
            $this->sendUploadError('Non authentifié', 403);
            return;
        }
        try {
            $boardId = isset($_POST['board_id']) ? (int) $_POST['board_id'] : (isset($_GET['board_id']) ? (int) $_GET['board_id'] : 0);
            if (!$boardId || !Board::find($boardId)) {
                $this->sendUploadError('Board invalide', 400);
                return;
            }
            if (empty($_FILES['file']) || !isset($_FILES['file']['error'])) {
                $this->sendUploadError('Aucun fichier reçu (vérifiez post_max_size et upload_max_filesize en PHP)', 400);
                return;
            }
            $err = (int) $_FILES['file']['error'];
            if ($err !== UPLOAD_ERR_OK) {
                $msg = $err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE
                    ? 'Fichier trop volumineux (PHP: upload_max_filesize / post_max_size)'
                    : ($err === UPLOAD_ERR_NO_FILE ? 'Aucun fichier envoyé' : 'Erreur upload PHP (code ' . $err . ')');
                $this->sendUploadError($msg, 400);
                return;
            }
            if (empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
                $this->sendUploadError('Fichier temporaire invalide', 400);
                return;
            }
            $file = $_FILES['file'];
            $mime = $file['type'] ?? '';
            if (!in_array($mime, self::ALLOWED_MIMES, true)) {
                $this->sendUploadError('Type non autorisé (MP4, MOV, WebM uniquement)', 400);
                return;
            }
            if ($file['size'] > self::MAX_REEL_SIZE) {
                $this->sendUploadError('Fichier trop volumineux (max 100 Mo)', 400);
                return;
            }
            $baseName = preg_replace('/\.[^.]+$/', '', $file['name']);
            $baseName = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $baseName) ?: 'reel';
            $ext = 'mp4';
            if ($mime === 'video/quicktime') {
                $ext = 'mov';
            } elseif ($mime === 'video/webm') {
                $ext = 'webm';
            }
            $storageDir = (defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 2)) . '/storage/reels/' . $boardId;
            if (!is_dir($storageDir)) {
                if (!@mkdir($storageDir, 0755, true)) {
                    $this->sendUploadError('Impossible de créer le dossier de stockage (droits écriture)', 500);
                    return;
                }
            }
            $fileName = uniqid('', true) . '_' . substr($baseName, 0, 80) . '.' . $ext;
            $filePath = 'reels/' . $boardId . '/' . $fileName;
            $absPath = $storageDir . '/' . $fileName;
            if (!move_uploaded_file($file['tmp_name'], $absPath)) {
                $this->sendUploadError('Erreur lors de l\'enregistrement du fichier (droits écriture?)', 500);
                return;
            }
            $id = Reel::create($boardId, $baseName, $filePath, $mime);
            $url = 'index.php?action=stream-reel&id=' . $id;
            if (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(200);
            echo json_encode(['id' => $id, 'name' => $baseName, 'url' => $url]);
        } catch (\Throwable $e) {
            $this->sendUploadError('Erreur serveur: ' . $e->getMessage(), 500);
        }
    }

    /** Envoie une erreur JSON pour l’upload et arrête tout autre envoi. */
    private function sendUploadError(string $message, int $httpCode = 400): void
    {
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($httpCode);
        echo json_encode(['error' => $message]);
    }

    public function streamReel(): void
    {
        if (!Session::isLoggedIn()) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (!$id) {
            header('HTTP/1.1 400 Bad Request');
            exit;
        }
        $reel = Reel::findById($id);
        if (!$reel || !Board::find((int) $reel['board_id'])) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $absPath = Reel::getAbsolutePath($reel['file_path']);
        if (!is_file($absPath)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        header('Content-Type: ' . $reel['mime_type']);
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . filesize($absPath));
        readfile($absPath);
        exit;
    }

    public function deleteReel(): void
    {
        if (!Session::isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Non authentifié']);
            exit;
        }
        $id = isset($_POST['id']) ? (int) $_POST['id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'ID manquant']);
            exit;
        }
        $reel = Reel::findById($id);
        if (!$reel || !Board::find((int) $reel['board_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Reel introuvable']);
            exit;
        }
        Reel::delete($id);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    private function jsonError(string $message): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $message]);
    }

    public function dashboard(): void
    {
        if (!Session::isLoggedIn()) {
            header('Location: index.php?action=login');
            exit;
        }
        $boards = Board::listAll();
        require __DIR__ . '/../View/board/dashboard.php';
    }

    public function newBoard(): void
    {
        if (!Session::isLoggedIn()) {
            header('Location: index.php?action=login');
            exit;
        }
        $name = trim($_POST['name'] ?? '') ?: 'Shooting sans titre';
        $id = Board::create($name);
        header('Location: index.php?action=board&id=' . $id);
        exit;
    }

    public function index(): void
    {
        if (!Session::isLoggedIn()) {
            header('Location: index.php?action=login');
            exit;
        }
        $boardId = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if (!$boardId) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        $board = Board::find($boardId);
        if (!$board) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        $boardName = $board['name'];
        $state = null;
        if (!empty($board['state'])) {
            $decoded = json_decode($board['state'], true);
            $state = is_array($decoded) ? $decoded : null;
        }
        $reelsRaw = Reel::findByBoard($boardId);
        $reels = array_map(static function (array $r) {
            return [
                'id' => $r['id'],
                'name' => $r['name'],
                'url' => 'index.php?action=stream-reel&id=' . $r['id'],
            ];
        }, $reelsRaw);
        require __DIR__ . '/../View/board/index.php';
    }

    public function saveBoard(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=dashboard');
            exit;
        }
        if (!Session::isLoggedIn()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Non authentifié']);
            exit;
        }
        $input = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $id = isset($input['id']) ? (int) $input['id'] : 0;
        $name = isset($input['name']) ? trim((string) $input['name']) : null;
        $state = isset($input['state']) ? $input['state'] : null;
        if (!$id) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'ID manquant']);
            exit;
        }
        $board = Board::find($id);
        if (!$board) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Board introuvable']);
            exit;
        }
        $data = [];
        if ($name !== null) {
            $data['name'] = $name ?: 'Shooting sans titre';
        }
        if ($state !== null) {
            $data['state'] = $state;
        }
        if ($data !== []) {
            Board::update($id, $data);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }

    public function deleteBoard(): void
    {
        if (!Session::isLoggedIn()) {
            header('Location: index.php?action=login');
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id) {
            Reel::deleteByBoardId($id);
            Board::delete($id);
        }
        header('Location: index.php?action=dashboard');
        exit;
    }

    public function toggleFinished(): void
    {
        if (!Session::isLoggedIn()) {
            header('Location: index.php?action=login');
            exit;
        }
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id) {
            Board::toggleFinished($id);
        }
        header('Location: index.php?action=dashboard');
        exit;
    }
}
