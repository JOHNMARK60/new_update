@extends('layouts.app')

@section('heading', auth()->user()->isAdmin() ? 'Admin overview' : 'Cashier overview')
@section('subtitle', 'Today’s activity, sales trends, and inventory signals.')

@section('actions')
    <a class="btn primary" href="{{ route('pos.index') }}">New sale</a>
@endsection

@section('content')
    <div class="stats">
        <article><span>Today's sales</span><strong>&#8369;{{ number_format($stats['sales'], 2) }}</strong></article>
        <article><span>Transactions</span><strong>{{ $stats['transactions'] }}</strong></article>
        <article><span>Items sold</span><strong>{{ $stats['items'] }}</strong></article>
        <article><span>Available products</span><strong>{{ $stats['available'] }}</strong></article>
        <article><span>Low stock</span><strong>{{ $stats['low_stock'] }}</strong></article>
        @if(auth()->user()->isAdmin())<article><span>Cashiers</span><strong>{{ $stats['cashiers'] }}</strong></article>@endif
    </div>
    <div class="chart-grid">
        <section class="card"><div class="module-card-header"><div><h2>Sales trend</h2><p>Last seven days</p></div></div><canvas id="dailyChart"></canvas></section>
        <section class="card"><div class="module-card-header"><div><h2>Monthly sales</h2><p>Last six months</p></div></div><canvas id="monthlyChart"></canvas></section>
        <section class="card"><div class="module-card-header"><div><h2>Top-selling products</h2><p>All paid sales</p></div></div><canvas id="topChart"></canvas></section>
    </div>
    @if(auth()->user()->isAdmin() && $notifications->isNotEmpty())
        <section class="card notification-list"><div class="module-card-header"><div><h2>Closing notifications</h2><p>Recent cashier submissions</p></div><a href="{{ route('closings.index') }}">Review all</a></div>@foreach($notifications as $notification)<article @class(['unread' => ! $notification->read_at])><b>{{ $notification->title }}</b><span>{{ $notification->body }}</span><small>{{ $notification->created_at?->diffForHumans() }}</small></article>@endforeach</section>
    @endif
    <section class="card table-wrap"><div class="module-card-header"><div><h2>Recent sales</h2><p>Paid transactions recorded today.</p></div><a href="{{ route('reports.index') }}">View reports</a></div><table><thead><tr><th>Receipt</th><th>Cashier</th><th>Items</th><th>Time</th><th>Total</th></tr></thead><tbody>@forelse($recent as $sale)<tr><td><a href="{{ route('sales.receipt', $sale) }}">{{ $sale->receipt_no }}</a></td><td>{{ $sale->cashier_name }}</td><td>{{ $sale->items->sum('quantity') }}</td><td>{{ $sale->sale_date->format('g:i A') }}</td><td>&#8369;{{ number_format($sale->total_amount, 2) }}</td></tr>@empty<tr><td colspan="5" class="empty">No transactions today.</td></tr>@endforelse</tbody></table></section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>const chart=(id,type,labels,data,label)=>new Chart(document.getElementById(id),{type,data:{labels,datasets:[{label,data,borderColor:'#2563eb',backgroundColor:type==='line'?'rgba(37,99,235,.12)':['#2563eb','#059669','#7c3aed','#d97706','#0891b2','#db2777','#4f46e5'],fill:type==='line',tension:.35}]},options:{responsive:true,plugins:{legend:{display:type==='line'}}}});chart('dailyChart','line',@json($daily->pluck('label')),@json($daily->pluck('total')),'Sales');chart('monthlyChart','bar',@json($monthly->pluck('label')),@json($monthly->pluck('total')),'Sales');chart('topChart','doughnut',@json($topProducts->pluck('product_name')),@json($topProducts->pluck('quantity')),'Units');</script>
@endpush
