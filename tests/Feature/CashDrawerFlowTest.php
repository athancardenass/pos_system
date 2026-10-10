<?php

namespace Tests\Feature;

use App\Models\CashDrawer;
use App\Models\Employee;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashDrawerFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_manager_can_open_and_close_shift_with_cash_reconciliation(): void
    {
        $manager = Employee::query()->where('username', 'manager')->firstOrFail();

        $this->actingAs($manager)
            ->postJson(route('cash-drawer.open'), [
                'opening_cash' => '500.00',
                'register_id' => 'REG 01',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $drawer = CashDrawer::query()->where('employee_id', $manager->employee_id)->where('status', 'open')->firstOrFail();

        $openStatus = $this->actingAs($manager)
            ->getJson(route('cash-drawer.status'))
            ->assertOk()
            ->assertJsonPath('has_open_drawer', true);
        $this->assertNotEmpty($openStatus->json('drawer.opened_at'));

        $this->actingAs($manager)
            ->postJson(route('cash-drawer.close'), ['actual_cash' => '500.00'])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('drawer.status', 'closed')
            ->assertJsonPath('drawer.difference', '0.00');

        $this->assertSame('closed', $drawer->fresh()->status);
        $this->assertEquals(0, (float) $drawer->fresh()->difference);

        $closedStatus = $this->actingAs($manager)
            ->getJson(route('cash-drawer.status'))
            ->assertOk()
            ->assertJsonPath('has_open_drawer', false);
        $this->assertNotEmpty($closedStatus->json('last_closed_at'));
    }

    public function test_shift_cannot_be_closed_without_an_open_drawer(): void
    {
        $manager = Employee::query()->where('username', 'manager')->firstOrFail();

        $this->actingAs($manager)
            ->from(route('pos.index'))
            ->post(route('cash-drawer.close'), ['actual_cash' => '500.00'])
            ->assertRedirect(route('pos.index'))
            ->assertSessionHasErrors('drawer');
    }
}
