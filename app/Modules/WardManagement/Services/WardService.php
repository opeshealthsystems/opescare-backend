<?php

namespace App\Modules\WardManagement\Services;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\BedTransfer;
use App\Models\Ward;
use Illuminate\Support\Facades\DB;

class WardService
{
    // ── Ward CRUD ─────────────────────────────────────────────────

    public function createWard(array $data): Ward
    {
        $ward = Ward::create($data);

        // Auto-seed beds if total_beds > 0
        if (!empty($data['total_beds']) && $data['total_beds'] > 0) {
            $this->seedBeds($ward, $data['total_beds'], $data['bed_type'] ?? 'standard');
        }

        return $ward;
    }

    public function seedBeds(Ward $ward, int $count, string $bedType = 'standard'): void
    {
        $prefix = strtoupper(substr($ward->name, 0, 1));
        for ($i = 1; $i <= $count; $i++) {
            Bed::firstOrCreate(
                ['ward_id' => $ward->id, 'bed_number' => $prefix . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)],
                ['status' => 'available', 'bed_type' => $bedType]
            );
        }
    }

    // ── Admission ─────────────────────────────────────────────────

    public function admit(array $data, string $actorId): Admission
    {
        DB::beginTransaction();
        try {
            // Row-lock the bed for the duration of the transaction so two
            // concurrent admits cannot both observe it as available and double-book.
            $bed = Bed::whereKey($data['bed_id'])->lockForUpdate()->firstOrFail();

            if (!$bed->isAvailable()) {
                throw new \RuntimeException("Bed {$bed->bed_number} is not available (status: {$bed->status}).");
            }

            $admission = Admission::create([
                'facility_id'             => $data['facility_id'],
                'patient_id'              => $data['patient_id'],
                'bed_id'                  => $bed->id,
                'visit_id'                => $data['visit_id'] ?? null,
                'admitted_by'             => $actorId,
                'attending_physician_id'  => $data['attending_physician_id'] ?? null,
                'status'                  => 'active',
                'admission_reason'        => $data['admission_reason'] ?? null,
                'admitted_at'             => now(),
            ]);

            $bed->update(['status' => 'occupied']);

            DB::commit();
            return $admission;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ── Discharge ─────────────────────────────────────────────────

    public function discharge(Admission $admission, array $data, string $actorId): Admission
    {
        DB::beginTransaction();
        try {
            $admission->update([
                'status'               => 'discharged',
                'discharge_reason'     => $data['discharge_reason'] ?? null,
                'discharge_destination'=> $data['discharge_destination'] ?? 'home',
                'discharged_at'        => now(),
            ]);

            $admission->bed->update(['status' => 'available']);

            DB::commit();
            return $admission->fresh();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ── Transfer ──────────────────────────────────────────────────

    public function transfer(Admission $admission, string $toBedId, ?string $reason, string $actorId): BedTransfer
    {
        DB::beginTransaction();
        try {
            $toBed = Bed::findOrFail($toBedId);
            if (!$toBed->isAvailable()) {
                throw new \RuntimeException("Target bed {$toBed->bed_number} is not available.");
            }

            $fromBedId = $admission->bed_id;

            // Free old bed
            $admission->bed->update(['status' => 'available']);

            // Occupy new bed
            $toBed->update(['status' => 'occupied']);

            // Update admission
            $admission->update(['bed_id' => $toBedId]);

            $transfer = BedTransfer::create([
                'admission_id'   => $admission->id,
                'from_bed_id'    => $fromBedId,
                'to_bed_id'      => $toBedId,
                'reason'         => $reason,
                'transferred_by' => $actorId,
                'transferred_at' => now(),
            ]);

            DB::commit();
            return $transfer;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ── Occupancy snapshot ────────────────────────────────────────

    public function occupancySummary(string $facilityId): array
    {
        $wards = Ward::where('facility_id', $facilityId)
            ->where('is_active', true)
            ->withCount(['beds', 'beds as available_count' => fn($q) => $q->where('status','available'),
                          'beds as occupied_count' => fn($q) => $q->where('status','occupied')])
            ->get();

        $totalBeds   = $wards->sum('beds_count');
        $totalOccupied = $wards->sum('occupied_count');
        $totalAvailable = $wards->sum('available_count');

        return [
            'wards'          => $wards,
            'total_beds'     => $totalBeds,
            'total_occupied' => $totalOccupied,
            'total_available'=> $totalAvailable,
            'occupancy_rate' => $totalBeds > 0 ? round(($totalOccupied / $totalBeds) * 100, 1) : 0,
        ];
    }
}
