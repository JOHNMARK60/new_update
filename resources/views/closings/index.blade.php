@extends('layouts.app')

@section('heading', 'Daily closing')
@section('subtitle', 'Reconcile expected and actual cash, then review closing history.')

@section('content')
    <form class="card form-grid compact" method="post" action="{{ route('closings.store') }}" id="closing-form" data-expected-url="{{ route('closings.expected') }}">
        @csrf
        <div class="full module-card-header"><div><h2>Close a business day</h2><p>Expected cash is calculated from open paid sales.</p></div></div>
        <label>Date<input id="closing-date" type="date" name="closing_date" max="{{ today()->format('Y-m-d') }}" value="{{ old('closing_date', today()->format('Y-m-d')) }}" required></label>
        @if(auth()->user()->isAdmin())<label>Cashier<select id="closing-cashier" name="cashier_id" required>@foreach($cashiers as $cashier)<option value="{{ $cashier->id }}">{{ $cashier->name }}</option>@endforeach</select></label>@else<input id="closing-cashier" type="hidden" value="{{ auth()->id() }}">@endif
        <label>Expected cash<input id="expected-cash" value="Loading..." readonly></label>
        <label>Open transactions<input id="expected-transactions" value="0" readonly></label>
        <label>Actual cash<input id="actual-cash" type="number" step="0.01" min="0" name="actual_cash_amount" required></label>
        <label>Notes<input name="notes" maxlength="255"></label>
        <div class="full"><button class="btn primary">Save closing</button></div>
    </form>

    <form class="catalog-toolbar" method="get"><label>History from<input type="date" name="date_from" value="{{ request('date_from') }}"></label><label>History to<input type="date" name="date_to" value="{{ request('date_to') }}"></label><button class="btn">Filter history</button>@if(request()->hasAny(['date_from','date_to']))<a class="btn" href="{{ route('closings.index') }}">Clear</a>@endif</form>
    <section class="card table-wrap"><div class="module-card-header"><div><h2>Closing history</h2><p>Submitted reconciliation reports and administrator feedback.</p></div></div><table><thead><tr><th>Date</th><th>Cashier</th><th>Transactions</th><th>Expected</th><th>Actual</th><th>Difference</th><th>Feedback / Review</th></tr></thead><tbody>@forelse($reports as $report)<tr><td>{{ $report->closing_date->format('M j, Y') }}</td><td>{{ $report->cashier_name }}</td><td>{{ $report->total_transactions }}</td><td>&#8369;{{ number_format($report->expected_cash_amount, 2) }}</td><td>&#8369;{{ number_format($report->actual_cash_amount, 2) }}</td><td @class(['danger' => $report->difference_amount < 0, 'positive' => $report->difference_amount > 0])>&#8369;{{ number_format($report->difference_amount, 2) }}</td><td>@if(auth()->user()->isAdmin())<form class="review-form" method="post" action="{{ route('closings.review', $report) }}">@csrf @method('PATCH')<select name="review_status"><option @selected($report->review_status === 'balanced')>balanced</option><option @selected($report->review_status === 'over')>over</option><option @selected($report->review_status === 'short')>short</option><option @selected($report->review_status === 'reviewed')>reviewed</option></select><input name="admin_feedback" value="{{ $report->admin_feedback }}" placeholder="Feedback" required><button class="btn small">Save</button></form>@else<span class="badge">{{ ucfirst($report->review_status) }}</span>@if($report->admin_feedback)<small class="feedback">{{ $report->admin_feedback }}</small>@endif @endif</td></tr>@empty<tr><td colspan="7" class="empty">No closing reports yet.</td></tr>@endforelse</tbody></table></section>
    {{ $reports->links() }}
@endsection

@push('scripts')
<script>const form=document.querySelector('#closing-form'),date=document.querySelector('#closing-date'),cashier=document.querySelector('#closing-cashier'),expected=document.querySelector('#expected-cash'),transactions=document.querySelector('#expected-transactions'),actual=document.querySelector('#actual-cash');async function loadExpected(){if(!date.value||!cashier.value)return;const url=new URL(form.dataset.expectedUrl,location.origin);url.searchParams.set('date',date.value);url.searchParams.set('cashier_id',cashier.value);const response=await fetch(url,{headers:{Accept:'application/json'}});if(!response.ok)return;const data=await response.json();expected.value='\u20B1'+Number(data.expected).toFixed(2);transactions.value=data.transactions;if(!actual.dataset.edited)actual.value=Number(data.expected).toFixed(2)}date.onchange=loadExpected;cashier.onchange=loadExpected;actual.oninput=()=>actual.dataset.edited='1';loadExpected();</script>
@endpush
