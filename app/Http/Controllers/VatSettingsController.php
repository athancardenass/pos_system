<?php

namespace App\Http\Controllers;

use App\Models\VatSetting;
use App\Services\AuditLogger;
use App\Services\VatSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VatSettingsController extends Controller
{
    public function edit(VatSettingsService $settings): View
    {
        return view('settings.vat', [
            'ratePercent' => $settings->currentRatePercent(),
        ]);
    }

    public function update(Request $request, VatSettingsService $settings): RedirectResponse
    {
        $validated = $request->validate([
            'rate_percent' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100'],
        ]);

        $oldRate = $settings->currentRatePercent();
        $newRate = round((float) $validated['rate_percent'], 2);
        $settings->updateRatePercent($newRate);

        AuditLogger::record(
            'update',
            'vat_settings',
            VatSetting::SINGLETON_ID,
            sprintf('Changed VAT rate from %.2f%% to %.2f%%', $oldRate, $newRate),
        );

        return redirect()->route('vat-settings.edit')
            ->with('status', 'VAT rate updated. The new rate applies to future sales.');
    }
}
