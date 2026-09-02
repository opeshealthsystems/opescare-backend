<?php

namespace App\Modules\MasterPatientIndex\Services;

use App\Models\MpiCandidate;
use App\Models\Patient;
use App\Models\PatientIdentifier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * MasterPatientIndexService
 *
 * Manages the Master Patient Index (MPI) — the authoritative source for patient
 * identity deduplication and cross-facility identifier linking.
 *
 * Responsibilities:
 *  1. searchCandidates()    — look up patients by identifiers or demographics
 *  2. detectDuplicates()    — find potential duplicate pairs for a facility
 *  3. confirmMatch()        — mark two patients as the same (merge candidate confirmed)
 *  4. rejectMatch()         — mark a candidate pair as not the same person
 *  5. linkIdentifier()      — attach an external identifier to a patient record
 *  6. listCandidates()      — list MPI candidates for review (paginated)
 */
class MasterPatientIndexService
{
    // Minimum score to surface a candidate pair for review
    public const MATCH_THRESHOLD = 60.0;

    // ── 1. Search ─────────────────────────────────────────────────────────

    /**
     * Search patients by identifier list or demographic triple.
     *
     * Called by the Connect API and MPI controller.
     * Respects encrypted fields — phone and DOB are never LIKE-searched.
     */
    public function searchCandidates(array $data): Collection
    {
        $candidates = collect();

        // Path A: Identifier-based (exact match)
        if (! empty($data['identifiers'])) {
            foreach ($data['identifiers'] as $identifier) {
                $matches = PatientIdentifier::where('identifier_type', $identifier['type'])
                    ->where('identifier_value', $identifier['value'])
                    ->with('patient')
                    ->get()
                    ->pluck('patient')
                    ->filter();

                $candidates = $candidates->merge($matches);
            }
        }

        // Path B: Demographic triple (name + phone hash + DOB + sex)
        if (
            ! empty($data['first_name']) &&
            ! empty($data['last_name']) &&
            ! empty($data['phone_number']) &&
            ! empty($data['date_of_birth']) &&
            ! empty($data['sex'])
        ) {
            $formattedDob = Carbon::parse($data['date_of_birth'])->format('Y-m-d');

            // phone_number is encrypted — query by hash, verify DOB in PHP
            $matches = Patient::where('first_name', $data['first_name'])
                ->where('last_name', $data['last_name'])
                ->where('phone_number_hash', Patient::phoneHash($data['phone_number']))
                ->where('sex', $data['sex'])
                ->get()
                ->filter(fn ($p) => $p->date_of_birth?->format('Y-m-d') === $formattedDob);

            $candidates = $candidates->merge($matches);
        }

        return $candidates->unique('id');
    }

    // ── 2. Duplicate Detection ────────────────────────────────────────────

    /**
     * Detect potential duplicate patient pairs for a given facility.
     *
     * Matching strategy (cumulative scoring):
     *   +40  same first_name + last_name
     *   +30  same phone_number_hash
     *   +20  same date_of_birth (decrypted PHP comparison)
     *   +10  same sex
     *
     * Pairs scoring >= MATCH_THRESHOLD are surfaced as MpiCandidates.
     * Existing pairs (any status) are not re-created.
     *
     * @param  string  $facilityId  Only patients registered at this facility
     * @param  int     $limit       Max patients to scan per call (default 500)
     * @return int     Number of new candidate pairs created
     */
    public function detectDuplicates(string $facilityId, int $limit = 500): int
    {
        $patients = Patient::where('facility_id', $facilityId)
            ->select(['id', 'first_name', 'last_name', 'phone_number_hash', 'date_of_birth', 'sex'])
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $created = 0;

        // Compare every unique pair — O(n²/2); limit keeps this tractable
        for ($i = 0; $i < $patients->count(); $i++) {
            for ($j = $i + 1; $j < $patients->count(); $j++) {
                $a = $patients[$i];
                $b = $patients[$j];

                // Skip if pair already exists in any status
                $alreadyExists = MpiCandidate::where(function ($q) use ($a, $b) {
                    $q->where('source_patient_id', $a->id)->where('target_patient_id', $b->id);
                })->orWhere(function ($q) use ($a, $b) {
                    $q->where('source_patient_id', $b->id)->where('target_patient_id', $a->id);
                })->exists();

                if ($alreadyExists) {
                    continue;
                }

                [$score, $reasons] = $this->scoreMatch($a, $b);

                if ($score >= self::MATCH_THRESHOLD) {
                    MpiCandidate::create([
                        'source_patient_id' => $a->id,
                        'target_patient_id' => $b->id,
                        'match_score'       => $score,
                        'match_reasons'     => $reasons,
                        'status'            => 'pending_review',
                    ]);
                    $created++;
                }
            }
        }

        Log::info('MasterPatientIndex: duplicate detection complete', [
            'facility_id'     => $facilityId,
            'patients_scanned' => $patients->count(),
            'new_candidates'  => $created,
        ]);

        return $created;
    }

