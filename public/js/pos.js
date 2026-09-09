(() => {
    const form = document.getElementById('checkout-form');
    if (!form) return;

    const money = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
    const search = document.getElementById('product-search');
    const productButtons = [...document.querySelectorAll('.pos-product')];
    const productById = new Map(productButtons.map((button) => [String(button.dataset.id), button]));
    const cart = new Map();
    let activeCategory = '';

    const elements = {
        lines: document.getElementById('cart-lines'),
        count: document.getElementById('cart-count'),
        visible: document.getElementById('visible-count'),
        subtotal: document.getElementById('subtotal-display'),
        total: document.getElementById('total-display'),
        change: document.getElementById('change-display'),
        discountType: document.getElementById('discount-type'),
        discountValue: document.getElementById('discount-value'),
        discountReason: document.getElementById('discount-reason'),
        tax: document.getElementById('tax'),
        tendered: document.getElementById('tendered'),
        paymentMethod: document.getElementById('payment-method'),
        cashPayment: document.getElementById('cash-payment'),
        digitalPayment: document.getElementById('digital-payment'),
        customer: document.getElementById('customer-name'),
        heldSaleId: document.getElementById('held-sale-id'),
        submit: document.getElementById('complete-sale'),
        network: document.getElementById('network-status'),
    };

    const number = (value) => Math.max(0, Number.parseFloat(value) || 0);
    const totals = () => {
        const subtotal = [...cart.values()].reduce((sum, item) => sum + item.price * item.quantity, 0);
        const enteredDiscount = number(elements.discountValue.value);
        const discount = elements.discountType.value === 'percent'
            ? subtotal * Math.min(enteredDiscount, 100) / 100
            : Math.min(enteredDiscount, subtotal);
        const total = Math.max(0, subtotal - discount + number(elements.tax.value));
        const change = elements.paymentMethod.value === 'cash'
            ? Math.max(0, number(elements.tendered.value) - total)
            : 0;
        return { subtotal, discount, total, change };
    };

    const persist = () => {
        try {
            localStorage.setItem(form.dataset.storageKey, JSON.stringify({
                cart: [...cart.values()],
                customer: elements.customer.value,
                discountType: elements.discountType.value,
                discountValue: elements.discountValue.value,
                discountReason: elements.discountReason.value,
                tax: elements.tax.value,
                paymentMethod: elements.paymentMethod.value,
            }));
        } catch (_) { /* Browsing may have storage disabled. */ }
    };

    const canCheckout = (total) => {
        if (!navigator.onLine || cart.size === 0) return false;
        if (elements.paymentMethod.value !== 'cash') return true;
        return number(elements.tendered.value) + 0.0001 >= total;
    };

    const render = () => {
        elements.lines.replaceChildren();
        let itemCount = 0;

        if (!cart.size) {
            const empty = document.createElement('div');
            empty.className = 'empty-cart';
            const title = document.createElement('b');
            title.textContent = 'Your cart is empty';
            const hint = document.createElement('span');
            hint.textContent = 'Scan or select a product to start.';
            empty.append(title, hint);
            elements.lines.append(empty);
        }

        [...cart.values()].forEach((item, index) => {
            itemCount += item.quantity;
            const line = document.createElement('article');
            line.className = 'cart-line';
            line.tabIndex = 0;
            line.dataset.cartId = item.id;
            line.setAttribute('aria-label', `${item.name}, quantity ${item.quantity}. Use plus, minus, or Delete to edit.`);

            const copy = document.createElement('div');
            copy.className = 'cart-line-copy';
            const title = document.createElement('strong');
            title.textContent = item.name;
            const detail = document.createElement('span');
            detail.textContent = `${money.format(item.price)} each`;
            copy.append(title, detail);

            const controls = document.createElement('div');
            controls.className = 'quantity-controls';
            const minus = document.createElement('button');
            minus.type = 'button';
            minus.dataset.action = 'minus';
            minus.dataset.id = item.id;
            minus.setAttribute('aria-label', `Remove one ${item.name}`);
            minus.textContent = '\u2212';
            const quantity = document.createElement('span');
            quantity.textContent = item.quantity;
            const plus = document.createElement('button');
            plus.type = 'button';
            plus.dataset.action = 'plus';
            plus.dataset.id = item.id;
            plus.disabled = item.quantity >= item.stock;
            plus.setAttribute('aria-label', `Add one ${item.name}`);
            plus.textContent = '+';
            controls.append(minus, quantity, plus);

            const lineTotal = document.createElement('b');
            lineTotal.className = 'line-total';
            lineTotal.textContent = money.format(item.price * item.quantity);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'remove-line';
            remove.dataset.action = 'remove';
            remove.dataset.id = item.id;
            remove.setAttribute('aria-label', `Remove ${item.name}`);
            remove.textContent = '\u00d7';

            const id = document.createElement('input');
            id.type = 'hidden';
            id.name = `items[${index}][product_id]`;
            id.value = item.id;
            const qty = document.createElement('input');
            qty.type = 'hidden';
            qty.name = `items[${index}][quantity]`;
            qty.value = item.quantity;
            line.append(copy, controls, lineTotal, remove, id, qty);
            elements.lines.append(line);
        });

        const values = totals();
        elements.count.textContent = `${itemCount} item${itemCount === 1 ? '' : 's'}`;
        elements.subtotal.textContent = money.format(values.subtotal);
        elements.total.textContent = money.format(values.total);
        elements.change.textContent = money.format(values.change);
        elements.submit.disabled = !canCheckout(values.total);
        persist();
    };

    const beep = () => {
        try {
            const Context = window.AudioContext || window.webkitAudioContext;
            if (!Context) return;
            const context = new Context();
            const oscillator = context.createOscillator();
            const gain = context.createGain();
            oscillator.frequency.value = 880;
            gain.gain.setValueAtTime(0.04, context.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, context.currentTime + 0.08);
            oscillator.connect(gain).connect(context.destination);
            oscillator.start();
            oscillator.stop(context.currentTime + 0.08);
        } catch (_) { /* Audio feedback is optional. */ }
    };

    const addProduct = (button, quantity = 1) => {
        const id = String(button.dataset.id);
        const stock = Number.parseInt(button.dataset.stock, 10);
        const existing = cart.get(id);
        const requested = (existing?.quantity || 0) + quantity;
        if (requested > stock) return;
        cart.set(id, {
            id,
            name: button.dataset.name,
            price: number(button.dataset.price),
            stock,
            quantity: requested,
        });
        beep();
        render();
    };

    const setPayment = (method) => {
        elements.paymentMethod.value = method;
        document.querySelectorAll('[data-payment]').forEach((button) => button.classList.toggle('active', button.dataset.payment === method));
        const isCash = method === 'cash';
        elements.cashPayment.hidden = !isCash;
        elements.digitalPayment.hidden = isCash;
        elements.tendered.required = isCash;
        render();
    };

    const filterProducts = () => {
        const query = search.value.trim().toLowerCase();
        let visible = 0;
        productButtons.forEach((button) => {
            const matchesCategory = !activeCategory || button.dataset.category === activeCategory;
            const matchesSearch = !query || button.dataset.search.includes(query);
            button.hidden = !(matchesCategory && matchesSearch);
            if (!button.hidden) visible++;
        });
        elements.visible.textContent = `${visible} available`;
    };

    const visibleProducts = () => productButtons.filter((button) => !button.hidden);
    const focusProduct = (button) => {
        if (!button) return;
        productButtons.forEach((product) => product.classList.toggle('keyboard-focus', product === button));
        button.focus({ preventScroll: true });
        button.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    };

    const moveProductFocus = (current, key) => {
        const visible = visibleProducts();
        if (!visible.length) return;
        const index = Math.max(0, visible.indexOf(current));
        const firstTop = visible[0].offsetTop;
        const columns = Math.max(1, visible.filter((button) => button.offsetTop === firstTop).length);
        const next = key === 'ArrowLeft' ? index - 1
            : key === 'ArrowRight' ? index + 1
                : key === 'ArrowUp' ? index - columns
                    : key === 'ArrowDown' ? index + columns
                        : key === 'Home' ? 0 : visible.length - 1;
        focusProduct(visible[Math.max(0, Math.min(visible.length - 1, next))]);
    };

    const restore = () => {
        try {
            const saved = JSON.parse(localStorage.getItem(form.dataset.storageKey));
            if (!saved) return;
            (saved.cart || []).forEach((savedItem) => {
                const button = productById.get(String(savedItem.id));
                if (!button) return;
                const stock = Number.parseInt(button.dataset.stock, 10);
                const quantity = Math.min(Math.max(1, Number.parseInt(savedItem.quantity, 10) || 1), stock);
                if (quantity > 0) cart.set(String(savedItem.id), {
                    id: String(savedItem.id), name: button.dataset.name,
                    price: number(button.dataset.price), stock, quantity,
                });
            });
            elements.customer.value = saved.customer || '';
            elements.discountType.value = saved.discountType === 'percent' ? 'percent' : 'fixed';
            elements.discountValue.value = saved.discountValue || 0;
            elements.discountReason.value = saved.discountReason || '';
            elements.tax.value = saved.tax || 0;
            setPayment(['cash', 'card', 'gcash'].includes(saved.paymentMethod) ? saved.paymentMethod : 'cash');
        } catch (_) { localStorage.removeItem(form.dataset.storageKey); }
    };

    const clearCart = () => {
        cart.clear();
        elements.heldSaleId.value = '';
        elements.customer.value = '';
        elements.discountValue.value = 0;
        elements.discountReason.value = '';
        elements.tax.value = 0;
        elements.tendered.value = '';
        render();
    };

    const updateNetwork = () => {
        const online = navigator.onLine;
        elements.network.textContent = online ? 'Online' : 'Offline \u2014 checkout paused';
        elements.network.classList.toggle('online', online);
        elements.network.classList.toggle('offline', !online);
        render();
    };

    productButtons.forEach((button) => {
        button.addEventListener('click', () => addProduct(button));
        button.addEventListener('focus', () => productButtons.forEach((product) => product.classList.toggle('keyboard-focus', product === button)));
        button.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            moveProductFocus(button, event.key);
        });
    });
    document.querySelectorAll('.category-button').forEach((button) => button.addEventListener('click', () => {
        activeCategory = button.dataset.category;
        document.querySelectorAll('.category-button').forEach((item) => item.classList.toggle('active', item === button));
        filterProducts();
    }));
    search.addEventListener('input', filterProducts);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            focusProduct(visibleProducts()[0]);
            return;
        }
        if (event.key !== 'Enter') return;
        event.preventDefault();
        const query = search.value.trim().toLowerCase();
        const exact = productButtons.find((button) => button.dataset.sku && button.dataset.sku === query);
        const firstVisible = productButtons.find((button) => !button.hidden);
        if (query && (exact || firstVisible)) {
            addProduct(exact || firstVisible);
            search.value = '';
            filterProducts();
        } else if (firstVisible) {
            focusProduct(firstVisible);
        }
    });

    const changeCart = (id, action, refocus = false) => {
        const item = cart.get(id);
        if (!item) return;
        if (action === 'plus' && item.quantity < item.stock) item.quantity++;
        if (action === 'minus') item.quantity--;
        if (action === 'remove' || item.quantity <= 0) cart.delete(id);
        render();
        if (refocus && cart.has(id)) elements.lines.querySelector(`[data-cart-id="${CSS.escape(id)}"]`)?.focus();
    };

    elements.lines.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) return;
        changeCart(button.dataset.id, button.dataset.action);
    });
    elements.lines.addEventListener('keydown', (event) => {
        const line = event.target.closest('.cart-line');
        if (!line || ['INPUT', 'SELECT'].includes(event.target.tagName)) return;
        const action = event.key === '+' || event.key === '=' ? 'plus'
            : event.key === '-' || event.key === '_' ? 'minus'
                : event.key === 'Delete' || event.key === 'Backspace' ? 'remove' : null;
        if (!action) return;
        event.preventDefault();
        changeCart(line.dataset.cartId, action, true);
    });

    [elements.discountType, elements.discountValue, elements.discountReason, elements.tax, elements.tendered, elements.customer]
        .forEach((field) => field.addEventListener('input', render));
    document.querySelectorAll('[data-payment]').forEach((button) => button.addEventListener('click', () => setPayment(button.dataset.payment)));
    document.querySelectorAll('[data-cash]').forEach((button) => button.addEventListener('click', () => {
        const total = totals().total;
        elements.tendered.value = button.dataset.cash === 'exact' ? total.toFixed(2) : button.dataset.cash;
        render();
    }));
    document.getElementById('clear-cart').addEventListener('click', clearCart);

    const closePanels = () => {
        document.querySelectorAll('.pos-side-panel').forEach((panel) => { panel.hidden = true; });
        document.getElementById('panel-backdrop').hidden = true;
    };
    const openPanel = (id) => {
        closePanels();
        const panel = document.getElementById(id);
        panel.hidden = false;
        document.getElementById('panel-backdrop').hidden = false;
        panel.querySelector('[data-close-panel], button, a')?.focus();
    };
    document.querySelectorAll('[data-toggle-panel]').forEach((button) => button.addEventListener('click', () => openPanel(button.dataset.togglePanel)));
    document.querySelectorAll('[data-close-panel]').forEach((button) => button.addEventListener('click', closePanels));
    document.getElementById('panel-backdrop').addEventListener('click', closePanels);

    const csrf = form.querySelector('input[name="_token"]').value;
    document.getElementById('hold-sale').addEventListener('click', async () => {
        if (!cart.size) return;
        const response = await fetch(form.dataset.holdUrl, {
            method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({
                cart: [...cart.values()].map((item) => ({ product_id: Number(item.id), quantity: item.quantity })),
                customer_name: elements.customer.value || null,
                discount_type: elements.discountType.value,
                discount_value: number(elements.discountValue.value),
                discount_reason: elements.discountReason.value || null,
                tax: number(elements.tax.value),
                payment_method: elements.paymentMethod.value,
            }),
        });
        if (response.ok) { localStorage.removeItem(form.dataset.storageKey); window.location.reload(); }
        else alert('The sale could not be held. Please check the order and try again.');
    });

    const heldData = JSON.parse(document.getElementById('held-sales-data').textContent || '{}');
    document.querySelectorAll('.recall-hold').forEach((button) => button.addEventListener('click', () => {
        const held = heldData[button.dataset.id];
        if (!held) return;
        cart.clear();
        (held.cart || []).forEach((line) => {
            const product = productById.get(String(line.product_id));
            if (product) addProduct(product, Math.min(Number(line.quantity) || 1, Number(product.dataset.stock)));
        });
        elements.heldSaleId.value = held.id;
        elements.customer.value = held.customer_name || '';
        elements.discountType.value = held.discount_type || 'fixed';
        elements.discountValue.value = held.discount_value || 0;
        elements.discountReason.value = held.discount_reason || '';
        elements.tax.value = held.tax || 0;
        setPayment(held.payment_method || 'cash');
        closePanels();
        render();
    }));
    document.querySelectorAll('.delete-hold').forEach((button) => button.addEventListener('click', async () => {
        if (!confirm('Delete this held sale?')) return;
        const url = form.dataset.holdDelete.replace('__ID__', button.dataset.id);
        const response = await fetch(url, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } });
        if (response.ok) window.location.reload();
        else alert('The held sale could not be deleted.');
    }));

    form.addEventListener('submit', (event) => {
        const values = totals();
        if (!canCheckout(values.total)) { event.preventDefault(); return; }
        elements.submit.disabled = true;
        elements.submit.textContent = 'Processing sale\u2026';
    });
    document.addEventListener('keydown', (event) => {
        const typing = ['INPUT', 'SELECT', 'TEXTAREA'].includes(event.target.tagName);
        if (event.key === 'F1') {
            event.preventDefault();
            const panel = document.getElementById('keyboard-panel');
            if (panel.hidden) openPanel('keyboard-panel'); else closePanels();
        }
        if (event.key === 'F2') { event.preventDefault(); closePanels(); search.focus(); search.select(); }
        if (event.key === 'F3') { event.preventDefault(); closePanels(); elements.customer.focus(); elements.customer.select(); }
        if (event.key === 'F4') { event.preventDefault(); closePanels(); elements.discountValue.focus(); elements.discountValue.select(); }
        if (event.key === 'F6') { event.preventDefault(); setPayment('cash'); elements.tendered.focus(); }
        if (event.key === 'F7') { event.preventDefault(); setPayment('card'); }
        if (event.key === 'F8') { event.preventDefault(); setPayment('gcash'); }
        if (event.key === 'F9') { event.preventDefault(); setPayment('cash'); elements.tendered.focus(); elements.tendered.select(); }
        if (event.key === 'F10') { event.preventDefault(); if (!elements.submit.disabled) form.requestSubmit(); }
        if (event.ctrlKey && event.key.toLowerCase() === 'h') { event.preventDefault(); document.getElementById('hold-sale').click(); }
        if (event.ctrlKey && event.key === 'Enter') { event.preventDefault(); if (!elements.submit.disabled) form.requestSubmit(); }
        if (event.altKey && event.key.toLowerCase() === 'h') { event.preventDefault(); openPanel('held-panel'); }
        if (event.altKey && event.key.toLowerCase() === 'r') { event.preventDefault(); openPanel('recent-panel'); }
        if (!typing && (event.key === '+' || event.key === '=') && document.activeElement?.classList.contains('pos-product')) {
            event.preventDefault();
            addProduct(document.activeElement);
        }
        if (event.key === 'Escape') { event.preventDefault(); closePanels(); search.value = ''; filterProducts(); search.focus(); }
    });
    window.addEventListener('online', updateNetwork);
    window.addEventListener('offline', updateNetwork);

    restore();
    filterProducts();
    updateNetwork();
})();
