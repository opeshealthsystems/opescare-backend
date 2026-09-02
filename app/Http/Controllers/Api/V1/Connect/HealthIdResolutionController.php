<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Enums\IdentityStatus;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientMergeAlias;
use App\Services\AuditLogger;
use App\Services\Identity\HealthIdGeneratorService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Health ID Resolution — the "find or create" interoperability endpoint.
 *
 * External HIS systems (e.g. OpesCare HIS) call this endpoint when they have
 * a patient who may or may not already have an OpesCare Health ID.
 *
 * SECURITY MODEL:
 *  - All callers must be authenticated via `auth.bearer` middleware (RS256 JWT).
 *  - Lookups are scoped to the caller's facility — cross-facility enumeration
 *    is blocked unless the integration client has the `global_resolve` permission.
 *  - Auto-creation requires explicit `consent_acknowledged: true` in the payload.
 *    Without it the endpoint returns 422 CONSENT_REQUIRED rather than creating
 *    a shadow identity the patient never consented to.
 *
 * Flow:
 *  1. If health_id provided → look up patient. Return summary.
 *  2. If demographics provided → search by name + DOB + country.
 *     a. Found → return existing Health ID + summary.
 *     b. Not found + consent_acknowledged → create patient + generate Health ID.
 *     c. Not found + no consent → return 422 CONSENT_REQUIRED.
 */
class HealthIdResolutionController extends Controller
{
    public function __construct(private HealthIdGeneratorService $generator) {}

    public function resolve(Request $request): JsonResponse
    {
        // ── Input validation ──────────────────────────────────────────────────
        $validated = $request->validate([
            'health_id'            => 'nullable|string|max:30',
            'first_name'           => 'nullable|string|max:100',
            'last_name'            => 'nullable|string|max:100',
            'date_of_birth'        => 'nullable|date_format:Y-m-d',
            'country_code'         => 'nullable|string|size:2',
            'sex'                  => 'nullable|string|in:male,female',
            'phone_number'         => 'nullable|string|max:30',
            'purpose'              => 'required|string|max:100',
            'external_reference'   => 'nullable|string|max:200',
            // SECURITY: Caller must explicitly acknowledge patient consent before
            // an identity record can be auto-created on their behalf.
            'consent_acknowledged' => 'nullable|boolean',
        ]);

        $healthId           = $validated['health_id']          ?? null;
        $firstName          = $validated['first_name']          ?? null;
        $lastName           = $validated['last_name']           ?? null;
        $dob                = $validated['date_of_birth']       ?? null;
        $countryCode        = strtoupper($validated['country_code'] ?? 'CM');
        $sex                = $validated['sex']                 ?? null;
        $phone              = $validated['phone_number']        ?? null;
        $purpose            = $validated['purpose'];
        $externalRef        = $validated['external_reference']  ?? null;
        $consentAcknowledged = (bool) ($validated['consent_acknowledged'] ?? false);

        $hasHealthId     = !empty($healthId);
        $hasDemographics = !empty($firstName) && !empty($lastName) && !empty($dob);

        if (!$hasHealthId && !$hasDemographics) {
            return response()->json([
                'status'     => 'error',
                'error_code' => 'MISSING_LOOKUP_CRITERIA',
                'message'    => __('api.provide_healthid_or_demographics'),
            ], 422);
        }

        // ── Caller context (populated by auth.bearer middleware) ──────────────
        $clientId   = $request->attributes->get('integration_client_id');
        $facilityId = $request->attributes->get('facility_id');
        $hasGlobal  = $request->attributes->get('permission_global_resolve', false);

        // ── Attempt lookup ────────────────────────────────────────────────────
        $patient = null;

        if ($hasHealthId) {
            $query = Patient::withoutGlobalScope('isolate_demo')
                ->where('health_id', $healthId);

            // Non-global callers can only resolve patients linked to their facility.
            if (!$hasGlobal && $facilityId) {
                $query->where('facility_id', $facilityId);
            }

            $patient = $query->first();

            // If the direct lookup failed, check for a merge alias.
            // This transparently resolves retired Health IDs from merged duplicates,
            // so external systems with an old card/ID still get the canonical record.
            if (! $patient && $hasHealthId) {
                $patient = PatientMergeAlias::resolveHealthId($healthId);
                if ($patient) {
                    Log::info('health_id_alias_resolved', [
                        'alias_health_id'      => $healthId,
                        'canonical_health_id'  => $patient->health_id,
                        'canonical_patient_id' => $patient->id,
                        'client_id'            => $clientId,
                        'facility_id'          => $facilityId,
                    ]);
                }
            }
        }

        if (!$patient && $hasDemographics) {
            $query = Patient::withoutGlobalScope('isolate_demo')
                ->where('first_name', $firstName)
                ->where('last_name', $lastName)
                ->whereDate('date_of_birth', $dob)
                ->where('country_code', $countryCode);

            if (!$hasGlobal && $facilityId) {
                $query->where('facility_id', $facilityId);
            }

            $patient = $query->first();
        }

        // ── Found: return existing Health ID ──────────────────────────────────
        if ($patient) {
            AuditLogger::log(
                $request,
                'health_id_resolved',
                'patient',
                $patient->id,
                $patient->id,
                false,
                null,
                ['purpose' => $purpose, 'external_reference' => $externalRef, 'client_id' => $clientId],
                ['health_id' => $patient->health_id]
            );

            return response()->json([
                'status'      => 'found',
                'health_id'   => $patient->health_id,
                'patient'     => $this->summary($patient),
                'next_action' => 'use_health_id',
            ], 200);
        }

        // ── Not found: health_id given but missing ────────────────────────────
        if (!$hasDemographics) {
            return response()->json([
                'status'     => 'not_found',
                'error_code' => 'HEALTH_ID_NOT_FOUND',
                'message'    => __('api.no_patient_provide_demographics'),
            ], 404);
        }

        // ── CONSENT GATE: must acknowledge patient consent before creation ────
        if (!$consentAcknowledged) {
            return response()->json([
                'status'     => 'consent_required',
                'error_code' => 'CONSENT_REQUIRED',
                'message'    => __('api.no_health_id_consent_required'),
                'next_action' => 'obtain_patient_consent_then_retry',
            ], 422);
        }

        // ── Auto-create patient + generate Health ID (atomic) ─────────────────
        // FIX (audit 2026-06-18): Previously had a TOCTOU race condition between
        // generate() and Patient::create(). Now uses the generator's callback pattern
        // which retries atomically inside the transaction on UniqueConstraintViolationException.
        try {
            $patient = DB::transaction(function () use (
                $countryCode, $firstName, $lastName, $dob, $sex, $phone, $facilityId
            ) {
                return $this->generator->generate($countryCode, function (string $healthId) use (
                    $countryCode, $firstName, $lastName, $dob, $sex, $phone, $facilityId
                ) {
                    return Patient::create([
                        'health_id'           => $healthId,
                        'first_name'          => $firstName,
                        'last_name'           => $lastName,
                        'date_of_birth'       => $dob,
                        'country_code'        => $countryCode,
                        'sex'                 => $sex,
                        'phone_number'        => $phone,
                        'facility_id'         => $facilityId,
                        'verification_status' => VerificationStatus::Pending,
                        'identity_status'     => IdentityStatus::Unverified,
                        // is_demo intentionally NOT set here — demo status is set only
                        // via seeders/migrations, never through the B2B integration API.
                    ]);
                });
            });
        } catch (UniqueConstraintViolationException $e) {
            // If we exhausted all retries, the namespace is likely exhausted.
            Log::error('health_id_namespace_exhaustion', [
                'client_id'  => $clientId,
                'facility_id'=> $facilityId,
            ]);

            return response()->json([
                'status'     => 'error',
                'error_code' => 'CREATION_FAILED',
                'message'    => __('api.health_id_generation_failed'),
            ], 500);
        } catch (\Throwable $e) {
            Log::error('health_id_auto_create_failed', [
                'error'      => $e->getMessage(),
                'client_id'  => $clientId,
                'facility_id'=> $facilityId,
            ]);

            return response()->json([
                'status'     => 'error',
                'error_code' => 'CREATION_FAILED',
                'message'    => __('api.health_id_registration_failed'),
            ], 500);
        }

        AuditLogger::log(
            $request,
            'health_id_auto_created',
            'patient',
            $patient->id,
            $patient->id,
            false,
            null,
            [
                'purpose'              => $purpose,
                'external_reference'   => $externalRef,
                'client_id'            => $clientId,
                'consent_acknowledged' => true,
            ],
            ['health_id' => $patient->health_id]
        );

        return response()->json([
            'status'      => 'created',
            'health_id'   => $patient->health_id,
            'patient'     => $this->summary($patient),
            'message'     => __('api.health_id_registered'),
            'next_action' => 'push_records',
        ], 201);
    }

