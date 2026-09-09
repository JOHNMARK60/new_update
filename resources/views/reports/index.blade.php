@extends('layouts.app')

@section('heading', 'Sales reports')
@section('subtitle', 'Analyze transactions, products, payments, and cashier performance.')

@section('actions')
    <a class="btn" href="{{ route('reports.csv', request()->query()) }}">Export CSV</a>
    <a class="btn primary" href="{{ route('reports.pdf', request()->query()) }}">Export PDF</a>
@endsection

@section('content')
    <form class="card report-filters" method="get">
        <label>Report period<select name="report_type"><option value="daily" @selected(request('report_type','daily') === 'daily')>Daily</option><option value="weekly" @selected(request('report_type') === 'weekly')>Weekly</option><option value="monthly" @selected(request('report_type') === 'monthly')>Monthly</option><option value="yearly" @selected(request('report_type') === 'yearly')>Yearly</option></select></label>
        <label>Anchor date<input type="date" name="date" value="{{ request('date', today()->format('Y-m-d')) }}"></label>
        <label>Custom from<input type="date" name="date_from" value="{{ request('date_from') }}"></label>
        <label>Custom to<input type="date" name="date_to" value="{{ request('date_to') }}"></label>
        @if(auth()->user()->isAdmin())<label>Cashier<select name="cashier_id"><option value="">All cashiers</option>@foreach($cashiers as $cashier)<option value="{{ $cashier->id }}" @selected(request('cashier_id') == $cashier->id)>{{ $cashier->name }}</option>@endforeach</select></label>@endif
        <label>Product<select name="product_id"><option value="">All products</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected(request('product_id') == $product->id)>{{ $product->name }}</option>@endforeach</select></label>
        <label>Category<select name="category_id"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
        <label>Status<select name="status"><option value="">All statuses</option><option value="paid" @selected(request('status') === 'paid')>Paid</option><option value="void" @selected(request('status') === 'void')>Void</option></select></label>
        <div class="filter-action"><button class="btn primary">Apply filters</button><a class="btn" href="{{ route('reports.index') }}">Reset</a></div>
    </form>
    <p class="period-label">Showing {{ \Carbon\Carbon::parse($from)->format('M j, Y') }} to {{ \Carbon\Carbon::parse($to)->format('M j, Y') }}</p>
    <div class="stats"><article><span>Total sales</span><strong>&#8369;{{ number_format($summary->sales, 2) }}</strong></article><article><span>Transactions</span><strong>{{ $summary->transactions }}</strong></article><article><span>Items sold</span><strong>{{ $summary->items }}</strong></article><article><span>Cash received</span><strong>&#8369;{{ number_format($summary->cash, 2) }}</strong></article><article><span>Void transactions</span><strong>{{ $summary->voids }}</strong></article></div>
    <div class="report-grid">
        <section class="card table-wrap"><div class="module-card-header"><div><h2>Item summary</h2><p>Products sold during this period.</p></div></div><table><thead><tr><th>Product</th><th>Category</th><th>Qty</th><th>Amount</th></tr></thead><tbody>@forelse($itemSummary as $item)<tr><td>{{ $item->product_name }}</td><td>{{ $item->category_name }}</td><td>{{ $item->quantity }}</td><td>&#8369;{{ number_format($item->total, 2) }}</td></tr>@empty<tr><td colspan="4" class="empty">No item sales.</td></tr>@endforelse</tbody></table></section>
        <section class="card table-wrap"><div class="module-card-header"><div><h2>Cashier performance</h2><p>Transactions and sales by cashier.</p></div></div><table><thead><tr><th>Cashier</th><th>Transactions</th><th>Sales</th></tr></thead><tbody>@forelse($cashierPerformance as $row)<tr><td>{{ $row->cashier_name }}</td><td>{{ $row->transactions }}</td><td>&#8369;{{ number_format($row->total, 2) }}</td></tr>@empty<tr><td colspan="3" class="empty">No performance data.</td></tr>@endforelse</tbody></table><div class="module-card-header payment-title"><div><h2>Payment summary</h2></div></div><table><thead><tr><th>Method</th><th>Count</th><th>Amount</th><th>Change</th></tr></thead><tbody>@forelse($payments as $payment)<tr><td>{{ ucfirst($payment->payment_method) }}</td><td>{{ $payment->transactions }}</td><td>&#8369;{{ number_format($payment->amount, 2) }}</td><td>&#8369;{{ number_format($payment->change_total, 2) }}</td></tr>@empty<tr><td colspan="4" class="empty">No payments.</td></tr>@endforelse</tbody></table></section>
    </div>
    <section class="card table-wrap"><div class="module-card-header"><div><h2>Transaction history</h2><p>Individual sales matching the selected filters.</p></div></div><table><thead><tr><th>Receipt</th><th>Date</th><th>Cashier</th><th>Items</th><th>Status</th><th>Total</th></tr></thead><tbody>@forelse($sales as $sale)<tr><td><a href="{{ route('sales.receipt', $sale) }}">{{ $sale->receipt_no }}</a></td><td>{{ $sale->sale_date->format('M j, Y g:i A') }}</td><td>{{ $sale->cashier_name }}</td><td>{{ $sale->items->sum('quantity') }}</td><td><span class="badge">{{ ucfirst($sale->status) }}</span></td><td>&#8369;{{ number_format($sale->total_amount, 2) }}</td></tr>@empty<tr><td colspan="6" class="empty">No sales match these filters.</td></tr>@endforelse</tbody></table></section>
    {{ $sales->links() }}
@endsection
