<?php

namespace App\Services\Integration\Inventory;

use App\Services\Integration\ExternalSystemInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ProductLookupService implements ExternalSystemInterface
{
    public function systemName(): string
    {
        return 'inventory';
    }

    /**
     * Look up one Inventory product by its exact SKU. The result is read-only
     * and is not mapped to a POS product_id.
     *
     * @return array{status: int, product: array{external_id: string, sku: string, barcode: string|null, name: string, shelf_qty: float, backroom_qty: float, on_hand: float, status: string}|null, total: int}
     */
    public function lookupBySku(string $sku): array
    {
        $baseUrl = rtrim(trim((string) config('services.inventory.base_url')), '/');
        $token = trim((string) config('services.inventory.token'));

        if ($baseUrl === '' || $token === '' || !\App\Services\Integration\Endpoint::allowed($baseUrl, 'inventory')) {
            return ['status' => 503, 'product' => null, 'total' => 0];
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(2)
                ->timeout(5)
                ->withOptions(['verify' => true, 'allow_redirects' => false])
                ->get($baseUrl.'/api/v1/catalog/products', [
                    'sku' => $sku,
                    'page' => 1,
                    'per_page' => 2,
                ]);
        } catch (ConnectionException) {
            return ['status' => 504, 'product' => null, 'total' => 0];
        }

        if (! $response->successful()) {
            return ['status' => 502, 'product' => null, 'total' => 0];
        }

        $payload = $response->json();
        $total = is_array($payload)
            ? filter_var(data_get($payload, 'meta.total'), FILTER_VALIDATE_INT)
            : false;

        if (! is_array($payload) || ! is_array($payload['data'] ?? null) || $total === false || $total < 0) {
            return ['status' => 502, 'product' => null, 'total' => 0];
        }

        $matches = array_values(array_filter(
            $payload['data'],
            static fn ($product): bool => is_array($product) && ($product['sku'] ?? null) === $sku,
        ));

        if (count($matches) !== (int) $total) {
            return ['status' => 502, 'product' => null, 'total' => 0];
        }

        if ($matches === []) {
            return ['status' => 200, 'product' => null, 'total' => 0];
        }

        if (count($matches) !== 1) {
            return ['status' => 502, 'product' => null, 'total' => 0];
        }

        $product = $matches[0];
        $expectedExternalId = 'inventory:sku:'.$sku;
        if (($product['external_id'] ?? null) !== $expectedExternalId
            || ! is_string($product['name'] ?? null)
            || ! (is_string($product['barcode'] ?? null) || ($product['barcode'] ?? null) === null)
            || ! is_numeric($product['shelf_qty'] ?? null)
            || ! is_numeric($product['backroom_qty'] ?? null)
            || ! is_numeric($product['on_hand'] ?? null)
            || ! is_string($product['status'] ?? null)) {
            return ['status' => 502, 'product' => null, 'total' => 0];
        }

        return [
            'status' => 200,
            'product' => [
                'external_id' => $expectedExternalId,
                'sku' => $sku,
                'barcode' => $product['barcode'],
                'name' => $product['name'],
                'shelf_qty' => (float) $product['shelf_qty'],
                'backroom_qty' => (float) $product['backroom_qty'],
                'on_hand' => (float) $product['on_hand'],
                'status' => $product['status'],
            ],
            'total' => 1,
        ];
    }
}
