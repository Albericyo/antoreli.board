<?php

namespace App\Controller;

use App\Core\Session;

class AuthController
{
    public function showLogin(): void
    {
        if (Session::isLoggedIn()) {
            header('Location: index.php?action=dashboard');
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
        $adminEmail = trim($_ENV['ADMIN_EMAIL'] ?? '');
        $adminPassword = $_ENV['ADMIN_PASSWORD'] ?? '';
        $adminPasswordHash = trim($_ENV['ADMIN_PASSWORD_HASH'] ?? '');
        if ($adminEmail === '') {
            Session::setError('Configuration manquante (ADMIN_EMAIL).');
            header('Location: index.php?action=login');
            exit;
        }
        if ($email !== $adminEmail) {
            Session::setError('Identifiants incorrects.');
            header('Location: index.php?action=login');
            exit;
        }
        $passwordOk = false;
        if ($adminPasswordHash !== '') {
            $passwordOk = password_verify($password, $adminPasswordHash);
        } else {
            $passwordOk = ($password === $adminPassword);
        }
        if (!$passwordOk) {
            Session::setError('Identifiants incorrects.');
            header('Location: index.php?action=login');
            exit;
        }
        Session::setUser(1, $adminEmail);
        header('Location: index.php?action=dashboard');
        exit;
    }

    public function logout(): void
    {
        Session::destroy();
        header('Location: index.php?action=login');
        exit;
    }
}
