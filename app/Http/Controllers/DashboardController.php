<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $own = ! $request->user()->isAdmin();
        $scope = fn ($query) => $query->when($own, fn ($q) => $q->where('cashier_id', $request->user()->id));
        $todaySales = $scope(Sale::whereDate('sale_date', today())->where('status', 'paid'));
        $todayIds = (clone $todaySales)->pluck('id');
        $stats = [
            'sales' => (float) (clone $todaySales)->sum('total_amount'),
            'transactions' => (clone $todaySales)->count(),
            'items' => (int) SaleItem::whereIn('sale_id', $todayIds)->sum('quantity'),
            'products' => Product::count(),
            'available' => Product::where('quantity', '>', 0)->count(),
            'low_stock' => Product::whereColumn('quantity', '<=', 'low_stock_level')->count(),
            'cashiers' => User::where('role', 'cashier')->count(),
        ];
        $recent = (clone $todaySales)->with('items')->latest('sale_date')->limit(8)->get();
        $daily = $this->dailySeries($request, 7);
        $monthly = $this->monthlySeries($request, 6);
        $topProducts = SaleItem::query()->join('sales', 'sales.id', '=', 'sale_items.sale_id')->where('sales.status', 'paid')->when($own, fn ($q) => $q->where('sales.cashier_id', $request->user()->id))->selectRaw('sale_items.product_name, SUM(sale_items.quantity) quantity, SUM(sale_items.total_price) total')->groupBy('sale_items.product_name')->orderByDesc('quantity')->limit(7)->get();
        $notifications = $request->user()->isAdmin() ? AdminNotification::latest('created_at')->limit(6)->get() : collect();

        return view('dashboard', compact('stats', 'recent', 'daily', 'monthly', 'topProducts', 'notifications'));
    }

    private function dailySeries(Request $request, int $days): Collection
    {
        $rows = Sale::selectRaw('DATE(sale_date) period, SUM(total_amount) total')->where('status', 'paid')->where('sale_date', '>=', now()->subDays($days - 1)->startOfDay())->when(! $request->user()->isAdmin(), fn ($q) => $q->where('cashier_id', $request->user()->id))->groupBy('period')->pluck('total', 'period');

        return collect(range($days - 1, 0))->map(fn ($offset) => ['label' => today()->subDays($offset)->format('M d'), 'total' => (float) ($rows[today()->subDays($offset)->format('Y-m-d')] ?? 0)]);
    }

    private function monthlySeries(Request $request, int $months): Collection
    {
        $rows = Sale::selectRaw("DATE_FORMAT(sale_date, '%Y-%m') period, SUM(total_amount) total")->where('status', 'paid')->where('sale_date', '>=', now()->subMonths($months - 1)->startOfMonth())->when(! $request->user()->isAdmin(), fn ($q) => $q->where('cashier_id', $request->user()->id))->groupBy('period')->pluck('total', 'period');

        return collect(range($months - 1, 0))->map(fn ($offset) => ['label' => today()->subMonths($offset)->format('M Y'), 'total' => (float) ($rows[today()->subMonths($offset)->format('Y-m')] ?? 0)]);
    }
}
