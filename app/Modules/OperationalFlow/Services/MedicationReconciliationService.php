<?php
namespace App\Modules\OperationalFlow\Services;

use App\Models\DrugInteractionAlert;
use App\Models\MedicationReconciliation;
use App\Modules\ClinicalDecisionSupport\Services\DrugInteractionService;
use Illuminate\Support\Facades\DB;

class MedicationReconciliationService
{
    public function __construct(
        private DrugInteractionService $interactionService = new DrugInteractionService()
    ) {}

    /**
     * Create a reconciliation record.
     *
     * Throws \Exception('HARD_STOP_CONTRAINDICATION: ...') if any medication
     * with flag_hard_stop=true has a contraindicated interaction.
     */
    public function createReconciliation(
        string  $patientId,
        string  $providerId,
        string  $facilityId,
        array   $medications,
        ?string $notes = null
    ): MedicationReconciliation {
        $alerts = $this->interactionService->checkInteractions($medications);

        foreach ($medications as $med) {
            if (!empty($med['flag_hard_stop'])) {
                foreach ($alerts as $alert) {
                    if (
                        ($alert['drug_a'] === strtolower($med['name']) ||
                         $alert['drug_b'] === strtolower($med['name'])) &&
                        $alert['is_hard_stop']
                    ) {
                        throw new \Exception('HARD_STOP_CONTRAINDICATION: ' . $alert['description']);
                    }
                }
            }
        }

        return DB::transaction(function () use (
            $patientId, $providerId, $facilityId, $medications, $notes, $alerts
        ) {
            $rec = MedicationReconciliation::create([
                'patient_id'  => $patientId,
                'provider_id' => $providerId,
                'facility_id' => $facilityId,
                'medications' => $medications,
                'notes'       => $notes,
                'status'      => 'pending_review',
            ]);

            foreach ($alerts as $alert) {
                DrugInteractionAlert::create(array_merge($alert, [
                    'reconciliation_id' => $rec->id,
                ]));
            }

            return $rec;
        });
    }

    public function acknowledge(string $alertId, string $userId): DrugInteractionAlert
    {
        $alert = DrugInteractionAlert::findOrFail($alertId);
        $alert->update([
            'acknowledged'    => true,
            'acknowledged_by' => $userId,
            'acknowledged_at' => now(),
        ]);
        return $alert;
    }
}
