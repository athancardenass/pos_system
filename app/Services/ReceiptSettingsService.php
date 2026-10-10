<?php

namespace App\Services;

use App\Models\Receipt;
use App\Models\ReceiptSetting;

class ReceiptSettingsService
{
    /** @return array{id: int, store_name: string, store_address: ?string, tin: ?string, paper_width: string, footer_text: ?string} */
    public function current(): array
    {
        $setting = ReceiptSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'store_name' => 'Your Store',
                'paper_width' => '58',
                'footer_text' => 'Thank you for shopping with us.',
            ],
        );

        return [
            'id' => 1,
            'store_name' => (string) $setting->store_name,
            'store_address' => $setting->store_address,
            'tin' => $setting->tin,
            'paper_width' => in_array((string) $setting->paper_width, ['58', '80'], true) ? (string) $setting->paper_width : '58',
            'footer_text' => $setting->footer_text,
        ];
    }

    /** @param array{store_name: string, store_address: ?string, tin: ?string, paper_width: string, footer_text: ?string} $data */
    public function update(array $data): ReceiptSetting
    {
        return ReceiptSetting::query()->updateOrCreate(['id' => 1], $data);
    }

    /** @return array{id: int, store_name: string, store_address: ?string, tin: ?string, paper_width: string, footer_text: ?string} */
    public function forReceipt(?Receipt $receipt): array
    {
        $snapshot = $receipt?->settings_snapshot;
        if (is_array($snapshot) && isset($snapshot['store_name'])) {
            return [
                'id' => 1,
                'store_name' => (string) $snapshot['store_name'],
                'store_address' => $snapshot['store_address'] ?? null,
                'tin' => $snapshot['tin'] ?? null,
                'paper_width' => in_array((string) ($snapshot['paper_width'] ?? ''), ['58', '80'], true) ? (string) $snapshot['paper_width'] : '58',
                'footer_text' => $snapshot['footer_text'] ?? null,
            ];
        }

        return $this->current();
    }
}
