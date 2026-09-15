<?php

namespace App\Services\Referral;

use App\Enums\PatientReferralRequestStatus;
use App\Models\ConsentGrant;
use App\Models\Patient;
use App\Models\PatientReferralRequest;
use App\Models\ReferralAccessGrant;
use App\Models\ReferralCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The clinician-to-clinician referral lifecycle — ONE implementation shared by
 * the clinician app (Api\Mobile\MobileDoctorReferralController) and the web
 * desk (MedicalId\ReferralDeskController).
 *
 * Extracted verbatim from the mobile controller, which used to hold the state
 * machine, the consent decision and the grant revocation privately. Neither
 * controller re-implements any of it; each keeps only its own transport
 * (validation, JSON vs redirects, how it learns the clinician and facility).
 *
 * What lives here and nowhere else:
 *   TRANSITIONS        the legal status moves (anything else is refused)
 *   SIDES              which PARTY (referring / receiving) makes each move
 *   sendConsent()      whether records travel: an active ConsentGrant held by
 *                      the RECEIVING facility, intersected with the package
 *   revokeAccessGrants status-only revocation; grants are never deleted
 *   notifyPatient()    best-effort bell + push to the patient
 *
 * The facility and clinician are always PASSED IN by the caller, which reads
 * them from its auth layer. Nothing here reads a request.
 */
class ReferralDeskService
{
    /** Legal status moves. Anything not listed here is refused. */
    public const TRANSITIONS = [
        'draft'     => ['sent', 'cancelled'],
        'sent'      => ['accepted', 'rejected', 'cancelled', 'expired'],
        'accepted'  => ['completed', 'cancelled'],
        'rejected'  => [],
        'completed' => [],
        'cancelled' => [],
        'expired'   => [],
    ];

    /** Which party may make each move. */
    public const SIDES = [
        'sent'      => 'referring',
        'cancelled' => 'referring',
        'accepted'  => 'receiving',
        'rejected'  => 'receiving',
        'completed' => 'receiving',
    ];

    public const URGENCIES = ['routine', 'urgent', 'emergency'];

    /**
     * The record package vocabulary — the SAME list
     * MobileConsentRequestController::SCOPES uses, so a package can always be
     * checked against a grant.
     */
    public const RECORD_TYPES = [
        'patient.summary',
        'allergies.read',
        'problems.read',
        'encounters.read',
        'vitals.read',
        'medications.read',
        'diagnoses.read',
        'orders.read',
    ];

    /** Default / maximum life of a sent referral, in days. */
    public const DEFAULT_EXPIRY_DAYS = 30;
    public const MAX_EXPIRY_DAYS     = 180;

    /** A referral this facility is a party to, or null. */
    public function findForFacility(string $id, string $facilityId): ?ReferralCase
    {
        if (! Str::isUuid($id)) {
            return null;
        }

        return ReferralCase::query()
            ->whereKey($id)
            ->where(function ($q) use ($facilityId) {
                $q->where('referring_facility_id', $facilityId)
                  ->orWhere('receiving_facility_id', $facilityId);
            })
            ->first();
    }

    /**
     * Expiry is a state, not a job that may or may not have run. A sent
     * referral past its date reads as expired here and now.
     */
    public function currentStatus(ReferralCase $referral): string
    {
        return ($referral->status === 'sent' && $referral->isExpired())
            ? 'expired'
            : (string) $referral->status;
    }

    /** @return array<int,string> */
    public function allowedTransitions(string $status): array
    {
        return array_values(self::TRANSITIONS[$status] ?? []);
    }

    /**
     * The moves THIS facility may make on this referral right now — the legal
     * moves filtered to the ones its side of the referral owns.
     *
     * @return array<int,string>
     */
    public function movesFor(ReferralCase $referral, string $facilityId): array
    {
        return array_values(array_filter(
            $this->allowedTransitions($this->currentStatus($referral)),
            fn (string $target) => isset(self::SIDES[$target]) && $this->partyFacility($referral, self::SIDES[$target]) === $facilityId,
        ));
    }

    /**
     * Why a move may not be made, or null when it may.
     *
     * @return array{error: string, current_status?: string, allowed?: array<int,string>}|null
     */
    public function refusal(ReferralCase $referral, string $facilityId, string $target, string $side): ?array
    {
        // Wrong party: this facility IS a party and knows the referral exists —
        // it is simply the other side.
        if ($this->partyFacility($referral, $side) !== $facilityId) {
            return ['error' => 'WRONG_PARTY'];
        }

        $current = $this->currentStatus($referral);

        if (! in_array($target, self::TRANSITIONS[$current] ?? [], true)) {
            return [
                'error'          => 'ILLEGAL_TRANSITION',
                'current_status' => $current,
                'allowed'        => $this->allowedTransitions($current),
            ];
        }

        return null;
    }

    // ══════════════════════════════════════════════════════════════════
    // Writes
    // ══════════════════════════════════════════════════════════════════

