<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Manager-only CRUD for digital coupons. Thin: validation + persistence only.
 * Whether a code may still be spent is PromotionService's job.
 */
class CouponController extends Controller
{
    public function index(): View
    {
        return view('coupons.index', [
            'coupons' => Coupon::query()->orderBy('code')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('coupons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $coupon = Coupon::query()->create($this->validated($request));

        AuditLogger::record('create', 'coupon', $coupon->coupon_id, 'Created coupon '.$coupon->code);

        return redirect()->route('coupons.index')->with('status', 'Coupon created.');
    }

    public function edit(Coupon $coupon): View
    {
        return view('coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $coupon->update($this->validated($request, $coupon));

        AuditLogger::record('update', 'coupon', $coupon->coupon_id, 'Updated coupon '.$coupon->code);

        return redirect()->route('coupons.index')->with('status', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        // coupon_redemption.coupon_id is ON DELETE RESTRICT — a spent code keeps its history.
        if ($coupon->redemptions()->exists()) {
            return back()->with('error', 'Cannot delete a coupon that has already been redeemed. Deactivate it instead.');
        }

        $id = $coupon->coupon_id;
        $code = $coupon->code;
        $coupon->delete();

        AuditLogger::record('delete', 'coupon', $id, 'Deleted coupon '.$code);

        return redirect()->route('coupons.index')->with('status', 'Coupon deleted.');
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        // Codes are matched case-insensitively at the register, so they are canonicalised
        // to uppercase BEFORE the uniqueness check (and the model mutator keeps it that way).
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        // Only compare the window when both ends exist (starts_at is optional).
        $endsAtRules = ['nullable', 'date'];
        if ($request->filled('starts_at')) {
            $endsAtRules[] = 'after:starts_at';
        }

        return $request->validate([
            'code' => [
                'required', 'string', 'max:40',
                // Regex: letters/digits only, no spaces — a code with spaces is unreadable at a register.
                'regex:/^[A-Z0-9\-]+$/',
                Rule::unique('coupon', 'code')->ignore($coupon?->coupon_id, 'coupon_id'),
            ],
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:'.implode(',', Coupon::TYPES),
            'value' => [
                'required',
                'numeric',
                fn (string $attribute, mixed $value, Closure $fail) => match ($request->input('type')) {
                    'percentage' => (float) $value > 100
                        ? $fail('A percentage coupon cannot exceed 100%.')
                        : ((float) $value < 0.01 ? $fail('A coupon value must be at least 0.01.') : null),
                    'fixed' => (float) $value < 0.01
                        ? $fail('A coupon value must be at least 0.01.')
                        : null,
                    default => null,
                },
            ],
            'min_purchase' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'per_customer_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => $endsAtRules,
            'is_active' => 'required|boolean',
        ]);
    }
}
