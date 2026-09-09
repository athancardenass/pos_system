<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with(['category', 'supplier', 'inventory'])
            ->orderBy('product_name')
            ->paginate(15);

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        return view('products.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $product = Product::query()->create($data);

        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 0,
        ]);

        AuditLogger::record('create', 'product', $product->product_id, 'Created product '.$product->product_name);

        return redirect()->route('products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product): View
    {
        return view('products.edit', array_merge($this->formData(), compact('product')));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        AuditLogger::record('update', 'product', $product->product_id, 'Updated product '.$product->product_name);

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->isInUse()) {
            return back()->with('error', 'Cannot delete a product that has sales or purchase history.');
        }

        $id = $product->product_id;
        $name = $product->product_name;
        $product->delete();

        AuditLogger::record('delete', 'product', $id, 'Deleted product '.$name);

        return redirect()->route('products.index')->with('status', 'Product deleted.');
    }

    private function formData(): array
    {
        return [
            'categories' => Category::query()->orderBy('category_name')->get(),
            'suppliers' => Supplier::query()->orderBy('supplier_name')->get(),
        ];
    }

    /**
     * Return a unique, EAN-13-shaped 13-digit barcode not already used.
     * Used by the "Generate" button on the product form so staff never
     * hand-type a barcode. Loops until the code is unique in the table.
     */
    public function generateBarcode(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['barcode' => Product::generateBarcode()]);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'category_id' => 'nullable|exists:category,category_id',
            'supplier_id' => 'nullable|exists:supplier,supplier_id',
            'product_name' => 'required|string|max:150',
            'description' => 'nullable|string|max:255',
            'barcode' => [
                'required',
                'string',
                'max:50',
                'unique:product,barcode,' . ($product?->product_id ?? 'NULL') . ',product_id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (preg_match('/^\d{13}$/', $value)) {
                        $sum = 0;
                        for ($i = 0; $i < 12; $i++) {
                            $sum += (int) $value[$i] * ($i % 2 === 0 ? 1 : 3);
                        }
                        $check = (10 - ($sum % 10)) % 10;
                        if ($check !== (int) $value[12]) {
                            $fail('The barcode is not a valid EAN-13 (check digit is wrong). Use the Generate button to create a valid one.');
                        }
                    }
                },
            ],
            'unit_price' => 'required|numeric|min:0',
            'cost_price' => 'required|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
        ]);
    }
}
