<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\HeldSale;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\Sale;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(Request $request): View
    {
        $recent = Sale::with('items')->when(! $request->user()->isAdmin(), fn ($query) => $query->where('cashier_id', $request->user()->id))->latest('sale_date')->limit(10)->get();
        $holds = HeldSale::where('user_id', $request->user()->id)->latest()->get();
        $shift = CashierShift::where('user_id', $request->user()->id)->where('status', 'open')->latest('opened_at')->first();

        return view('pos.index', [
            'products' => Product::with('category')->where('quantity', '>', 0)->orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(), 'recent' => $recent, 'holds' => $holds, 'shift' => $shift,
        ]);
    }

    public function store(Request $request, CheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate([
            'items' => 'required|array|min:1', 'items.*.product_id' => 'required|integer', 'items.*.quantity' => 'required|integer|min:1',
            'customer_name' => 'nullable|max:150', 'discount' => 'nullable|numeric|min:0', 'discount_type' => 'nullable|in:fixed,percent',
            'discount_value' => 'nullable|numeric|min:0', 'discount_reason' => 'nullable|max:255', 'tax' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,card,gcash', 'tendered_amount' => 'nullable|required_if:payment_method,cash|numeric|min:0',
            'held_sale_id' => 'nullable|integer|exists:held_sales,id',
        ]);
        $sale = $checkout->checkout(
            $request->user(), $data['items'], (float) ($data['discount'] ?? 0), (float) ($data['tax'] ?? 0),
            (float) ($data['tendered_amount'] ?? 0), $data['payment_method'], $data['customer_name'] ?? null,
            $data['discount_type'] ?? 'fixed', isset($data['discount_value']) ? (float) $data['discount_value'] : null,
            $data['discount_reason'] ?? null,
        );
        if (! empty($data['held_sale_id'])) {
            HeldSale::where('id', $data['held_sale_id'])->where('user_id', $request->user()->id)->delete();
        }

        return redirect()->route('sales.receipt', $sale)->with('success', 'Sale completed.');
    }

    public function receipt(Request $request, Sale $sale): View
    {
        abort_unless($request->user()->isAdmin() || $sale->cashier_id === $request->user()->id, 403);

        return view('pos.receipt', ['sale' => $sale->load('items', 'payment')]);
    }

    public function void(Request $request, Sale $sale): RedirectResponse
    {
        $data = $request->validate(['void_reason' => 'required|max:255']);
        DB::transaction(function () use ($request, $sale, $data) {
            $lockedSale = Sale::with('items')->lockForUpdate()->findOrFail($sale->id);
            if ($lockedSale->status !== 'paid') {
                throw ValidationException::withMessages(['void_reason' => 'This transaction is already void.']);
            }
            if ($lockedSale->closing_status === 'closed') {
                throw ValidationException::withMessages(['void_reason' => 'A closed transaction cannot be voided.']);
            }
            foreach ($lockedSale->items as $item) {
                $product = Product::lockForUpdate()->find($item->product_id);
                if (! $product) {
                    continue;
                }
                $before = $product->quantity;
                $product->increment('quantity', $item->quantity);
                InventoryLog::create(['product_id' => $product->id, 'action' => 'Void restoration', 'quantity_change' => $item->quantity, 'stock_before' => $before, 'stock_after' => $before + $item->quantity, 'reference_type' => 'sale_void', 'reference_id' => $lockedSale->id, 'created_by' => $request->user()->id, 'created_at' => now()]);
            }
            $lockedSale->update(['status' => 'void', 'voided_by' => $request->user()->id, 'voided_at' => now(), 'void_reason' => $data['void_reason'], 'closing_status' => 'void']);
        });

        return back()->with('success', 'Transaction voided and stock restored.');
    }
}
