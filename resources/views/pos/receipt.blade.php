@extends('layouts.app')
@push('head')<link rel="stylesheet" href="{{ asset('css/pos.css') }}?v={{ filemtime(public_path('css/pos.css')) }}"><link rel="stylesheet" href="{{ asset('css/pos-runtime.css') }}?v={{ filemtime(public_path('css/pos-runtime.css')) }}">@endpush

@section('heading', 'Official receipt')
@section('subtitle', 'Completed transaction and payment details.')

@section('actions')
    <a class="btn" href="{{ route('pos.index') }}">New sale</a>
    <button class="btn primary" onclick="window.print()">Print receipt</button>
@endsection

@push('scripts')
<script>try { localStorage.removeItem(@json('kanto-pos-cart-'.auth()->id())); } catch (_) {}</script>
@endpush

@section('content')
    <section class="receipt card">
        @if($sale->status === 'void')
            <div class="receipt-void"><strong>VOID TRANSACTION</strong><span>{{ $sale->void_reason }}</span></div>
        @endif
        <div class="center"><h2>KANTO GOODS</h2><p>{{ $sale->status === 'void' ? 'Voided sales receipt' : 'Official sales receipt' }}</p><strong>{{ $sale->receipt_no }}</strong></div>
        <div class="receipt-meta"><span>{{ $sale->sale_date->format('M j, Y g:i A') }}</span><span>Cashier: {{ $sale->cashier_name }}</span></div>
        @if($sale->customer_name)<div class="receipt-meta"><span>Customer: {{ $sale->customer_name }}</span></div>@endif
        @foreach($sale->items as $item)<div class="receipt-line"><span>{{ $item->product_name }} &times; {{ $item->quantity }}</span><b>&#8369;{{ number_format($item->total_price, 2) }}</b></div>@endforeach
        <hr><div class="receipt-line"><span>Subtotal</span><b>&#8369;{{ number_format($sale->subtotal_amount, 2) }}</b></div>
        <div class="receipt-line">
            <span>
                Discount
                @if($sale->discount > 0 && $sale->discount_type === 'percent')
                    ({{ number_format($sale->discount_value, 2) }}%)
                @endif
            </span>
            <b>-&#8369;{{ number_format($sale->discount, 2) }}</b>
        </div>
        @if($sale->discount_reason)<div class="receipt-note">Discount reason: {{ $sale->discount_reason }}</div>@endif
        <div class="receipt-line"><span>Tax</span><b>&#8369;{{ number_format($sale->tax, 2) }}</b></div>
        <div class="receipt-line grand"><span>Total</span><b>&#8369;{{ number_format($sale->total_amount, 2) }}</b></div>
        <div class="receipt-line"><span>{{ ucfirst($sale->payment_method) }} payment</span><b>&#8369;{{ number_format($sale->tendered_amount, 2) }}</b></div>
        @if($sale->payment_method === 'cash')<div class="receipt-line"><span>Change</span><b>&#8369;{{ number_format($sale->change_amount, 2) }}</b></div>@endif
        <p class="center receipt-thanks">Thank you for shopping with us.</p>
    </section>
@endsection
