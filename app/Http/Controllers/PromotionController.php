<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manager-only CRUD for promotion rules. Thin by design: no discount math happens here —
 * PromotionService owns every calculation (this controller only validates + persists).
 */
class PromotionController extends Controller
{
    public function index(): View
    {
        // Scope names are resolved from lookup maps so the list never runs a query per row.
        $products = Product::query()->orderBy('product_name')->pluck('product_name', 'product_id');
        $categories = Category::query()->orderBy('category_name')->pluck('category_name', 'category_id');

        return view('promotions.index', [
            'promotions' => Promotion::query()->orderBy('name')->paginate(15),
            'productNames' => $products,
            'categoryNames' => $categories,
        ]);
    }

    public function create(): View
    {
        return view('promotions.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $promotion = Promotion::query()->create($this->validated($request));

        AuditLogger::record('create', 'promotion', $promotion->promotion_id, 'Created promotion '.$promotion->name);

        return redirect()->route('promotions.index')->with('status', 'Promotion created.');
    }

    public function edit(Promotion $promotion): View
    {
        return view('promotions.edit', ['promotion' => $promotion] + $this->formOptions());
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($this->validated($request));

        AuditLogger::record('update', 'promotion', $promotion->promotion_id, 'Updated promotion '.$promotion->name);

        return redirect()->route('promotions.index')->with('status', 'Promotion updated.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        // sale_promotion.promotion_id is ON DELETE RESTRICT — history is never destroyed.
        if ($promotion->appliedOnSales()->exists()) {
            return back()->with('error', 'Cannot delete a promotion that has been applied to a sale. Deactivate it instead.');
        }

        $id = $promotion->promotion_id;
        $name = $promotion->name;
        $promotion->delete();

        AuditLogger::record('delete', 'promotion', $id, 'Deleted promotion '.$name);

        return redirect()->route('promotions.index')->with('status', 'Promotion deleted.');
    }

    /**
     * @return array{products: \Illuminate\Support\Collection, categories: \Illuminate\Support\Collection}
     */
    private function formOptions(): array
    {
        return [
            'products' => Product::query()->orderBy('product_name')->get(['product_id', 'product_name']),
            'categories' => Category::query()->orderBy('category_name')->get(['category_id', 'category_name']),
        ];
    }

    /**
     * Type-conditional rules: each family of fields is required only for the type that
     * uses it, so one form serves all four promotion types.
     */
    private function validated(Request $request): array
    {
        // scope_id and value are type/scope dependent, so their rule SETS are built first.
        $scopeIdRules = match ((string) $request->input('scope')) {
            'product' => ['required', 'integer', 'exists:product,product_id'],
            'category' => ['required', 'integer', 'exists:category,category_id'],
            // A cart-wide rule targets the whole basket, so it must NOT carry a scope_id.
            default => ['nullable'],
        };

        // Only compare the window when both ends exist (starts_at is optional).
        $endsAtRules = ['nullable', 'date'];
        if ($request->filled('starts_at')) {
            $endsAtRules[] = 'after:starts_at';
        }

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:'.implode(',', Promotion::TYPES),
            'scope' => 'required|in:'.implode(',', Promotion::SCOPES),
            'scope_id' => $scopeIdRules,
            'value' => [
                'nullable',
                'numeric',
                fn (string $attribute, mixed $value, Closure $fail) => $this->checkValue($request, $value, $fail),
            ],
            'bundle_qty' => 'required_if:type,bundle_price|nullable|integer|min:1',
            'bundle_price' => 'required_if:type,bundle_price|nullable|numeric|min:0.01',
            'x_qty' => 'required_if:type,buy_x_get_y|nullable|integer|min:1',
            'y_qty' => 'required_if:type,buy_x_get_y|nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => $endsAtRules,
            'is_active' => 'required|boolean',
        ]);

        // Only percentage/fixed use `value`; clear it for the quantity-driven types so a
        // stale edit cannot leave a half-configured rule behind. Same for scope_id.
        if (! in_array($data['type'], ['percentage', 'fixed'], true)) {
            $data['value'] = null;
        }

        if ($data['scope'] === 'cart') {
            $data['scope_id'] = null;
        }

        return $data;
    }

    /**
     * value is 0.01-100 for percentage, >= 0.01 for fixed, unused otherwise.
     */
    private function checkValue(Request $request, mixed $value, Closure $fail): void
    {
        $type = $request->input('type');

        if (in_array($type, ['percentage', 'fixed'], true)) {
            if ($value === null || $value === '') {
                $fail('A percentage or fixed promotion needs a value.');

                return;
            }

            if ((float) $value < 0.01) {
                $fail('A promotion value must be at least 0.01.');

                return;
            }
        }

        if ($type === 'percentage' && $value !== null && $value !== '' && (float) $value > 100) {
            $fail('A percentage promotion cannot exceed 100%.');
        }
    }
}
