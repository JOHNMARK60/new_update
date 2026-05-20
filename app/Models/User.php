<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\AbstractModel;

class User extends AbstractModel
{
    public function getId(): int
    {
        return (int) $this->get('id');
    }

    public function getFullName(): string
    {
        return trim((string) $this->get('first_name') . ' ' . (string) $this->get('last_name'));
    }

    public function getRole(): string
    {
        return (string) $this->get('role', 'cashier');
    }

    public function can(string $permission): bool
    {
        return $this->getRole() === 'admin' || in_array($permission, ['process_sale', 'print_receipt', 'view_own_reports', 'close_own_day'], true);
    }
}

