<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Product;
use App\Models\SaleTransaction;
use App\Services\Integration\CRM\CustomerService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function findReceipt(string $query): ?SaleTransaction
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }
        $sales = SaleTransaction::with(['receipt', 'employee', 'saleDetails.product']);
        if (ctype_digit($query) && filter_var($query, FILTER_VALIDATE_INT) !== false) {
            return $sales->find((int) $query);
        }

        return $sales->whereHas('receipt', fn ($q) => $q->where('receipt_number', $query))->first();
    }

    /**
     * Prepare catalog and discount data required for the POS checkout terminal.
     *
     * @return array{
     *     products: Collection,
     *     categories: Collection,
     *     discounts: Collection,
     *     productsJson: Collection,
     *     recentReceipts: Collection,
     * }
     */
    public function getPosIndexData(): array
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with(['inventory', 'category', 'inventoryMapping'])
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
            'sku' => $p->inventoryMapping?->inventory_sku,
            'category_id' => $p->category_id,
            'category_name' => $p->category?->category_name ?? 'General',
        ]);

        try {
            $stocks = $this->getProductStockSnapshot();
            $productsJson = $productsJson->map(fn (array $product) => array_replace($product, [
                'stock' => $stocks[$product['id']]['stock'] ?? 0,
            ]));
        } catch (ValidationException) {
            // An offline source must never advertise stale stock as sellable.
            $productsJson = $productsJson->map(fn (array $product) => array_replace($product, ['stock' => 0]));
        }

        $recentReceipts = SaleTransaction::query()
            ->with(['receipt', 'employee'])
            ->where('status', 'completed')
            ->whereHas('receipt')
            ->orderByDesc('transaction_date')
            ->limit(5)
            ->get();

        return [
            'products' => $products,
            'categories' => $categories,
            'discounts' => $discounts,
            'productsJson' => $productsJson,
            'recentReceipts' => $recentReceipts,
        ];
    }

    /**
     * Search the CRM directory using its stable external identity.
     *
     * @return list<array{id: int, name: string, contact: ?string, points: int}>
     */
    public function searchPosCustomers(string $search, int $limit = 20): array
    {
        if (blank(config('services.crm.token')) || blank(config('services.crm.base_url'))) {
            return [];
        }

        // Held carts retain POS's integer key, rather than a CRM customer code.
        if (ctype_digit($search)) {
            $cached = Customer::query()->where('customer_id', $search)
                ->whereNotNull('crm_external_id')->where('customer_status', 'active')->first();
            if ($cached) {
                return [[
                    'id' => (int) $cached->customer_id, 'name' => $cached->fullName(),
                    'external_id' => $cached->crm_external_id,
                    'contact' => $cached->contact_number, 'points' => (int) $cached->loyalty_points,
                ]];
            }
        }

        $result = app(CustomerService::class)->searchReadOnly($search);
        if ($result['status'] !== 200) {
            throw ValidationException::withMessages([
                'customer' => 'CRM customer search is unavailable. Try again.',
            ]);
        }

        $ids = Customer::query()->whereIn('crm_external_id', array_column($result['customers'], 'external_id'))
            ->pluck('customer_id', 'crm_external_id');

        return array_map(static fn (array $customer): array => [
            'id' => $ids[$customer['external_id']] ?? null,
            'external_id' => $customer['external_id'],
            'name' => $customer['display_name'],
            'contact' => null,
            'points' => $customer['loyalty']['points'],
        ], array_slice($result['customers'], 0, $limit));
    }

    public function getProductStockSnapshot(bool $includeInactive = false): array
    {
        $live = app(\App\Services\Integration\Inventory\LiveStockService::class);
        $snapshot = $live->snapshot();
        return Product::query()
            ->when(! $includeInactive, fn ($query) => $query->where('is_active', true))
            ->with('inventory')
            ->get()
            ->mapWithKeys(fn (Product $product) => [
                (int) $product->product_id => [
                    'stock' => $live->configured()
                        ? $live->available((int) $product->product_id, $snapshot)
                        : $product->stockQuantity(),
                    'unit_of_measure' => $product->unit_of_measure ?? 'piece',
                ],
            ])
            ->all();
    }

    /**
     * Eager-load POS-owned relations for receipt rendering and refund interaction without N+1 queries.
     */
    public function loadSaleForReceipt(SaleTransaction $saleTransaction): SaleTransaction
    {
        $saleTransaction->load([
            'employee',
            'discount',
            'payment',
            'receipt',
            'refunds.employee',
            'refunds.items',
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
