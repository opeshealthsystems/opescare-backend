<?php

namespace App\Services\Compliance;

use App\Enums\AuditEventType;
use App\Enums\IdentityStatus;
use App\Enums\VerificationStatus;
use App\Models\AccountClosureRequest;
use App\Models\MedicalIdAccessEvent;
use App\Models\Patient;
use App\Models\User;
use App\Modules\Governance\Services\AccessLogService;
use App\Modules\Governance\Services\CountryPolicyService;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Close a patient's account — and only the account.
 * =================================================
 *
 * The store requirement is blunt: an app that creates accounts must let a
 * patient delete theirs from inside the app (Apple 5.1.1(v), Google Play
 * account-deletion policy). The medical reality is equally blunt: on this
 * platform "delete the patient" is not a thing that can be allowed to mean
 * "delete the record".
 *
 * WHY A CASCADE WOULD BE A CATASTROPHE — measured, not assumed
 * -----------------------------------------------------------
 * `patients.id` is the parent of 88 foreign keys declared ON DELETE CASCADE.
 * Among them: `prescriptions`, `lab_results`, `lab_orders`, `encounters`,
 * `visits`, `invoices`, `radiology_reports`, `immunization_records` — and
 * `medical_id_access_events`, the patient's own evidence of who opened their
 * record. One `$patient->delete()` would silently take all of it. A pharmacy
 * that dispensed a controlled medicine would lose its record of dispensing it;
 * the patient would lose the proof of who read their file. Neither is ours to
 * destroy, and `App\Models\Prescription` says so in code — its `deleting`
 * hook throws, because a clinical event is amended or voided, never deleted.
 *
 * `users.id` is the parent of 47 more CASCADEs, including `access_logs.actor_id`
 * and `user_legal_acceptances`. So the login row is not deleted either: it is
 * emptied. Deleting it would erase the log of the patient's own actions.
 *
 * SO WHAT THIS ACTUALLY DOES
 * --------------------------
 * Three groups, and the screen names all three before the patient confirms.
 *
 *   1. ERASED       Everything that lets somebody sign in as, contact, or
 *                   identify this person from the account: access tokens,
 *                   mobile sessions, push tokens, the PIN, the password, the
 *                   sign-in email, the phone number and its lookup hash, the
 *                   patient's email and address, the emergency contact, the
 *                   national ID and CNAMGS numbers, the referral code, and any
 *                   outstanding OTP / password-reset codes. Set to NULL or
 *                   overwritten in place — not soft-deleted, not archived.
 *
 *   2. DE-IDENTIFIED  The `patients` row itself survives, because it is the
 *                   spine every clinical record hangs from, but the name is
 *                   overwritten and the identity is marked erased. What stays
 *                   on it is what a medical record cannot be read without:
 *                   the Health ID, date of birth, sex, blood group.
 *
 *   3. RETAINED     The clinical record and the access log, under Cameroon's
 *                   medical-record retention period (10 years by default, read
 *                   from the country policy). `retention_until` is stamped on
 *                   the closure request at execution, and `purgeExpiredRetention()`
 *                   is the code that honours it.
 *
 * WHAT IS DELIBERATELY NOT CLAIMED
 * --------------------------------
 * `IdentityStatus::ErasurePending` is set, and `IdentityStatus::isBlocked()`
 * describes it as hidden from routine clinical lookups — but `isBlocked()` has
 * no callers anywhere in this codebase, so nothing enforces that today. The
 * status is therefore written as a MARKER and the patient-facing text says the
 * record is "marked closed", never that it is hidden. What actually stops a
 * sign-in is group 1, which is enforced by the absence of the credentials
 * themselves, and is what the disclosure promises.
 *
 * REVERSIBILITY
 * -------------
 * Nothing above happens when the patient asks. It happens GRACE_PERIOD_DAYS
 * later, and only if the request is still `pending`. An accidental irreversible
 * wipe of a health record is not a recoverable mistake, so the window is long
 * and the cancel is one authenticated call.
 */
