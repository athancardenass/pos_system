<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\ReceiptSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceiptSettingsController extends Controller
{
    public function edit(ReceiptSettingsService $settings): View
    {
        return view('settings.receipt', ['settings' => $settings->current()]);
    }

    public function update(Request $request, ReceiptSettingsService $settings): RedirectResponse
    {
        $data = $request->validate([
            'store_name' => 'required|string|max:150',
            'store_address' => 'nullable|string|max:1000',
            'tin' => 'nullable|string|max:50',
            'paper_width' => 'required|in:58,80',
            'footer_text' => 'nullable|string|max:500',
        ]);

        $settings->update($data);
        AuditLogger::record('update', 'receipt_settings', 1, 'Updated store and receipt settings.');

        return redirect()->route('receipt-settings.edit')->with('status', 'Receipt settings saved. New settings apply to future receipts.');
    }
}
