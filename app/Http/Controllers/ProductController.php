<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with('category', 'supplier')
            ->when($request->q, fn ($query, $term) => $query->where(fn ($nested) => $nested->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$term}%"))->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$term}%"))))
            ->when($request->category_id, fn ($query, $id) => $query->where('category_id', $id))
            ->when($request->supplier_id, fn ($query, $id) => $query->where('supplier_id', $id))
            ->orderBy('name')->paginate(18)->withQueryString();

        return view('products.index', ['products' => $products, 'categories' => Category::withCount('products')->orderBy('name')->get(), 'suppliers' => Supplier::orderBy('name')->get()]);
    }

    public function show(Product $product): View
    {
        return view('products.show', ['product' => $product->load('category', 'supplier')]);
    }

    public function create(): View
    {
        return view('products.form', $this->formData(new Product));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data = $this->image($request, $data);
        $product = Product::create($data);
        $this->log($product, 0, $product->quantity, 'Created product');

        return redirect()->route('products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('products.form', $this->formData($product));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $before = $product->quantity;
        $data = $this->image($request, $this->validated($request), $product);
        $product->update($data);
        $this->log($product, $before, $product->quantity, 'Updated product');

        return redirect()->route('products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    private function validated(Request $r): array
    {
        return $r->validate(['name' => 'required|max:150', 'sku' => 'nullable|max:50|unique:products,sku,'.($r->route('product')?->id ?? 'NULL'), 'category_id' => 'nullable|exists:categories,id', 'supplier_id' => 'nullable|exists:suppliers,id', 'price' => 'required|numeric|min:0', 'quantity' => 'required|integer|min:0', 'low_stock_level' => 'required|integer|min:0', 'expiration_date' => 'nullable|date', 'image' => 'nullable|image|max:3072']);
    }

    private function image(Request $r, array $data, ?Product $product = null): array
    {
        unset($data['image']);
        if ($r->hasFile('image')) {
            $name = uniqid('product_', true).'.'.$r->file('image')->extension();
            $r->file('image')->move(public_path('assets/uploads/products'), $name);
            $data['image_path'] = 'assets/uploads/products/'.$name;
        }

        return $data;
    }

    private function formData(Product $product): array
    {
        return compact('product') + ['categories' => Category::orderBy('name')->get(), 'suppliers' => Supplier::orderBy('name')->get()];
    }

    private function log(Product $p, int $before, int $after, string $action): void
    {
        InventoryLog::create(['product_id' => $p->id, 'action' => $action, 'quantity_change' => $after - $before, 'stock_before' => $before, 'stock_after' => $after, 'reference_type' => 'product', 'reference_id' => $p->id, 'created_by' => auth()->id(), 'created_at' => now()]);
    }
}
