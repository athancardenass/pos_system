<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Product;
use App\Models\SaleTransaction;
use Illuminate\Support\Collection;

class SaleService
{
    /**
     * Prepare catalog, customer, and discount data required for the POS checkout terminal.
     *
     * @return array{
     *     products: Collection,
     *     categories: Collection,
     *     discounts: Collection,
     *     productsJson: Collection,
     * }
     */
    public function getPosIndexData(): array
    {
        $products = Product::query()
            ->with(['inventory', 'category'])
            ->orderBy('product_name')
            ->get();

        $categories = Category::query()
            ->orderBy('category_name')
            ->get();

        $discounts = Discount::query()
            ->orderBy('discount_name')
            ->get()
            ->filter(fn (Discount $discount) => $discount->isActive())
            ->values();

        $productsJson = $products->map(fn (Product $p) => [
            'id' => $p->product_id,
            'name' => $p->product_name,
            'price' => (float) $p->unit_price,
            'stock' => $p->stockQuantity(),
            'reorder_level' => (float) ($p->reorder_level ?? 0),
            'critical_reorder_level' => (float) ($p->critical_reorder_level ?? 0),
            'unit_of_measure' => $p->unit_of_measure ?? 'piece',
            'barcode' => $p->barcode,
            'category_id' => $p->category_id,
            'category_name' => $p->category?->category_name ?? 'General',
        ]);

        return [
            'products' => $products,
            'categories' => $categories,
            'discounts' => $discounts,
            'productsJson' => $productsJson,
        ];
    }

    /** @return list<array{id: int, name: string, contact: ?string, points: int}> */
    public function searchPosCustomers(string $search, int $limit = 20): array
    {
        $search = trim($search);
        $tokens = array_values(array_filter(preg_split('/\s+/', $search) ?: []));

        if ($search === '') {
            return [];
        }

        return Customer::query()
            ->select(['customer_id', 'first_name', 'last_name', 'contact_number', 'email', 'loyalty_points'])
            ->where('customer_status', 'active')
            ->where(function ($matches) use ($search, $tokens): void {
                $pattern = '%'.$search.'%';
                $matches->whereRaw('CAST(customer_id AS CHAR) LIKE ?', [$pattern])
                    ->orWhere('contact_number', 'like', $pattern)
                    ->orWhere('email', 'like', $pattern)
                    ->orWhere(function ($nameMatches) use ($tokens): void {
                        foreach ($tokens as $token) {
                            $tokenPattern = '%'.$token.'%';
                            $nameMatches->where(function ($tokenMatches) use ($tokenPattern): void {
                                $tokenMatches->where('first_name', 'like', $tokenPattern)
                                    ->orWhere('last_name', 'like', $tokenPattern);
                            });
                        }
                    });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(max(1, min($limit, 20)))
            ->get()
            ->map(fn (Customer $customer): array => [
                'id' => (int) $customer->customer_id,
                'name' => $customer->fullName(),
                'contact' => $customer->contact_number ?: $customer->email,
                'points' => (int) $customer->loyalty_points,
            ])
            ->all();
    }

    /** @return array<int, array{stock: float, unit_of_measure: string}> */
    public function getProductStockSnapshot(): array
    {
        return Product::query()
            ->where('is_active', true)
            ->with('inventory')
            ->get()
            ->mapWithKeys(fn (Product $product) => [
                (int) $product->product_id => [
                    'stock' => $product->stockQuantity(),
                    'unit_of_measure' => $product->unit_of_measure ?? 'piece',
                ],
            ])
            ->all();
    }

    /**
     * Eager-load relations for receipt rendering and refund interaction without N+1 queries.
     */
    public function loadSaleForReceipt(SaleTransaction $saleTransaction): SaleTransaction
    {
        $saleTransaction->load([
            'customer',
            'employee',
            'discount',
            'payment',
            'receipt',
            'refunds.employee',
            'refunds.items',
            'appliedPromotions.promotion',
            'couponRedemptions.coupon',
            'saleDetails' => fn ($query) => $query
                ->with('product')
                ->withSum('refundItems as refunded_qty', 'quantity'),
        ]);

        return $saleTransaction;
    }

    /**
     * Compute days remaining within the standard refund window.
     */
    public function refundDaysRemaining(SaleTransaction $sale): int
    {
        $daysOld = $sale->transaction_date
            ? (int) $sale->transaction_date->diffInDays(now())
            : 0;

        return max(0, RefundService::WINDOW_DAYS - $daysOld);
    }

    /**
     * Determine whether the sale is outside the standard refund policy window.
     */
    public function isOutsideRefundWindow(SaleTransaction $sale): bool
    {
        $daysOld = $sale->transaction_date
            ? (int) $sale->transaction_date->diffInDays(now())
            : 0;

        return $daysOld > RefundService::WINDOW_DAYS;
    }
}
