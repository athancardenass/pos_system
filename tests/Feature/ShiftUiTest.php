<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Services\CashDrawerService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShiftUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_cashier_can_view_own_report_but_not_another_cashier_report(): void
    {
        $cashier = Employee::where('username', 'cashier')->firstOrFail();
        $manager = Employee::where('username', 'manager')->firstOrFail();
        $this->actingAs($cashier);
        $own = app(CashDrawerService::class)->openDrawer($cashier->employee_id, 500);
        app(CashDrawerService::class)->closeDrawer($cashier->employee_id, 500);
        $other = app(CashDrawerService::class)->openDrawer($manager->employee_id, 1000);
        $this->get('/cash-drawer/'.$own->drawer_id.'/report')->assertOk()->assertSee('Closing Shift Report')->assertSee('500.00');
        $this->get('/cash-drawer/'.$other->drawer_id.'/report')->assertForbidden();
        $this->actingAs($manager)->get('/cash-drawer/'.$own->drawer_id.'/report')->assertOk();
    }

    public function test_cash_movement_requires_approval_and_updates_status_breakdown(): void
    {
        $cashier = Employee::where('username', 'cashier')->firstOrFail();
        $manager = Employee::where('username', 'manager')->firstOrFail();
        $manager->manager_pin_hash = Hash::make('2468');
        $manager->save();
        $this->actingAs($cashier);
        $drawer = app(CashDrawerService::class)->openDrawer($cashier->employee_id, 500);
        $data = ['kind' => 'manual_in', 'amount' => 200, 'reason' => 'Change fund', 'operation_key' => (string) Str::uuid()];
        $this->postJson('/cash-drawer/movements', $data)->assertUnprocessable();
        $token = $this->postJson(route('manager-authorization.authorize'), ['action' => 'cash_movement', 'pin' => '2468', 'register_id' => 'REG 01', 'details' => $data])->assertOk()->json('token');
        $this->postJson('/cash-drawer/movements', $data + ['manager_authorization_token' => $token, 'register_id' => 'REG 01'])->assertOk();
        $this->assertEquals(700, $drawer->fresh()->expected_cash);
        $this->getJson(route('cash-drawer.status'))->assertOk()->assertJsonPath('report.cash_in', 200)->assertJsonPath('report.expected_cash', 700);
        $this->get('/cash-drawer/movements')->assertOk()->assertSee('Cash In')->assertSee('Drawer Transfer');
    }

    public function test_cashout_requires_reason_and_does_not_change_sales(): void
    {
        $manager = Employee::where('username', 'manager')->firstOrFail();
        $this->actingAs($manager);
        $drawer = app(CashDrawerService::class)->openDrawer($manager->employee_id, 1000);
        $this->postJson('/cash-drawer/movements', ['kind' => 'manual_out', 'amount' => 200, 'operation_key' => (string) Str::uuid()])->assertUnprocessable();
        $this->postJson('/cash-drawer/movements', ['kind' => 'manual_out', 'amount' => 200, 'reason' => 'Cash pickup', 'operation_key' => (string) Str::uuid()])->assertOk();
        $this->getJson(route('cash-drawer.status'))->assertJsonPath('report.gross_sales', 0)->assertJsonPath('report.cash_out', 200);
        $this->assertEquals(800,$drawer->fresh()->expected_cash);
    }
}
