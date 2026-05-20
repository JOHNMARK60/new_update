<?php
declare(strict_types=1);

namespace App\Services;

class Permission
{
    private const MAP = [
        'admin' => ['*'],
        'cashier' => ['process_sale', 'print_receipt', 'view_own_reports', 'close_own_day', 'view_products'],
    ];

    public static function can(string $role, string $permission): bool
    {
        $allowed = self::MAP[$role] ?? [];

        return in_array('*', $allowed, true) || in_array($permission, $allowed, true);
    }
}

