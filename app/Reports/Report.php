<?php
declare(strict_types=1);

namespace App\Reports;

use App\Contracts\ReportInterface;
use App\Repositories\SaleRepository;

abstract class Report implements ReportInterface
{
    protected SaleRepository $sales;

    public function __construct(?SaleRepository $sales = null)
    {
        $this->sales = $sales ?: new SaleRepository();
    }

    public function generateReport(array $filters = []): array
    {
        $filters = array_merge($this->dateRange($filters), $filters);

        return [
            'type' => $this->label(),
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'summary' => $this->sales->summary($filters),
            'items' => $this->sales->itemSummary($filters),
            'payments' => $this->sales->paymentSummary($filters),
            'cashiers' => $this->sales->cashierPerformance($filters),
            'transactions' => $this->sales->transactions($filters),
        ];
    }

    abstract protected function label(): string;

    abstract protected function dateRange(array $filters): array;
}

