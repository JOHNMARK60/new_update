@extends('layouts.app')

@section('heading', 'Stock status')
@section('subtitle', 'Monitor availability, thresholds, and inventory movement.')

@section('header-search')
    <form class="header-search" method="get" action="{{ route('inventory.status') }}"><input name="q" value="{{ request('q') }}" placeholder="Search product or SKU…" aria-label="Search inventory"></form>
@endsection

@section('content')
    <div class="stats"><article><span>Catalog products</span><strong>{{ $stats['catalog'] }}</strong></article><article><span>Total units</span><strong>{{ number_format($stats['units']) }}</strong></article><article><span>Low stock</span><strong>{{ $stats['low'] }}</strong></article><article><span>Out of stock</span><strong>{{ $stats['out'] }}</strong></article></div>
    <section class="card table-wrap"><div class="module-card-header"><div><h2>Inventory levels</h2><p>Products ordered by lowest available quantity.</p></div>@if(request('q'))<a href="{{ route('inventory.status') }}">Clear search</a>@endif</div><table><thead><tr><th>Product</th><th>SKU</th><th>Category</th><th>Quantity</th><th>Low level</th><th>Status</th></tr></thead><tbody>@forelse($products as $product)<tr><td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td><td>{{ $product->sku ?: '—' }}</td><td>{{ $product->category?->name ?: 'Uncategorized' }}</td><td>{{ $product->quantity }}</td><td>{{ $product->low_stock_level }}</td><td>@if($product->quantity <= 0)<span class="badge badge-red">Out of stock</span>@elseif($product->is_low_stock)<span class="badge badge-amber">Low stock</span>@else<span class="badge badge-green">In stock</span>@endif</td></tr>@empty<tr><td colspan="6" class="empty">No inventory items match your search.</td></tr>@endforelse</tbody></table></section>
    {{ $products->links() }}
    @if(auth()->user()->isAdmin())
        <section class="card table-wrap logs"><div class="module-card-header"><div><h2>Inventory movement log</h2><p>Latest product and sale stock changes.</p></div></div><table><thead><tr><th>Date</th><th>Product</th><th>Action</th><th>Change</th><th>Before</th><th>After</th><th>Reference</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ \Carbon\Carbon::parse($log->created_at)->format('M j, Y g:i A') }}</td><td>{{ $log->product_name ?: 'Deleted product' }}</td><td>{{ $log->action }}</td><td @class(['danger' => $log->quantity_change < 0, 'positive' => $log->quantity_change > 0])>{{ $log->quantity_change > 0 ? '+' : '' }}{{ $log->quantity_change }}</td><td>{{ $log->stock_before }}</td><td>{{ $log->stock_after }}</td><td>{{ $log->reference_type }} #{{ $log->reference_id }}</td></tr>@empty<tr><td colspan="7" class="empty">No inventory movements.</td></tr>@endforelse</tbody></table></section>
    @endif
@endsection
