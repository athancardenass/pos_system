<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Receipt;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptBarcodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_and_reprint_show_product_barcode_numbers_as_text_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $cashier = Employee::where('username', 'cashier')->firstOrFail();
        $receipt = Receipt::firstOrFail();
        $sale = $receipt->saleTransaction()->with('saleDetails.product')->firstOrFail();
        $barcodes = $sale->saleDetails->pluck('product.barcode')->filter();
        $this->assertNotEmpty($barcodes);
        foreach ([[], ['reprint' => 1]] as $query) {
            $response = $this->actingAs($cashier)->get(route('pos.show', array_merge([
                'saleTransaction' => $receipt->transaction_id,
            ], $query)))->assertOk()
                ->assertDontSee('id="receipt-barcode"', false)
                ->assertDontSee('data-product-barcode=', false)
                ->assertDontSee('JsBarcode.all.min.js', false)
                ->assertDontSee('receipt-barcode.js', false);
            foreach ($barcodes as $barcode) {
                $response->assertSee('<span class="receipt-product-code receipt-mono">EAN-13: '.e($barcode).'</span>', false);
            }
        }
    }
}
