<?php

namespace App\Core;

class Router
{
    private string $action;

    public function __construct()
    {
        $this->action = trim($_GET['action'] ?? '') ?: 'dashboard';
    }

    public function dispatch(): void
    {
        if ($this->action === 'login') {
            $this->dispatchAuth('showLogin');
            return;
        }
        if ($this->action === 'do-login') {
            $this->dispatchAuth('login');
            return;
        }
        if ($this->action === 'logout') {
            $this->dispatchAuth('logout');
            return;
        }
        if (!Session::isLoggedIn()) {
            header('Location: index.php?action=login');
            exit;
        }
        if ($this->action === 'dashboard' || $this->action === '') {
            $this->dispatchBoard('dashboard');
            return;
        }
        if ($this->action === 'board') {
            $this->dispatchBoard('index');
            return;
        }
        if ($this->action === 'new-board') {
            $this->dispatchBoard('newBoard');
            return;
        }
        if ($this->action === 'save-board') {
            $this->dispatchBoard('saveBoard');
            return;
        }
        if ($this->action === 'delete-board') {
            $this->dispatchBoard('deleteBoard');
            return;
        }
        if ($this->action === 'toggle-finished') {
            $this->dispatchBoard('toggleFinished');
            return;
        }
        header('Location: index.php?action=dashboard');
        exit;
    }

    private function dispatchAuth(string $method): void
    {
        $controller = new \App\Controller\AuthController();
        if (!method_exists($controller, $method)) {
            header('Location: index.php?action=login');
            exit;
        }
        $controller->$method();
    }

    private function dispatchBoard(string $method): void
    {
        $controller = new \App\Controller\BoardController();
        if (!method_exists($controller, $method)) {
            header('Location: index.php?action=dashboard');
            exit;
        }
        $controller->$method();
    }
}
