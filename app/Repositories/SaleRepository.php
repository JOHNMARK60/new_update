<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;
use App\Models\Payment;
use RuntimeException;

class SaleRepository extends BaseRepository
{
    protected string $table = 'sales';

    public function createSale(array $items, int $cashierId, string $cashierName, Payment $payment, float $discount = 0.0, float $tax = 0.0): int
    {
        if ($cashierId <= 0 || trim($cashierName) === '') {
            throw new RuntimeException('A logged-in cashier is required before completing a sale.');
        }

        $items = $this->normalizeItems($items);

        if ($items === []) {
            throw new RuntimeException('Add at least one product before completing the transaction.');
        }

        $this->pdo->beginTransaction();

        try {
            $productIds = array_keys($items);
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id IN ({$placeholders}) FOR UPDATE");
            $stmt->execute($productIds);
            $products = [];

            foreach ($stmt->fetchAll() as $product) {
                $products[(int) $product['id']] = $product;
            }

            $saleItems = [];
            $subtotal = 0.0;

            foreach ($items as $productId => $quantity) {
                if (!isset($products[$productId])) {
                    throw new RuntimeException('One or more selected products no longer exist.');
                }

                $product = $products[$productId];

                if ((int) $product['quantity'] < $quantity) {
                    throw new RuntimeException($product['name'] . ' has only ' . (int) $product['quantity'] . ' item(s) left.');
                }

                $unitPrice = round((float) $product['price'], 2);
                $lineTotal = round($unitPrice * $quantity, 2);
                $subtotal += $lineTotal;
                $saleItems[] = [
                    'product_id' => $productId,
                    'product_name' => $product['name'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $lineTotal,
                    'stock_before' => (int) $product['quantity'],
                    'stock_after' => (int) $product['quantity'] - $quantity,
                ];
            }

            $discount = round(max(0, $discount), 2);
            $tax = round(max(0, $tax), 2);
            $total = round(max($subtotal - $discount + $tax, 0), 2);

            if (abs($payment->getAmount() - $total) > 0.009) {
                $payment->set('amount', $total);
            }

            if (!$payment->isSufficient()) {
                throw new RuntimeException('Tendered amount is not enough for this transaction.');
            }

            $receiptNo = $this->createReceiptNo();
            $firstItem = $saleItems[0];
            $insertSale = $this->pdo->prepare(
                "INSERT INTO sales
                    (receipt_no, cashier_id, cashier_name, product_id, quantity, total_price, subtotal_amount,
                     discount, tax, total_amount, tendered_amount, change_amount, payment_method,
                     user_id, sale_date, status, closing_status)
                 VALUES
                    (:receipt_no, :cashier_id, :cashier_name, :product_id, :quantity, :total_price, :subtotal_amount,
                     :discount, :tax, :total_amount, :tendered_amount, :change_amount, :payment_method,
                     :user_id, NOW(), 'paid', 'open')"
            );
            $insertSale->execute([
                'receipt_no' => $receiptNo,
                'cashier_id' => $cashierId,
                'cashier_name' => $cashierName,
                'product_id' => $firstItem['product_id'],
                'quantity' => $firstItem['quantity'],
                'total_price' => $total,
                'subtotal_amount' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total_amount' => $total,
                'tendered_amount' => $payment->getTenderedAmount(),
                'change_amount' => $payment->getChangeAmount(),
                'payment_method' => $payment->getMethod(),
                'user_id' => $cashierId,
            ]);
            $saleId = (int) $this->pdo->lastInsertId();

            $insertItem = $this->pdo->prepare(
                'INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, total_price)
                 VALUES (:sale_id, :product_id, :product_name, :quantity, :unit_price, :total_price)'
            );
            $updateStock = $this->pdo->prepare('UPDATE products SET quantity = quantity - :quantity_out WHERE id = :product_id AND quantity >= :quantity_check');
            $insertLog = $this->pdo->prepare(
                'INSERT INTO inventory_logs
                    (product_id, action, quantity_change, stock_before, stock_after, reference_type, reference_id, created_by)
                 VALUES
                    (:product_id, :action, :quantity_change, :stock_before, :stock_after, :reference_type, :reference_id, :created_by)'
            );

            foreach ($saleItems as $item) {
                $insertItem->execute([
                    'sale_id' => $saleId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);

                $updateStock->execute([
                    'quantity_out' => $item['quantity'],
                    'quantity_check' => $item['quantity'],
                    'product_id' => $item['product_id'],
                ]);

                if ($updateStock->rowCount() !== 1) {
                    throw new RuntimeException($item['product_name'] . ' stock changed while processing. Please retry.');
                }

                $insertLog->execute([
                    'product_id' => $item['product_id'],
                    'action' => 'Sold ' . $item['quantity'] . ' item(s)',
                    'quantity_change' => -$item['quantity'],
                    'stock_before' => $item['stock_before'],
                    'stock_after' => $item['stock_after'],
                    'reference_type' => 'sale',
                    'reference_id' => $saleId,
                    'created_by' => $cashierId,
                ]);
            }

            $insertPayment = $this->pdo->prepare(
                'INSERT INTO payments
                    (sale_id, amount, tendered_amount, change_amount, currency, payment_method, payment_date)
                 VALUES
                    (:sale_id, :amount, :tendered_amount, :change_amount, :currency, :payment_method, NOW())'
            );
            $insertPayment->execute([
                'sale_id' => $saleId,
                'amount' => $total,
                'tendered_amount' => $payment->getTenderedAmount(),
                'change_amount' => $payment->getChangeAmount(),
                'currency' => 'PHP',
                'payment_method' => $payment->getMethod(),
            ]);

            $this->pdo->prepare('INSERT INTO receipts (sale_id, receipt_no) VALUES (:sale_id, :receipt_no)')
                ->execute(['sale_id' => $saleId, 'receipt_no' => $receiptNo]);

            $this->pdo->commit();

            return $saleId;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function getSaleWithItems(int $saleId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.*, u.email AS cashier_email
             FROM sales s
             LEFT JOIN users u ON u.id = s.cashier_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $saleId]);
        $sale = $stmt->fetch();

        if (!$sale) {
            return null;
        }

        $itemStmt = $this->pdo->prepare('SELECT * FROM sale_items WHERE sale_id = :sale_id ORDER BY id ASC');
        $itemStmt->execute(['sale_id' => $saleId]);
        $sale['items'] = $itemStmt->fetchAll();

        $paymentStmt = $this->pdo->prepare('SELECT * FROM payments WHERE sale_id = :sale_id ORDER BY id DESC LIMIT 1');
        $paymentStmt->execute(['sale_id' => $saleId]);
        $sale['payment'] = $paymentStmt->fetch() ?: null;

        return $sale;
    }

    public function transactions(array $filters = []): array
    {
        [$where, $params] = $this->buildFilterWhere($filters, 's');
        $stmt = $this->pdo->prepare(
            "SELECT s.*,
                    COALESCE(item_totals.line_count, 0) AS line_count,
                    COALESCE(item_totals.qty, s.quantity, 0) AS items_sold
             FROM sales s
             LEFT JOIN (
                SELECT sale_id, COUNT(*) AS line_count, SUM(quantity) AS qty
                FROM sale_items
                GROUP BY sale_id
             ) item_totals ON item_totals.sale_id = s.id
             {$where}
             ORDER BY s.sale_date DESC, s.id DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function summary(array $filters = []): array
    {
        [$where, $params] = $this->buildFilterWhere($filters, 's');
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT s.id) AS total_transactions,
                    COALESCE(SUM(CASE WHEN s.status = 'void' THEN 0 ELSE s.total_amount END), 0) AS total_sales,
                    COALESCE(SUM(CASE WHEN s.status = 'void' THEN 0 ELSE s.tendered_amount END), 0) AS total_cash_received,
                    COALESCE(SUM(CASE WHEN s.status = 'void' THEN 0 ELSE item_totals.qty END), 0) AS total_items_sold,
                    SUM(CASE WHEN s.status = 'void' THEN 1 ELSE 0 END) AS void_transactions
             FROM sales s
             LEFT JOIN (
                SELECT sale_id, SUM(quantity) AS qty
                FROM sale_items
                GROUP BY sale_id
             ) item_totals ON item_totals.sale_id = s.id
             {$where}"
        );
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'total_transactions' => (int) ($row['total_transactions'] ?? 0),
            'total_sales' => (float) ($row['total_sales'] ?? 0),
            'total_cash_received' => (float) ($row['total_cash_received'] ?? 0),
            'total_items_sold' => (int) ($row['total_items_sold'] ?? 0),
            'void_transactions' => (int) ($row['void_transactions'] ?? 0),
        ];
    }

    public function itemSummary(array $filters = []): array
    {
        $saleFilters = $filters;
        unset($saleFilters['product_id'], $saleFilters['category_id']);
        [$where, $params] = $this->buildFilterWhere($saleFilters, 's');
        $extraClauses = [];

        if (!empty($filters['product_id'])) {
            $extraClauses[] = 'si.product_id = :item_product_id';
            $params['item_product_id'] = (int) $filters['product_id'];
        }

        if (!empty($filters['category_id'])) {
            $extraClauses[] = 'p.category_id = :item_category_id';
            $params['item_category_id'] = (int) $filters['category_id'];
        }

        if ($extraClauses) {
            $where .= ($where === '' ? 'WHERE ' : ' AND ') . implode(' AND ', $extraClauses);
        }

        $stmt = $this->pdo->prepare(
            "SELECT si.product_id,
                    si.product_name,
                    COALESCE(c.name, 'Uncategorized') AS category_name,
                    SUM(si.quantity) AS quantity_sold,
                    SUM(si.total_price) AS total_amount
             FROM sale_items si
             INNER JOIN sales s ON s.id = si.sale_id
             LEFT JOIN products p ON p.id = si.product_id
             LEFT JOIN categories c ON c.id = p.category_id
             {$where}
             GROUP BY si.product_id, si.product_name, c.name
             ORDER BY quantity_sold DESC, total_amount DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function cashierPerformance(array $filters = []): array
    {
        [$where, $params] = $this->buildFilterWhere($filters, 's');
        $stmt = $this->pdo->prepare(
            "SELECT cashier_id,
                    cashier_name,
                    COUNT(DISTINCT id) AS total_transactions,
                    COALESCE(SUM(qty), 0) AS total_items_sold,
                    COALESCE(SUM(total_amount), 0) AS total_sales
             FROM (
                SELECT s.id,
                       COALESCE(s.cashier_id, s.user_id) AS cashier_id,
                       COALESCE(NULLIF(s.cashier_name, ''), CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')), 'N/A') AS cashier_name,
                       s.total_amount,
                       item_totals.qty
                FROM sales s
                LEFT JOIN users u ON u.id = COALESCE(s.cashier_id, s.user_id)
                LEFT JOIN (
                    SELECT sale_id, SUM(quantity) AS qty
                    FROM sale_items
                    GROUP BY sale_id
                ) item_totals ON item_totals.sale_id = s.id
                {$where}
             ) cashier_sales
             GROUP BY cashier_id, cashier_name
             ORDER BY total_sales DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function paymentSummary(array $filters = []): array
    {
        [$where, $params] = $this->buildFilterWhere($filters, 's');
        $stmt = $this->pdo->prepare(
            "SELECT COALESCE(p.payment_method, s.payment_method, 'cash') AS payment_method,
                    COUNT(DISTINCT s.id) AS transaction_count,
                    COALESCE(SUM(p.amount), SUM(s.total_amount), 0) AS amount,
                    COALESCE(SUM(p.tendered_amount), SUM(s.tendered_amount), 0) AS tendered_amount,
                    COALESCE(SUM(p.change_amount), SUM(s.change_amount), 0) AS change_amount
             FROM sales s
             LEFT JOIN payments p ON p.sale_id = s.id
             {$where}
             GROUP BY payment_method
             ORDER BY amount DESC"
        );
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function salesByDay(array $filters = [], int $days = 7): array
    {
        $days = max(1, min($days, 31));
        $endDate = new \DateTimeImmutable((string) ($filters['date_to'] ?? date('Y-m-d')));
        $startDate = !empty($filters['date_from'])
            ? new \DateTimeImmutable((string) $filters['date_from'])
            : $endDate->modify('-' . ($days - 1) . ' days');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $filters['date_from'] = $startDate->format('Y-m-d');
        $filters['date_to'] = $endDate->format('Y-m-d');

        [$where, $params] = $this->buildFilterWhere($filters, 's');
        $stmt = $this->pdo->prepare(
            "SELECT DATE(s.sale_date) AS period,
                    COUNT(DISTINCT s.id) AS total_transactions,
                    COALESCE(SUM(CASE WHEN s.status = 'void' THEN 0 ELSE s.total_amount END), 0) AS total_sales,
                    COALESCE(SUM(CASE WHEN s.status = 'void' THEN 0 ELSE item_totals.qty END), 0) AS total_items_sold
             FROM sales s
             LEFT JOIN (
                SELECT sale_id, SUM(quantity) AS qty
                FROM sale_items
                GROUP BY sale_id
             ) item_totals ON item_totals.sale_id = s.id
             {$where}
             GROUP BY DATE(s.sale_date)
             ORDER BY period ASC"
        );
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(string) $row['period']] = [
                'total_transactions' => (int) $row['total_transactions'],
                'total_sales' => (float) $row['total_sales'],
                'total_items_sold' => (int) $row['total_items_sold'],
            ];
        }

        $series = [];
        for ($cursor = $startDate; $cursor->getTimestamp() <= $endDate->getTimestamp(); $cursor = $cursor->modify('+1 day')) {
            $period = $cursor->format('Y-m-d');
            $values = $rows[$period] ?? [
                'total_transactions' => 0,
                'total_sales' => 0.0,
                'total_items_sold' => 0,
            ];

            $series[] = array_merge([
                'period' => $period,
                'label' => $cursor->format('M d'),
            ], $values);
        }

        return $series;
    }

    public function salesByMonth(array $filters = [], int $months = 6): array
    {
        $months = max(1, min($months, 24));
        $endDate = (new \DateTimeImmutable((string) ($filters['date_to'] ?? date('Y-m-d'))))
            ->modify('last day of this month');
        $startDate = !empty($filters['date_from'])
            ? (new \DateTimeImmutable((string) $filters['date_from']))->modify('first day of this month')
            : $endDate->modify('first day of this month')->modify('-' . ($months - 1) . ' months');

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate->modify('first day of this month'), $startDate->modify('last day of this month')];
        }

        $filters['date_from'] = $startDate->format('Y-m-d');
        $filters['date_to'] = $endDate->format('Y-m-d');

        [$where, $params] = $this->buildFilterWhere($filters, 's');
        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(s.sale_date, '%Y-%m') AS period,
                    COUNT(DISTINCT s.id) AS total_transactions,
                    COALESCE(SUM(CASE WHEN s.status = 'void' THEN 0 ELSE s.total_amount END), 0) AS total_sales,
                    COALESCE(SUM(CASE WHEN s.status = 'void' THEN 0 ELSE item_totals.qty END), 0) AS total_items_sold
             FROM sales s
             LEFT JOIN (
                SELECT sale_id, SUM(quantity) AS qty
                FROM sale_items
                GROUP BY sale_id
             ) item_totals ON item_totals.sale_id = s.id
             {$where}
             GROUP BY DATE_FORMAT(s.sale_date, '%Y-%m')
             ORDER BY period ASC"
        );
        $stmt->execute($params);

        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(string) $row['period']] = [
                'total_transactions' => (int) $row['total_transactions'],
                'total_sales' => (float) $row['total_sales'],
                'total_items_sold' => (int) $row['total_items_sold'],
            ];
        }

        $series = [];
        for ($cursor = $startDate; $cursor->getTimestamp() <= $endDate->getTimestamp(); $cursor = $cursor->modify('+1 month')) {
            $period = $cursor->format('Y-m');
            $values = $rows[$period] ?? [
                'total_transactions' => 0,
                'total_sales' => 0.0,
                'total_items_sold' => 0,
            ];

            $series[] = array_merge([
                'period' => $period,
                'label' => $cursor->format('M Y'),
            ], $values);
        }

        return $series;
    }

