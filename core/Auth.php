<?php
declare(strict_types=1);

class Auth
{
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['user']);
    }

    public static function userRoleName(): string
    {
        $user = self::user();
        if (!$user) {
            return '';
        }

        return trim((string)($user['role_name'] ?? ''));
    }
}
