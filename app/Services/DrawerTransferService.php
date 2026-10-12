<?php

namespace App\Services;

use App\Models\CashDrawer;
use App\Models\DrawerTransfer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DrawerTransferService
{
    public function __construct(private readonly CashDrawerService $drawers) {}

    public function initiate(int $employeeId, array $data, array $approval): DrawerTransfer
    {
        return DB::transaction(function () use ($employeeId, $data, $approval) {
            $source = $this->drawers->getOpenDrawer($employeeId);
            if (! $source) {
                throw ValidationException::withMessages(['drawer' => 'Open a shift before transferring cash.']);
            }
            $pair = $this->lockPair($source->drawer_id, (int) $data['destination_drawer_id']);
            $source = $pair->get($source->drawer_id);
            $dest = $pair->get((int) $data['destination_drawer_id']);
            $old = DrawerTransfer::where('operation_key', $data['operation_key'])->lockForUpdate()->first();
            if ($old) {
                if ($old->source_drawer_id != $source->drawer_id || $old->destination_drawer_id != $dest->drawer_id
                    || abs((float) $old->amount - (float) $data['amount']) > .001 || $old->reason !== $data['reason']) {
                    throw ValidationException::withMessages(['operation_key' => 'This transfer request ID has different data.']);
                }

                return $old;
            }
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0 || $amount > (float) $source->expected_cash) {
                throw ValidationException::withMessages(['amount' => 'Transfer must be positive and cannot exceed expected drawer cash.']);
            }
            $transfer = DrawerTransfer::create(['operation_key' => $data['operation_key'], 'source_drawer_id' => $source->drawer_id,
                'destination_drawer_id' => $dest->drawer_id, 'amount' => $amount, 'reason' => $data['reason'],
                'approved_by_employee_id' => $approval['approved_by_employee_id'], 'status' => 'pending']);
            $this->drawers->record($source, ['kind' => 'transfer_out', 'payment_method' => 'cash', 'amount' => -$amount,
                'reason' => $transfer->reason, 'approved_by_employee_id' => $transfer->approved_by_employee_id,
                'transfer_id' => $transfer->id, 'operation_key' => 'transfer-out:'.$transfer->id]);
            AuditLogger::record('drawer_transfer_sent', 'drawer_transfers', $transfer->id, 'Cash transfer awaiting recipient confirmation.');

            return $transfer;
        });
    }

    public function accept(int $employeeId, DrawerTransfer $transfer): DrawerTransfer
    {
        return DB::transaction(function () use ($employeeId, $transfer) {
            $pair = $this->lockPair($transfer->source_drawer_id, $transfer->destination_drawer_id);
            $transfer = DrawerTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            $destination = $pair->get($transfer->destination_drawer_id);
            if ((int) $destination->employee_id !== $employeeId) {
                throw new AuthorizationException('Only the receiving cashier may confirm receipt.');
            }
            if ($transfer->status === 'accepted') {
                return $transfer;
            }
            if ($transfer->status !== 'pending') {
                throw ValidationException::withMessages(['transfer' => 'This transfer was cancelled.']);
            }
            $this->drawers->record($destination, ['kind' => 'transfer_in', 'payment_method' => 'cash', 'amount' => (float) $transfer->amount,
                'reason' => $transfer->reason, 'approved_by_employee_id' => $transfer->approved_by_employee_id,
                'transfer_id' => $transfer->id, 'operation_key' => 'transfer-in:'.$transfer->id]);
            $transfer->update(['status' => 'accepted', 'accepted_at' => now()]);
            AuditLogger::record('drawer_transfer_received', 'drawer_transfers', $transfer->id, 'Receiving cashier confirmed cash receipt.');

            return $transfer->fresh();
        });
    }

    public function cancel(int $employeeId, DrawerTransfer $transfer, array $approval): DrawerTransfer
    {
        return DB::transaction(function () use ($employeeId, $transfer, $approval) {
            $pair = $this->lockPair($transfer->source_drawer_id, $transfer->destination_drawer_id);
            $transfer = DrawerTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            $source = $pair->get($transfer->source_drawer_id);
            if ((int) $source->employee_id !== $employeeId) {
                throw new AuthorizationException('Only the sending cashier may cancel.');
            }
            if ($transfer->status === 'cancelled') {
                return $transfer;
            }
            if ($transfer->status !== 'pending') {
                throw ValidationException::withMessages(['transfer' => 'Received transfers cannot be cancelled.']);
            }
            $this->drawers->record($source, ['kind' => 'transfer_reversal', 'payment_method' => 'cash', 'amount' => (float) $transfer->amount,
                'reason' => 'Cancelled transfer: '.$transfer->reason, 'approved_by_employee_id' => $approval['approved_by_employee_id'],
                'transfer_id' => $transfer->id, 'operation_key' => 'transfer-cancel:'.$transfer->id]);
            $transfer->update(['status' => 'cancelled', 'cancelled_at' => now()]);
            AuditLogger::record('drawer_transfer_cancelled', 'drawer_transfers', $transfer->id, 'Unreceived transfer returned to source drawer.');

            return $transfer->fresh();
        });
    }

    private function lockPair(int $source, int $destination)
    {
        if ($source === $destination) {
            throw ValidationException::withMessages(['destination_drawer_id' => 'Choose another open drawer.']);
        }
        $pair = CashDrawer::whereIn('drawer_id', [$source, $destination])->orderBy('drawer_id')->lockForUpdate()->get()->keyBy('drawer_id');
        if ($pair->count() !== 2 || $pair->contains(fn ($d) => ! $d->isOpen())) {
            throw ValidationException::withMessages(['drawer' => 'Both drawer shifts must remain open.']);
        }

        return $pair;
    }
}
