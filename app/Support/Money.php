<?php
declare(strict_types=1);

namespace App\Support;

class Money
{
    public static function format(float|int|string $amount): string
    {
        return '₱' . number_format((float) $amount, 2);
    }
}