    // ── 3. Confirm Match (merge) ──────────────────────────────────────────

    /**
     * Confirm that source and target are the same patient.
     *
     * Marks the candidate as merged and records the reviewer.
     * Does NOT physically merge patient records — that is handled by the
     * DuplicateMergeController which has the full merge workflow including
     * audit trail and alias table.
     *
     * @throws RuntimeException  if candidate is not in pending_review status
     */
    public function confirmMatch(MpiCandidate $candidate, string $actorId): MpiCandidate
    {
        if (! $candidate->isPending()) {
            throw new RuntimeException('MPI_CANDIDATE_NOT_PENDING: status is ' . $candidate->status);
        }

        $candidate->forceFill([
            'status'      => 'merged',
            'reviewed_by' => $actorId,
            'reviewed_at' => Carbon::now(),
        ])->save();

        Log::info('MasterPatientIndex: match confirmed', [
            'candidate_id'      => $candidate->id,
            'source_patient_id' => $candidate->source_patient_id,
            'target_patient_id' => $candidate->target_patient_id,
            'reviewed_by'       => $actorId,
        ]);

        return $candidate->fresh();
    }

    // ── 4. Reject Match ───────────────────────────────────────────────────

    /**
     * Mark the candidate pair as not being the same patient.
     *
     * @throws RuntimeException  if candidate is not in pending_review status
     */
    public function rejectMatch(MpiCandidate $candidate, string $actorId): MpiCandidate
    {
        if (! $candidate->isPending()) {
            throw new RuntimeException('MPI_CANDIDATE_NOT_PENDING: status is ' . $candidate->status);
        }

        $candidate->forceFill([
            'status'      => 'rejected',
            'reviewed_by' => $actorId,
            'reviewed_at' => Carbon::now(),
        ])->save();

        Log::info('MasterPatientIndex: match rejected', [
            'candidate_id' => $candidate->id,
            'reviewed_by'  => $actorId,
        ]);

        return $candidate->fresh();
    }

    // ── 5. Link Identifier ────────────────────────────────────────────────

    /**
     * Attach an external identifier to a patient record.
     *
     * Identifier types: national_id, passport, nhis_number, insurance_id,
     *                   lab_id, pharmacy_id, facility_mrn, custom
     *
     * Uses updateOrCreate to prevent duplicates — re-linking the same
     * identifier_type + identifier_value is safe (idempotent).
     *
     * @throws RuntimeException  if identifier_type or identifier_value is missing
     */
    public function linkIdentifier(string $patientId, array $identifier): PatientIdentifier
    {
        if (empty($identifier['type']) || empty($identifier['value'])) {
            throw new RuntimeException('MPI_LINK_IDENTIFIER: type and value are required');
        }

        $linked = PatientIdentifier::updateOrCreate(
            [
                'patient_id'       => $patientId,
                'identifier_type'  => $identifier['type'],
                'identifier_value' => $identifier['value'],
            ],
            [
                'issuer'      => $identifier['issuer']      ?? null,
                'facility_id' => $identifier['facility_id'] ?? null,
            ]
        );

        Log::info('MasterPatientIndex: identifier linked', [
            'patient_id'       => $patientId,
            'identifier_type'  => $identifier['type'],
            'identifier_value' => $identifier['value'],
        ]);

        return $linked;
    }

    // ── 6. List Candidates ────────────────────────────────────────────────

    /**
     * List MPI candidates, optionally filtered by status or facility.
     */
    public function listCandidates(array $filters = [], int $perPage = 20)
    {
        $query = MpiCandidate::with([
            'sourcePatient:id,first_name,last_name,health_id,facility_id',
            'targetPatient:id,first_name,last_name,health_id,facility_id',
            'reviewer:id,name',
        ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['min_score'])) {
            $query->where('match_score', '>=', $filters['min_score']);
        }

        return $query->orderByDesc('match_score')->paginate($perPage);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Score how likely two patients are the same person.
     *
     * @return array{0: float, 1: array<string>}  [score, reasons]
     */
    private function scoreMatch(Patient $a, Patient $b): array
    {
        $score   = 0.0;
        $reasons = [];

        if (
            strtolower($a->first_name) === strtolower($b->first_name) &&
            strtolower($a->last_name)  === strtolower($b->last_name)
        ) {
            $score   += 40;
            $reasons[] = 'name_match';
        }

        if ($a->phone_number_hash && $a->phone_number_hash === $b->phone_number_hash) {
            $score   += 30;
            $reasons[] = 'phone_hash_match';
        }

        if (
            $a->date_of_birth && $b->date_of_birth &&
            $a->date_of_birth->format('Y-m-d') === $b->date_of_birth->format('Y-m-d')
        ) {
            $score   += 20;
            $reasons[] = 'dob_match';
        }

        if ($a->sex && $a->sex === $b->sex) {
            $score   += 10;
            $reasons[] = 'sex_match';
        }

        return [$score, $reasons];
    }
}
