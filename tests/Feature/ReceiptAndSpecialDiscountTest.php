<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Promotion;
use App\Models\Product;
use App\Models\SaleTransaction;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptAndSpecialDiscountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function employee(string $username): Employee
    {
        return Employee::query()->where('username', $username)->firstOrFail();
    }

    private function product(string $name, float $price = 100, float $stock = 20): Product
    {
        $product = Product::query()->create([
            'product_name' => $name,
            'barcode' => '4006381000997',
            'unit_price' => $price,
            'cost_price' => $price / 2,
            'reorder_level' => 5,
        ]);
        Inventory::query()->create(['product_id' => $product->product_id, 'stock_quantity' => $stock]);

        return $product;
    }

    private function checkout(Employee $employee, Product $product, array $overrides = []): SaleTransaction
    {
        $payload = array_replace([
            'register_id' => 'REG 01',
            'payment_method' => 'cash',
            'amount_paid' => 500,
            'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
        ], $overrides);

        $this->actingAs($employee)->post(route('pos.store'), $payload)->assertRedirect();

        return SaleTransaction::query()->latest('transaction_id')->firstOrFail();
    }

    public function test_receipt_sequences_are_per_register_and_failed_checkouts_do_not_consume_numbers(): void
    {
        $manager = $this->employee('manager');
        $product = $this->product('Sequence item', 50);

        $first = $this->checkout($manager, $product, ['amount_paid' => 50]);

        $this->actingAs($manager)
            ->from(route('pos.index'))
            ->post(route('pos.store'), [
                'register_id' => 'REG 01',
                'payment_method' => 'cash',
                'amount_paid' => 1,
                'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            ])
            ->assertRedirect(route('pos.index'))
            ->assertSessionHasErrors('amount_paid');

        $second = $this->checkout($manager, $product, ['amount_paid' => 50]);
        $otherRegister = $this->checkout($manager, $product, [
            'register_id' => 'REG 02',
            'amount_paid' => 50,
        ]);

        $this->assertSame('REG01-000001', $first->receipt->receipt_number);
        $this->assertSame('REG01-000002', $second->receipt->receipt_number);
        $this->assertSame('REG 01', $second->receipt->register_id);
        $this->assertSame('REG02-000001', $otherRegister->receipt->receipt_number);
    }

    public function test_receipt_settings_are_snapshotted_and_reprint_marks_the_receipt(): void
    {
        $manager = $this->employee('manager');
        $cashier = $this->employee('cashier');

        $this->actingAs($manager)->put(route('receipt-settings.update'), [
            'store_name' => 'Market North',
            'store_address' => '12 Main Street, Calasiao',
            'tin' => '123-456-789-000',
            'paper_width' => '80',
            'footer_text' => 'Keep your receipt.',
        ])->assertRedirect(route('receipt-settings.edit'));
        $this->actingAs($manager)->get(route('receipt-settings.edit'))
            ->assertOk()
            ->assertSee('Thermal paper width')
            ->assertSee('paper_width_58', false)
            ->assertSee('paper_width_80', false);

        $product = $this->product('Receipt item', 112);
        $sale = $this->checkout($cashier, $product, ['amount_paid' => 120]);

        $this->actingAs($cashier)
            ->get(route('pos.reprint-last'))
            ->assertRedirect(route('pos.reprint', $sale));
        $this->get(route('pos.reprint', $sale))
            ->assertRedirect(route('pos.show', ['saleTransaction' => $sale, 'reprint' => 1]));
        $this->get(route('pos.show', ['saleTransaction' => $sale, 'reprint' => 1]))
            ->assertOk()
            ->assertSee('REPRINT')
            ->assertSee('data-paper-width="80"', false)
            ->assertSee('Market North')
            ->assertSee('12 Main Street, Calasiao')
            ->assertSee('TIN: 123-456-789-000')
            ->assertSee('Receipt / OR')
            ->assertSee('Cashier')
            ->assertSee('Register')
            ->assertSee('VATable sales')
            ->assertSee('VAT-exempt sales')
            ->assertSee('Keep your receipt.')
            ->assertDontSee('PROMOTION')
            ->assertDontSee('COUPON');

        $this->actingAs($manager)->put(route('receipt-settings.update'), [
            'store_name' => 'Market South',
            'paper_width' => '58',
            'footer_text' => 'Updated footer.',
        ])->assertRedirect();

        $this->actingAs($cashier)->get(route('pos.show', $sale))
            ->assertSee('Market North')
            ->assertDontSee('Market South');
    }

    public function test_senior_and_pwd_use_twenty_percent_of_vat_exempt_base_and_require_proof(): void
    {
        Promotion::query()->update(['is_active' => false]);
        $manager = $this->employee('manager');
        $product = $this->product('Special policy item', 112);
        $pwd = Discount::query()->create([
            'discount_name' => 'PWD 15%',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);
        $senior = Discount::query()->create([
            'discount_name' => 'Senior Citizen 20%',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $this->actingAs($manager)->get(route('pos.index'))
            ->assertOk()
            ->assertSee('id="senior-pwd-proof"', false)
            ->assertSee('Full name on ID')
            ->assertSee('Senior Citizen / PWD ID number')
            ->assertSee('F10')
            ->assertSee('PWD 20% · VAT-exempt base')
            ->assertDontSee('PWD 15%');

        $before = SaleTransaction::query()->count();
        $this->actingAs($manager)
            ->from(route('pos.index'))
            ->post(route('pos.store'), [
                'discount_id' => $pwd->discount_id,
                'senior_pwd_type' => 'pwd',
                'register_id' => 'REG 01',
                'payment_method' => 'cash',
                'amount_paid' => 80,
                'items' => [['product_id' => $product->product_id, 'quantity' => 1]],
            ])
            ->assertRedirect(route('pos.index'))
            ->assertSessionHasErrors('senior_pwd_name');
        $this->assertDatabaseCount('sale_transaction', $before);

        $pwdSale = $this->checkout($manager, $product, [
            'discount_id' => $pwd->discount_id,
            'senior_pwd_type' => 'pwd',
            'senior_pwd_name' => 'Alex Sample',
            'senior_pwd_id_number' => 'PWD-1001',
            'amount_paid' => 80,
        ]);
        $this->assertEquals(80.00, (float) $pwdSale->total_amount);
        $this->assertEquals(100.00, (float) $pwdSale->discount_snapshot['base_amount']);
        $this->assertEquals(20.00, (float) $pwdSale->discount_snapshot['amount']);
        $this->assertEquals(12.00, (float) $pwdSale->tax_snapshot['vat_exemption_amount']);
        $this->assertEquals(0.00, (float) $pwdSale->tax_snapshot['vat_amount']);
        $this->assertSame('PWD 20% · VAT-exempt base', $pwdSale->discount_snapshot['name']);

        $seniorSale = $this->checkout($manager, $product, [
            'discount_id' => $senior->discount_id,
            'senior_pwd_type' => 'senior_citizen',
            'senior_pwd_name' => 'Sam Sample',
            'senior_pwd_id_number' => 'SC-2002',
            'amount_paid' => 80,
        ]);
        $this->assertSame('senior_citizen', $seniorSale->senior_pwd_type);
        $this->assertSame('Sam Sample', $seniorSale->senior_pwd_name);
        $this->assertSame('SC-2002', $seniorSale->senior_pwd_id_number);
        $this->assertEquals(80.00, (float) $seniorSale->total_amount);

        $this->actingAs($manager)->get(route('pos.show', $pwdSale))
            ->assertOk()
            ->assertSee('PWD holder')
            ->assertSee('Alex Sample')
            ->assertSee('PWD-1001')
            ->assertSee('PWD 20% · VAT-exempt base')
            ->assertSee('DISC-'.str_pad((string) $pwd->discount_id, 3, '0', STR_PAD_LEFT))
            ->assertDontSee('PWD 15%');
    }

    public function test_pwd_discount_editor_saves_the_fixed_twenty_percent_policy(): void
    {
        $manager = $this->employee('manager');
        $discount = Discount::query()->create([
            'discount_name' => 'PWD 15%',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
        ]);

        $this->actingAs($manager)
            ->put(route('discounts.update', $discount), [
                'discount_name' => 'PWD 15%',
                'discount_type' => 'fixed',
                'discount_value' => 5,
                'start_date' => now()->subDay()->toDateString(),
                'end_date' => now()->addDay()->toDateString(),
            ])
            ->assertRedirect(route('discounts.index'));

        $discount->refresh();
        $this->assertSame('percentage', $discount->discount_type);
        $this->assertEquals(20.00, (float) $discount->discount_value);

        $this->actingAs($manager)->get(route('discounts.index'))
            ->assertOk()
            ->assertSee('PWD 20% · VAT-exempt base')
            ->assertDontSee('PWD 15%');
    }
}
