<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\CashDrawerService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IncidentReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(Employee::where('username', 'cashier')->firstOrFail());
    }

    private function report(): int
    {
        $product = Product::create(['product_name' => 'Safety test', 'barcode' => '4006381333931', 'unit_price' => 50, 'cost_price' => 20]);
        Inventory::create(['product_id' => $product->product_id, 'stock_quantity' => 10]);
        $this->post('/incidents/safety', ['product_id' => $product->product_id, 'quantity' => 1, 'description' => 'Suspected contamination', 'batch_number' => 'B-12'])
            ->assertSessionHasNoErrors()->assertRedirect();
        return $product->product_id;
    }

    public function test_session_error_flash_uses_accessible_alert_semantics(): void
    {
        $this->withSession(['error' => 'Unable to continue.'])
            ->get('/incidents')
            ->assertOk()
            ->assertSee('<div class="flash flash-error" role="alert"', false)
            ->assertSee('Unable to continue.');
    }

    public function test_report_holds_product_and_blocks_checkout_without_deducting_stock(): void
    {
        $id = $this->report();
        $this->post('/pos', ['payment_method' => 'cash', 'amount_paid' => 50, 'items' => [['product_id' => $id, 'quantity' => 1]]])
            ->assertSessionHasErrors('items');
        $this->assertDatabaseHas('inventory', ['product_id' => $id, 'stock_quantity' => 10]);
        $this->assertDatabaseHas('incident_reports', ['product_id' => $id, 'status' => 'reported', 'external_status' => 'not_connected']);
    }

    public function test_cashier_cannot_resolve_or_export_and_manager_can_review(): void
    {
        $this->report();
        $id = DB::table('incident_reports')->value('id');
        $this->post("/incidents/$id/review", ['status' => 'resolved', 'findings' => 'Examined and cleared'])->assertForbidden();
        $this->get("/incidents/$id/export")->assertForbidden();
        $this->actingAs(Employee::where('username', 'manager')->firstOrFail());
        $this->post("/incidents/$id/review", ['status' => 'resolved', 'findings' => 'Examined and cleared'])->assertSessionHasNoErrors();
        $this->get("/incidents/$id/export")->assertOk()->assertJsonPath('external_status', 'not_connected');
        $this->get("/incidents/$id")->assertOk()->assertSee('Examined and cleared');
    }

    public function test_variance_case_is_created_once_with_saved_balances_and_is_private(): void
    {
        $service = app(CashDrawerService::class);
        $drawer = $service->openDrawer(auth()->id(), 1000);
        $service->closeDrawer(auth()->id(), 900, 'Recount needed');
        $this->assertDatabaseHas('incident_reports', ['drawer_id' => $drawer->drawer_id, 'kind' => 'cash_variance', 'difference' => -100]);
        $id = DB::table('incident_reports')->value('id');
        $this->get("/incidents/$id")->assertOk()->assertSee('Recount needed');
        $this->actingAs(Employee::where('username', 'manager')->firstOrFail());
        $this->post("/incidents/$id/acknowledge", ['external_reference' => 'HR-12'])->assertSessionHasErrors('external_reference');
        $this->post("/incidents/$id/review", ['status' => 'under_investigation', 'findings' => 'Verified recount; forwarded for review'])->assertSessionHasNoErrors();
        $this->post("/incidents/$id/acknowledge", ['external_reference' => 'HR-12'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('incident_reports', ['id' => $id, 'external_status' => 'acknowledged_manually', 'external_reference' => 'HR-12']);
        $this->assertEquals(-100, $drawer->fresh()->difference);
        $this->assertEquals(900, $drawer->fresh()->report_snapshot['actual_cash']);
        $other = Employee::where('username', 'cashier')->firstOrFail()->replicate();
        $other->username = 'other-cashier';
        $other->save();
        $this->actingAs($other)->get("/incidents/$id")->assertForbidden();
    }

    public function test_register_and_form_render_and_balanced_shift_creates_no_case(): void
    {
        $this->get('/incidents')->assertOk()->assertSee('Incident reports');
        $this->get('/incidents/create')->assertOk()->assertSee('Report product safety concern');
        $service = app(CashDrawerService::class);
        $service->openDrawer(auth()->id(), 100);
        $service->closeDrawer(auth()->id(), 100);
        $this->assertDatabaseCount('incident_reports', 0);
    }

    public function test_incident_access_is_grouped_under_reports_navigation(): void
    {
        $this->get('/incidents')->assertOk()->assertSee('>Reports</a>', false)->assertDontSee('>Incidents</a>', false);
        $this->actingAs(Employee::where('username', 'manager')->firstOrFail());
        $this->get('/reports')->assertOk()->assertSee('Incident reports')->assertSee(route('incidents.index'), false)->assertDontSee('>Incidents</a>', false);
        $this->get('/incidents')->assertOk()->assertSee('Back to reports');
    }

    public function test_other_open_safety_cases_keep_hold_after_manager_resolves_one(): void
    {
        $id = $this->report();
        $first = DB::table('incident_reports')->value('id');
        $this->post('/incidents/safety', ['product_id' => $id, 'quantity' => 1, 'description' => 'Second concern'])->assertSessionHasNoErrors();
        $this->actingAs(Employee::where('username', 'manager')->firstOrFail());
        $this->post("/incidents/$first/review", ['status' => 'resolved', 'findings' => 'First complaint cleared'])->assertSessionHasNoErrors();
        $product = Product::findOrFail($id);
        $product->update(['is_active' => true]);
        $this->post('/pos', ['payment_method' => 'cash', 'amount_paid' => 50, 'items' => [['product_id' => $id, 'quantity' => 1]]])->assertSessionHasErrors('items');
        $second = DB::table('incident_reports')->max('id');
        $this->post("/incidents/$second/review", ['status' => 'resolved', 'findings' => 'All affected items removed; remaining stock checked'])->assertSessionHasNoErrors();
        $this->post('/pos', ['payment_method' => 'cash', 'amount_paid' => 50, 'items' => [['product_id' => $id, 'quantity' => 1]]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inventory', ['product_id' => $id, 'stock_quantity' => 9]);
    }

    public function test_pending_card_verification_rechecks_safety_hold_and_preserves_pending_payment(): void
    {
        $product = Product::create(['product_name' => 'Pending item', 'barcode' => '4006381333948', 'unit_price' => 50, 'cost_price' => 20]);
        Inventory::create(['product_id' => $product->product_id, 'stock_quantity' => 10]);
        $service = app(\App\Services\CardVerificationService::class);
        $salesBefore = DB::table('sale_transaction')->count();
        $pending = $service->submit(['payment_method' => 'card', 'payment_provider' => 'Visa', 'reference_number' => 'INCIDENT-CARD-001', 'card_last4' => '1234', 'amount_paid' => 50, 'items' => [['product_id' => $product->product_id, 'quantity' => 1]]], auth()->id());
        $this->post('/incidents/safety', ['product_id' => $product->product_id, 'quantity' => 1, 'description' => 'Contamination report'])->assertSessionHasNoErrors();
        $this->actingAs(Employee::where('username', 'manager')->firstOrFail());
        try {
            $service->verify($pending, auth()->id());
            $this->fail('Held product payment completed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('items', $e->errors());
        }
        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertDatabaseCount('sale_transaction', $salesBefore);
    }

    public function test_held_products_cannot_start_card_or_ewallet_reservations(): void
    {
        $id = $this->report();
        foreach ([\App\Services\CardVerificationService::class, \App\Services\EwalletVerificationService::class] as $service) {
            try {
                app($service)->submit(['payment_method' => $service === \App\Services\CardVerificationService::class ? 'card' : 'e-wallet', 'payment_provider' => 'Test', 'reference_number' => 'HOLD-123', 'amount_paid' => 50, 'items' => [['product_id' => $id, 'quantity' => 1]]], auth()->id());
                $this->fail('Held product reserved');
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertArrayHasKey('items', $e->errors());
            }
        }
        $this->assertDatabaseHas('inventory', ['product_id' => $id, 'stock_quantity' => 10]);
    }

    public function test_evidence_is_private_and_html_is_not_stored_as_report_markup(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $product = Product::firstOrFail();
        $image = \Illuminate\Http\UploadedFile::fake()->createWithContent('evidence.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
        $this->post('/incidents/safety', ['product_id' => $product->product_id, 'quantity' => 1, 'description' => '<b>Suspected concern</b>', 'evidence' => $image])->assertSessionHasNoErrors();
        $incident = \App\Models\IncidentReport::firstOrFail();
        $this->assertSame('Suspected concern', $incident->description);
        $this->get("/incidents/{$incident->id}/evidence")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $other = auth()->user()->replicate();
        $other->username = 'evidence-other-cashier';
        $other->save();
        $this->actingAs($other)->get("/incidents/{$incident->id}/evidence")->assertForbidden();
        $this->get('/incidents')->assertDontSee('Suspected concern');
        $this->actingAs(Employee::where('username', 'manager')->firstOrFail());
        $this->get("/incidents/{$incident->id}/export")->assertOk()->assertJsonMissingPath('evidence_path');
    }
}
