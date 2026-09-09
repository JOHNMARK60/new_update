@extends('layouts.app')

@section('heading', 'Product catalog')
@section('subtitle', 'Browse products, prices, availability, categories, and suppliers.')

@section('header-search')
    <form class="header-search" method="get" action="{{ route('products.index') }}">
        <input name="q" value="{{ request('q') }}" placeholder="Search product, SKU, category…" aria-label="Search products">
    </form>
@endsection

@section('actions')
    @if(auth()->user()->isAdmin())
        <a class="btn primary" href="{{ route('products.create') }}">Add product</a>
    @endif
@endsection

@section('content')
    <form class="catalog-toolbar" method="get">
        <input type="hidden" name="q" value="{{ request('q') }}">
        <select name="category_id" aria-label="Filter by category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select>
        <select name="supplier_id" aria-label="Filter by supplier"><option value="">All suppliers</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>@endforeach</select>
        <button class="btn">Apply filters</button>
        @if(request()->hasAny(['q','category_id','supplier_id']))<a class="btn" href="{{ route('products.index') }}">Clear</a>@endif
    </form>

    <div class="product-grid">
        @forelse($products as $product)
            <article class="product-card">
                @if($product->image_path)<img src="{{ asset($product->image_path) }}" alt="{{ $product->name }}">@else<div class="placeholder">KG</div>@endif
                <div><small>{{ $product->category?->name ?? 'Uncategorized' }} &middot; {{ $product->sku ?: 'No SKU' }}</small><h3><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></h3><strong>&#8369;{{ number_format($product->price, 2) }}</strong><p @class(['danger' => $product->is_low_stock])>{{ $product->quantity }} in stock</p>@if(auth()->user()->isAdmin())<div class="row"><a class="btn small" href="{{ route('products.edit', $product) }}">Edit</a><form method="post" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">@csrf @method('DELETE')<button class="btn small danger-btn">Delete</button></form></div>@endif</div>
            </article>
        @empty
            <section class="card empty">No products match your search or filters.</section>
        @endforelse
    </div>
    {{ $products->links() }}

    @if(auth()->user()->isAdmin())
        <div class="management-grid">
            <section class="card"><div class="module-card-header"><div><h2>Categories</h2><p>Create, rename, or remove empty categories.</p></div></div><form class="row" method="post" action="{{ route('categories.store') }}">@csrf<input name="name" placeholder="New category" required><button class="btn primary">Add</button></form><div class="manage-list">@foreach($categories as $category)<article><form class="row grow" method="post" action="{{ route('categories.update', $category) }}">@csrf @method('PUT')<input name="name" value="{{ $category->name }}" required><small>{{ $category->products_count }} products</small><button class="btn small">Rename</button></form>@if($category->products_count === 0)<form method="post" action="{{ route('categories.destroy', $category) }}">@csrf @method('DELETE')<button class="btn small danger-btn">Delete</button></form>@endif</article>@endforeach</div></section>
            <section class="card"><div class="module-card-header"><div><h2>Suppliers</h2><p>Maintain sources available in product forms.</p></div></div><form class="form-stack" method="post" action="{{ route('suppliers.store') }}">@csrf<input name="name" placeholder="Supplier name" required><input name="contact_no" placeholder="Contact number"><button class="btn primary">Add supplier</button></form><div class="manage-list">@foreach($suppliers as $supplier)<article><span><b>{{ $supplier->name }}</b><small>{{ $supplier->contact_no }}</small></span><form method="post" action="{{ route('suppliers.destroy', $supplier) }}">@csrf @method('DELETE')<button class="btn small danger-btn">Remove</button></form></article>@endforeach</div></section>
        </div>
    @endif
@endsection
