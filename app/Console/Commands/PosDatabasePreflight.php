<?php

namespace App\Console\Commands;

use App\Services\DatabasePreflightService;
use Illuminate\Console\Command;

class PosDatabasePreflight extends Command
{
    protected $signature = 'pos:database-preflight {--checkout-only : Check only whether current register sales can use the database schema}';

    protected $description = 'Read-only check that the database matches the current POS checkout schema';

    public function handle(DatabasePreflightService $preflight): int
    {
        $report = $preflight->inspect();

        $this->line('Database driver: '.$report['driver']);

        if ($this->option('checkout-only')) {
            if ($report['checkout_ready']) {
                $this->info('Checkout schema is ready; register sales are not blocked by a schema mismatch.');

                return self::SUCCESS;
            }

            $this->error('Checkout is blocked because its required database schema is not ready.');
            $this->line('Do not accept sales until a manager has backed up the database and resolved the listed migration/schema issues.');

            foreach ($report['checkout_pending_migrations'] as $migration) {
                $this->line('  - pending checkout migration: '.$migration);
            }
            foreach ($report['missing_schema'] as $item) {
                $this->line('  - missing schema: '.$item);
            }
            if ($report['error'] !== null) {
                $this->line('  - '.$report['error']);
            }

            return self::FAILURE;
        }

        if ($report['error'] !== null) {
            $this->error($report['error']);
        }

        if ($report['pending_migrations'] !== []) {
            $this->error('Pending migrations:');
            foreach ($report['pending_migrations'] as $migration) {
                $this->line('  - '.$migration);
            }
        }

        if ($report['checkout_pending_migrations'] === [] && $report['missing_schema'] === [] && $report['error'] === null) {
            $this->comment('Checkout schema is present. Pending migrations listed above do not block register sales.');
        }

        if ($report['missing_schema'] !== []) {
            $this->error('Missing checkout schema:');
            foreach ($report['missing_schema'] as $item) {
                $this->line('  - '.$item);
            }
        }

        if (! $report['ready']) {
            $this->newLine();
            if (! $report['checkout_ready']) {
                $this->warn('Do not accept sales until the checkout schema is corrected.');
            } elseif ($report['pending_migrations'] !== []) {
                $this->comment('The register checkout guard will allow sales because its required schema is present.');
            }
            $this->warn('Back up the live database and verify the backup before applying migrations.');
            $this->line('This command is read-only; it does not create backups or run migrations.');

            return self::FAILURE;
        }

        $this->info('Ready: migrations are current and required checkout tables and columns are present.');
        $this->comment('This command is read-only. Back up the database before any future migration run.');

        return self::SUCCESS;
    }
}
