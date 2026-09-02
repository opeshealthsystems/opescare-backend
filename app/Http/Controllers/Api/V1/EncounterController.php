<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AllergyResource;
use App\Http\Resources\ClinicalNoteResource;
use App\Http\Resources\DiagnosisResource;
use App\Models\ClinicalNote;
use App\Modules\EncounterManagement\Services\ConsultationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * EncounterController — Clinical Encounter Documentation API.
 *
 * Provides REST endpoints for clinical note writing, allergy recording,
 * and diagnosis coding within a patient visit.
 *
 * CLINICAL DATA RULE: Every write is audited via ConsultationService.
 * Amendments to signed notes create a new note row with provenance link
 * (amends_note_id). The original note is marked 'amended' — never deleted.
 *
 * Routes protected by VerifyIntegrationClient middleware.
 *
 * Endpoints:
 *  POST  /v1/encounters/notes                   — save (draft or signed) clinical note
 *  GET   /v1/encounters/notes/{note}            — retrieve a clinical note
 *  POST  /v1/encounters/notes/{note}/amend      — amend a signed clinical note
 *  POST  /v1/encounters/allergies               — record a patient allergy
 *  POST  /v1/encounters/diagnoses               — record a diagnosis (ICD-10 / SNOMED)
 */
class EncounterController extends Controller
{
    public function __construct(private readonly ConsultationService $service) {}

    // ── Clinical Notes ────────────────────────────────────────────────────

    /**
     * Save a clinical note (draft or signed).
     *
     * Body: {
     *   visit_id, provider_id,
     *   history_of_present_illness?, examination_findings?,
     *   treatment_plan?,
     *   status: draft|signed,
     *   actor_id?
     * }
     */
    public function saveNote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visit_id'                       => ['required', 'uuid', 'exists:visits,id'],
            'provider_id'                    => ['required', 'uuid'],
            'history_of_present_illness'     => ['nullable', 'string'],
            'examination_findings'           => ['nullable', 'string'],
            'treatment_plan'                 => ['nullable', 'string'],
            'status'                         => ['nullable', 'in:draft,signed'],
            'actor_id'                       => ['nullable', 'uuid'],
        ]);

        $note = $this->service->saveClinicalNote($validated, $validated['actor_id'] ?? null);

        return response()->json([
            'message' => $note->status === 'signed' ? 'Clinical note signed.' : 'Clinical note saved as draft.',
            'data'    => ClinicalNoteResource::make($note),
        ], 201);
    }

    /**
     * Retrieve a clinical note.
     */
    public function showNote(ClinicalNote $note): JsonResponse
    {
        return response()->json(['data' => ClinicalNoteResource::make($note)]);
    }

    /**
     * Amend a signed clinical note.
     * Creates a new signed note linked to the original via amends_note_id.
     * The original note is marked 'amended' (immutable audit trail preserved).
     *
     * Body: {
     *   history_of_present_illness?, examination_findings?, treatment_plan?,
     *   amendment_reason (required),
     *   actor_id?
     * }
     */
    public function amendNote(ClinicalNote $note, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'history_of_present_illness' => ['nullable', 'string'],
            'examination_findings'       => ['nullable', 'string'],
            'treatment_plan'             => ['nullable', 'string'],
            'amendment_reason'           => ['required', 'string', 'min:10', 'max:1000'],
            'actor_id'                   => ['nullable', 'uuid'],
        ]);

        try {
            $amended = $this->service->amendClinicalNote(
                $note->id,
                $validated,
                $validated['actor_id'] ?? null
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'           => __('api.clinical_note_amended'),
            'data'              => ClinicalNoteResource::make($amended),
            'original_note_id'  => $note->id,
        ], 201);
    }

    // ── Allergies ─────────────────────────────────────────────────────────

    /**
     * Record a patient allergy.
     *
     * Body: { patient_id, substance, severity?, actor_id? }
     * severity: mild|moderate|severe|life_threatening
     */
    public function recordAllergy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id' => ['required', 'uuid', 'exists:patients,id'],
            'substance'  => ['required', 'string', 'max:255'],
            'severity'   => ['nullable', 'in:mild,moderate,severe,life_threatening'],
            'actor_id'   => ['nullable', 'uuid'],
        ]);

        $allergy = $this->service->recordAllergy($validated, $validated['actor_id'] ?? null);

        return response()->json(['message' => __('api.allergy_recorded'), 'data' => AllergyResource::make($allergy)], 201);
    }

    // ── Diagnoses ─────────────────────────────────────────────────────────

    /**
     * Record a diagnosis for a visit.
     *
     * Body: {
     *   patient_id, visit_id, display_name,
     *   code_system?: ICD-10|SNOMED|other,
     *   code?,
     *   status?: active|resolved|ruled_out,
     *   is_primary?: boolean,
     *   actor_id?
     * }
     */
    public function recordDiagnosis(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'patient_id'   => ['required', 'uuid', 'exists:patients,id'],
            'visit_id'     => ['required', 'uuid', 'exists:visits,id'],
            'display_name' => ['required', 'string', 'max:255'],
            'code_system'  => ['nullable', 'string', 'in:ICD-10,SNOMED,other'],
            'code'         => ['nullable', 'string', 'max:50'],
            'status'       => ['nullable', 'in:active,resolved,ruled_out'],
            'is_primary'   => ['nullable', 'boolean'],
            'actor_id'     => ['nullable', 'uuid'],
        ]);

        $diagnosis = $this->service->recordDiagnosis($validated, $validated['actor_id'] ?? null);

        return response()->json(['message' => __('api.diagnosis_recorded'), 'data' => DiagnosisResource::make($diagnosis)], 201);
    }
}