class AccountDeletionService
{
    /**
     * How long a patient has to change their mind.
     *
     * 30 days, and the number is stored on each request rather than read from
     * here at execution time — a patient shown "3 October" must not have that
     * date moved by a later edit to this constant.
     *
     * Why this long: the destination is a medical record, and the two most
     * likely reasons a deletion request is wrong are the two that take time to
     * surface — a phone that was lost or stolen and is later recovered, and a
     * patient who deletes in frustration during a care episode that is still
     * running. Both resolve inside a month.
     */
    public const GRACE_PERIOD_DAYS = 30;

    /** Fallback if the country policy carries no retention period. */
    public const DEFAULT_RETENTION_YEARS = 10;

    /**
     * What the name becomes. ASCII and language-neutral on purpose: this is a
     * stored database value read by clinicians in both languages and by the
     * admin portal, not UI copy, so it must not be written in the language the
     * patient's phone happened to be set to.
     */
    public const REDACTED_GIVEN_NAME  = 'Closed';
    public const REDACTED_FAMILY_NAME = 'Account';

    /** Domain for the tombstoned sign-in address. `.invalid` is reserved (RFC 2606). */
    public const CLOSED_EMAIL_DOMAIN = 'account-closed.invalid';

    public function __construct(
        private readonly CountryPolicyService $countryPolicy,
    ) {
    }

    /* ====================================================================== */
    /* Reading the current state                                              */
    /* ====================================================================== */

    /** The patient's live closure request, if they have one. */
    public function pendingFor(string $patientId): ?AccountClosureRequest
    {
        return AccountClosureRequest::query()
            ->where('patient_id', $patientId)
            ->where('status', AccountClosureRequest::STATUS_PENDING)
            ->latest('created_at')
            ->first();
    }

    /** The most recent request of any status — so a completed one stays visible. */
    public function latestFor(string $patientId): ?AccountClosureRequest
    {
        return AccountClosureRequest::query()
            ->where('patient_id', $patientId)
            ->latest('created_at')
            ->first();
    }

    /**
     * The retention period this patient's record falls under, in years.
     *
     * Read from the published country policy so a deployment in a second
     * country tells its patients that country's number, not Cameroon's.
     */
    public function retentionYears(?string $countryCode): int
    {
        $settings = $this->countryPolicy->getSettings($countryCode ?: 'CM');

        $years = $settings['retention_period_years'] ?? self::DEFAULT_RETENTION_YEARS;

        return is_numeric($years) && (int) $years > 0
            ? (int) $years
            : self::DEFAULT_RETENTION_YEARS;
    }

    /* ====================================================================== */
    /* The disclosure — what the patient reads before confirming              */
    /* ====================================================================== */

    /**
     * Tables whose rows are RETAINED, with the lang key that names them.
     *
     * Counted live against the patient's own record so the screen says "7
     * prescriptions", not "your prescriptions may be retained". A patient
     * deciding whether to close an account is entitled to know the size of
     * what survives, and a vague sentence is how a promise becomes untrue.
     *
     * Every entry is guarded by Schema::hasTable — this list must never be the
     * reason the deletion screen 500s on a deployment that trimmed a module.
     *
     * @var array<string, string>  table => lang key under account_deletion.kept_items
     */
    private const RETAINED_TABLES = [
        'prescriptions'             => 'prescriptions',
        'lab_results'               => 'lab_results',
        'lab_orders'                => 'lab_orders',
        'encounters'                => 'encounters',
        'visits'                    => 'visits',
        'appointments'              => 'appointments',
        'imaging_orders'            => 'imaging_orders',
        'radiology_reports'         => 'radiology_reports',
        'immunization_records'      => 'immunizations',
        'problem_lists'             => 'problems',
        'diagnoses'                 => 'diagnoses',
        'invoices'                  => 'invoices',
        'payments'                  => 'payments',
        'medical_id_access_events'  => 'access_events',
        'access_logs'               => 'access_logs',
    ];

