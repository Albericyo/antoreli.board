<?php

namespace App\Controller;

use App\Core\Session;
use App\Model\User;

class AuthController
{
    public function showLogin(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: index.php');
            exit;
        }
        $error = Session::getError();
        require __DIR__ . '/../View/auth/login.php';
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=login');
            exit;
        }
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($email === '' || $password === '') {
            Session::setError('Email et mot de passe requis.');
            header('Location: index.php?action=login');
            exit;
        }
        $user = User::verifyPassword($email, $password);
        if (!$user) {
            Session::setError('Identifiants incorrects.');
            header('Location: index.php?action=login');
            exit;
        }
        Session::setUser((int) $user['id'], $user['email']);
        header('Location: index.php');
        exit;
    }

    public function logout(): void
    {
        Session::destroy();
        header('Location: index.php?action=login');
        exit;
    }
}
