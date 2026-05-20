<?php
declare(strict_types=1);

namespace App\Reports;

class DailyReport extends Report
{
    protected function label(): string
    {
        return 'Daily Sales Report';
    }

    protected function dateRange(array $filters): array
    {
        $date = $filters['date'] ?? $filters['date_from'] ?? date('Y-m-d');

        return ['date_from' => $date, 'date_to' => $date];
    }
}

