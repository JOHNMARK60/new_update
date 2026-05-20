<?php
require_once __DIR__ . '/../config/auth.php';
require_role('cashier');

use App\Repositories\ProductRepository;
use App\Services\Auth;

$productRepository = new ProductRepository($pdo);
$products = $productRepository->available();
$productPayload = array_map(static function (array $product): array {
    $imagePath = trim((string) ($product['image_path'] ?? ''));
    $category = trim((string) ($product['category_name'] ?? ''));
    $sku = trim((string) ($product['sku'] ?? ''));

    return [
        'id' => (int) $product['id'],
        'name' => $product['name'],
        'price' => (float) $product['price'],
        'quantity' => (int) $product['quantity'],
        'category' => $category !== '' ? $category : 'Uncategorized',
        'sku' => $sku,
        'image_url' => $imagePath !== '' ? app_url($imagePath) : '',
    ];
}, $products);
$categories = array_values(array_unique(array_map(static fn (array $product): string => $product['category'], $productPayload)));
sort($categories, SORT_NATURAL | SORT_FLAG_CASE);
$cashierName = Auth::cashierName();
$pageTitle = 'KANTO GOODS POS | Cashier';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../config/app_head.php'; ?>
</head>
<body class="app-shell pos-page">
    <?php include __DIR__ . '/user_sidebar.php'; ?>

    <main class="main-content pos-main-content">
        <?php
        $appHeaderRole = 'cashier';
        $appHeaderRoleLabel = 'Cashier';
        $appHeaderKicker = 'Cashier workspace';
        $appHeaderTitle = 'Welcome, ' . $cashierName;
        $appHeaderSubtitle = 'Process customer purchases and complete cash payments.';
        $appHeaderIcon = 'fa-cash-register';
        $appHeaderHome = 'user_dashboard.php';
        $appHeaderSearchPlaceholder = 'Search visible products by name, SKU or barcode...';
        $appHeaderActions = [
            ['href' => 'cashier_products.php', 'label' => 'Products', 'icon' => 'fa-box', 'class' => 'btn btn-secondary'],
            ['href' => 'cashier_closing.php', 'label' => 'Daily Closing', 'icon' => 'fa-calendar-check', 'class' => 'btn btn-secondary'],
        ];
        include __DIR__ . '/../config/app_header.php';
        ?>

        <form action="cashier_receipt.php" method="POST" id="saleForm" class="pos-layout">
            <section class="pos-sale-area">
                <section class="panel pos-add-panel">
                    <div class="pos-panel-title">
                        <div>
                            <h2>Products</h2>
                            <p>Click any product card to add it to the current sale.</p>
                        </div>
                        <span class="pos-shortcut">F2</span>
                    </div>

                    <div class="pos-add-grid">
                        <div class="form-group pos-search-group">
                            <label for="productSearch">Product</label>
                            <div class="pos-search-input">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input id="productSearch" type="search" list="productOptions" autocomplete="off" placeholder="Search product by name, SKU or scan barcode...">
                                <input type="hidden" id="product">
                                <datalist id="productOptions"></datalist>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="quantity">Quantity</label>
                            <div class="pos-qty-control pos-qty-control-large">
                                <button type="button" id="quantityMinus" aria-label="Decrease quantity">-</button>
                                <input type="number" id="quantity" value="1" min="1">
                                <button type="button" id="quantityPlus" aria-label="Increase quantity">+</button>
                            </div>
                        </div>

                        <button type="button" id="addItem" class="pos-add-btn">
                            <i class="fa-solid fa-cart-plus"></i>
                            <span>Add Item</span>
                            <kbd>F4</kbd>
                        </button>
                    </div>

                    <div class="pos-filter-row" id="categoryFilters" aria-label="Product categories">
                        <button type="button" class="pos-filter is-active" data-category="">
                            <i class="fa-solid fa-border-all"></i>
                            All Items
                        </button>
                        <?php foreach ($categories as $category): ?>
                            <button type="button" class="pos-filter" data-category="<?php echo e($category); ?>">
                                <i class="fa-solid fa-tag"></i>
                                <?php echo e($category); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>

                    <div class="pos-product-shelf" id="posProductShelf" aria-label="Visible product list"></div>
                </section>

                <section class="panel pos-cart-panel">
                    <div class="pos-panel-title pos-cart-title">
                        <div>
                            <h2>Current Sale <span id="cartCount">(0 items)</span></h2>
                            <p id="cartHelper">No items added yet.</p>
                        </div>
                        <button type="button" id="clearSaleTop" class="pos-clear-link">
                            <i class="fa-regular fa-trash-can"></i>
                            Clear All
                        </button>
                    </div>

                    <div id="posMessage" class="pos-message hidden"></div>

                    <div class="pos-cart-table-wrap">
                        <table class="pos-cart-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th>Unit Price</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="cartBody">
                                <tr>
                                    <td colspan="6" class="pos-empty-cart">No items added.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pos-cart-footer">
                        <span>Items: <strong id="cartItemsTotal">0</strong></span>
                        <span>Subtotal: <strong id="cartSubtotalPill">&#8369;0.00</strong></span>
                    </div>
                </section>
            </section>

            <aside class="panel pos-payment-card">
                <div class="pos-payment-head">
                    <div>
                        <h2>Payment Summary</h2>
                        <p>Review totals before completing the transaction.</p>
                    </div>
                    <i class="fa-regular fa-receipt"></i>
                </div>

                <div class="pos-summary-lines">
                    <p><span>Subtotal</span><strong id="subtotalDisplay">&#8369;0.00</strong></p>
                    <label class="pos-money-field" for="discount">
                        <span><i class="fa-solid fa-tags"></i> Discount</span>
                        <input type="number" step="0.01" min="0" name="discount" id="discount" value="0.00">
                    </label>
                    <label class="pos-money-field" for="tax">
                        <span><i class="fa-solid fa-percent"></i> Tax</span>
                        <input type="number" step="0.01" min="0" name="tax" id="tax" value="0.00">
                    </label>
                    <p class="pos-total-line"><span>Total</span><strong id="totalDisplay">&#8369;0.00</strong></p>
                </div>

                <div class="pos-payment-fields">
                    <div class="form-group">
                        <label for="tendered_amount">Tendered Amount</label>
                        <input type="number" step="0.01" min="0" name="tendered_amount" id="tendered_amount" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <select name="payment_method" id="payment_method">
                            <option value="cash">Cash</option>
                        </select>
                    </div>
                </div>

                <div class="pos-change-box">
                    <span>Change</span>
                    <strong id="changeDisplay">&#8369;0.00</strong>
                </div>

                <input type="hidden" name="items_json" id="itemsJson">
                <input type="hidden" name="computed_total" id="computedTotal">

                <div class="pos-payment-actions">
                    <button type="submit" class="pos-complete-btn">
                        <i class="fa-regular fa-circle-check"></i>
                        Complete Transaction
                        <kbd>F5</kbd>
                    </button>
                    <button type="button" id="clearSale" class="pos-clear-btn">
                        <i class="fa-regular fa-trash-can"></i>
                        Clear Sale
                        <kbd>F7</kbd>
                    </button>
                </div>
            </aside>
        </form>

        <footer class="pos-terminal-footer">
            <span><i class="fa-solid fa-circle"></i> Terminal 01</span>
            <span><i class="fa-regular fa-calendar"></i> <strong id="posDate"><?php echo date('M d, Y'); ?></strong></span>
            <span><i class="fa-regular fa-clock"></i> <strong id="posTime"><?php echo date('h:i A'); ?></strong></span>
            <span class="pos-online"><i class="fa-solid fa-cloud"></i> Online</span>
            <span>KANTO GOODS</span>
        </footer>
    </main>

    <script>
        const products = <?php echo json_encode($productPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        const productMap = new Map(products.map(product => [String(product.id), product]));
        const cart = new Map();

        const headerSearch = document.querySelector('.app-header-search input');
        const productSearch = document.getElementById('productSearch');
        const productOptions = document.getElementById('productOptions');
        const product = document.getElementById('product');
        const quantity = document.getElementById('quantity');
        const quantityMinus = document.getElementById('quantityMinus');
        const quantityPlus = document.getElementById('quantityPlus');
        const addItem = document.getElementById('addItem');
        const clearSale = document.getElementById('clearSale');
        const clearSaleTop = document.getElementById('clearSaleTop');
        const categoryFilters = document.getElementById('categoryFilters');
        const posProductShelf = document.getElementById('posProductShelf');
        const cartBody = document.getElementById('cartBody');
        const cartCount = document.getElementById('cartCount');
        const cartHelper = document.getElementById('cartHelper');
        const cartItemsTotal = document.getElementById('cartItemsTotal');
        const cartSubtotalPill = document.getElementById('cartSubtotalPill');
        const message = document.getElementById('posMessage');
        const discount = document.getElementById('discount');
        const tax = document.getElementById('tax');
        const tendered = document.getElementById('tendered_amount');
        const itemsJson = document.getElementById('itemsJson');
        const computedTotal = document.getElementById('computedTotal');
        const saleForm = document.getElementById('saleForm');
        const posDate = document.getElementById('posDate');
        const posTime = document.getElementById('posTime');

        const subtotalDisplay = document.getElementById('subtotalDisplay');
        const totalDisplay = document.getElementById('totalDisplay');
        const changeDisplay = document.getElementById('changeDisplay');

        let activeCategory = '';

        const peso = value => '\u20B1' + Number(value || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        const normalize = value => String(value || '').trim().toLowerCase();
        const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
        const escapeAttr = value => escapeHtml(value).replace(/`/g, '&#096;');
        const productLabel = item => `${item.name}${item.sku ? ` (${item.sku})` : ''}`;

        function showMessage(text, type = 'error') {
            message.textContent = text;
            message.className = 'pos-message ' + (type === 'error' ? 'is-error' : 'is-success');

            if (window.KantoSwal) {
                window.KantoSwal({
                    icon: type === 'error' ? 'warning' : 'success',
                    title: type === 'error' ? 'POS Notice' : 'POS Updated',
                    text
                });
            }
        }

        function hideMessage() {
            message.className = 'pos-message hidden';
        }

        function confirmAction(options) {
            if (window.KantoSwal) {
                return window.KantoSwal(options);
            }

            return Promise.resolve({ isConfirmed: window.confirm(options.text || options.title || 'Continue?') });
        }

        function currentProductQuery() {
            return normalize(headerSearch?.value || productSearch.value);
        }

        function filteredProducts() {
            const query = currentProductQuery();

            return products.filter(item => {
                const matchesCategory = activeCategory === '' || item.category === activeCategory;
                const matchesSearch = query === ''
                    || normalize(item.name).includes(query)
                    || normalize(item.sku).includes(query)
                    || normalize(item.category).includes(query)
                    || String(item.id) === query;

                return matchesCategory && matchesSearch;
            });
        }

        function renderProductOptions() {
            productOptions.innerHTML = products.map(item => (
                `<option value="${escapeAttr(productLabel(item))}" data-id="${item.id}"></option>`
            )).join('');
        }

        function renderProductShelf() {
            const items = filteredProducts();

            if (items.length === 0) {
                posProductShelf.innerHTML = `
                    <div class="pos-product-empty">
                        <i class="fa-solid fa-box-open"></i>
                        <span>No products match the current search or category.</span>
                    </div>
                `;
                return;
            }

            posProductShelf.innerHTML = items.map(item => `
                <button type="button" class="pos-product-tile" data-product-card="${item.id}" ${item.quantity <= 0 ? 'disabled' : ''}>
                    <span class="pos-product-tile-image">${productThumb(item)}</span>
                    <span class="pos-product-tile-body">
                        <strong>${escapeHtml(item.name)}</strong>
                        <small>${escapeHtml(item.category)}${item.sku ? ` - ${escapeHtml(item.sku)}` : ''}</small>
                        <span>
                            <b>${peso(item.price)}</b>
                            <em>${item.quantity} left</em>
                        </span>
                    </span>
                    <i class="fa-solid fa-plus"></i>
                </button>
            `).join('');
        }

        function findProductFromSearch() {
            if (product.value && productMap.has(product.value)) {
                return productMap.get(product.value);
            }

            const query = normalize(productSearch.value);

            if (!query) {
                return null;
            }

            return products.find(item => [
                productLabel(item),
                item.name,
                item.sku,
                item.id
            ].some(candidate => normalize(candidate) === query)) || products.find(item => (
                normalize(item.name).includes(query) ||
                normalize(item.sku).includes(query) ||
                String(item.id) === query
            )) || null;
        }

        function setSelectedProduct(item) {
            product.value = item ? String(item.id) : '';
            if (item) {
                productSearch.value = productLabel(item);
            }
        }

        function selectedQuantity() {
            return Math.max(parseInt(quantity.value || '1', 10), 1);
        }

        function setQuantity(value) {
            quantity.value = Math.max(parseInt(value || '1', 10), 1);
        }

        function cartArray() {
            return Array.from(cart.values()).map(item => ({
                product_id: item.id,
                quantity: item.quantity
            }));
        }

        function calculate() {
            const items = Array.from(cart.values());
            const subtotal = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const discountAmount = Math.max(Number(discount.value || 0), 0);
            const taxAmount = Math.max(Number(tax.value || 0), 0);
            const total = Math.max(subtotal - discountAmount + taxAmount, 0);
            const change = Math.max(Number(tendered.value || 0) - total, 0);
            const itemQty = items.reduce((sum, item) => sum + item.quantity, 0);

            subtotalDisplay.textContent = peso(subtotal);
            totalDisplay.textContent = peso(total);
            changeDisplay.textContent = peso(change);
            cartSubtotalPill.textContent = peso(subtotal);
            cartItemsTotal.textContent = itemQty;
            cartCount.textContent = `(${itemQty} ${itemQty === 1 ? 'item' : 'items'})`;
            cartHelper.textContent = itemQty === 0 ? 'No items added yet.' : 'Review quantities and totals before payment.';
            itemsJson.value = JSON.stringify(cartArray());
            computedTotal.value = total.toFixed(2);

            return { subtotal, discountAmount, taxAmount, total, change };
        }

        function productThumb(item) {
            if (item.image_url) {
                return `<img src="${escapeAttr(item.image_url)}" alt="${escapeAttr(item.name)}">`;
            }

            return '<span class="pos-thumb-placeholder"><i class="fa-solid fa-box"></i></span>';
        }

        function renderCart() {
            const items = Array.from(cart.values());

            if (items.length === 0) {
                cartBody.innerHTML = '<tr><td colspan="6" class="pos-empty-cart">No items added.</td></tr>';
                calculate();
                return;
            }

            cartBody.innerHTML = items.map((item, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <div class="pos-item-cell">
                            <span class="pos-item-thumb">${productThumb(item)}</span>
                            <span>
                                <strong>${escapeHtml(item.name)}</strong>
                                <small>${item.sku ? escapeHtml(item.sku) : 'SKU not set'}</small>
                                <em>${escapeHtml(item.category)}</em>
                            </span>
                        </div>
                    </td>
                    <td>${peso(item.price)}</td>
                    <td>
                        <div class="pos-qty-control">
                            <button type="button" data-step="-1" data-id="${item.id}" aria-label="Decrease ${escapeAttr(item.name)} quantity">-</button>
                            <input type="number" value="${item.quantity}" min="1" data-qty-input="${item.id}" aria-label="${escapeAttr(item.name)} quantity">
                            <button type="button" data-step="1" data-id="${item.id}" aria-label="Increase ${escapeAttr(item.name)} quantity">+</button>
                        </div>
                    </td>
                    <td><strong>${peso(item.price * item.quantity)}</strong></td>
                    <td>
                        <div class="pos-row-actions">
                            <button type="button" class="edit-btn" data-edit="${item.id}" title="Edit item">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button type="button" class="delete-btn" data-remove="${item.id}" title="Remove item">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            calculate();
        }

        function addProductToCart(selected, qty = 1) {
            hideMessage();

            if (!selected) {
                showMessage('Select a product first.');
                productSearch.focus();
                return false;
            }

            const existingQty = cart.get(selected.id)?.quantity || 0;

            if (existingQty + qty > selected.quantity) {
                showMessage(`${selected.name} has only ${selected.quantity} item(s) available.`);
                return false;
            }

            cart.set(selected.id, {
                id: selected.id,
                name: selected.name,
                price: Number(selected.price),
                stock: Number(selected.quantity),
                quantity: existingQty + qty,
                category: selected.category,
                sku: selected.sku,
                image_url: selected.image_url
            });

            renderCart();
            window.KantoToast?.('success', `${selected.name} added to cart.`);
            return true;
        }

        function addSelectedItem() {
            const selected = findProductFromSearch();
            const qty = selectedQuantity();

            if (!addProductToCart(selected, qty)) {
                return;
            }

            product.value = '';
            productSearch.value = '';
            setQuantity(1);
        }

        function clearCurrentSale() {
            confirmAction({
                icon: 'question',
                title: 'Clear sale?',
                text: 'All items in the current transaction will be removed.',
                showCancelButton: true,
                confirmButtonText: 'Yes, clear sale',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (!result.isConfirmed) {
                    return;
                }

                cart.clear();
                discount.value = '0.00';
                tax.value = '0.00';
                tendered.value = '';
                product.value = '';
                productSearch.value = '';
                setQuantity(1);
                hideMessage();
                renderCart();
                window.KantoToast?.('info', 'Sale cleared.');
            });
        }

        productSearch.addEventListener('input', () => {
            const selected = findProductFromSearch();
            product.value = selected ? String(selected.id) : '';
            renderProductShelf();
        });

        productSearch.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                addSelectedItem();
            }
        });

        quantityMinus.addEventListener('click', () => setQuantity(selectedQuantity() - 1));
        quantityPlus.addEventListener('click', () => setQuantity(selectedQuantity() + 1));
        addItem.addEventListener('click', addSelectedItem);
        clearSale.addEventListener('click', clearCurrentSale);
        clearSaleTop.addEventListener('click', clearCurrentSale);

        categoryFilters.addEventListener('click', event => {
            const button = event.target.closest('[data-category]');

            if (!button) {
                return;
            }

            activeCategory = button.dataset.category || '';
            categoryFilters.querySelectorAll('[data-category]').forEach(item => {
                item.classList.toggle('is-active', item === button);
            });
            product.value = '';
            productSearch.value = '';
            renderProductOptions();
            renderProductShelf();
        });

        if (headerSearch) {
            headerSearch.addEventListener('input', renderProductShelf);
            headerSearch.addEventListener('keydown', event => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    const firstMatch = filteredProducts()[0] || null;
                    addProductToCart(firstMatch, 1);
                }
            });
        }

        posProductShelf.addEventListener('click', event => {
            const button = event.target.closest('[data-product-card]');

            if (!button) {
                return;
            }

            const selected = productMap.get(button.dataset.productCard);
            addProductToCart(selected, 1);
        });

        cartBody.addEventListener('click', event => {
            const removeButton = event.target.closest('[data-remove]');
            const stepButton = event.target.closest('[data-step]');
            const editButton = event.target.closest('[data-edit]');

            if (removeButton) {
                cart.delete(Number(removeButton.dataset.remove));
                renderCart();
                window.KantoToast?.('info', 'Item removed from cart.');
                return;
            }

            if (stepButton) {
                const id = Number(stepButton.dataset.id);
                const item = cart.get(id);
                const nextQty = (item?.quantity || 0) + Number(stepButton.dataset.step);

                if (!item) {
                    return;
                }

                if (nextQty <= 0) {
                    cart.delete(id);
                } else if (nextQty > item.stock) {
                    showMessage(`${item.name} has only ${item.stock} item(s) available.`);
                } else {
                    item.quantity = nextQty;
                }

                renderCart();
                return;
            }

            if (editButton) {
                const item = cart.get(Number(editButton.dataset.edit));

                if (!item) {
                    return;
                }

                setSelectedProduct(item);
                setQuantity(item.quantity);
                productSearch.focus();
                showMessage('Item loaded. Use the quantity controls to adjust the sale.', 'success');
            }
        });

        cartBody.addEventListener('input', event => {
            const input = event.target.closest('[data-qty-input]');

            if (!input) {
                return;
            }

            const id = Number(input.dataset.qtyInput);
            const item = cart.get(id);
            const nextQty = Math.max(parseInt(input.value || '1', 10), 1);

            if (!item) {
                return;
            }

            if (nextQty > item.stock) {
                input.value = item.stock;
                item.quantity = item.stock;
                showMessage(`${item.name} has only ${item.stock} item(s) available.`);
            } else {
                item.quantity = nextQty;
            }

            calculate();
        });

        [discount, tax, tendered].forEach(input => input.addEventListener('input', calculate));

        saleForm.addEventListener('submit', event => {
            const totals = calculate();

            if (cart.size === 0) {
                event.preventDefault();
                showMessage('Add at least one item before completing the sale.');
                return;
            }

            if (Number(tendered.value || 0) < totals.total) {
                event.preventDefault();
                showMessage('Tendered amount cannot be less than the total amount.');
            }
        });

        function updateClock() {
            const now = new Date();
            posDate.textContent = now.toLocaleDateString('en-US', {
                month: 'short',
                day: '2-digit',
                year: 'numeric'
            });
            posTime.textContent = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        document.addEventListener('keydown', event => {
            if (event.key === 'F2') {
                event.preventDefault();
                productSearch.focus();
            }

            if (event.key === 'F4') {
                event.preventDefault();
                addSelectedItem();
            }

            if (event.key === 'F7') {
                event.preventDefault();
                clearCurrentSale();
            }
        });

        renderProductOptions();
        renderProductShelf();
        renderCart();
        updateClock();
        window.setInterval(updateClock, 30000);
    </script>
</body>
</html>
