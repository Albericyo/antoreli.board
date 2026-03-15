<?php

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function getUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function getEmail(): ?string
    {
        return $_SESSION['email'] ?? null;
    }

    public static function setUser(int $userId, string $email): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;
    }

    public static function setError(string $message): void
    {
        $_SESSION['login_error'] = $message;
    }

    public static function getError(): ?string
    {
        $msg = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);
        return $msg;
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
        }
    }
}
