<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Services\CashDrawerService;
use App\Services\DrawerTransferService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DrawerTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function fixture(): array
    {
        $manager = Employee::where('username', 'manager')->firstOrFail();
        $cashier = Employee::where('username', 'cashier')->firstOrFail();
        $drawers = app(CashDrawerService::class);
        $this->actingAs($manager);

        return [$manager, $cashier, $drawers->openDrawer($manager->employee_id, 1000), $drawers->openDrawer($cashier->employee_id, 1500)];
    }

    public function test_transfer_deducts_source_then_credits_only_after_receiver_confirms(): void
    {
        [$manager,$cashier,$source,$dest] = $this->fixture();
        $service = app(DrawerTransferService::class);
        $data = ['destination_drawer_id' => $dest->drawer_id, 'amount' => 500, 'reason' => 'Change fund', 'operation_key' => (string) Str::uuid()];
        $approval = ['approved_by_employee_id' => $manager->employee_id];
        $transfer = $service->initiate($manager->employee_id, $data, $approval);
        $this->assertEquals(500, $source->fresh()->expected_cash);
        $this->assertEquals(1500, $dest->fresh()->expected_cash);
        $this->assertSame($transfer->id, $service->initiate($manager->employee_id, $data, $approval)->id);
        $this->actingAs($cashier);
        $service->accept($cashier->employee_id, $transfer);
        $service->accept($cashier->employee_id, $transfer);
        $this->assertEquals(2000, $dest->fresh()->expected_cash);
        $this->assertDatabaseCount('drawer_movements', 2);
    }

    public function test_unreceived_transfer_blocks_closure_and_cancellation_restores_source(): void
    {
        [$manager,$cashier,$source,$dest] = $this->fixture();
        $service = app(DrawerTransferService::class);
        $approval = ['approved_by_employee_id' => $manager->employee_id];
        $transfer = $service->initiate($manager->employee_id, ['destination_drawer_id' => $dest->drawer_id, 'amount' => 500, 'reason' => 'Change fund', 'operation_key' => (string) Str::uuid()], $approval);
        try {
            app(CashDrawerService::class)->closeDrawer($manager->employee_id, 500);
            $this->fail('Pending transfer closure accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('drawer', $e->errors());
        }
        $service->cancel($manager->employee_id, $transfer, $approval);
        $this->assertEquals(1000, $source->fresh()->expected_cash);
        $this->assertEquals(1500, $dest->fresh()->expected_cash);
        $this->assertSame('closed', app(CashDrawerService::class)->closeDrawer($manager->employee_id, 1000)->status);
    }

    public function test_sender_cannot_accept_for_another_cashier(): void
    {
        [$manager,$cashier,$source,$dest] = $this->fixture();
        $service = app(DrawerTransferService::class);
        $transfer = $service->initiate($manager->employee_id, ['destination_drawer_id' => $dest->drawer_id, 'amount' => 100, 'reason' => 'Change fund', 'operation_key' => (string) Str::uuid()], ['approved_by_employee_id' => $manager->employee_id]);
        $this->expectException(AuthorizationException::class);
        $service->accept($manager->employee_id, $transfer);
    }

    public function test_same_transfer_key_with_changed_amount_is_rejected(): void
    {
        [$manager,$cashier,$source,$dest] = $this->fixture();
        $service = app(DrawerTransferService::class);
        $data = ['destination_drawer_id' => $dest->drawer_id, 'amount' => 100, 'reason' => 'Change fund', 'operation_key' => (string) Str::uuid()];
        $approval = ['approved_by_employee_id' => $manager->employee_id];
        $service->initiate($manager->employee_id, $data, $approval);
        $this->expectException(ValidationException::class);
        $service->initiate($manager->employee_id,array_replace($data,['amount' => 200]),$approval);
    }
}
