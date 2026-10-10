<?php

namespace App\Console\Commands;

use App\Services\Integration\Inventory\WebhookInbox;
use App\Services\Integration\Inventory\StockEventDispatcher;
use App\Services\Integration\MasterSyncService;
use Illuminate\Console\Command;
use Throwable;

class ProcessInventoryWebhooks extends Command
{
    protected $signature = 'integrations:webhooks {--once : Process one batch and exit}';
    protected $description = 'Process Inventory webhooks and send POS stock events promptly with durable retries';

    public function handle(WebhookInbox $inbox, MasterSyncService $sync, StockEventDispatcher $stock): int
    {
        do {
            try {
                $count = $inbox->process($sync, $stock);
                if ($count) $this->info("Processed $count Inventory webhook(s).");
            } catch (Throwable $exception) {
                $this->error('Webhook sync failed; notification retained for retry ('.class_basename($exception).').');
                if ($this->option('once')) return self::FAILURE;
                usleep(5_000_000);
            }
            if (! $this->option('once')) usleep(2_000_000);
        } while (! $this->option('once'));
        return self::SUCCESS;
    }
}
