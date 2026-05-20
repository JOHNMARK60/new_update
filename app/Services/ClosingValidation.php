<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\SaleRepository;
use PDO;
use RuntimeException;

class ClosingValidation
{
    private PDO $pdo;
    private SaleRepository $sales;

    public function __construct(?PDO $pdo = null, ?SaleRepository $sales = null)
    {
        $this->pdo = $pdo ?: Database::getConnection();
        $this->sales = $sales ?: new SaleRepository($this->pdo);
    }

    public function closeDay(string $date, int $cashierId, string $cashierName, int $closedBy, float $actualCashAmount, string $notes = ''): int
    {
        if ($cashierId <= 0) {
            throw new RuntimeException('Select a cashier before closing the day.');
        }

        $existing = $this->pdo->prepare('SELECT id FROM closing_reports WHERE closing_date = :closing_date AND cashier_id = :cashier_id LIMIT 1');
        $existing->execute(['closing_date' => $date, 'cashier_id' => $cashierId]);

        if ($existing->fetch()) {
            throw new RuntimeException('This cashier already has a closing report for the selected date.');
        }

        $filters = ['date_from' => $date, 'date_to' => $date, 'cashier_id' => $cashierId, 'status' => 'paid', 'closing_status' => 'open'];
        $summary = $this->sales->summary($filters);
        $expectedCash = round((float) $summary['total_sales'], 2);
        $actualCashAmount = round(max(0, $actualCashAmount), 2);
        $difference = round($actualCashAmount - $expectedCash, 2);
        $reviewStatus = $this->reviewStatus($difference);
        $adminFeedback = $this->automaticFeedback($difference);

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO closing_reports
                    (closing_date, cashier_id, cashier_name, total_transactions, total_items_sold, total_sales,
                     total_cash_received, expected_cash_amount, actual_cash_amount, difference_amount, closed_by, notes,
                     review_status, admin_feedback)
                 VALUES
                    (:closing_date, :cashier_id, :cashier_name, :total_transactions, :total_items_sold, :total_sales,
                     :total_cash_received, :expected_cash_amount, :actual_cash_amount, :difference_amount, :closed_by, :notes,
                     :review_status, :admin_feedback)'
            );
            $stmt->execute([
                'closing_date' => $date,
                'cashier_id' => $cashierId,
                'cashier_name' => $cashierName,
                'total_transactions' => $summary['total_transactions'],
                'total_items_sold' => $summary['total_items_sold'],
                'total_sales' => $summary['total_sales'],
                'total_cash_received' => $summary['total_cash_received'],
                'expected_cash_amount' => $expectedCash,
                'actual_cash_amount' => $actualCashAmount,
                'difference_amount' => $difference,
                'closed_by' => $closedBy,
                'notes' => trim($notes) !== '' ? trim($notes) : null,
                'review_status' => $reviewStatus,
                'admin_feedback' => $adminFeedback,
            ]);
            $closingId = (int) $this->pdo->lastInsertId();

            if ($closedBy === $cashierId) {
                (new AdminNotification($this->pdo))->createClosingNotification($closingId, $cashierName, $date, $expectedCash, $actualCashAmount);
            }

            $update = $this->pdo->prepare(
                "UPDATE sales
                 SET closing_status = 'closed', closed_at = NOW()
                 WHERE DATE(sale_date) = :sale_date
                 AND COALESCE(cashier_id, user_id) = :cashier_id
                 AND status = 'paid'"
            );
            $update->execute(['sale_date' => $date, 'cashier_id' => $cashierId]);

            $this->pdo->commit();

            return $closingId;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function closings(array $filters = []): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $clauses[] = 'cr.closing_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $clauses[] = 'cr.closing_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['cashier_id'])) {
            $clauses[] = 'cr.cashier_id = :cashier_id';
            $params['cashier_id'] = (int) $filters['cashier_id'];
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        $stmt = $this->pdo->prepare(
            "SELECT cr.*, CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS closed_by_name
             FROM closing_reports cr
             LEFT JOIN users u ON u.id = cr.closed_by
             {$where}
             ORDER BY cr.closing_date DESC, cr.closing_time DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    private function reviewStatus(float $difference): string
    {
        if ($difference < 0) {
            return 'short';
        }

        if ($difference > 0) {
            return 'over';
        }

        return 'balanced';
    }

    private function automaticFeedback(float $difference): ?string
    {
        if ($difference >= 0) {
            return null;
        }

        return sprintf(
            'Short PHP %s. Please explain missing cash before next shift.',
            number_format(abs($difference), 2)
        );
    }
}
