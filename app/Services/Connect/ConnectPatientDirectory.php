<?php

namespace App\Services\Connect;

use App\Enums\IdentityStatus;
use App\Enums\VerificationStatus;
use App\Models\HealthIdAlias;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * The register as a Connect partner may see it.
 *
 * ── ENVIRONMENT PARTITION ─────────────────────────────────────────────────
 * The token's `environment` claim decides which half of the register a
 * partner reads: a `production` token sees real patients (is_demo = false),
 * anything else — a sandbox app — sees only demo/test patients
 * (is_demo = true). This is set explicitly on every query, NOT left to the
 * IsDemoRecord global scope, because that scope follows the HOST's demo mode
 * (config demo.enabled) and a sandbox credential on the live host must still
 * never see a real person.
 *
 * ── ATTACH-ONLY ───────────────────────────────────────────────────────────
 * Nothing here creates a patient. A partner resolves a Health ID that exists
 * or is told it does not; identities are minted only by the patient's own
 * registration (Patient::findExistingIdentity() guards those paths). This is
 * the same rule FhirIngestGuard::resolveSubject() applies to ingest.
 *
 * ── MINIMAL DISCLOSURE ────────────────────────────────────────────────────
 * What leaves is the Health ID, a masked name, sex, year of birth, country and
 * status — enough for a clerk to say "yes, that is the person in front of me",
 * never enough to learn who someone is from a Health ID alone.
 */
final class ConnectPatientDirectory
{
    public function isSandbox(Request $request): bool
    {
        return $request->attributes->get('bearer_environment') !== 'production';
    }

    /** Patients of the caller's environment only, demo scope bypassed and re-applied explicitly. */
    public function query(Request $request): Builder
    {
        return Patient::withoutGlobalScope('isolate_demo')
            ->where('patients.is_demo', $this->isSandbox($request));
    }

    /**
     * Exact Health ID → patient, following an active alias and any merge, inside
     * the caller's partition. Returns [patient, resolvedViaAlias].
     *
     * @return array{0: ?Patient, 1: bool}
     */
    public function resolveHealthId(Request $request, string $healthId): array
    {
        $key = strtoupper(trim($healthId));
        if ($key === '' || strlen($key) > 64) {
            return [null, false];
        }

        $patient = $this->query($request)->where('health_id', $key)->first();
        $viaAlias = false;

        if ($patient === null) {
            $alias = HealthIdAlias::where('alias_value', $key)->where('status', 'active')->first();
            if ($alias !== null) {
                $patient = $this->query($request)->whereKey($alias->patient_id)->first();
                $viaAlias = $patient !== null;
            }
        }

        if ($patient !== null) {
            $before = $patient->getKey();
            $patient = $this->followMerge($request, $patient);
            $viaAlias = $viaAlias || ($patient !== null && $patient->getKey() !== $before);
        }

        return [$patient, $viaAlias];
    }

