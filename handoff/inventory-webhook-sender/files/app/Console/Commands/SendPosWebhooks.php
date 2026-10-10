<?php

namespace App\Console\Commands;

use App\Services\PosWebhookSender;
use Illuminate\Console\Command;
use Throwable;

class SendPosWebhooks extends Command
{
    protected $signature = 'pos:webhooks {--once : Send one batch and exit}';
    protected $description = 'Send product/stock notifications to POS with durable retries';

    public function handle(PosWebhookSender $sender): int
    {
        do {
            try {
                $result = $sender->sendPending();
                if ($result['sent'] || $result['failed']) $this->line('Delivered: '.$result['sent'].'; pending retries: '.$result['failed']);
                if ($this->option('once')) return $result['failed'] ? self::FAILURE : self::SUCCESS;
            } catch (Throwable $exception) {
                $this->error('POS sender could not run. Check .env and the outbox migration ('.class_basename($exception).').');
                if ($this->option('once')) return self::FAILURE;
                usleep(5_000_000);
            }
            usleep(2_000_000);
        } while (! $this->option('once'));
        return self::SUCCESS;
    }
}
