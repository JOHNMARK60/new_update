<?php
declare(strict_types=1);

namespace App\Reports;

class YearlyReport extends Report
{
    protected function label(): string
    {
        return 'Yearly Sales Report';
    }

    protected function dateRange(array $filters): array
    {
        $anchor = strtotime((string) ($filters['date'] ?? $filters['date_from'] ?? date('Y-m-d')));

        return [
            'date_from' => date('Y-01-01', $anchor),
            'date_to' => date('Y-12-31', $anchor),
        ];
    }
}

