<?php

namespace App\Services;

use App\Models\SaleTransaction;
use App\Models\VatSetting;

class VatSettingsService
{
    public function currentRate(): float
    {
        $rate = VatSetting::query()->whereKey(VatSetting::SINGLETON_ID)->value('rate');

        return $rate !== null ? (float) $rate : (float) config('vat.rate', SaleTransaction::VAT_RATE);
    }

    public function currentRatePercent(): float
    {
        return round($this->currentRate() * 100, 2);
    }

    public function updateRatePercent(float $ratePercent): void
    {
        VatSetting::query()->updateOrCreate(
            ['id' => VatSetting::SINGLETON_ID],
            ['rate' => round($ratePercent / 100, 4)],
        );
    }
}
