<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\SaleTransaction;
use App\Models\VatSetting;
use App\Services\VatSettingsService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VatSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['vat.enabled' => true]);
        $this->seed(DatabaseSeeder::class);
    }

    private function employee(string $username): Employee
    {
        return Employee::query()->where('username', $username)->firstOrFail();
    }

    private function product(string $name, float $price): Product
    {
        $product = Product::query()->create([
            'product_name' => $name,
            'barcode' => Product::generateBarcode(),
            'unit_price' => $price,
            'cost_price' => round($price / 2, 2),
            'reorder_level' => 5,
        ]);

        Inventory::query()->create([
            'product_id' => $product->product_id,
            'stock_quantity' => 10,
        ]);

        return $product;
    }

    public function test_manager_can_update_vat_rate_for_future_sales(): void
    {
        $manager = $this->employee('manager');

        $this->actingAs($manager)
            ->get(route('vat-settings.edit'))
            ->assertOk()
            ->assertSee('class="btn btn-secondary"', false)
            ->assertSee('12.00');

        $this->actingAs($manager)
            ->put(route('vat-settings.update'), ['rate_percent' => '15.50'])
            ->assertRedirect(route('vat-settings.edit'))
            ->assertSessionHas('status', 'VAT rate updated. The new rate applies to future sales.');

        $this->assertSame(0.155, (float) VatSetting::query()->findOrFail(VatSetting::SINGLETON_ID)->rate);
    }

    public function test_cashier_cannot_view_or_change_vat_settings(): void
    {
        $cashier = $this->employee('cashier');

        $this->actingAs($cashier)->get(route('vat-settings.edit'))->assertForbidden();
        $this->actingAs($cashier)
            ->put(route('vat-settings.update'), ['rate_percent' => '15.50'])
            ->assertForbidden();

        $this->assertSame(0.12, (float) VatSetting::query()->findOrFail(VatSetting::SINGLETON_ID)->rate);
    }

    public function test_vat_rate_validation_rejects_values_above_one_hundred_percent(): void
    {
        $this->actingAs($this->employee('manager'))
            ->put(route('vat-settings.update'), ['rate_percent' => '100.01'])
            ->assertSessionHasErrors('rate_percent');

        $this->assertSame(0.12, (float) VatSetting::query()->findOrFail(VatSetting::SINGLETON_ID)->rate);
    }

    public function test_discounts_page_renders_vat_settings_as_a_styled_secondary_action(): void
    {
        $this->actingAs($this->employee('manager'))
            ->get(route('discounts.index'))
            ->assertOk()
            ->assertSee('href="'.route('vat-settings.edit').'"', false)
            ->assertSee('class="btn btn-secondary"', false)
            ->assertSee('VAT settings');
    }

    public function test_checkout_and_receipt_keep_the_sale_time_vat_rate(): void
    {
        app(VatSettingsService::class)->updateRatePercent(15.50);
        $product = $this->product('VAT Snapshot Item', 115.50);

        $this->actingAs($this->employee('manager'))
            ->post(route('pos.store'), [
                'payment_method' => 'cash',
                'amount_paid' => 115.50,
                'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            ])
            ->assertRedirect();

        $sale = SaleTransaction::query()->latest('transaction_id')->firstOrFail();
        $this->assertSame(0.155, (float) $sale->vat_rate);
        $this->assertSame(15.50, $sale->vat_amount);

        app(VatSettingsService::class)->updateRatePercent(20.00);
        $sale->refresh();

        $this->assertSame(0.155, (float) $sale->vat_rate);
        $this->assertSame(15.50, $sale->vat_amount);

        $this->actingAs($this->employee('cashier'))
            ->get(route('pos.show', $sale))
            ->assertOk()
            ->assertSee('VAT (15.5%, included)')
            ->assertSee('₱15.50')
            ->assertDontSee('VAT (20%, included)');
    }

    public function test_pos_shows_modern_discount_picker_and_used_discounts_cannot_be_deleted(): void
    {
        $discount = Discount::query()->create([
            'discount_name' => 'Member savings',
            'discount_type' => 'fixed',
            'discount_value' => 5,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $this->actingAs($this->employee('manager'))
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('class="pos-discount-trigger"', false)
            ->assertSee('DISC-'.str_pad((string) $discount->discount_id, 3, '0', STR_PAD_LEFT))
            ->assertSee('Member savings');

        $product = $this->product('Discounted item', 100.00);

        $this->actingAs($this->employee('manager'))
            ->post(route('pos.store'), [
                'discount_id' => $discount->discount_id,
                'payment_method' => 'cash',
                'amount_paid' => 95.00,
                'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            ])
            ->assertRedirect();

        $this->actingAs($this->employee('manager'))
            ->delete(route('discounts.destroy', $discount))
            ->assertSessionHas('error', 'Cannot delete a discount that has been used on a sale.');

        $this->assertDatabaseHas('discount', ['discount_id' => $discount->discount_id]);
    }
}