    /**
     * Raise a referral as a DRAFT. Drafts disclose nothing.
     *
     * @param  array<string,mixed>  $validated
     */
    public function createDraft(
        Patient $patient,
        string $facilityId,
        string $clinicianId,
        ?string $receivingFacilityId,
        array $validated,
        ?PatientReferralRequest $patientRequest = null,
    ): ReferralCase {
        return DB::transaction(function () use ($validated, $patient, $clinicianId, $facilityId, $receivingFacilityId, $patientRequest) {
            $case = ReferralCase::create([
                'patient_id'              => $patient->id,
                // From the caller's auth layer. Never from a body.
                'referring_facility_id'   => $facilityId,
                'referring_provider_id'   => $clinicianId,
                'receiving_facility_id'   => $receivingFacilityId,
                'receiving_specialty'     => $validated['receiving_specialty'] ?? null,
                'receiving_provider_name' => $validated['receiving_provider_name'] ?? null,
                'urgency'                 => $validated['urgency'] ?? 'routine',
                'status'                  => 'draft',
                'reason'                  => $validated['reason'],
                'clinical_summary'        => $validated['clinical_summary'] ?? null,
                // What the clinician WANTS to include. send() decides what may go.
                'included_record_types'   => array_values(array_unique($validated['included_record_types'] ?? [])),
                'consent_grant_id'        => null,
                'expires_at'              => null,
                'created_by_id'           => $clinicianId,
            ]);

            if ($patientRequest) {
                // The CHECK constraint permits a case link only on `accepted`,
                // so both columns move together or neither does.
                $patientRequest->status           = PatientReferralRequestStatus::Accepted;
                $patientRequest->referral_case_id = $case->id;
                $patientRequest->reviewed_at      = now();
                $patientRequest->reviewed_by_id   = $clinicianId;
                $patientRequest->save();
            }

            return $case;
        });
    }

    /**
     * Send a draft. Returns the consent outcome, which the caller must report.
     *
     * @return array{grant_id: ?string, record_types: array<int,string>, access_grant: ?array<string,mixed>, basis: string}
     */
    public function send(ReferralCase $referral, ?int $expiresInDays, ?string $clinicianId): array
    {
        $expiresAt = Carbon::now()->addDays($expiresInDays ?? self::DEFAULT_EXPIRY_DAYS);

        $consent = $this->sendConsent($referral, $expiresAt, $clinicianId);

        DB::transaction(function () use ($referral, $expiresAt, $consent) {
            $referral->fill([
                'status'                => 'sent',
                'expires_at'            => $expiresAt,
                'consent_grant_id'      => $consent['grant_id'],
                'included_record_types' => $consent['record_types'],
            ])->save();

            if ($consent['access_grant']) {
                ReferralAccessGrant::create($consent['access_grant']);
            }
        });

        $this->notifyPatient($referral, 'sent');

        return $consent;
    }

    public function accept(ReferralCase $referral, ?string $clinicianId): void
    {
        $referral->fill([
            'status'         => 'accepted',
            'accepted_at'    => now(),
            'accepted_by_id' => $clinicianId,
        ])->save();

        $this->notifyPatient($referral, 'accepted');
    }

    public function reject(ReferralCase $referral, string $reason): void
    {
        $referral->fill([
            'status'           => 'rejected',
            'rejected_at'      => now(),
            'rejection_reason' => $reason,
        ])->save();

        // A refused referral must not leave a live key to the record behind it.
        $this->revokeAccessGrants($referral, 'referral_rejected');

        $this->notifyPatient($referral, 'rejected');
    }

    public function complete(ReferralCase $referral, ?string $feedback): void
    {
        $referral->fill([
            'status'       => 'completed',
            'completed_at' => now(),
            'feedback'     => $feedback ?? $referral->feedback,
        ])->save();

        $this->revokeAccessGrants($referral, 'referral_completed');

        $this->notifyPatient($referral, 'completed');
    }

    public function cancel(ReferralCase $referral, string $reason): void
    {
        $referral->fill([
            'status'              => 'cancelled',
            'cancelled_at'        => now(),
            'cancellation_reason' => $reason,
        ])->save();

        $this->revokeAccessGrants($referral, 'referral_cancelled');
    }

    // ══════════════════════════════════════════════════════════════════
    // Consent — the part that decides whether records travel
    // ══════════════════════════════════════════════════════════════════

