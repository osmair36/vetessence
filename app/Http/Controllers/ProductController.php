<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Services\DocumentPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:estoque');
    }

    public function index(Request $request)
    {
        $query = Product::with(['category', 'supplier']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%")
                  ->orWhere('barcode', 'like', "%{$request->search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('name')->paginate(20);

        $categories = Category::where('type', 'product')->orderBy('name')->get();

        return view('products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return redirect()->route('products.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'active_ingredient' => 'nullable|string|max:200',
            'target_species' => 'nullable|string|max:50',
            'reapplication_interval' => 'nullable|integer|min:1|max:3650',
            'reapplication_unit' => 'nullable|in:days,weeks,months|required_with:reapplication_interval',
            'is_active' => 'boolean',
        ]);

        if (!\Illuminate\Support\Facades\Schema::hasColumn('products', 'reapplication_interval')) {
            unset($validated['active_ingredient'], $validated['target_species'], $validated['reapplication_interval'], $validated['reapplication_unit']);
        }

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Produto cadastrado com sucesso!');
    }

    public function show(Product $product)
    {
        $product->load(['category', 'supplier', 'movements.user']);
        return view('products.show', compact('product'));
    }

    public function edit($product)
    {
        return redirect()->route('products.index');
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'active_ingredient' => 'nullable|string|max:200',
            'target_species' => 'nullable|string|max:50',
            'reapplication_interval' => 'nullable|integer|min:1|max:3650',
            'reapplication_unit' => 'nullable|in:days,weeks,months|required_with:reapplication_interval',
            'is_active' => 'boolean',
        ]);

        if (!\Illuminate\Support\Facades\Schema::hasColumn('products', 'reapplication_interval')) {
            unset($validated['active_ingredient'], $validated['target_species'], $validated['reapplication_interval'], $validated['reapplication_unit']);
        }

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy(Product $product)
    {
        if ($product->stock > 0) {
            return back()->with('error', 'Não é possível excluir produto com estoque.');
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produto excluído com sucesso!');
    }

    public function inventoryPrint(Request $request)
    {
        $query = Product::with(['category', 'supplier']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('name')->get();

        return view('products.inventory-print', [
            'products' => $products,
            'pdfUrl' => route('products.inventory.pdf', $request->query()),
        ]);
    }

    public function inventoryPdf(Request $request)
    {
        $query = Product::with(['category', 'supplier']);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('name')->get();
        $filename = 'inventario-estoque-' . date('Y-m-d') . '.pdf';

        return DocumentPdfService::download('products.inventory-print', [
            'products' => $products,
        ], $filename, 'landscape');
    }
}
