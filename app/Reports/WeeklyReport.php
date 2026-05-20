<?php
declare(strict_types=1);

namespace App\Reports;

class WeeklyReport extends Report
{
    protected function label(): string
    {
        return 'Weekly Sales Report';
    }

    protected function dateRange(array $filters): array
    {
        $anchor = strtotime((string) ($filters['date'] ?? $filters['date_from'] ?? date('Y-m-d')));

        return [
            'date_from' => date('Y-m-d', strtotime('monday this week', $anchor)),
            'date_to' => date('Y-m-d', strtotime('sunday this week', $anchor)),
        ];
    }
}