    /**
     * What may the RECEIVING facility actually read?
     *
     *   • no receiving facility on the platform → no record access
     *   • no active ConsentGrant held by THAT facility → sent with an empty package
     *   • an active grant → the INTERSECTION of the package and the grant scope
     *
     * @return array{grant_id: ?string, record_types: array<int,string>, access_grant: ?array<string,mixed>, basis: string}
     */
    public function sendConsent(ReferralCase $referral, Carbon $expiresAt, ?string $clinicianId): array
    {
        $requested = array_values(array_unique((array) ($referral->included_record_types ?? [])));

        if (! $referral->receiving_facility_id || $requested === []) {
            return [
                'grant_id'     => null,
                'record_types' => [],
                'access_grant' => null,
                'basis'        => $referral->receiving_facility_id ? 'none_requested' : 'external_destination',
            ];
        }

        $grant = ConsentGrant::query()
            ->where('patient_id', $referral->patient_id)
            // The RECEIVING facility. This line is the whole control.
            ->where('facility_id', $referral->receiving_facility_id)
            ->where('status', 'active')
            ->where('expires_at', '>=', Carbon::now())
            ->orderByDesc('expires_at')
            ->first();

        if (! $grant) {
            return [
                'grant_id'     => null,
                'record_types' => [],
                'access_grant' => null,
                'basis'        => 'no_consent',
            ];
        }

        $granted = array_values(array_unique((array) ($grant->scope ?? [])));
        $allowed = array_values(array_intersect($requested, $granted));

        if ($allowed === []) {
            return [
                'grant_id'     => $grant->id,
                'record_types' => [],
                'access_grant' => null,
                'basis'        => 'consent_scope_excludes_package',
            ];
        }

        // The access grant may not outlive the consent behind it.
        $grantExpiry = $grant->expires_at && $grant->expires_at->lt($expiresAt)
            ? $grant->expires_at
            : $expiresAt;

        return [
            'grant_id'     => $grant->id,
            'record_types' => $allowed,
            'basis'        => 'consent_grant',
            'access_grant' => [
                'referral_case_id'       => $referral->id,
                'patient_id'             => $referral->patient_id,
                'granted_to_facility_id' => $referral->receiving_facility_id,
                'granted_by_id'          => $clinicianId,
                'token'                  => hash('sha256', Str::uuid()->toString() . bin2hex(random_bytes(32))),
                'allowed_scopes'         => $allowed,
                'status'                 => 'active',
                'expires_at'             => $grantExpiry,
            ],
        ];
    }

    /** Close every live key this referral opened. Status only; rows stay. */
    public function revokeAccessGrants(ReferralCase $referral, string $reason): void
    {
        ReferralAccessGrant::query()
            ->where('referral_case_id', $referral->id)
            ->where('status', 'active')
            ->update([
                'status'            => 'revoked',
                'revoked_at'        => now(),
                'revocation_reason' => $reason,
                'updated_at'        => now(),
            ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // The patient is told
    // ══════════════════════════════════════════════════════════════════

    /**
     * The patient's bell (the `notifications` feed the patient app reads) and a
     * push when the channel exists. Best-effort: the status move is already
     * committed and a failed notification must not report it as failed.
     */
    public function notifyPatient(ReferralCase $referral, string $event): void
    {
        try {
            $patient = Patient::query()->withoutGlobalScopes()->find($referral->patient_id);

            if ($patient === null) {
                return;
            }

            $referral->loadMissing(['referringFacility:id,name', 'receivingFacility:id,name']);

            $locale = $this->patientLocale($patient->id);
            $params = [
                'from' => $referral->referringFacility?->name ?? '',
                'to'   => $referral->receivingFacility?->name ?? ($referral->receiving_specialty ?? ''),
            ];

            $title = __('referral_desk.notify.' . $event . '_title', $params, $locale);
            $body  = __('referral_desk.notify.' . $event . '_body', $params, $locale);
            $type  = 'referral_' . $event;

            DB::table('notifications')->insert([
                'id'              => (string) Str::uuid(),
                'type'            => $type,
                'notifiable_type' => $patient->getMorphClass(),
                'notifiable_id'   => $patient->id,
                'data'            => json_encode([
                    'type'             => $type,
                    'title'            => $title,
                    'message'          => $body,
                    'severity'         => $event === 'rejected' ? 'high' : 'normal',
                    'action_url'       => '/referrals',
                    'referral_case_id' => $referral->id,
                ], JSON_UNESCAPED_UNICODE),
                'read_at'         => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $push = '\App\Support\PatientPush';

            if (class_exists($push) && method_exists($push, 'send')) {
                $push::send($patient, 'referral', $title, $body, [
                    'referral_case_id' => $referral->id,
                    'action_url'       => '/referrals',
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('referral: patient notification failed', [
                'referral_case_id' => $referral->id,
                'event'            => $event,
                'error'            => $e->getMessage(),
            ]);
        }
    }

    private function partyFacility(ReferralCase $referral, string $side): ?string
    {
        return $side === 'referring'
            ? $referral->referring_facility_id
            : $referral->receiving_facility_id;
    }

    private function patientLocale(string $patientId): string
    {
        try {
            $preferred = DB::table('mobile_app_settings')->where('patient_id', $patientId)->value('preferred_language');
        } catch (\Throwable) {
            $preferred = null;
        }

        $preferred = strtolower(substr((string) $preferred, 0, 2));

        return in_array($preferred, ['en', 'fr'], true) ? $preferred : app()->getLocale();
    }
}