    /** Verify a Health ID format and existence without creating. */
    public function verify(Request $request, string $healthId): JsonResponse
    {
        $healthId = strtoupper(trim($healthId));

        if (!$this->generator->isValid($healthId)) {
            return response()->json([
                'status'     => 'invalid',
                'error_code' => 'HEALTH_ID_INVALID_FORMAT',
                'message'    => __('api.health_id_format_invalid'),
            ], 422);
        }

        $facilityId = $request->attributes->get('facility_id');
        $hasGlobal  = $request->attributes->get('permission_global_resolve', false);

        $query = Patient::withoutGlobalScope('isolate_demo')->where('health_id', $healthId);
        if (!$hasGlobal && $facilityId) {
            $query->where('facility_id', $facilityId);
        }

        $patient = $query->first();

        // Alias fallback: the health_id may belong to a merged/retired record.
        $resolvedViaAlias = false;
        if (! $patient) {
            $patient = PatientMergeAlias::resolveHealthId($healthId);
            $resolvedViaAlias = (bool) $patient;
        }

        if (!$patient) {
            return response()->json([
                'status'     => 'not_found',
                'error_code' => 'HEALTH_ID_NOT_FOUND',
                'message'    => __('api.health_id_not_in_opescare'),
            ], 404);
        }

        AuditLogger::log($request, 'health_id_verified', 'patient', $patient->id, $patient->id);

        return response()->json([
            'status'               => 'valid',
            'health_id'            => $patient->health_id,
            'resolved_via_alias'   => $resolvedViaAlias,
            'queried_health_id'    => $resolvedViaAlias ? $healthId : null,
            'patient'              => $this->summary($patient),
        ], 200);
    }

    private function summary(Patient $patient): array
    {
        return [
            'display_name'        => trim($patient->first_name . ' ' . $patient->last_name),
            'sex'                 => $patient->sex,
            'year_of_birth'       => $patient->date_of_birth?->year,
            'country_code'        => $patient->country_code,
            'verification_status' => $patient->verification_status ?? 'pending',
            'identity_status'     => $patient->identity_status   ?? 'unverified',
        ];
    }
}