    /**
     * The itemised, truthful account of what closing does — erased,
     * de-identified, retained — with live counts and real dates.
     *
     * @return array<string, mixed>
     */
    public function disclosure(Patient $patient, string $locale): array
    {
        $years         = $this->retentionYears($patient->country_code);
        $executesAfter = now()->addDays(self::GRACE_PERIOD_DAYS);
        $retentionEnd  = $executesAfter->copy()->addYears($years);

        return [
            'disclosure_version' => AccountClosureRequest::DISCLOSURE_VERSION,
            'grace_period_days'  => self::GRACE_PERIOD_DAYS,
            'retention_years'    => $years,

            // Dates the patient is being shown. Both are what will actually be
            // written if they confirm right now.
            'would_execute_after'  => $executesAfter->toIso8601String(),
            'would_retain_until'   => $retentionEnd->toDateString(),

            'headline' => __('account_deletion.headline', [], $locale),
            'summary'  => __('account_deletion.summary', [
                'days' => self::GRACE_PERIOD_DAYS,
            ], $locale),

            'erased' => [
                'title' => __('account_deletion.erased.title', [], $locale),
                'note'  => __('account_deletion.erased.note', [], $locale),
                'items' => $this->localisedList('account_deletion.erased.items', [
                    'sign_in', 'password', 'pin', 'sessions', 'devices',
                    'phone', 'email', 'address', 'emergency_contact',
                    'national_id', 'referral_code',
                ], $locale),
            ],

            'deidentified' => [
                'title' => __('account_deletion.deidentified.title', [], $locale),
                'note'  => __('account_deletion.deidentified.note', [], $locale),
                'items' => $this->localisedList('account_deletion.deidentified.items', [
                    'name', 'record_marked',
                ], $locale),
            ],

            'retained' => [
                'title'        => __('account_deletion.retained.title', [], $locale),
                'note'         => __('account_deletion.retained.note', [], $locale),
                'legal_basis'  => __('account_deletion.retained.legal_basis', [
                    'years' => $years,
                ], $locale),
                'purge_note'   => __('account_deletion.retained.purge_note', [
                    'date' => $retentionEnd->toDateString(),
                ], $locale),
                'retain_until' => $retentionEnd->toDateString(),
                'counts'       => $this->retainedCounts($patient->id, $locale),
            ],

            // The part most deletion screens leave out.
            'cannot_delete' => [
                'title' => __('account_deletion.cannot.title', [], $locale),
                'items' => $this->localisedList('account_deletion.cannot.items', [
                    'clinical_events', 'access_logs', 'facility_copies', 'fiscal',
                ], $locale),
            ],

            'confirmation' => [
                'phrase'       => __('account_deletion.confirm_phrase', [], $locale),
                'instruction'  => __('account_deletion.confirm_instruction', [
                    'phrase' => __('account_deletion.confirm_phrase', [], $locale),
                ], $locale),
                'reauth_note'  => __('account_deletion.reauth_note', [], $locale),
                'methods'      => $this->availableReauthMethods($patient),
            ],
        ];
    }

