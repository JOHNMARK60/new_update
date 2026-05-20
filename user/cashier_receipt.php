<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

use App\Models\Payment;
use App\Repositories\SaleRepository;
use App\Services\Auth;
use App\Services\Receipt;
use App\Services\ReceiptPrinter;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cashier_sales.php');
    exit();
}

$items = json_decode((string) ($_POST['items_json'] ?? '[]'), true);

if (!is_array($items) || $items === []) {
    $legacyProductId = (int) ($_POST['product_id'] ?? 0);
    $legacyQuantity = max(1, (int) ($_POST['quantity'] ?? 1));
    $items = $legacyProductId > 0 ? [['product_id' => $legacyProductId, 'quantity' => $legacyQuantity]] : [];
}

$tendered = round((float) ($_POST['tendered_amount'] ?? $_POST['cash'] ?? 0), 2);
$discount = round((float) ($_POST['discount'] ?? 0), 2);
$tax = round((float) ($_POST['tax'] ?? 0), 2);
$computedTotal = round((float) ($_POST['computed_total'] ?? $_POST['total_price'] ?? 0), 2);
$paymentMethod = trim((string) ($_POST['payment_method'] ?? 'cash')) ?: 'cash';
$payment = new Payment([
    'amount' => $computedTotal,
    'tendered_amount' => $tendered,
    'payment_method' => $paymentMethod,
]);

try {
    $saleRepository = new SaleRepository($pdo);
    $saleId = $saleRepository->createSale(
        $items,
        Auth::userId(),
        Auth::cashierName(),
        $payment,
        $discount,
        $tax
    );
    $sale = $saleRepository->getSaleWithItems($saleId);

    if (!$sale) {
        throw new RuntimeException('Unable to load the completed sale.');
    }
    swal_flash('success', 'Payment successful. Receipt is ready to print.', 'Sale completed and receipt generated.');
} catch (Throwable $e) {
    swal_flash('error', 'Failed transaction.', $e->getMessage());
    header('Location: cashier_sales.php');
    exit();
}

$receipt = new Receipt($sale);
$printer = new ReceiptPrinter();
$pageTitle = 'Official Receipt';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="receipt-page bg-slate-100 p-4 sm:p-8">
    <div class="no-print fixed right-5 top-5 z-10 flex gap-2">
        <a href="cashier_sales.php" class="btn btn-secondary">
            <i class="fa-solid fa-cash-register"></i>
            New Transaction
        </a>
        <button type="button" id="printReceipt" class="btn">
            <i class="fa-solid fa-print"></i>
            Print
        </button>
    </div>

    <main class="receipt-print-root mx-auto max-w-sm">
        <?php echo $printer->render($receipt); ?>
    </main>
    <script>
        const printButton = document.getElementById('printReceipt');

        function printReceiptPaper() {
            const receipt = document.querySelector('.receipt-paper');

            if (!receipt) {
                window.print();
                return;
            }

            const printFrame = document.createElement('iframe');
            printFrame.title = 'Receipt print preview';
            printFrame.setAttribute('aria-hidden', 'true');
            printFrame.style.position = 'fixed';
            printFrame.style.right = '0';
            printFrame.style.bottom = '0';
            printFrame.style.width = '80mm';
            printFrame.style.height = '100vh';
            printFrame.style.border = '0';
            printFrame.style.opacity = '0';
            printFrame.style.pointerEvents = 'none';
            printFrame.style.zIndex = '-1';
            document.body.appendChild(printFrame);

            const printWindow = printFrame.contentWindow;
            const printDocument = printWindow?.document;

            if (!printWindow || !printDocument) {
                printFrame.remove();
                window.print();
                return;
            }

            printDocument.open();
            printDocument.write(`<!doctype html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <title>Official Receipt</title>
                    <style>
                        @page { margin: 4mm; }
                        * { box-sizing: border-box; }
                        html,
                        body {
                            margin: 0;
                            padding: 0;
                            background: #ffffff;
                            color: #000000;
                            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
                            print-color-adjust: exact;
                            -webkit-print-color-adjust: exact;
                        }
                        body {
                            width: 72mm;
                            margin: 0 auto;
                        }
                        .receipt-paper {
                            width: 72mm;
                            margin: 0;
                            padding: 0;
                            border: 0;
                            box-shadow: none;
                            background: #ffffff;
                            color: #000000;
                            font-size: 11px;
                            line-height: 1.35;
                        }
                        .receipt-header { text-align: center; }
                        .receipt-header h1 {
                            margin: 0;
                            font-size: 16px;
                            font-weight: 800;
                        }
                        .receipt-header p,
                        .receipt-meta p,
                        .receipt-totals p,
                        .receipt-footer {
                            margin: 3px 0;
                            font-size: 11px;
                        }
                        .receipt-meta { margin-top: 12px; }
                        .receipt-meta p,
                        .receipt-totals p {
                            display: flex;
                            justify-content: space-between;
                            gap: 10px;
                        }
                        .receipt-rule {
                            margin: 10px 0;
                            border-top: 1px dashed #000000;
                        }
                        .receipt-table {
                            width: 100%;
                            border-collapse: collapse;
                            table-layout: fixed;
                        }
                        .receipt-table th,
                        .receipt-table td {
                            padding: 4px 2px;
                            border: 0;
                            background: transparent;
                            color: #000000;
                            font: inherit;
                            font-size: 10px;
                            text-transform: none;
                            white-space: normal;
                            word-break: break-word;
                        }
                        .receipt-table th:nth-child(2),
                        .receipt-table td:nth-child(2) {
                            text-align: center;
                            width: 10mm;
                        }
                        .receipt-table th:nth-child(3),
                        .receipt-table th:nth-child(4),
                        .receipt-table td:nth-child(3),
                        .receipt-table td:nth-child(4) {
                            text-align: right;
                            width: 17mm;
                        }
                        .receipt-total {
                            font-size: 13px !important;
                            font-weight: 800;
                        }
                        .receipt-footer {
                            text-align: center;
                            white-space: pre-line;
                        }
                    </style>
                </head>
                <body>${receipt.outerHTML}</body>
                </html>`);
            printDocument.close();

            const cleanup = () => {
                window.setTimeout(() => printFrame.remove(), 500);
            };

            const startPrint = () => {
                printWindow.focus();
                printWindow.addEventListener('afterprint', cleanup, { once: true });
                printWindow.print();
                window.setTimeout(cleanup, 10000);
            };

            window.setTimeout(startPrint, 150);
        }

        printButton?.addEventListener('click', () => {
            window.KantoSwal({
                icon: 'question',
                title: 'Print receipt?',
                text: 'Confirm before printing this receipt.',
                showCancelButton: true,
                confirmButtonText: 'Yes, print',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (!result.isConfirmed) {
                    return;
                }

                window.setTimeout(printReceiptPaper, 100);
            });
        });
    </script>
</body>
</html>