    /**
     * Demographic match inside the partition. date_of_birth is encrypted per
     * value, so the database narrows by name and PHP compares the date — the
     * same approach as Patient::findExistingIdentity().
     *
     * @return list<array{patient: Patient, confidence: float, method: string}>
     */
    public function matchDemographics(Request $request, ?string $firstName, ?string $lastName, ?string $fullName, string $dob, ?string $sex, ?string $phone): array
    {
        try {
            $target = Carbon::parse($dob)->toDateString();
        } catch (\Throwable) {
            return [];
        }

        $query = $this->query($request);
        if ($firstName !== null && $lastName !== null) {
            $query->whereRaw('lower(btrim(first_name)) = ?', [mb_strtolower(trim($firstName))])
                ->whereRaw('lower(btrim(last_name)) = ?', [mb_strtolower(trim($lastName))]);
        } else {
            // A full name may come in either order ("Ngono Marie" / "Marie Ngono").
            $full = preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $fullName)));
            $query->where(function (Builder $q) use ($full) {
                $q->whereRaw("lower(btrim(first_name)) || ' ' || lower(btrim(last_name)) = ?", [$full])
                    ->orWhereRaw("lower(btrim(last_name)) || ' ' || lower(btrim(first_name)) = ?", [$full]);
            });
        }

        $phoneHash = ($phone !== null && trim($phone) !== '') ? Patient::phoneHash($phone) : null;

        $matches = [];
        foreach ($query->limit(50)->get() as $candidate) {
            try {
                if ($candidate->date_of_birth?->toDateString() !== $target) {
                    continue;
                }
            } catch (\Throwable) {
                continue; // undecryptable row: cannot be ruled a match
            }

            $candidateSex = $this->sexValue($candidate);
            if ($sex !== null && $candidateSex !== null && $candidateSex !== $sex) {
                continue;
            }

            $candidate = $this->followMerge($request, $candidate);
            if ($candidate === null) {
                continue;
            }

            $phoneAgrees = $phoneHash !== null && hash_equals((string) $candidate->phone_number_hash, $phoneHash);
            $matches[(string) $candidate->getKey()] = [
                'patient'    => $candidate,
                'confidence' => $phoneAgrees ? 0.98 : ($sex !== null && $candidateSex === $sex ? 0.9 : 0.85),
                'method'     => $phoneAgrees ? 'demographics_phone' : 'demographics',
            ];
        }

        $matches = array_values($matches);
        usort($matches, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);

        return $matches;
    }

    /** The minimal, masked summary a partner sees before consent. */
    public function summary(Patient $patient): array
    {
        $identity = $this->enumValue($patient->identity_status) ?? IdentityStatus::Unverified->value;
        $verification = $this->enumValue($patient->verification_status) ?? VerificationStatus::Pending->value;

        try {
            $year = $patient->date_of_birth?->year;
        } catch (\Throwable) {
            $year = null;
        }

        return [
            'display_name'        => self::maskName(trim($patient->first_name . ' ' . $patient->last_name)),
            'sex'                 => $this->sexValue($patient),
            'year_of_birth'       => $year,
            'country_code'        => $patient->country_code ?: 'CM',
            'verification_status' => $verification,
            'identity_status'     => $identity,
            'active'              => $this->isActive($patient),
            'verified'            => in_array($verification, [
                VerificationStatus::Verified->value,
                VerificationStatus::VerifiedByFacility->value,
                VerificationStatus::FacilityVerified->value,
            ], true) || in_array($identity, [IdentityStatus::Verified->value, IdentityStatus::VerifiedByFacility->value], true),
        ];
    }

    /** Suspended, deceased, entered-in-error, erasure-pending identities are not active. */
    public function isActive(Patient $patient): bool
    {
        return ! in_array($this->enumValue($patient->identity_status), [
            IdentityStatus::Suspended->value,
            IdentityStatus::Deceased->value,
            IdentityStatus::EnteredInError->value,
            IdentityStatus::Merged->value,
            IdentityStatus::ErasurePending->value,
        ], true);
    }

    /** "Marie Ngono" → "M**** N****". */
    public static function maskName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];

        return implode(' ', array_map(
            fn (string $p) => mb_substr($p, 0, 1) . str_repeat('*', max(2, mb_strlen($p) - 1)),
            array_filter($parts, fn ($p) => $p !== '')
        ));
    }

    private function followMerge(Request $request, Patient $patient): ?Patient
    {
        for ($hop = 0; $hop < 5 && $patient->isMerged(); $hop++) {
            $next = $this->query($request)->whereKey($patient->merged_into_patient_id)->first();
            if ($next === null) {
                return null;
            }
            $patient = $next;
        }

        return $patient->isMerged() ? null : $patient;
    }

    private function sexValue(Patient $patient): ?string
    {
        $sex = strtolower((string) $this->enumValue($patient->sex));

        return match ($sex) {
            'male', 'm' => 'male',
            'female', 'f' => 'female',
            default => null,
        };
    }

    private function enumValue(mixed $value): ?string
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return $value === null ? null : (string) $value;
    }
}
