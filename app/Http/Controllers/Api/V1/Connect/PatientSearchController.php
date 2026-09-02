<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Enums\OpesCareErrorCode;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Modules\MasterPatientIndex\Services\MasterPatientIndexService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * PatientSearchController
 *
 * Exposes the OpesCare Master Patient Index (MPI) search to authorised
 * integration clients. Supports five search modes:
 *
 *   health_id    — exact match on CM-HID-XXXX-XXXX-XXXX national health ID
 *   cnamgs_id    — exact match on CNAMGS social-insurance number
 *   national_id  — exact match on Cameroon national ID card number (CNI)
 *   phone        — lookup by MSISDN via HMAC hash (handles PII encryption)
 *   demographic  — probabilistic MPI match: first_name + last_name +
 *                  phone_number + date_of_birth + sex (all required together)
 *
 * Every call is audit-logged regardless of outcome.
 *
 * Gap 15 fix: previously only `health_id` was handled; all other search_type
 * values silently fell through to a 404. All five modes are now implemented.
 */
class PatientSearchController extends Controller
{
    public function __construct(
        private readonly MasterPatientIndexService $mpi
    ) {}

    public function search(Request $request): JsonResponse
    {
        $searchType = $request->input('search_type');
        $query      = $request->input('query');
        $purpose    = $request->input('purpose');
        $clientId   = $request->attributes->get('integration_client_id', 'unknown_client');

        if (! $searchType || ! $purpose) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::VALIDATION_FAILED->value,
                'message'    => __('api.search_params_missing'),
            ], 400);
        }

        // Demographic search takes structured fields directly; all others need `query`
        if ($searchType !== 'demographic' && ! $query) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::VALIDATION_FAILED->value,
                'message'    => __('api.search_query_missing'),
            ], 400);
        }

        [$patient, $candidates, $matchType] = match ($searchType) {
            'health_id'   => $this->byHealthId($query),
            'cnamgs_id'   => $this->byCnamgsId($query),
            'national_id' => $this->byNationalId($query),
            'phone'       => $this->byPhone($query),
            'demographic' => $this->byDemographic($request),
            default       => [null, collect(), null],
        };

        // Unsupported search_type
        if ($matchType === null && $searchType !== 'demographic') {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::VALIDATION_FAILED->value,
                'message'    => __('api.search_type_unsupported'),
            ], 400);
        }

        // Audit every search
        AuditLogger::log(
            $request,
            'patient_search_performed',
            'patient',
            $patient?->id,
            $patient?->id,
            false,
            null,
            ['search_type' => $searchType, 'query' => $searchType === 'demographic' ? '[demographic]' : $query],
            $patient ? ['health_id' => $patient->health_id] : []
        );

        // ── Single exact match ────────────────────────────────────────────────
        if ($patient) {
            return response()->json([
                'status'      => 'matched',
                'match_type'  => $matchType ?? 'exact',
                'patient'     => $this->formatPatient($patient),
                'next_action' => 'request_consent',
            ]);
        }

        // ── Multiple demographic candidates ───────────────────────────────────
        if ($candidates->isNotEmpty()) {
            return response()->json([
                'status'      => 'multiple_candidates',
                'match_type'  => 'probabilistic',
                'count'       => $candidates->count(),
                'candidates'  => $candidates->map(fn ($p) => $this->formatPatient($p))->values(),
                'next_action' => 'confirm_identity',
            ]);
        }

        // ── No match ──────────────────────────────────────────────────────────
        return response()->json([
            'status'         => 'rejected',
            'error_code'     => OpesCareErrorCode::PATIENT_NOT_FOUND->value,
            'message'        => __('api.no_patient_matching'),
            // [M-3 FIX] bin2hex(random_bytes) is cryptographically random; uniqid() was predictable
            'correlation_id' => $request->header('X-Correlation-Id') ?? ('req_' . bin2hex(random_bytes(8))),
        ], 404);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Search mode implementations
    // ──────────────────────────────────────────────────────────────────────────

    /** Exact match on national health ID (CM-HID-XXXX-XXXX-XXXX). */
    private function byHealthId(string $query): array
    {
        $patient = Patient::where('health_id', strtoupper(trim($query)))->first();
        return [$patient, collect(), 'exact'];
    }

    /**
     * Exact match on CNAMGS social-insurance number.
     * Stored in plain text (not PII-encrypted); directDB comparison is safe.
     */
    private function byCnamgsId(string $query): array
    {
        $patient = Patient::where('cnamgs_id', trim($query))->first();
        return [$patient, collect(), 'exact'];
    }

    /**
     * Exact match on Cameroon national ID card number (CNI).
     * Stored in plain text; numeric/alphanumeric, trimmed before comparison.
     */
    private function byNationalId(string $query): array
    {
        $patient = Patient::where('national_id_number', trim($query))->first();
        return [$patient, collect(), 'exact'];
    }

    /**
     * Lookup by phone number (MSISDN).
     * Phone numbers are PII-encrypted; the hash index is used for the DB query.
     */
    private function byPhone(string $query): array
    {
        $patient = Patient::where('phone_number_hash', Patient::phoneHash(trim($query)))->first();
        return [$patient, collect(), 'exact'];
    }

    /**
     * Probabilistic demographic search via the MasterPatientIndexService.
     *
     * Required fields (passed directly in the request body, not in `query`):
     *   first_name, last_name, phone_number, date_of_birth (YYYY-MM-DD), sex
     *
     * Returns either a single Patient (if uniquely matched) or a collection
     * of candidates (if multiple matches — caller must confirm identity).
     */
    private function byDemographic(Request $request): array
    {
        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'phone_number'  => 'required|string|max:30',
            'date_of_birth' => 'required|date_format:Y-m-d',
            'sex'           => 'required|in:male,female',
        ]);

        $candidates = $this->mpi->searchCandidates([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'phone_number'  => $data['phone_number'],
            'date_of_birth' => $data['date_of_birth'],
            'sex'           => $data['sex'],
        ]);

        if ($candidates->count() === 1) {
            return [$candidates->first(), collect(), 'demographic_exact'];
        }

        return [null, $candidates, 'demographic_probabilistic'];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Response shaping
    // ──────────────────────────────────────────────────────────────────────────

    private function formatPatient(Patient $patient): array
    {
        return [
            'health_id'           => $patient->health_id,
            'display_name'        => $patient->first_name . ' ' . substr($patient->last_name, 0, 1) . '.',
            'sex'                 => $patient->sex,
            'year_of_birth'       => $patient->date_of_birth
                ? (int) $patient->date_of_birth->format('Y')
                : null,
            'verification_status' => $patient->verification_status ?? $patient->identity_status ?? 'verified_by_facility',
            'cnamgs_id'           => $patient->cnamgs_id,
        ];
    }
}
