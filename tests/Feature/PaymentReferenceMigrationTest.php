<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Services\PaymentReferenceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentReferenceMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_migration_encrypts_existing_wallet_reference_and_removes_plaintext_copies(): void
    {
        $migration = require database_path('migrations/2026_10_04_000006_secure_payment_references_and_create_pending_card_verifications.php');
        $migration->down();

        $rawReference = 'LEGACY-GCASH-9912';
        $employee = Employee::query()->where('username', 'cashier')->firstOrFail();
        $pendingId = DB::table('pending_ewallet_verifications')->insertGetId([
            'employee_id' => $employee->employee_id,
            'payment_provider' => 'GCash',
            'reference_number' => $rawReference,
            'submitted_amount' => 48.50,
            'checkout_payload' => json_encode([
                'payment_method' => 'e-wallet',
                'payment_provider' => 'GCash',
                'reference_number' => $rawReference,
                'items' => [],
            ], JSON_THROW_ON_ERROR),
            'status' => 'pending',
            'expires_at' => now()->addMinutes(60),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $secured = DB::table('pending_ewallet_verifications')->where('id', $pendingId)->first();
        $this->assertSame('', $secured->reference_number);
        $this->assertSame($rawReference, app(PaymentReferenceService::class)->decrypt($secured->reference_ciphertext));
        $this->assertStringNotContainsString($rawReference, (string) $secured->checkout_payload);
        $this->assertSame(
            app(PaymentReferenceService::class)->fingerprint('e-wallet', 'GCash', $rawReference),
            $secured->reference_fingerprint,
        );
        $this->assertDatabaseHas('payment_reference_registry', [
            'payment_method' => 'e-wallet',
            'payment_provider' => 'GCash',
            'reference_fingerprint' => $secured->reference_fingerprint,
            'source_type' => 'pending_ewallet_verifications',
            'source_id' => $pendingId,
        ]);
    }
}
