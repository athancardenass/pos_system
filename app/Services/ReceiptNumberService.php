<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\SaleTransaction;
use Illuminate\Support\Facades\DB;

class ReceiptNumberService
{
    /**
     * Issue the next receipt number inside the caller's sale transaction.
     * Counter updates roll back with failed checkouts; refunds keep the issued number.
     */
    public function issue(SaleTransaction $sale, ?string $registerId): Receipt
    {
        $registerId = trim((string) $registerId) ?: 'REG 01';
        $registerId = mb_substr($registerId, 0, 50);

        DB::table('register_receipt_counters')->insertOrIgnore([
            'register_id' => $registerId,
            'last_sequence' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counter = DB::table('register_receipt_counters')
            ->where('register_id', $registerId)
            ->lockForUpdate()
            ->first();

        $registerTag = strtoupper((string) preg_replace('/[^a-zA-Z0-9]/', '', $registerId));
        $registerTag = mb_substr($registerTag !== '' ? $registerTag : 'REG', 0, 12);
        $lastSequence = (int) $counter->last_sequence;
        if ($lastSequence === 0) {
            $lastSequence = (int) (Receipt::query()
                ->where('register_id', $registerId)
                ->max('sequence_number') ?? 0);

            // Keep issued numbers consumed if the schema migration is rolled back
            // and later reapplied (which removes the counter metadata but not receipts).
            if ($lastSequence === 0) {
                foreach (Receipt::query()
                    ->where('receipt_number', 'like', $registerTag.'-%')
                    ->pluck('receipt_number') as $existingNumber) {
                    if (preg_match('/^'.preg_quote($registerTag, '/').'-([0-9]+)$/', (string) $existingNumber, $matches) === 1) {
                        $lastSequence = max($lastSequence, (int) $matches[1]);
                    }
                }
            }
        }

        $sequence = $lastSequence + 1;
        DB::table('register_receipt_counters')
            ->where('register_id', $registerId)
            ->update(['last_sequence' => $sequence, 'updated_at' => now()]);
        $settings = app(ReceiptSettingsService::class)->current();

        return $sale->receipt()->create([
            'receipt_number' => $registerTag.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'issued_date' => now(),
            'register_id' => $registerId,
            'sequence_number' => $sequence,
            'settings_snapshot' => $settings,
        ]);
    }
}
