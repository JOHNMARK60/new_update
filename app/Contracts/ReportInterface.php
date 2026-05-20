<?php
declare(strict_types=1);

namespace App\Contracts;

interface ReportInterface
{
    public function generateReport(array $filters = []): array;
}

