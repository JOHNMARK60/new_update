@extends('layouts.app')
@push('head')<link rel="stylesheet" href="{{ asset('css/pos.css') }}?v={{ filemtime(public_path('css/pos.css')) }}"><link rel="stylesheet" href="{{ asset('css/pos-runtime.css') }}?v={{ filemtime(public_path('css/pos-runtime.css')) }}">@endpush

@section('heading', 'Point of Sale')
@section('subtitle', 'Scan, search, collect payment, and print a receipt.')

@section('header-search')
    <div class="header-search pos-header-search"><input id="product-search" placeholder="Scan barcode or search product / SKU…" aria-label="Search POS products" autocomplete="off" autofocus></div>
@endsection

@section('actions')
    <span id="network-status" class="status-pill online">Online</span>
    @if($shift)<span class="status-pill shift-open">Shift open · {{ $shift->opened_at->diffForHumans(null, true) }}</span>@else<span class="status-pill">No open shift</span>@endif
@endsection

@section('content')
    <section class="pos-command-bar">
        <div class="command-actions"><button class="btn" type="button" id="hold-sale">Hold sale <kbd>Ctrl H</kbd></button><button class="btn" type="button" data-toggle-panel="held-panel">Held sales <span class="count-badge">{{ $holds->count() }}</span></button><button class="btn" type="button" data-toggle-panel="recent-panel">Recent transactions</button><button class="btn" type="button" data-toggle-panel="keyboard-panel">Keyboard <kbd>F1</kbd></button></div>
        <div class="shift-actions">
            @if($shift)
                <form method="post" action="{{ route('pos.shift.close') }}" class="row">@csrf<input class="compact-input" type="number" step="0.01" min="0" name="closing_cash" placeholder="Closing cash"><button class="btn small">Close shift</button></form>
            @else
                <form method="post" action="{{ route('pos.shift.open') }}" class="row">@csrf<input class="compact-input" type="number" step="0.01" min="0" name="opening_cash" value="0" placeholder="Opening cash" required><button class="btn small primary">Open shift</button></form>
            @endif
        </div>
    </section>

    <div class="pos-workspace">
        <aside class="pos-categories" aria-label="Product categories">
            <button class="category-button active" type="button" data-category="">All products</button>
            @foreach($categories as $category)<button class="category-button" type="button" data-category="{{ $category->id }}">{{ $category->name }}</button>@endforeach
        </aside>

        <section class="pos-catalog">
            <div class="catalog-heading"><div><strong>Products</strong><small id="visible-count">{{ $products->count() }} available</small></div><span class="scan-hint">F2 Search · Enter Scan</span></div>
            <div class="pos-products" id="products">
                @forelse($products as $product)
                    <button type="button" class="pos-product" data-id="{{ $product->id }}" data-name="{{ $product->name }}" data-sku="{{ strtolower($product->sku ?? '') }}" data-search="{{ strtolower($product->name.' '.$product->sku) }}" data-price="{{ $product->price }}" data-stock="{{ $product->quantity }}" data-category="{{ $product->category_id }}">
                        <span class="stock-chip {{ $product->is_low_stock ? 'low' : '' }}">{{ $product->quantity }} left</span>
                        @if($product->image_path)<img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}">@else<div class="placeholder">KG</div>@endif
                        <span class="product-copy"><strong>{{ $product->name }}</strong><small>{{ $product->sku ?: 'No SKU' }}</small><b>&#8369;{{ number_format($product->price, 2) }}</b></span>
                    </button>
                @empty
                    <div class="card empty">No products are currently in stock.</div>
                @endforelse
            </div>
        </section>

        <form class="pos-cart" method="post" action="{{ route('pos.store') }}" id="checkout-form" data-storage-key="kanto-pos-cart-{{ auth()->id() }}" data-hold-url="{{ route('pos.holds.store') }}" data-hold-delete="{{ route('pos.holds.destroy', ['heldSale' => '__ID__']) }}">
            @csrf<input type="hidden" name="held_sale_id" id="held-sale-id">
            <div class="cart-header"><div><h2>Current order</h2><p id="cart-count">0 items</p></div><button type="button" class="link danger" id="clear-cart">Clear</button></div>
            <label class="customer-field"><span>Customer (optional)</span><input id="customer-name" name="customer_name" maxlength="150" placeholder="Walk-in customer"></label>
            <div id="cart-lines" class="cart-lines"><div class="empty-cart"><b>Your cart is empty</b><span>Scan or select a product to start.</span></div></div>
            <div class="cart-totals">
                <div><span>Subtotal</span><strong id="subtotal-display">&#8369;0.00</strong></div>
                <div class="discount-controls"><select id="discount-type" name="discount_type" aria-label="Discount type"><option value="fixed">Discount &#8369;</option><option value="percent">Discount %</option></select><input id="discount-value" type="number" step="0.01" min="0" name="discount_value" value="0"><input id="discount-reason" name="discount_reason" maxlength="255" placeholder="Discount reason"></div>
                <label class="inline-field"><span>Tax</span><input id="tax" type="number" step="0.01" min="0" name="tax" value="0"></label>
                <div class="grand-total"><span>Total</span><strong id="total-display">&#8369;0.00</strong></div>
            </div>
            <div class="payment-area">
                <div class="payment-tabs" role="group" aria-label="Payment method"><button class="active" type="button" data-payment="cash">Cash</button><button type="button" data-payment="card">Card</button><button type="button" data-payment="gcash">GCash</button></div>
                <input type="hidden" name="payment_method" id="payment-method" value="cash">
                <div id="cash-payment"><label>Cash tendered<input id="tendered" type="number" step="0.01" min="0" name="tendered_amount" required></label><div class="quick-cash"><button type="button" data-cash="exact">Exact</button><button type="button" data-cash="50">&#8369;50</button><button type="button" data-cash="100">&#8369;100</button><button type="button" data-cash="500">&#8369;500</button><button type="button" data-cash="1000">&#8369;1,000</button></div><div class="change-row"><span>Change</span><strong id="change-display">&#8369;0.00</strong></div></div>
                <p id="digital-payment" class="digital-payment" hidden>Confirm the customer’s payment on the terminal before completing this sale.</p>
            </div>
            <button class="complete-sale" id="complete-sale" disabled>Pay &amp; print receipt <kbd>Ctrl Enter</kbd></button>
        </form>
    </div>

    <aside id="held-panel" class="pos-side-panel" hidden><div class="panel-head"><div><h2>Held sales</h2><p>Recall an unfinished order.</p></div><button type="button" data-close-panel>&times;</button></div><div class="panel-list">@forelse($holds as $hold)<article><div><b>{{ $hold->label }}</b><span>{{ count($hold->cart) }} line(s) · {{ $hold->created_at->diffForHumans() }}</span>@if($hold->customer_name)<small>{{ $hold->customer_name }}</small>@endif</div><div class="row"><button class="btn small primary recall-hold" type="button" data-id="{{ $hold->id }}">Recall</button><button class="btn small danger-btn delete-hold" type="button" data-id="{{ $hold->id }}">Delete</button></div></article>@empty<p class="empty">No held sales.</p>@endforelse</div></aside>
    <aside id="recent-panel" class="pos-side-panel" hidden><div class="panel-head"><div><h2>Recent transactions</h2><p>Open or reprint a receipt.</p></div><button type="button" data-close-panel>&times;</button></div><div class="panel-list">@forelse($recent as $sale)<article><div><a href="{{ route('sales.receipt', $sale) }}"><b>{{ $sale->receipt_no }}</b></a><span>{{ $sale->sale_date->format('M j, g:i A') }} · {{ $sale->items->sum('quantity') }} item(s)</span><small>{{ ucfirst($sale->payment_method) }} · &#8369;{{ number_format($sale->total_amount, 2) }} · {{ ucfirst($sale->status) }}</small></div>@if(auth()->user()->isAdmin() && $sale->status === 'paid' && $sale->closing_status !== 'closed')<form method="post" action="{{ route('sales.void', $sale) }}" onsubmit="return confirm('Void this transaction and restore its stock?')">@csrf @method('PATCH')<input type="hidden" name="void_reason" value="Administrator void from POS"><button class="btn small danger-btn">Void</button></form>@endif</article>@empty<p class="empty">No recent transactions.</p>@endforelse</div></aside>
    <aside id="keyboard-panel" class="pos-side-panel" hidden><div class="panel-head"><div><h2>Keyboard controls</h2><p>Operate the counter without a mouse.</p></div><button type="button" data-close-panel>&times;</button></div><div class="shortcut-list"><div><kbd>F1</kbd><span>Open or close this guide</span></div><div><kbd>F2</kbd><span>Focus product search or scanner</span></div><div><kbd>F3</kbd><span>Focus customer name</span></div><div><kbd>F4</kbd><span>Focus discount</span></div><div><kbd>F6 / F7 / F8</kbd><span>Select cash, card, or GCash</span></div><div><kbd>F9</kbd><span>Focus cash tendered</span></div><div><kbd>F10</kbd><span>Complete sale and print</span></div><div><kbd>Ctrl + H</kbd><span>Hold current sale</span></div><div><kbd>Alt + H / Alt + R</kbd><span>Open held or recent sales</span></div><div><kbd>Arrow keys</kbd><span>Move through product cards</span></div><div><kbd>Enter</kbd><span>Add the selected or scanned product</span></div><div><kbd>+ / − / Delete</kbd><span>Change a focused cart line</span></div><div><kbd>Esc</kbd><span>Close panels and return to search</span></div></div></aside>
    <div id="panel-backdrop" class="panel-backdrop" hidden></div>
    <script type="application/json" id="held-sales-data">@json($holds->keyBy('id'))</script>
@endsection

@push('scripts')
<script src="{{ asset('js/pos.js') }}?v={{ filemtime(public_path('js/pos.js')) }}"></script>
@endpush
