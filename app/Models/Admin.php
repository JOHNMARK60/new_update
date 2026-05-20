<?php
declare(strict_types=1);

namespace App\Models;

class Admin extends User
{
    public function can(string $permission): bool
    {
        return true;
    }
}

