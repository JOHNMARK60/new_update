<?php

namespace App\Http\Controllers;

use App\Models\HeldSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeldSaleController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'label' => 'nullable|max:120', 'cart' => 'required|array|min:1', 'cart.*.product_id' => 'required|integer|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1', 'customer_name' => 'nullable|max:150', 'discount' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:fixed,percent', 'discount_value' => 'nullable|numeric|min:0', 'discount_reason' => 'nullable|max:255', 'tax' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,card,gcash',
        ]);
        $hold = HeldSale::create(array_merge($data, ['user_id' => $request->user()->id, 'label' => trim((string) ($data['label'] ?? '')) ?: 'Held '.now()->format('g:i A')]));

        return response()->json(['message' => 'Sale held.', 'hold' => $hold], 201);
    }

    public function destroy(Request $request, HeldSale $heldSale): JsonResponse
    {
        abort_unless($request->user()->isAdmin() || $heldSale->user_id === $request->user()->id, 403);
        $heldSale->delete();

        return response()->json(['message' => 'Held sale removed.']);
    }
}
