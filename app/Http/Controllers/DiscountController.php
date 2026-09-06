<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscountController extends Controller
{
    public function index(): View
    {
        return view('discounts.index', [
            'discounts' => Discount::query()->orderBy('discount_name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('discounts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $discount = Discount::query()->create($this->validated($request));

        AuditLogger::record('create', 'discount', $discount->discount_id, 'Created discount '.$discount->discount_name);

        return redirect()->route('discounts.index')->with('status', 'Discount created.');
    }

    public function edit(Discount $discount): View
    {
        return view('discounts.edit', compact('discount'));
    }

    public function update(Request $request, Discount $discount): RedirectResponse
    {
        $discount->update($this->validated($request));

        AuditLogger::record('update', 'discount', $discount->discount_id, 'Updated discount '.$discount->discount_name);

        return redirect()->route('discounts.index')->with('status', 'Discount updated.');
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        if ($discount->saleTransactions()->exists()) {
            return back()->with('error', 'Cannot delete a discount that has been used on a sale.');
        }

        $id = $discount->discount_id;
        $name = $discount->discount_name;
        $discount->delete();

        AuditLogger::record('delete', 'discount', $id, 'Deleted discount '.$name);

        return redirect()->route('discounts.index')->with('status', 'Discount deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'discount_name' => 'required|string|max:100',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                fn (string $attribute, mixed $value, Closure $fail) => match ($request->input('discount_type')) {
                    'percentage' => $value > 100
                        ? $fail('A percentage discount cannot exceed 100%.')
                        : null,
                    'fixed' => null,
                    default => null,
                },
            ],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
    }
}
