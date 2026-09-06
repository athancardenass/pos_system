<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SaleDetail;
use App\Models\SaleRefund;
use App\Models\SaleTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public const TYPES = [
        'sales' => 'Sales report',
        'products' => 'Top selling products',
        'inventory' => 'Inventory & stock levels',
        'refunds' => 'Refunds report',
    ];

    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        return view('reports.index', [
            'types' => self::TYPES,
            'from' => $from,
            'to' => $to,
            'previews' => [
                'sales' => $this->salesRows($from, $to),
                'products' => $this->productRows($from, $to),
                'inventory' => $this->inventoryRows(),
                'refunds' => $this->refundRows($from, $to),
            ],
        ]);
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        abort_unless(array_key_exists($type, self::TYPES), 404, 'Unknown report type.');

        [$from, $to] = $this->range($request);

        [$headers, $rows, $filename] = match ($type) {
            'sales' => [
                ['Transaction ID', 'Date', 'Cashier', 'Customer', 'Payment', 'Subtotal', 'Discount', 'Total', 'Status'],
                $this->salesRows($from, $to),
                "sales_{$from->format('Ymd')}_{$to->format('Ymd')}.csv",
            ],
            'products' => [
                ['Product', 'Category', 'Units Sold', 'Revenue'],
                $this->productRows($from, $to),
                "top_products_{$from->format('Ymd')}_{$to->format('Ymd')}.csv",
            ],
            'inventory' => [
                ['Product', 'Category', 'Price', 'Cost', 'Stock', 'Reorder Level', 'Status'],
                $this->inventoryRows(),
                'inventory_'.now()->format('Ymd').'.csv',
            ],
            'refunds' => [
                ['Refund ID', 'Sale ID', 'Date', 'Processed By', 'Amount', 'Type', 'Reason', 'Notes'],
                $this->refundRows($from, $to),
                "refunds_{$from->format('Ymd')}_{$to->format('Ymd')}.csv",
            ],
        };

        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads ₱ correctly
            fputcsv($out, $headers, ',', '"', '');
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => is_string($v) ? strip_tags($v) : $v, $row), ',', '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function range(Request $request): array
    {
        $data = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : Carbon::today()->endOfDay();
        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : $to->copy()->subDays(29)->startOfDay();

        return [$from, $to];
    }

    private function salesRows(Carbon $from, Carbon $to): array
    {
        return SaleTransaction::query()
            ->with(['employee', 'customer', 'discount'])
            ->whereBetween('transaction_date', [$from, $to])
            ->orderBy('transaction_date')
            ->get()
            ->map(fn (SaleTransaction $s) => [
                $s->transaction_id,
                $s->transaction_date?->format('Y-m-d H:i'),
                $s->employee?->username ?? '—',
                $s->customer?->fullName() ?? 'Walk-in',
                $s->payment_method,
                number_format((float) $s->subtotal, 2, '.', ''),
                $s->discount ? $s->discount->discount_name : '—',
                number_format((float) $s->total_amount, 2, '.', ''),
                $s->status,
            ])->all();
    }

    private function productRows(Carbon $from, Carbon $to): array
    {
        return SaleDetail::query()
            ->join('sale_transaction', 'sale_details.transaction_id', '=', 'sale_transaction.transaction_id')
            ->join('product', 'sale_details.product_id', '=', 'product.product_id')
            ->leftJoin('category', 'product.category_id', '=', 'category.category_id')
            ->where('sale_transaction.status', 'completed')
            ->whereBetween('sale_transaction.transaction_date', [$from, $to])
            ->select(
                'product.product_name',
                'category.category_name',
                DB::raw('SUM(sale_details.quantity) as units'),
                DB::raw('SUM(sale_details.subtotal) as revenue'),
            )
            ->groupBy('product.product_id', 'product.product_name', 'category.category_name')
            ->orderByDesc('units')
            ->get()
            ->map(fn ($r) => [
                $r->product_name,
                $r->category_name ?? '—',
                (int) $r->units,
                number_format((float) $r->revenue, 2, '.', ''),
            ])->all();
    }

    private function inventoryRows(): array
    {
        return Product::query()
            ->with(['category', 'inventory'])
            ->orderBy('product_name')
            ->get()
            ->map(function (Product $p) {
                $stock = $p->stockQuantity();
                $status = $stock === 0 ? 'Out of stock' : ($stock <= $p->reorder_level ? 'Low stock' : 'OK');

                return [
                    $p->product_name,
                    $p->category?->category_name ?? '—',
                    number_format((float) $p->unit_price, 2, '.', ''),
                    number_format((float) $p->cost_price, 2, '.', ''),
                    $stock,
                    (int) $p->reorder_level,
                    $status,
                ];
            })->all();
    }

    private function refundRows(Carbon $from, Carbon $to): array
    {
        return SaleRefund::query()
            ->with(['employee', 'sale'])
            ->whereBetween('refunded_at', [$from, $to])
            ->orderByDesc('refunded_at')
            ->get()
            ->map(fn (SaleRefund $r) => [
                $r->refund_id,
                $r->transaction_id,
                $r->refunded_at?->format('Y-m-d H:i'),
                $r->employee?->username ?? '—',
                number_format((float) $r->refund_amount, 2, '.', ''),
                $r->is_full_refund ? 'Full' : 'Partial',
                \App\Services\RefundService::REASONS[$r->reason] ?? $r->reason,
                $r->notes ?? '—',
            ])->all();
    }
}
