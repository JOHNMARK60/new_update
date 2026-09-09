<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\SalesReportPdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$from, $to] = $this->period($request);
        $query = $this->query($request, $from, $to);
        $saleIds = (clone $query)->select('sales.id');
        $summary = (clone $query)->selectRaw('COUNT(*) transactions, COALESCE(SUM(CASE WHEN status = "paid" THEN total_amount ELSE 0 END),0) sales, COALESCE(SUM(CASE WHEN status = "paid" AND payment_method = "cash" THEN total_amount ELSE 0 END),0) cash, COALESCE(SUM(status = "void"),0) voids')->first();
        $itemSummary = SaleItem::query()->join('products', 'products.id', '=', 'sale_items.product_id')->leftJoin('categories', 'categories.id', '=', 'products.category_id')->whereIn('sale_items.sale_id', clone $saleIds)->selectRaw("sale_items.product_name, COALESCE(categories.name,'Uncategorized') category_name, SUM(sale_items.quantity) quantity, SUM(sale_items.total_price) total")->groupBy('sale_items.product_name', 'categories.name')->orderByDesc('quantity')->get();
        $summary->items = (int) $itemSummary->sum('quantity');
        $payments = Payment::whereIn('sale_id', clone $saleIds)->selectRaw('payment_method, COUNT(*) transactions, SUM(amount) amount, SUM(tendered_amount) tendered, SUM(change_amount) change_total')->groupBy('payment_method')->get();
        $cashierPerformance = Sale::whereIn('id', clone $saleIds)->selectRaw('cashier_name, COUNT(*) transactions, SUM(total_amount) total')->groupBy('cashier_name')->orderByDesc('total')->get();
        $sales = $query->with('items')->latest('sale_date')->paginate(25)->withQueryString();

        return view('reports.index', ['sales' => $sales, 'summary' => $summary, 'itemSummary' => $itemSummary, 'payments' => $payments, 'cashierPerformance' => $cashierPerformance, 'cashiers' => User::where('role', 'cashier')->orderBy('first_name')->get(), 'products' => Product::orderBy('name')->get(), 'categories' => Category::orderBy('name')->get(), 'from' => $from, 'to' => $to]);
    }

    public function csv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->period($request);
        $sales = $this->query($request, $from, $to)->with('items')->latest('sale_date')->get();

        return response()->streamDownload(function () use ($sales) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Receipt', 'Date', 'Cashier', 'Product', 'Quantity', 'Unit Price', 'Line Total', 'Sale Total', 'Status']);
            foreach ($sales as $sale) {
                foreach ($sale->items as $item) {
                    fputcsv($out, [$sale->receipt_no, $sale->sale_date, $sale->cashier_name, $item->product_name, $item->quantity, $item->unit_price, $item->total_price, $sale->total_amount, $sale->status]);
                }
            }
            fclose($out);
        }, 'kanto-sales-'.$from.'-'.$to.'.csv', ['Content-Type' => 'text/csv']);
    }

    public function pdf(Request $request, SalesReportPdf $pdf): Response
    {
        [$from, $to] = $this->period($request);
        $query = $this->query($request, $from, $to);
        $ids = (clone $query)->select('sales.id');
        $items = SaleItem::whereIn('sale_id', $ids)->selectRaw('product_name, SUM(quantity) quantity, SUM(total_price) total')->groupBy('product_name')->orderByDesc('quantity')->get()->toArray();
        $total = (float) $query->sum('total_amount');

        return response($pdf->render($items, $from, $to, $total, $request->user()->name), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="kanto-sales-'.$from.'-'.$to.'.pdf"']);
    }

    private function query(Request $request, string $from, string $to): Builder
    {
        return Sale::query()->whereDate('sale_date', '>=', $from)->whereDate('sale_date', '<=', $to)
            ->when(! $request->user()->isAdmin(), fn ($q) => $q->where('cashier_id', $request->user()->id))
            ->when($request->cashier_id && $request->user()->isAdmin(), fn ($q) => $q->where('cashier_id', $request->integer('cashier_id')))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->product_id, fn ($q) => $q->whereHas('items', fn ($items) => $items->where('product_id', $request->integer('product_id'))))
            ->when($request->category_id, fn ($q) => $q->whereHas('items.product', fn ($products) => $products->where('category_id', $request->integer('category_id'))));
    }

    private function period(Request $request): array
    {
        if ($request->date_from || $request->date_to) {
            return [$request->date_from ?: $request->date_to, $request->date_to ?: $request->date_from];
        }
        $date = Carbon::parse($request->date ?: today());

        return match ($request->report_type) {
            'weekly' => [$date->copy()->startOfWeek()->toDateString(), $date->copy()->endOfWeek()->toDateString()],
            'monthly' => [$date->copy()->startOfMonth()->toDateString(), $date->copy()->endOfMonth()->toDateString()],
            'yearly' => [$date->copy()->startOfYear()->toDateString(), $date->copy()->endOfYear()->toDateString()],
            default => [$date->toDateString(), $date->toDateString()],
        };
    }
}
