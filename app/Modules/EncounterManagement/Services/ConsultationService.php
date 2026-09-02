<?php

namespace App\Modules\EncounterManagement\Services;

use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\AllergyRecord;
use App\Models\AuditEvent;
use Exception;
use Illuminate\Support\Facades\DB;

class ConsultationService
{
    /**
     * Saves a draft or signed clinical note.
     */
    public function saveClinicalNote(array $data, ?string $actorId = null): ClinicalNote
    {
        DB::beginTransaction();
        try {
            $note = ClinicalNote::create([
                'visit_id' => $data['visit_id'],
                'provider_id' => $data['provider_id'] ?? $actorId,
                'history_of_present_illness' => $data['history_of_present_illness'] ?? null,
                'examination_findings' => $data['examination_findings'] ?? null,
                'treatment_plan' => $data['treatment_plan'] ?? null,
                'status' => $data['status'] ?? 'draft',
                'signed_at' => (isset($data['status']) && $data['status'] === 'signed') ? now() : null,
            ]);

            AuditEvent::create([
                'actor_id' => $actorId,
                'encounter_id' => $note->visit_id,
                'action_type' => 'create',
                'resource_type' => 'clinical_note',
                'resource_id' => $note->id,
                'reason' => 'Clinical note saved',
            ]);

            DB::commit();
            return $note;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Amends an already signed note. A signed note cannot be overwritten silently.
     */
    public function amendClinicalNote(string $originalNoteId, array $data, ?string $actorId = null): ClinicalNote
    {
        DB::beginTransaction();
        try {
            $original = ClinicalNote::findOrFail($originalNoteId);

            if ($original->status !== 'signed') {
                throw new Exception("Only signed notes can be amended. Drafts can be updated directly.");
            }

            $original->update(['status' => 'amended']);

            $newNote = ClinicalNote::create([
                'visit_id' => $original->visit_id,
                'provider_id' => $actorId,
                'history_of_present_illness' => $data['history_of_present_illness'] ?? $original->history_of_present_illness,
                'examination_findings' => $data['examination_findings'] ?? $original->examination_findings,
                'treatment_plan' => $data['treatment_plan'] ?? $original->treatment_plan,
                'status' => 'signed',
                'signed_at' => now(),
                'amends_note_id' => $original->id, // explicit provenance linking
            ]);

            AuditEvent::create([
                'actor_id' => $actorId,
                'encounter_id' => $newNote->visit_id,
                'action_type' => 'amend',
                'resource_type' => 'clinical_note',
                'resource_id' => $newNote->id,
                'reason' => $data['amendment_reason'] ?? 'Clinical note amended',
            ]);

            DB::commit();
            return $newNote;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function recordAllergy(array $data, ?string $actorId = null): AllergyRecord
    {
        DB::beginTransaction();
        try {
            $allergy = AllergyRecord::create([
                'patient_id' => $data['patient_id'],
                'provider_id' => $actorId,
                'substance' => $data['substance'],
                'severity' => $data['severity'] ?? 'moderate',
                'status' => 'active',
            ]);

            AuditEvent::create([
                'actor_id' => $actorId,
                'patient_id' => $data['patient_id'],
                'action_type' => 'create',
                'resource_type' => 'allergy_record',
                'resource_id' => $allergy->id,
            ]);

            DB::commit();
            return $allergy;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function recordDiagnosis(array $data, ?string $actorId = null): Diagnosis
    {
        DB::beginTransaction();
        try {
            $diagnosis = Diagnosis::create([
                'patient_id' => $data['patient_id'],
                'visit_id' => $data['visit_id'],
                'provider_id' => $actorId,
                'code_system' => $data['code_system'] ?? null,
                'code' => $data['code'] ?? null,
                'display_name' => $data['display_name'],
                'status' => $data['status'] ?? 'active',
                'is_primary' => $data['is_primary'] ?? true,
            ]);

            AuditEvent::create([
                'actor_id' => $actorId,
                'patient_id' => $data['patient_id'],
                'encounter_id' => $data['visit_id'],
                'action_type' => 'create',
                'resource_type' => 'diagnosis',
                'resource_id' => $diagnosis->id,
            ]);

            DB::commit();
            return $diagnosis;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}