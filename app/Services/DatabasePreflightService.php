<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class DatabasePreflightService
{
    /** Migrations that alter the POS sale, payment, or receipt persistence path. */
    private const CHECKOUT_MIGRATIONS = [
        '2026_10_04_000001_add_configurable_vat_rate',
        '2026_10_04_000002_create_pending_ewallet_verifications_table',
        '2026_10_04_000003_add_manager_authorization_audit_fields',
        '2026_10_04_000004_add_receipt_settings_and_sale_snapshots',
        '2026_10_04_000005_add_checkout_idempotency_keys',
        '2026_10_04_000006_secure_payment_references_and_create_pending_card_verifications',
    ];

    /**
     * Schema used by the current register checkout and receipt flows.
     * Keep this list in step with migrations that change those flows.
     *
     * @var array<string, list<string>>
     */
    private const REQUIRED_SCHEMA = [
        'sale_transaction' => [
            'transaction_id',
            'vat_rate',
            'senior_pwd_type',
            'senior_pwd_name',
            'senior_pwd_id_number',
            'discount_snapshot',
            'tax_snapshot',
            'checkout_idempotency_key',
        ],
        'receipt' => ['receipt_number', 'register_id', 'sequence_number', 'settings_snapshot'],
        'receipt_settings' => ['id', 'store_name', 'store_address', 'tin', 'paper_width', 'footer_text'],
        'register_receipt_counters' => ['register_id', 'last_sequence'],
        'payment' => ['reference_ciphertext', 'reference_fingerprint', 'card_last4'],
        'pending_ewallet_verifications' => [
            'checkout_idempotency_key',
            'reference_ciphertext',
            'reference_fingerprint',
        ],
        'pending_card_verifications' => ['checkout_idempotency_key', 'reference_ciphertext', 'reference_fingerprint'],
        'payment_reference_registry' => ['payment_method', 'payment_provider', 'reference_fingerprint'],
    ];

    /**
     * Inspect the configured database without changing it.
     *
     * @return array{
     *     ready: bool,
     *     checkout_ready: bool,
     *     driver: string,
     *     pending_migrations: list<string>,
     *     checkout_pending_migrations: list<string>,
     *     missing_schema: list<string>,
     *     error: ?string
     * }
     */
    public function inspect(): array
    {
        $driver = (string) config('database.default', 'unknown');
        $pendingMigrations = [];
        $checkoutPendingMigrations = [];
        $missingSchema = [];
        $error = null;

        try {
            $connection = DB::connection();
            $schema = $connection->getSchemaBuilder();

            if (! $schema->hasTable('migrations')) {
                $error = 'The migrations table is missing.';
            } else {
                $ranMigrations = $connection->table('migrations')
                    ->pluck('migration')
                    ->map(fn ($migration): string => (string) $migration)
                    ->all();

                $migrationFiles = glob(database_path('migrations/*.php')) ?: [];
                $migrationNames = array_map(
                    static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
                    $migrationFiles,
                );
                $pendingMigrations = array_values(array_diff($migrationNames, $ranMigrations));
                sort($pendingMigrations);
                $checkoutPendingMigrations = array_values(array_intersect(
                    self::CHECKOUT_MIGRATIONS,
                    $pendingMigrations,
                ));
            }

            foreach (self::REQUIRED_SCHEMA as $table => $columns) {
                if (! $schema->hasTable($table)) {
                    foreach ($columns as $column) {
                        $missingSchema[] = $table.'.'.$column;
                    }

                    continue;
                }

                foreach ($columns as $column) {
                    if (! $schema->hasColumn($table, $column)) {
                        $missingSchema[] = $table.'.'.$column;
                    }
                }
            }
        } catch (Throwable) {
            $error = 'The configured database could not be checked. Verify database connectivity and credentials.';
        }

        return [
            'ready' => $error === null && $pendingMigrations === [] && $missingSchema === [],
            'checkout_ready' => $error === null && $checkoutPendingMigrations === [] && $missingSchema === [],
            'driver' => $driver,
            'pending_migrations' => $pendingMigrations,
            'checkout_pending_migrations' => $checkoutPendingMigrations,
            'missing_schema' => $missingSchema,
            'error' => $error,
        ];
    }

    /** Stop sale creation when the deployed schema does not match this code. */
    public function assertCheckoutReady(): void
    {
        $report = $this->inspect();

        if ($report['checkout_ready']) {
            return;
        }

        $message = 'Sales are paused because the database schema does not match this application. Contact a manager to back up the database and complete the required migrations before accepting sales.';

        throw ValidationException::withMessages(['database' => $message]);
    }
}