    public function buildFilterWhere(array $filters, string $saleAlias = 's'): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $clauses[] = "DATE({$saleAlias}.sale_date) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $clauses[] = "DATE({$saleAlias}.sale_date) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['cashier_id'])) {
            $clauses[] = "COALESCE({$saleAlias}.cashier_id, {$saleAlias}.user_id) = :cashier_id";
            $params['cashier_id'] = (int) $filters['cashier_id'];
        }

        if (!empty($filters['status'])) {
            $clauses[] = "{$saleAlias}.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['closing_status'])) {
            $clauses[] = "{$saleAlias}.closing_status = :closing_status";
            $params['closing_status'] = $filters['closing_status'];
        }

        if (!empty($filters['product_id']) || !empty($filters['category_id'])) {
            $exists = "SELECT 1 FROM sale_items fsi LEFT JOIN products fp ON fp.id = fsi.product_id WHERE fsi.sale_id = {$saleAlias}.id";

            if (!empty($filters['product_id'])) {
                $exists .= ' AND fsi.product_id = :product_id';
                $params['product_id'] = (int) $filters['product_id'];
            }

            if (!empty($filters['category_id'])) {
                $exists .= ' AND fp.category_id = :category_id';
                $params['category_id'] = (int) $filters['category_id'];
            }

            $clauses[] = "EXISTS ({$exists})";
        }

        return [$clauses ? 'WHERE ' . implode(' AND ', $clauses) : '', $params];
    }

    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $normalized[$productId] = ($normalized[$productId] ?? 0) + $quantity;
        }

        return $normalized;
    }

    private function createReceiptNo(): string
    {
        do {
            $receiptNo = 'POS-' . date('Ymd') . '-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $this->pdo->prepare('SELECT id FROM sales WHERE receipt_no = :receipt_no LIMIT 1');
            $stmt->execute(['receipt_no' => $receiptNo]);
        } while ($stmt->fetch());

        return $receiptNo;
    }
}
