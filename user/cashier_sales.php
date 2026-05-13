<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

$products = mysqli_query($conn, 'SELECT * FROM products WHERE quantity > 0 ORDER BY name ASC');
$pageTitle = 'RetailFlow POS | Cashier';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="app-shell">
    <?php include __DIR__ . '/user_sidebar.php'; ?>

    <main class="main-content">
        <header class="page-topbar">
            <div>
                <h1 class="page-title">RetailFlow POS</h1>
                <p class="page-subtitle">Fast cashier checkout terminal.</p>
            </div>
            <span class="badge bg-white text-ink shadow-sm">
                <i class="fa-solid fa-user text-brand"></i>
                <?php echo e($_SESSION['first_name']); ?>
            </span>
        </header>

        <div class="rf-mobile-shell">
            <form action="cashier_receipt.php" method="POST" id="saleForm">
                <section class="panel rf-pos-card">
                    <h2 class="text-3xl font-extrabold text-black">Transaction Details</h2>

                    <div class="form-group mt-7">
                        <label for="product">Product Selection</label>
                        <select name="product_id" id="product" required>
                            <option value="">Select a product...</option>
                            <?php while ($row = mysqli_fetch_assoc($products)): ?>
                                <option
                                    value="<?php echo (int) $row['id']; ?>"
                                    data-price="<?php echo e($row['price']); ?>"
                                    data-stock="<?php echo (int) $row['quantity']; ?>">
                                    <?php echo e($row['name']); ?> - <?php echo money($row['price']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mt-6 grid grid-cols-2 gap-6">
                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <div class="grid min-h-[70px] grid-cols-3 overflow-hidden rounded-[14px] border-2 border-[var(--rf-line)] bg-white">
                                <button type="button" id="qtyMinus" class="bg-slate-100 text-3xl font-bold">-</button>
                                <input type="number" name="quantity" id="quantity" value="1" min="1" required class="!min-h-0 !rounded-none !border-0 !p-0 text-center">
                                <button type="button" id="qtyPlus" class="bg-slate-100 text-3xl font-bold">+</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="cash">Cash Tendered</label>
                            <div class="relative">
                                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-2xl text-slate-500">$</span>
                                <input type="number" step="0.01" min="0" name="cash" id="cash" placeholder="0.00" required class="pl-12">
                            </div>
                        </div>
                    </div>
                </section>

                <div class="rf-action-grid">
                    <button type="button" id="addItem" class="rf-action-btn rf-action-primary">
                        <i class="fa-solid fa-list-check"></i>
                        Add Item
                    </button>
                    <button type="button" class="rf-action-btn rf-action-purple">
                        <i class="fa-solid fa-percent"></i>
                        Discount
                    </button>
                    <button type="button" id="clearSale" class="rf-action-btn">
                        <i class="fa-solid fa-trash-list text-red-700"></i>
                        Clear
                    </button>
                    <button type="button" onclick="window.print()" class="rf-action-btn">
                        <i class="fa-solid fa-print"></i>
                        Print
                    </button>
                </div>

                <section class="rf-summary">
                    <div class="flex items-center justify-between text-xl">
                        <span class="text-slate-700">Unit Price</span>
                        <strong id="priceDisplay" class="text-slate-800">$0.00</strong>
                    </div>

                    <div class="my-5 border-t border-slate-300"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-3xl font-extrabold text-black">Total Amount</span>
                        <strong id="totalDisplay" class="text-3xl font-extrabold text-[var(--rf-blue)]">$0.00</strong>
                    </div>

                    <div class="mt-5 flex items-center justify-between text-xl">
                        <span class="text-slate-700">Change Due</span>
                        <strong id="changeDisplay" class="font-mono text-red-700">$0.00</strong>
                    </div>
                </section>

                <input type="hidden" name="total_price" id="totalInput">

                <button type="submit" class="submit-btn rf-complete">
                    <i class="fa-regular fa-circle-check"></i>
                    Complete Transaction
                </button>
            </form>

            <section class="rf-quick-view">
                <h2 class="mb-5 text-lg font-extrabold uppercase tracking-[.12em] text-slate-800">Quick View</h2>
                <div class="rf-quick-art" aria-hidden="true"></div>
            </section>
        </div>
    </main>

    <script>
        const product = document.getElementById('product');
        const quantity = document.getElementById('quantity');
        const cash = document.getElementById('cash');
        const priceDisplay = document.getElementById('priceDisplay');
        const totalDisplay = document.getElementById('totalDisplay');
        const changeDisplay = document.getElementById('changeDisplay');
        const totalInput = document.getElementById('totalInput');
        const qtyMinus = document.getElementById('qtyMinus');
        const qtyPlus = document.getElementById('qtyPlus');
        const clearSale = document.getElementById('clearSale');
        const addItem = document.getElementById('addItem');

        const money = value => '$' + Number(value || 0).toFixed(2);

        function calculateTotal() {
            const selected = product.options[product.selectedIndex];
            const price = parseFloat(selected?.dataset.price || '0');
            const stock = parseInt(selected?.dataset.stock || '0', 10);
            let qty = parseInt(quantity.value || '1', 10);

            if (qty < 1) {
                qty = 1;
                quantity.value = 1;
            }

            if (stock > 0 && qty > stock) {
                qty = stock;
                quantity.value = stock;
            }

            const total = price * qty;
            const change = Math.max((parseFloat(cash.value || '0') - total), 0);

            priceDisplay.textContent = money(price);
            totalDisplay.textContent = money(total);
            changeDisplay.textContent = money(change);
            totalInput.value = total.toFixed(2);
        }

        qtyMinus.addEventListener('click', () => {
            quantity.value = Math.max(1, parseInt(quantity.value || '1', 10) - 1);
            calculateTotal();
        });

        qtyPlus.addEventListener('click', () => {
            quantity.value = parseInt(quantity.value || '1', 10) + 1;
            calculateTotal();
        });

        clearSale.addEventListener('click', () => {
            product.value = '';
            quantity.value = 1;
            cash.value = '';
            calculateTotal();
        });

        addItem.addEventListener('click', calculateTotal);
        product.addEventListener('change', calculateTotal);
        quantity.addEventListener('input', calculateTotal);
        cash.addEventListener('input', calculateTotal);
        calculateTotal();
    </script>
</body>
</html>
