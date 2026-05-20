<?php
declare(strict_types=1);

namespace App\Services;

class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    public static function role(): string
    {
        return (string) ($_SESSION['role'] ?? '');
    }

    public static function cashierName(): string
    {
        $first = trim((string) ($_SESSION['first_name'] ?? ''));
        $last = trim((string) ($_SESSION['last_name'] ?? ''));

        return trim($first . ' ' . $last) ?: 'Cashier';
    }

    public static function isLoggedIn(): bool
    {
        return self::userId() > 0;
    }
}

