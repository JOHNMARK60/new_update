<?php
declare(strict_types=1);

namespace App\Core;

abstract class BaseController
{
    protected function inputString(array $source, string $key, string $default = ''): string
    {
        return trim((string) ($source[$key] ?? $default));
    }

    protected function inputInt(array $source, string $key, int $default = 0): int
    {
        return (int) ($source[$key] ?? $default);
    }

    protected function inputMoney(array $source, string $key, float $default = 0.0): float
    {
        return round((float) ($source[$key] ?? $default), 2);
    }
}

