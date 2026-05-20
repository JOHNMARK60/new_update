<?php
declare(strict_types=1);

namespace App\Reports;

class MonthlyReport extends Report
{
    protected function label(): string
    {
        return 'Monthly Sales Report';
    }

    protected function dateRange(array $filters): array
    {
        $anchor = strtotime((string) ($filters['date'] ?? $filters['date_from'] ?? date('Y-m-d')));

        return [
            'date_from' => date('Y-m-01', $anchor),
            'date_to' => date('Y-m-t', $anchor),
        ];
    }
}

