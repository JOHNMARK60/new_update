<?php
declare(strict_types=1);

namespace App\Models;

class Role
{
    public const ADMIN = 'admin';
    public const CASHIER = 'cashier';

    public static function label(string $role): string
    {
        return $role === self::ADMIN ? 'Administrator' : 'Cashier';
    }
}

