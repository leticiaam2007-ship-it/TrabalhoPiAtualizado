<?php
declare(strict_types=1);

namespace App\Core;

final class SessionAuth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function isLoggedIn(): bool
    {
        self::start();
        return isset($_SESSION['usuario_id']) && isset($_SESSION['email']);
    }

    public static function userId(): ?int
    {
        self::start();
        return isset($_SESSION['usuario_id']) ? (int) $_SESSION['usuario_id'] : null;
    }

    public static function userType(): ?string
    {
        self::start();
        return isset($_SESSION['tipo']) ? (string) $_SESSION['tipo'] : null;
    }

    public static function login(array $user): void
    {
        self::start();
        session_regenerate_id(true);

        $_SESSION['usuario_id'] = (int) $user['id'];
        $_SESSION['nome'] = (string) $user['nome'];
        $_SESSION['email'] = (string) $user['email'];
        $_SESSION['tipo'] = (string) $user['tipo'];
        $_SESSION['foto'] = isset($user['foto']) ? (string) $user['foto'] : '';
    }

    public static function requireLogin(string $redirect = 'login.php?erro=login'): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . $redirect);
            exit;
        }
    }

    public static function requireAdmin(string $redirect = 'login.php?erro=acesso'): void
    {
        self::requireLogin();
        if (self::userType() !== 'admin') {
            header('Location: ' . $redirect);
            exit;
        }
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
