<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\ClosingReport;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClosingController extends Controller
{
    public function index(Request $r): View
    {
        if ($r->user()->isAdmin()) {
            AdminNotification::where('type', 'cashier_closing')->whereNull('read_at')->update(['read_at' => now()]);
        }
        $reports = ClosingReport::when(! $r->user()->isAdmin(), fn ($q) => $q->where('cashier_id', $r->user()->id))->when($r->date_from, fn ($q, $date) => $q->whereDate('closing_date', '>=', $date))->when($r->date_to, fn ($q, $date) => $q->whereDate('closing_date', '<=', $date))->latest('closing_time')->paginate(20)->withQueryString();
        $cashiers = $r->user()->isAdmin() ? User::where('role', 'cashier')->orderBy('first_name')->get() : collect([$r->user()]);

        return view('closings.index', compact('reports', 'cashiers'));
    }

    public function store(Request $r): RedirectResponse
    {
        $data = $r->validate(['closing_date' => 'required|date|before_or_equal:today', 'cashier_id' => 'nullable|exists:users,id', 'actual_cash_amount' => 'nullable|numeric|min:0', 'notes' => 'nullable|max:255']);
        $cashier = $r->user()->isAdmin() ? User::where('role', 'cashier')->findOrFail($data['cashier_id']) : $r->user();
        if (ClosingReport::where('closing_date', $data['closing_date'])->where('cashier_id', $cashier->id)->exists()) {
            throw ValidationException::withMessages(['closing_date' => 'This cashier already has a closing report for that date.']);
        }
        $sales = Sale::where('cashier_id', $cashier->id)->whereDate('sale_date', $data['closing_date'])->where('status', 'paid')->where('closing_status', 'open');
        $total = (float) (clone $sales)->sum('total_amount');
        $cashTotal = (float) (clone $sales)->where('payment_method', 'cash')->sum('total_amount');
        $items = (int) (clone $sales)->withSum('items', 'quantity')->get()->sum('items_sum_quantity');
        $actual = array_key_exists('actual_cash_amount', $data) && $data['actual_cash_amount'] !== null ? (float) $data['actual_cash_amount'] : $cashTotal;
        $difference = round($actual - $cashTotal, 2);
        DB::transaction(function () use ($data, $cashier, $sales, $total, $cashTotal, $items, $difference, $r) {
            $actual = array_key_exists('actual_cash_amount', $data) && $data['actual_cash_amount'] !== null ? (float) $data['actual_cash_amount'] : $cashTotal;
            $feedback = $difference < 0 ? 'Short PHP '.number_format(abs($difference), 2).'. Please explain missing cash before next shift.' : null;
            $closing = ClosingReport::create(['closing_date' => $data['closing_date'], 'cashier_id' => $cashier->id, 'cashier_name' => $cashier->name, 'total_transactions' => (clone $sales)->count(), 'total_items_sold' => $items, 'total_sales' => $total, 'total_cash_received' => $cashTotal, 'expected_cash_amount' => $cashTotal, 'actual_cash_amount' => $actual, 'difference_amount' => $difference, 'closing_time' => now(), 'closed_by' => $r->user()->id, 'status' => 'closed', 'notes' => $data['notes'] ?? null, 'review_status' => $difference < 0 ? 'short' : ($difference > 0 ? 'over' : 'balanced'), 'admin_feedback' => $feedback]);
            if (! $r->user()->isAdmin()) {
                AdminNotification::create(['type' => 'cashier_closing', 'title' => 'Cashier closing submitted', 'body' => $cashier->name.' closed '.$data['closing_date'].'. Expected cash PHP '.number_format($cashTotal, 2).', actual PHP '.number_format($actual, 2).'.', 'link_url' => route('closings.index', absolute: false), 'related_type' => 'closing_report', 'related_id' => $closing->id, 'created_at' => now()]);
            }
            $sales->update(['closing_status' => 'closed', 'closed_at' => now()]);
        });

        return back()->with('success', 'Closing report saved.');
    }

    public function expected(Request $request): JsonResponse
    {
        $data = $request->validate(['date' => 'required|date', 'cashier_id' => 'nullable|exists:users,id']);
        $cashierId = $request->user()->isAdmin() ? (int) $data['cashier_id'] : $request->user()->id;
        $sales = Sale::where('cashier_id', $cashierId)->whereDate('sale_date', $data['date'])->where('status', 'paid')->where('closing_status', 'open');

        return response()->json(['expected' => (float) (clone $sales)->where('payment_method', 'cash')->sum('total_amount'), 'total_sales' => (float) (clone $sales)->sum('total_amount'), 'transactions' => $sales->count()]);
    }

    public function review(Request $r, ClosingReport $closing): RedirectResponse
    {
        $data = $r->validate(['admin_feedback' => 'required|max:255', 'review_status' => 'required|in:balanced,over,short,reviewed']);
        $closing->update($data + ['reviewed_by' => $r->user()->id, 'reviewed_at' => now()]);

        return back()->with('success', 'Closing report reviewed.');
    }
}
