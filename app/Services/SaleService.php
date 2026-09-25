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
     *     customers: Collection,
     *     discounts: Collection,
     *     productsJson: Collection,
     *     customersJson: Collection
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

        $customers = Customer::query()
            ->where('customer_status', 'active')
            ->orderBy('last_name')
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
            'barcode' => $p->barcode,
            'category_id' => $p->category_id,
            'category_name' => $p->category?->category_name ?? 'General',
        ]);

        $customersJson = $customers->map(fn (Customer $c) => [
            'id' => $c->customer_id,
            'name' => $c->fullName(),
            'contact' => $c->contact_number,
            'points' => (int) $c->loyalty_points,
        ]);

        return [
            'products' => $products,
            'categories' => $categories,
            'customers' => $customers,
            'discounts' => $discounts,
            'productsJson' => $productsJson,
            'customersJson' => $customersJson,
        ];
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
