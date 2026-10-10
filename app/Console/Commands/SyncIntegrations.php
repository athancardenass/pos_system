<?php

namespace App\Console\Commands;

use App\Services\Integration\MasterSyncService;
use App\Services\Integration\Inventory\StockEventDispatcher;
use App\Services\Integration\CRM\CustomerLoyaltyDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SyncIntegrations extends Command
{
    protected $signature = 'integrations:sync {system? : inventory, hrms, or crm; omit for all}';
    protected $description = 'Synchronize Inventory products/stock, CRM customers, and HR employees';

    public function handle(MasterSyncService $sync, StockEventDispatcher $stock, CustomerLoyaltyDispatcher $loyalty): int
    {
        $lock = Cache::lock('integrations-master-sync', 600);
        if (! $lock->get()) { $this->warn('Integration sync is already running.'); return self::SUCCESS; }
        $failed = false;
        try {
            foreach ($this->argument('system') ? [$this->argument('system')] : ['inventory', 'crm', 'hrms'] as $system) {
                try {
                    if ($system === 'inventory') $this->line('Inventory stock events sent: '.$stock->dispatch());
                    if ($system === 'crm') {
                        $result = $loyalty->dispatch();
                        $this->line('CRM loyalty events sent: '.$result['sent']);
                        if ($result['failed'] > 0) {
                            $failed = true;
                            $this->warn($result['failed'].' loyalty event(s) remain pending; inspect customer_outbox_event.last_error.');
                        }
                    }
                    $this->info($system.' records synchronized: '.$sync->sync($system));
                } catch (Throwable $exception) {
                    $failed = true;
                    $this->error($system.': synchronization failed ('.class_basename($exception).'). Check configuration, conflicts, and API availability.');
                }
            }
        } finally { $lock->release(); }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