    /**
     * Live per-table counts of what survives, already labelled in the
     * patient's language. Tables with nothing in them are dropped: a list of
     * fifteen zeroes teaches nobody anything.
     *
     * @return list<array{key: string, label: string, count: int}>
     */
    private function retainedCounts(string $patientId, string $locale): array
    {
        $out = [];

        foreach (self::RETAINED_TABLES as $table => $key) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'patient_id')) {
                continue;
            }

            try {
                $count = DB::table($table)->where('patient_id', $patientId)->count();
            } catch (\Throwable $e) {
                // A count that cannot be taken is reported as unknown by being
                // omitted — never as zero, which would understate what is kept.
                Log::warning('account_deletion.count_failed', [
                    'table' => $table,
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if ($count === 0) {
                continue;
            }

            $out[] = [
                'key'   => $key,
                'label' => __("account_deletion.kept_items.$key", [], $locale),
                'count' => $count,
            ];
        }

        return $out;
    }

    /** @return list<array{key: string, text: string}> */
    private function localisedList(string $namespace, array $keys, string $locale): array
    {
        return array_map(fn (string $k) => [
            'key'  => $k,
            'text' => __("$namespace.$k", [], $locale),
        ], $keys);
    }

    /**
     * How this particular patient can prove it is them.
     *
     * A patient created at a facility desk may have a PIN and no portal
     * password; one who self-registered in the app has both. Reporting the
     * real set means the screen never asks for a credential that does not
     * exist — and means `request()` can refuse safely when there is neither.
     *
     * @return list<string>
     */
    public function availableReauthMethods(Patient $patient): array
    {
        $methods = [];

        $user = $this->linkedUser($patient->id);
        if ($user !== null && filled($user->password)) {
            $methods[] = 'password';
        }

        if (filled($patient->pin_hash)) {
            $methods[] = 'pin';
        }

        return $methods;
    }

    public function linkedUser(string $patientId): ?User
    {
        return User::where('patient_id', $patientId)->first();
    }

    /**
     * Check a re-authentication secret.
     *
     * Returns the method that matched, or null. Both branches run a hash
     * comparison even when the stored hash is absent is NOT done here on
     * purpose — an absent credential is reported up front by
     * availableReauthMethods(), so there is no enumeration signal to protect:
     * the caller is already authenticated as this patient.
     */
    public function verifyReauth(Patient $patient, string $secret): ?string
    {
        $user = $this->linkedUser($patient->id);

        if ($user !== null && filled($user->password) && Hash::check($secret, $user->password)) {
            return 'password';
        }

        if (filled($patient->pin_hash) && Hash::check($secret, $patient->pin_hash)) {
            return 'pin';
        }

        return null;
    }

    /**
     * Does the typed confirmation match?
     *
     * Accepted in BOTH languages regardless of the current locale. A patient
     * whose phone is in French but who reads the English word, or who switched
     * language mid-flow, has still expressed the same intention, and failing
     * them on a locale mismatch would be a trap rather than a safeguard.
     */
    public function confirmationMatches(string $typed): bool
    {
        $typed = Str::upper(trim($typed));

        foreach (['en', 'fr'] as $locale) {
            if ($typed === Str::upper(__('account_deletion.confirm_phrase', [], $locale))) {
                return true;
            }
        }

        return false;
    }

    /* ====================================================================== */
    /* Requesting, cancelling                                                 */
    /* ====================================================================== */

    /**
     * Open the grace period.
     *
     * Idempotent by design: a patient who already has a pending request gets
     * that same request back rather than a second one. The partial unique
     * index added by the 2026-09-05 migration is the backstop for the race
     * this cannot see (two taps, two connections).
     *
     * @param array{reason?: ?string, reauth_method: string, via?: string, ip?: ?string, user_agent?: ?string, export_requested?: bool} $context
     */
    public function request(Patient $patient, array $context): AccountClosureRequest
    {
        $existing = $this->pendingFor($patient->id);
        if ($existing !== null) {
            return $existing;
        }

        $request = AccountClosureRequest::create([
            'patient_id'            => $patient->id,
            'reason'                => $context['reason'] ?? null,
            'status'                => AccountClosureRequest::STATUS_PENDING,
            'data_delete_requested' => true,
            'data_export_requested' => (bool) ($context['export_requested'] ?? false),
            'executes_after'        => now()->addDays(self::GRACE_PERIOD_DAYS),
            'grace_period_days'     => self::GRACE_PERIOD_DAYS,
            'disclosure_version'    => AccountClosureRequest::DISCLOSURE_VERSION,
            'reauth_method'         => $context['reauth_method'],
            'requested_via'         => $context['via'] ?? 'mobile',
            'requested_ip'          => $context['ip'] ?? null,
            'requested_user_agent'  => Str::limit((string) ($context['user_agent'] ?? ''), 500, '') ?: null,
        ]);

        $this->audit(
            $patient,
            AuditEventType::DataErasureRequest->value,
            'requested',
            "Account closure requested; erasure scheduled for {$request->executes_after?->toIso8601String()}",
        );

        return $request;
    }

    /**
     * Undo a pending request.
     *
     * The row is kept and marked, never deleted — a patient asking "did I
     * cancel that?" deserves a record that answers, and an auditor asking
     * "was an erasure ever requested for this record?" deserves the same.
     */
    public function cancel(AccountClosureRequest $request, ?string $reason = null): AccountClosureRequest
    {
        if (! $request->isPending()) {
            return $request;
        }

        $request->update([
            'status'           => AccountClosureRequest::STATUS_CANCELLED,
            'cancelled_at'     => now(),
            'cancelled_reason' => $reason !== null ? Str::limit($reason, 290, '') : null,
        ]);

        $patient = $request->patient()->first();
        if ($patient !== null) {
            $this->audit(
                $patient,
                AuditEventType::DataErasureRequest->value,
                'cancelled',
                'Account closure request cancelled by the patient before the grace period elapsed',
            );
        }

        return $request->refresh();
    }

    /* ====================================================================== */
    /* Execution                                                              */
    /* ====================================================================== */

    /**
     * Carry out the closure. Everything, or nothing.
     *
     * Wrapped in a transaction because a half-erased account is the worst of
     * both worlds: the patient cannot sign in and their phone number is still
     * on file. A throw anywhere rolls the whole thing back and leaves the
     * request `pending`, so the next sweep retries it.
     */
    public function execute(AccountClosureRequest $request): AccountClosureRequest
    {
        if (! $request->isPending()) {
            return $request;
        }

        /** @var Patient|null $patient */
        $patient = Patient::find($request->patient_id);

        if ($patient === null) {
            // The record is already gone by some other route. Close the request
            // honestly rather than leaving it pending forever.
            $request->update([
                'status'            => AccountClosureRequest::STATUS_COMPLETED,
                'executed_at'       => now(),
                'completed_at'      => now(),
                'execution_summary' => ['note' => 'patient record already absent at execution time'],
            ]);

            return $request->refresh();
        }

        $years        = $this->retentionYears($patient->country_code);
        $retainUntil  = now()->addYears($years)->toDateString();
        $healthId     = $patient->health_id;
        $oldPhone     = $patient->phone_number;
        $oldEmail     = $patient->email;

        $summary = DB::transaction(function () use ($patient, $oldPhone, $oldEmail) {
            $steps = [];

            // ── 1. Sessions and devices ───────────────────────────────────
            // Every live way into the account, cut first. If anything later in
            // the transaction throws, this rolls back with it — but on the
            // success path no token outlives the erasure.
            $steps['tokens_deleted']   = $this->deleteWhere('patient_access_tokens', 'patient_id', $patient->id);
            $steps['sessions_deleted'] = $this->deleteWhere('mobile_sessions', 'patient_id', $patient->id);
            $steps['push_tokens_deleted']    = $this->deleteWhere('push_device_tokens', 'patient_id', $patient->id);
            $steps['consent_devices_deleted'] = $this->deleteWhere('mobile_consent_devices', 'patient_id', $patient->id);
            $steps['app_settings_deleted']   = $this->deleteWhere('mobile_app_settings', 'patient_id', $patient->id);

            // Outstanding one-time codes, keyed by the identifiers that are
            // about to be erased. Deleted BEFORE the identifiers go, or they
            // become unreachable rows that still unlock nothing but linger.
            $steps['otp_codes_deleted'] = $oldPhone !== null
                ? $this->deleteWhere('patient_otp_codes', 'phone_number', $oldPhone)
                : 0;

            // ── 2. Sign-in credentials ────────────────────────────────────
            // The users row is emptied, not deleted: 47 foreign keys cascade
            // from users.id, including access_logs.actor_id, so deleting it
            // would destroy the log of this patient's own actions.
            $user = User::where('patient_id', $patient->id)->first();

            if ($user !== null) {
                $steps['reset_codes_deleted'] = $this->deleteWhere('password_reset_codes', 'email', $user->email);

                $user->forceFill([
                    'name'     => self::REDACTED_GIVEN_NAME . ' ' . self::REDACTED_FAMILY_NAME,
                    // Unique and unroutable. loginWithCredentials() and
                    // forgotPassword() both look the account up by this
                    // address; after this write neither can find it.
                    'email'    => 'closed-' . Str::lower(Str::random(24)) . '@' . self::CLOSED_EMAIL_DOMAIN,
                    // A hash of a value nobody holds, rather than null: the
                    // column is NOT NULL, and an empty-string password is a
                    // hash somebody could conceivably match.
                    'password' => Hash::make(Str::random(64)),
                    'status'   => 'closed',
                    'remember_token'            => null,
                    'email_verified_at'         => null,
                    'two_factor_secret'         => null,
                    'two_factor_recovery_codes' => null,
                    'two_factor_confirmed_at'   => null,
                ])->save();

                $steps['user_deidentified'] = true;
                $steps['user_id']           = $user->id;
            } else {
                $steps['reset_codes_deleted'] = $oldEmail !== null
                    ? $this->deleteWhere('password_reset_codes', 'email', $oldEmail)
                    : 0;
                $steps['user_deidentified'] = false;
            }

            // ── 3. Identifiers on the patient record ──────────────────────
            // forceFill, not update(): phone_number and its keyed lookup hash
            // are written by a mutator pair, and `phone_number_hash` is not in
            // $fillable. Assigning phone_number = null nulls both (see
            // Patient::setPhoneNumberAttribute), which is what actually breaks
            // the phone + PIN login path.
            $patient->forceFill([
                'first_name'          => self::REDACTED_GIVEN_NAME,
                'last_name'           => self::REDACTED_FAMILY_NAME,
                'middle_name'         => null,
                'phone_number'        => null,
                'email'               => null,
                'address'             => null,
                'emergency_contact'   => null,
                'national_id_number'  => null,
                'national_id_type'    => null,
                'cnamgs_id'           => null,
                'cnamgs_verified_at'  => null,
                'pin_hash'            => null,
                'push_token'          => null,
                'push_platform'       => null,
                'referral_code'       => null,
                'privacy_preferences' => null,
                // Markers. Nothing in this codebase enforces them today
                // (IdentityStatus::isBlocked() has no callers), so they are
                // recorded as state and never described to the patient as
                // protection — see the class docblock.
                'identity_status'     => IdentityStatus::ErasurePending->value,
                'verification_status' => VerificationStatus::ErasurePending->value,
            ])->save();

            $steps['patient_deidentified'] = true;

            return $steps;
        });

        $summary['retained_counts'] = $this->rawRetainedCounts($request->patient_id);
        $summary['retention_years'] = $years;

        $request->update([
            'status'            => AccountClosureRequest::STATUS_COMPLETED,
            'executed_at'       => now(),
            'completed_at'      => now(),
            'retention_until'   => $retainUntil,
            'execution_summary' => $summary,
        ]);

        // Audited AFTER the fact and outside the transaction: the audit trail
        // must survive even if it is written a moment late, and must not be
        // rolled back by a failure in its own insert.
        $this->auditRaw(
            patientId: $patient->id,
            healthId: $healthId,
            accessType: AuditEventType::DataErasureRequest->value,
            result: 'executed',
            notes: "Account closed and de-identified; clinical record retained until {$retainUntil}",
        );

        Log::info('account_deletion.executed', [
            'closure_request_id' => $request->id,
            'patient_id'         => $patient->id,
            'retention_until'    => $retainUntil,
        ]);

        return $request->refresh();
    }

    /**
     * Execute every request whose grace period has elapsed.
     *
     * @return array{examined: int, executed: int, failed: int, ids: list<string>}
     */
    public function executeDue(?CarbonInterface $now = null, int $limit = 500): array
    {
        $now = $now ?? now();

        $due = AccountClosureRequest::query()
            ->where('status', AccountClosureRequest::STATUS_PENDING)
            ->whereNotNull('executes_after')
            ->where('executes_after', '<=', $now)
            ->orderBy('executes_after')
            ->limit($limit)
            ->get();

        $executed = 0;
        $failed   = 0;
        $ids      = [];

        foreach ($due as $request) {
            try {
                $this->execute($request);
                $executed++;
                $ids[] = (string) $request->id;
            } catch (\Throwable $e) {
                // One bad row must not stop the sweep. The request stays
                // `pending`, so tomorrow's run retries it.
                $failed++;
                Log::error('account_deletion.execute_failed', [
                    'closure_request_id' => $request->id,
                    'error'              => $e->getMessage(),
                ]);
            }
        }

        return ['examined' => $due->count(), 'executed' => $executed, 'failed' => $failed, 'ids' => $ids];
    }

    /**
     * The second, later half of the promise: destroy the retained record once
     * its statutory retention period has run out.
     *
     * This is what makes "we keep it until <date>" a commitment rather than a
     * wish. It is deliberately the ONLY place a `patients` row is deleted, and
     * it is reached only for a row that was closed at least `retention_years`
     * ago — at which point the cascade that would be a catastrophe today is
     * exactly what the retention rule calls for.
     *
     * @return array{examined: int, purged: int, failed: int}
     */
    public function purgeExpiredRetention(?CarbonInterface $now = null, int $limit = 200): array
    {
        $now = $now ?? now();

        $expired = AccountClosureRequest::query()
            ->where('status', AccountClosureRequest::STATUS_COMPLETED)
            ->whereNotNull('retention_until')
            ->whereDate('retention_until', '<=', $now->toDateString())
            ->limit($limit)
            ->get();

        $purged = 0;
        $failed = 0;

        foreach ($expired as $request) {
            try {
                DB::transaction(function () use ($request) {
                    $patient = Patient::find($request->patient_id);

                    if ($patient !== null) {
                        // Detach the closure request first: it cascades from
                        // patients, and deleting the patient would take the
                        // evidence that the purge was authorised.
                        DB::table('account_closure_requests')
                            ->where('id', $request->id)
                            ->update(['patient_id' => null]);

                        $patient->delete();
                    }

                    DB::table('account_closure_requests')
                        ->where('id', $request->id)
                        ->update([
                            'status'    => AccountClosureRequest::STATUS_PURGED,
                            'purged_at' => now(),
                        ]);
                });

                $purged++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('account_deletion.purge_failed', [
                    'closure_request_id' => $request->id,
                    'error'              => $e->getMessage(),
                ]);
            }
        }

        return ['examined' => $expired->count(), 'purged' => $purged, 'failed' => $failed];
    }

    /* ====================================================================== */
    /* Helpers                                                                */
    /* ====================================================================== */

    /** Delete rows, tolerating a table a trimmed deployment does not have. */
    private function deleteWhere(string $table, string $column, mixed $value): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return DB::table($table)->where($column, $value)->delete();
    }

    /** @return array<string, int> */
    private function rawRetainedCounts(string $patientId): array
    {
        $counts = [];

        foreach (self::RETAINED_TABLES as $table => $key) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'patient_id')) {
                continue;
            }

            try {
                $counts[$table] = DB::table($table)->where('patient_id', $patientId)->count();
            } catch (\Throwable) {
                // Omitted rather than reported as zero.
            }
        }

        return $counts;
    }

    private function audit(Patient $patient, string $accessType, string $result, string $notes): void
    {
        $this->auditRaw($patient->id, $patient->health_id, $accessType, $result, $notes);
    }

    /**
     * Write both audit trails.
     *
     * `medical_id_access_events` is the one GenerateMinsanteMonthlyReport
     * counts under `erasure_requests`, so a closure shows up in the ministry
     * report without anything else being wired.
     *
     * `access_logs.actor_id` is a NOT NULL foreign key onto users, so the
     * second write is skipped — with a log line, never silently — for a
     * patient who has no linked portal user. Losing that row does not lose the
     * event: the first write always happens.
     */
    private function auditRaw(
        string $patientId,
        ?string $healthId,
        string $accessType,
        string $result,
        string $notes,
    ): void {
        try {
            // forceFill rather than create(): `notes` is a real column on
            // medical_id_access_events but is not in the model's $fillable, and
            // the note is the part an auditor actually reads.
            (new MedicalIdAccessEvent())->forceFill([
                'patient_id'  => $patientId,
                'health_id'   => $healthId,
                'actor_id'    => $patientId,
                'actor_type'  => 'patient',
                'facility_id' => null,
                'access_type' => $accessType,
                'purpose'     => 'data_subject_rights',
                'result'      => $result,
                'notes'       => $notes,
                'ip_address'  => request()->ip(),
                'user_agent'  => Str::limit((string) request()->userAgent(), 250, ''),
            ])->save();
        } catch (\Throwable $e) {
            Log::error('account_deletion.audit_failed', [
                'patient_id' => $patientId,
                'result'     => $result,
                'error'      => $e->getMessage(),
            ]);
        }

        $userId = User::where('patient_id', $patientId)->value('id');

        if ($userId === null) {
            Log::warning('account_deletion.access_log_skipped_no_linked_user', [
                'patient_id' => $patientId,
                'result'     => $result,
            ]);

            return;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: (string) $userId,
                actorType: 'patient',
                organizationId: null,
                facilityId: null,
                purpose: 'data_subject_rights',
                dataCategory: 'account',
                resourceType: 'account_closure_request',
                resourceId: $patientId,
                accessType: $accessType,
            );
        } catch (\Throwable $e) {
            Log::error('account_deletion.access_log_failed', [
                'patient_id' => $patientId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
