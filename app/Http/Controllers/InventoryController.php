<?php

namespace App\Http\Controllers;

use App\Models\InventoryLog;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $products = Product::with('category')->when($request->q, fn ($query, $term) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")))->orderBy('quantity')->paginate(25)->withQueryString();
        $stats = [
            'catalog' => Product::count(),
            'units' => Product::sum('quantity'),
            'low' => Product::whereColumn('quantity', '<=', 'low_stock_level')->where('quantity', '>', 0)->count(),
            'out' => Product::where('quantity', '<=', 0)->count(),
        ];
        $logs = InventoryLog::query()->leftJoin('products', 'products.id', '=', 'inventory_logs.product_id')->select('inventory_logs.*', 'products.name as product_name')->latest('inventory_logs.created_at')->limit(30)->get();

        return view('inventory.index', compact('products', 'stats', 'logs'));
    }
}
