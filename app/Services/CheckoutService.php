<?php

namespace App\Services;

use App\Models\CashierShift;
use App\Models\InventoryLog;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function checkout(
        User $cashier,
        array $lines,
        float $discount,
        float $tax,
        float $tendered,
        string $paymentMethod = 'cash',
        ?string $customerName = null,
        string $discountType = 'fixed',
        ?float $discountValue = null,
        ?string $discountReason = null,
    ): Sale {
        $quantities = collect($lines)->groupBy('product_id')->map(fn ($rows) => $rows->sum('quantity'));
        if ($quantities->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Add at least one product.']);
        }
        $paymentMethod = in_array($paymentMethod, ['cash', 'card', 'gcash'], true) ? $paymentMethod : 'cash';
        $discountType = in_array($discountType, ['fixed', 'percent'], true) ? $discountType : 'fixed';

        return DB::transaction(function () use ($cashier, $quantities, $discount, $tax, $tendered, $paymentMethod, $customerName, $discountType, $discountValue, $discountReason) {
            $products = Product::whereIn('id', $quantities->keys())->lockForUpdate()->get()->keyBy('id');
            $items = collect();
            foreach ($quantities as $id => $quantity) {
                $product = $products->get((int) $id);
                if (! $product || $quantity < 1 || $product->quantity < $quantity) {
                    throw ValidationException::withMessages(['cart' => ($product?->name ?? 'A product').' has insufficient stock.']);
                }
                $items->push(['product' => $product, 'quantity' => (int) $quantity, 'total' => round((float) $product->price * $quantity, 2)]);
            }

            $subtotal = round($items->sum('total'), 2);
            $enteredDiscount = max(0, $discountValue ?? $discount);
            $calculatedDiscount = $discountType === 'percent' ? round($subtotal * min($enteredDiscount, 100) / 100, 2) : min($enteredDiscount, $subtotal);
            if (! $cashier->isAdmin() && $calculatedDiscount > round($subtotal * 0.20, 2)) {
                throw ValidationException::withMessages(['discount_value' => 'Discounts above 20% require an administrator.']);
            }
            if ($calculatedDiscount > 0 && trim((string) $discountReason) === '') {
                throw ValidationException::withMessages(['discount_reason' => 'Enter a reason for the discount.']);
            }

            $tax = round(max(0, $tax), 2);
            $total = max(0, round($subtotal - $calculatedDiscount + $tax, 2));
            if ($paymentMethod === 'cash' && $tendered < $total) {
                throw ValidationException::withMessages(['tendered_amount' => 'Cash tendered is less than the total.']);
            }
            if ($paymentMethod !== 'cash') {
                $tendered = $total;
            }
            $change = $paymentMethod === 'cash' ? round($tendered - $total, 2) : 0.0;
            $receiptNo = $this->receiptNumber();
            $first = $items->first();
            $shiftId = CashierShift::where('user_id', $cashier->id)->where('status', 'open')->latest('opened_at')->value('id');

            $sale = Sale::create([
                'receipt_no' => $receiptNo, 'cashier_id' => $cashier->id, 'shift_id' => $shiftId, 'cashier_name' => $cashier->name,
                'customer_name' => trim((string) $customerName) ?: null, 'product_id' => $first['product']->id, 'quantity' => $first['quantity'],
                'total_price' => $total, 'subtotal_amount' => $subtotal, 'discount' => $calculatedDiscount, 'discount_type' => $discountType,
                'discount_value' => $enteredDiscount, 'discount_reason' => trim((string) $discountReason) ?: null, 'tax' => $tax,
                'total_amount' => $total, 'tendered_amount' => $tendered, 'change_amount' => $change, 'payment_method' => $paymentMethod,
                'user_id' => $cashier->id, 'sale_date' => now(), 'status' => 'paid', 'closing_status' => 'open',
            ]);

            foreach ($items as $line) {
                $product = $line['product'];
                $before = $product->quantity;
                $product->decrement('quantity', $line['quantity']);
                $sale->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'quantity' => $line['quantity'], 'unit_price' => $product->price, 'total_price' => $line['total']]);
                InventoryLog::create(['product_id' => $product->id, 'action' => 'Sale', 'quantity_change' => -$line['quantity'], 'stock_before' => $before, 'stock_after' => $before - $line['quantity'], 'reference_type' => 'sale', 'reference_id' => $sale->id, 'created_by' => $cashier->id, 'created_at' => now()]);
            }
            Payment::create(['sale_id' => $sale->id, 'amount' => $total, 'tendered_amount' => $tendered, 'change_amount' => $change, 'currency' => 'PHP', 'payment_method' => $paymentMethod, 'payment_date' => now()]);
            Receipt::create(['sale_id' => $sale->id, 'receipt_no' => $receiptNo]);

            return $sale->load('items', 'payment');
        }, 3);
    }

    private function receiptNumber(): string
    {
        do {
            $number = 'POS-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Sale::where('receipt_no', $number)->exists());

        return $number;
    }
}
