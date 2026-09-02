<?php

namespace App\Modules\WardManagement\Services;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\BedTransfer;
use App\Models\DischargePlan;
use App\Models\VisitTimeline;
use Illuminate\Support\Facades\DB;

/**
 * AdmissionService — Module 19 (Ward / Admission / Bed Management)
 *
 * Manages the full inpatient admission lifecycle:
 * admit → bed assignment → transfers → discharge planning → discharge.
 */
class AdmissionService
{
    /**
     * Admit a patient to a bed.
     */
    public function admit(array $data, string $actorId): Admission
    {
        return DB::transaction(function () use ($data, $actorId) {
            // Row-lock the bed for the duration of the transaction so two
            // concurrent admits cannot both observe it available and double-book.
            $bed = Bed::whereKey($data['bed_id'])->lockForUpdate()->firstOrFail();
            if ($bed->status !== 'available') {
                throw new \RuntimeException("Bed {$bed->id} is not available.");
            }

            $admission = Admission::create(array_merge($data, [
                'status'       => 'admitted',
                'admitted_at'  => $data['admitted_at'] ?? now(),
                'admitted_by'  => $actorId,
            ]));

            // Mark bed as occupied
            $bed->update(['status' => 'occupied']);

            if (isset($data['visit_id'])) {
                VisitTimeline::record($data['visit_id'], 'patient_admitted', [
                    'admission_id' => $admission->id,
                    'bed_id'       => $bed->id,
                ]);
            }

            return $admission;
        });
    }

    /**
     * Transfer patient to a different bed.
     */
    public function transferBed(
        Admission $admission,
        string $toBedId,
        string $reason,
        string $transferredBy
    ): BedTransfer {
        $fromBedId = $admission->bed_id;
        $toBed = Bed::findOrFail($toBedId);

        if ($toBed->status !== 'available') {
            throw new \RuntimeException("Target bed {$toBedId} is not available.");
        }

        // Record transfer
        $transfer = BedTransfer::create([
            'admission_id'    => $admission->id,
            'from_bed_id'     => $fromBedId,
            'to_bed_id'       => $toBedId,
            'reason'          => $reason,
            'transferred_by'  => $transferredBy,
            'transferred_at'  => now(),
        ]);

        // Update bed statuses
        if ($fromBedId) {
            Bed::where('id', $fromBedId)->update(['status' => 'available']);
        }
        $toBed->update(['status' => 'occupied']);

        // Update admission bed
        $admission->update(['bed_id' => $toBedId]);

        return $transfer;
    }

    /**
     * Create a discharge plan for an admission.
     */
    public function createDischargePlan(array $data): DischargePlan
    {
        return DischargePlan::create(array_merge($data, [
            'status' => 'draft',
        ]));
    }

    /**
     * Discharge a patient — requires an approved discharge plan.
     */
    public function discharge(
        Admission $admission,
        string $dischargeReason,
        string $dischargeDestination,
        string $actorId
    ): void {
        // Verify discharge plan is approved
        $plan = DischargePlan::where('admission_id', $admission->id)
            ->where('status', 'approved')
            ->first();

        if (! $plan) {
            throw new \RuntimeException('No approved discharge plan found. Approve a discharge plan before discharging.');
        }

        // Free the bed
        if ($admission->bed_id) {
            Bed::where('id', $admission->bed_id)->update(['status' => 'available']);
        }

        $admission->update([
            'status'                => 'discharged',
            'discharge_reason'      => $dischargeReason,
            'discharge_destination' => $dischargeDestination,
            'discharged_at'         => now(),
        ]);

        $plan->update(['status' => 'completed']);

        if ($admission->visit_id) {
            VisitTimeline::record($admission->visit_id, 'patient_discharged', [
                'admission_id'          => $admission->id,
                'discharge_destination' => $dischargeDestination,
                'actor_id'              => $actorId,
            ]);
        }
    }
}
