<?php
namespace App\Services\Pharmacy;

use App\Models\ControlledSubstanceDispensing;
use App\Models\ControlledSubstanceInventory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ControlledSubstanceService {
    private const DISCREPANCY_THRESHOLD    = 0.01;
    private const WITNESS_REQUIRED_SCHEDULES = ['schedule_ii'];

    public function dispense(array $data): ControlledSubstanceDispensing {
        if (in_array($data['schedule'], self::WITNESS_REQUIRED_SCHEDULES, true) && empty($data['witness_id'])) {
            throw new RuntimeException("Schedule II controlled substances require a witness. Provide witness_id.");
        }

        return DB::transaction(function () use ($data): ControlledSubstanceDispensing {
            $inventory = ControlledSubstanceInventory::where('facility_id', $data['facility_id'])
                ->where('drug_code', $data['drug_code'])
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = (float) $inventory->current_balance;
            $qty           = (float) $data['quantity_dispensed'];

            if ($balanceBefore < $qty) {
                throw new RuntimeException("Insufficient stock: balance {$balanceBefore} {$inventory->unit}, requested {$qty}.");
            }

            $balanceAfter = round($balanceBefore - $qty, 2);
            $inventory->update(['current_balance' => $balanceAfter]);

            return ControlledSubstanceDispensing::create(array_merge($data, [
                'stock_balance_before' => $balanceBefore,
                'stock_balance_after'  => $balanceAfter,
                'dispensed_at'         => $data['dispensed_at'] ?? now(),
            ]));
        });
    }

    public function confirmWitness(string $dispensingId, string $witnessId): ControlledSubstanceDispensing {
        $dispensing = ControlledSubstanceDispensing::findOrFail($dispensingId);
        if (!in_array($dispensing->schedule, self::WITNESS_REQUIRED_SCHEDULES, true)) {
            throw new RuntimeException("Witness confirmation is only required for: " . implode(', ', self::WITNESS_REQUIRED_SCHEDULES));
        }
        if ($dispensing->witness_confirmed_at !== null) {
            throw new RuntimeException("Dispensing {$dispensingId} has already been witnessed.");
        }
        $dispensing->update([
            'witness_id'           => $witnessId,
            'witness_confirmed_at' => now(),
        ]);
        return $dispensing->fresh();
    }

    public function reconcileInventory(string $facilityId, string $drugCode, float $actualBalance, string $reconcilierId): ControlledSubstanceInventory {
        $inventory   = ControlledSubstanceInventory::where('facility_id', $facilityId)->where('drug_code', $drugCode)->firstOrFail();
        $discrepancy = abs((float) $inventory->current_balance - $actualBalance);
        if ($discrepancy > self::DISCREPANCY_THRESHOLD) {
            $this->flagDiscrepancy($inventory->id, "Reconciliation discrepancy: recorded {$inventory->current_balance}, physical count {$actualBalance}, delta {$discrepancy} {$inventory->unit}.");
        }
        $inventory->update([
            'current_balance'    => $actualBalance,
            'last_reconciled_at' => now(),
            'last_reconciled_by' => $reconcilierId,
        ]);
        return $inventory->fresh();
    }

    public function getDispenseLog(string $facilityId, Carbon $from, Carbon $to): Collection {
        return ControlledSubstanceDispensing::where('facility_id', $facilityId)
            ->whereBetween('dispensed_at', [$from, $to])
            ->with(['patient','dispensedBy','witness'])
            ->orderByDesc('dispensed_at')
            ->get();
    }

    public function getInventory(string $facilityId): Collection {
        return ControlledSubstanceInventory::where('facility_id', $facilityId)
            ->orderBy('schedule')->orderBy('drug_name')->get();
    }

    public function flagDiscrepancy(string $inventoryId, string $reason): void {
        Log::critical('Controlled substance inventory discrepancy detected', [
            'inventory_id' => $inventoryId,
            'reason'       => $reason,
            'flagged_at'   => now()->toIso8601String(),
        ]);
        if (class_exists(\App\Events\ControlledSubstanceDiscrepancy::class)) {
            \Illuminate\Support\Facades\Event::dispatch(new \App\Events\ControlledSubstanceDiscrepancy($inventoryId, $reason));
        }
    }
}
