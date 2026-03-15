<?php

namespace App\Core;

class Router
{
    private string $action;

    public function __construct()
    {
        $this->action = trim($_GET['action'] ?? '') ?: 'board';
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
        if ($this->action === 'board' || $this->action === '') {
            if (!Session::isLoggedIn()) {
                header('Location: index.php?action=login');
                exit;
            }
            $this->dispatchBoard('index');
            return;
        }
        header('Location: index.php?action=login');
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
            header('Location: index.php');
            exit;
        }
        $controller->$method();
    }
}
