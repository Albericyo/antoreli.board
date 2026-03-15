<?php

namespace App\Controller;

use App\Core\Session;
use App\Model\Board;

class BoardController
{
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
