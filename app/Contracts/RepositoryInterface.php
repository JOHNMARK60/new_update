<?php
declare(strict_types=1);

namespace App\Contracts;

interface RepositoryInterface
{
    public function find(int $id): ?array;

    public function all(): array;
}

