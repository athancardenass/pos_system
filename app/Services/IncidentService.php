<?php

namespace App\Services;

use App\Models\CashDrawer;
use App\Models\IncidentReport;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IncidentService
{
    // Call while holding the product row lock, shared with checkout and catalog sync.
    public function assertSellable(int $productId): void
    {
        // A locking read sees reports committed while this transaction waited for
        // the product lock, even when MariaDB already established a snapshot.
        if (IncidentReport::where('kind', 'product_safety')->where('product_id', $productId)->where('status', '!=', 'resolved')->lockForUpdate()->first(['id'])) {
            throw ValidationException::withMessages(['items' => 'This product is on safety hold. Ask a manager to review its incident report.']);
        }
    }

    public function reportSafety(array $data): IncidentReport
    {
        return DB::transaction(function () use ($data) {
            $product = Product::whereKey($data['product_id'])->lockForUpdate()->firstOrFail();
            $incident = IncidentReport::create(array_merge($data, [
                'kind' => 'product_safety', 'employee_id' => auth()->id(), 'status' => 'reported',
                'snapshot' => ['product_name' => $product->product_name, 'barcode' => $product->barcode,
                    'inventory_sku' => $product->inventoryMapping?->inventory_sku],
                'history' => [$this->entry('reported', 'Product placed on safety hold.')],
            ]));
            AuditLogger::record('safety_reported', 'incident_reports', $incident->id, 'Reported product safety concern; product placed on hold.');
            return $incident;
        });
    }

    public function recordVariance(CashDrawer $drawer): void
    {
        if (abs((float) $drawer->difference) < .005) {
            return;
        }
        $incident = IncidentReport::firstOrCreate(['drawer_id' => $drawer->drawer_id], [
            'kind' => 'cash_variance', 'employee_id' => $drawer->employee_id,
            'description' => $drawer->reconciliation_notes, 'expected_cash' => $drawer->expected_cash,
            'actual_cash' => $drawer->actual_cash, 'difference' => $drawer->difference,
            'snapshot' => ['shift' => $drawer->report_snapshot, 'hrms_employee_no' => $drawer->employee?->hrms_employee_no],
            'history' => [$this->entry('reported', 'Variance recorded from closed shift.')],
        ]);
        if ($incident->wasRecentlyCreated) {
            AuditLogger::record('variance_reported', 'incident_reports', $incident->id, 'Created cash variance case for shift '.$drawer->drawer_id.'.');
        }
    }

    public function review(IncidentReport $incident, string $status, string $findings): void
    {
        DB::transaction(function () use ($incident, $status, $findings) {
            if ($incident->product_id) {
                Product::whereKey($incident->product_id)->lockForUpdate()->firstOrFail();
            }
            $locked = IncidentReport::whereKey($incident->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'resolved') {
                throw ValidationException::withMessages(['status' => 'This case is resolved. File a new report for a new concern.']);
            }
            $history = $locked->history;
            $history[] = $this->entry($status, $findings);
            $locked->update(['status' => $status, 'findings' => $findings, 'reviewed_by' => auth()->id(), 'history' => $history]);
            AuditLogger::record('incident_reviewed', 'incident_reports', $locked->id, 'Manager reviewed incident: '.$status.'.');
        });
    }

    public function acknowledge(IncidentReport $incident, string $reference): void
    {
        DB::transaction(function () use ($incident, $reference) {
            $locked = IncidentReport::whereKey($incident->id)->lockForUpdate()->firstOrFail();
            if (! $locked->reviewed_by) {
                throw ValidationException::withMessages(['external_reference' => 'Review this case before recording external acknowledgement.']);
            }
            if ($locked->external_reference) {
                throw ValidationException::withMessages(['external_reference' => 'An external acknowledgement is already recorded.']);
            }
            $history = $locked->history;
            $history[] = $this->entry('acknowledged_manually', $reference);
            $locked->update(['external_status' => 'acknowledged_manually', 'external_reference' => $reference, 'history' => $history]);
            AuditLogger::record('incident_acknowledged', 'incident_reports', $locked->id, 'Manager recorded manual external acknowledgement.');
        });
    }

    private function entry(string $action, string $notes): array
    {
        return ['action' => $action, 'notes' => $notes, 'employee_id' => auth()->id(), 'at' => now()->toIso8601String()];
    }
}
