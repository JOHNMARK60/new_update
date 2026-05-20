<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\PrintableReceiptInterface;
use App\Support\Money;

class Receipt implements PrintableReceiptInterface
{
    private array $sale;
    private array $store;

    public function __construct(array $sale, array $store = [])
    {
        $this->sale = $sale;
        $this->store = array_merge([
            'name' => 'KANTO GOODS',
            'address' => '123 Main Street, Cebu City',
            'contact' => '0912-345-6789',
            'footer' => 'Thank you for shopping! Come again.',
        ], $store);
    }

    public function toArray(): array
    {
        return [
            'store' => $this->store,
            'receipt_no' => $this->sale['receipt_no'] ?? str_pad((string) ($this->sale['id'] ?? 0), 6, '0', STR_PAD_LEFT),
            'sale_date' => $this->sale['sale_date'] ?? date('Y-m-d H:i:s'),
            'cashier_name' => $this->sale['cashier_name'] ?? 'Cashier',
            'items' => $this->sale['items'] ?? [],
            'subtotal' => (float) ($this->sale['subtotal_amount'] ?? $this->sale['total_amount'] ?? 0),
            'discount' => (float) ($this->sale['discount'] ?? 0),
            'tax' => (float) ($this->sale['tax'] ?? 0),
            'total' => (float) ($this->sale['total_amount'] ?? 0),
            'tendered' => (float) ($this->sale['tendered_amount'] ?? 0),
            'change' => (float) ($this->sale['change_amount'] ?? 0),
            'payment_method' => $this->sale['payment_method'] ?? 'cash',
        ];
    }

    public function renderHtml(): string
    {
        $receipt = $this->toArray();
        ob_start();
        ?>
        <section class="receipt-paper">
            <header class="receipt-header">
                <h1><?php echo htmlspecialchars($receipt['store']['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?php echo htmlspecialchars($receipt['store']['address'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p>Contact No: <?php echo htmlspecialchars($receipt['store']['contact'], ENT_QUOTES, 'UTF-8'); ?></p>
            </header>

            <div class="receipt-meta">
                <p><span>Receipt No:</span><strong><?php echo htmlspecialchars((string) $receipt['receipt_no'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                <p><span>Date:</span><strong><?php echo date('m/d/Y', strtotime((string) $receipt['sale_date'])); ?></strong></p>
                <p><span>Time:</span><strong><?php echo date('h:i A', strtotime((string) $receipt['sale_date'])); ?></strong></p>
                <p><span>Cashier:</span><strong><?php echo htmlspecialchars((string) $receipt['cashier_name'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
            </div>

            <div class="receipt-rule"></div>
            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>ITEM</th>
                        <th>QTY</th>
                        <th>PRICE</th>
                        <th>TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($receipt['items'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) $item['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int) $item['quantity']; ?></td>
                            <td><?php echo Money::format($item['unit_price']); ?></td>
                            <td><?php echo Money::format($item['total_price']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="receipt-rule"></div>

            <div class="receipt-totals">
                <p><span>Subtotal:</span><strong><?php echo Money::format($receipt['subtotal']); ?></strong></p>
                <p><span>Discount:</span><strong><?php echo Money::format($receipt['discount']); ?></strong></p>
                <p><span>Tax:</span><strong><?php echo Money::format($receipt['tax']); ?></strong></p>
                <p class="receipt-total"><span>Total:</span><strong><?php echo Money::format($receipt['total']); ?></strong></p>
                <p><span>Tendered:</span><strong><?php echo Money::format($receipt['tendered']); ?></strong></p>
                <p><span>Change:</span><strong><?php echo Money::format($receipt['change']); ?></strong></p>
            </div>

            <div class="receipt-rule"></div>
            <footer class="receipt-footer">
                <?php echo htmlspecialchars((string) $receipt['store']['footer'], ENT_QUOTES, 'UTF-8'); ?>
            </footer>
        </section>
        <?php

        return (string) ob_get_clean();
    }
}

