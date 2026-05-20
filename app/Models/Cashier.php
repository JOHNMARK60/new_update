<?php
declare(strict_types=1);

namespace App\Models;

class Cashier extends User
{
    public function can(string $permission): bool
    {
        return in_array($permission, ['process_sale', 'print_receipt', 'view_own_reports', 'close_own_day'], true);
    }
}

