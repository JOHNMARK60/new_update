@extends('layouts.app')

@section('heading', 'Product details')
@section('subtitle', 'Review product information and current availability.')

@section('actions')
    @if(auth()->user()->isAdmin())<a class="btn primary" href="{{ route('products.edit', $product) }}">Edit product</a>@endif
    <a class="btn" href="{{ route('products.index') }}">Back to catalog</a>
@endsection

@section('content')
    <section class="card product-detail"><div>@if($product->image_path)<img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}">@else<div class="placeholder">KG</div>@endif</div><div><span class="badge">{{ $product->category?->name ?: 'Uncategorized' }}</span><h2>{{ $product->name }}</h2><strong class="detail-price">&#8369;{{ number_format($product->price, 2) }}</strong><dl><dt>SKU</dt><dd>{{ $product->sku ?: 'Not assigned' }}</dd><dt>Supplier</dt><dd>{{ $product->supplier?->name ?: 'Not assigned' }}</dd><dt>Available stock</dt><dd>{{ $product->quantity }} units</dd><dt>Low-stock threshold</dt><dd>{{ $product->low_stock_level }} units</dd><dt>Expiration</dt><dd>{{ $product->expiration_date?->format('M j, Y') ?: 'Not specified' }}</dd><dt>Status</dt><dd>@if($product->quantity <= 0)<span class="danger">Out of stock</span>@elseif($product->is_low_stock)<span class="warning">Low stock</span>@else<span class="positive">In stock</span>@endif</dd></dl></div></section>
@endsection
