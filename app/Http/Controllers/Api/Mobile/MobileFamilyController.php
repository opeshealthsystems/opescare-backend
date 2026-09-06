<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\FamilyLinkResource;
use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use App\Modules\PatientIdentity\Exceptions\DuplicateIdentityFound;
use App\Modules\PatientIdentity\Services\PatientIdentityService;
use App\Services\Family\DependentAccessAudit;
use App\Services\Family\DependentRecordSummary;
use App\Services\Family\FamilyInvitationNotifier;
use App\Support\HealthIdCardPayload;
use App\Support\PatientPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Mobile Patient API — Family / Dependents.
 *
 * The guardian side (list, register a child, invite an adult, cancel, member
 * detail) and the dependent side (invitations addressed to me, accept,
 * decline). The signed-in account is always the one acting: ActAsDependent
 * refuses the X-OpesCare-Dependent header on every family/* path, so
 * `patient_id` / `patient_user_id` here are the caller's own.
 *
 * Vocabulary: `access_level` is `full` | `read_only` (FamilyLink maps legacy
 * spellings on read); `relationship` is the GUARDIAN's role toward the
 * dependent — see the FamilyLink docblock.
 */
class MobileFamilyController extends Controller
{
    /**
     * The guardian's user account — the identity a link hangs off and audit
     * trails are attributed to. auth.mobile resolves it from the bearer token
     * (users.patient_id); a patient with no portal user cannot hold links.
     */
    private function guardianUser(Request $request): ?User
    {
        $userId = $request->attributes->get('patient_user_id');
        if ($userId) {
            return User::find($userId);
        }

        return User::where('patient_id', $request->attributes->get('patient_id'))->first();
    }

    // ── Guardian side ────────────────────────────────────────────────────────

    /**
     * GET /api/mobile/family
     * Active + pending links managed by the authenticated guardian.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->guardianUser($request);
        if (! $user) {
            return response()->json(['data' => []]);
        }

        $links = FamilyLink::where('guardian_user_id', $user->id)
            ->whereIn('status', [FamilyLink::STATUS_ACTIVE, FamilyLink::STATUS_PENDING_INVITE])
            ->with('dependentPatient')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (FamilyLink $link) => $this->formatLink($link))
            ->values();

        return response()->json(['data' => FamilyLinkResource::collection(collect($links))]);
    }

    /**
     * GET /api/mobile/family/members/{linkId}
     * A dependent's Health ID card and a summary of their record — only for
     * the guardian who holds the ACTIVE link. Anyone else gets 404: the
     * response must not say whether the link exists.
     */
    public function show(Request $request, string $linkId): JsonResponse
    {
        $user = $this->guardianUser($request);
        if (! $user) {
            return $this->memberNotFound();
        }

        $link = FamilyLink::query()
            ->active()
            ->where('id', $linkId)
            ->where('guardian_user_id', $user->id)
            ->with('dependentPatient')
            ->first();

        // A dependent hidden by the data partition is, for this caller, not there.
        if (! $link || ! $link->dependentPatient) {
            return $this->memberNotFound();
        }

        // The dependent turned 18 and the grace period ran out: the link is
        // still on file but grants nothing until they re-consent.
        if ($link->isExpiredByAge()) {
            return response()->json([
                'message'    => __('family_api.dependent_forbidden'),
                'error_code' => 'dependent_forbidden',
            ], 403);
        }

        $patient   = $link->dependentPatient;
        $patientId = $patient->id;

        DependentAccessAudit::record(
            link: $link,
            guardianUserId: $user->id,
            dataCategory: 'summary',
            resourceType: 'FamilyLink',
            resourceId: $link->id,
            accessType: 'read',
        );

        return response()->json([
            'data' => [
                'link' => [
                    'id'                        => $link->id,
                    'relationship'              => $link->relationship,
                    'access_level'              => $link->access_level,
                    'status'                    => $link->status,
                    'created_at'                => $link->created_at?->toIso8601String(),
                    'age_transition_expires_at' => $link->age_transition_expires_at?->toIso8601String(),
                ],
                // Same names and semantics as GET /mobile/health-id-card, plus
                // id / first_name / last_name. No QR for a read-only guardian:
                // presenting the card at a counter is an act, not a look.
                'patient' => ['id' => $patientId]
                    + HealthIdCardPayload::for($patient, withQr: $link->isFullAccess())
                    + ['first_name' => $patient->first_name, 'last_name' => $patient->last_name],
                'immunizations'         => DependentRecordSummary::immunizations($patientId),
                'upcoming_appointments' => DependentRecordSummary::upcomingAppointments($patientId),
                'records_summary'       => DependentRecordSummary::counts($patientId),
            ],
        ]);
    }

    /**
     * POST /api/mobile/family
     * Register a CHILD: a new Patient (through PatientIdentityService — the
     * only sanctioned way to mint a Health ID) plus an active link. Adults are
     * never registered on someone else's say-so; they are invited.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->guardianUser($request);
        if (! $user) {
            return response()->json(['message' => __('api.no_user_linked')], 403);
        }

        // The app posts first_name + last_name; the web form and older clients
        // post one full_name. Accept either, never both missing.
        $data = $request->validate([
            'full_name'     => 'required_without:first_name|nullable|string|max:200',
            'first_name'    => 'required_without:full_name|nullable|string|max:100',
            'last_name'     => 'required_with:first_name|nullable|string|max:100',
            'date_of_birth' => 'required|date|before:today',
            'sex'           => 'required|in:male,female', // patients.sex is constrained to these two
            'relationship'  => ['required', Rule::in(FamilyLink::RELATIONSHIPS)],
            'access_level'  => ['nullable', Rule::in(FamilyLink::ACCESS_LEVELS)],
            'blood_group'   => 'nullable|string|max:20',
            'phone'         => 'nullable|string|max:30',
        ]);

        $majority = (int) config('family.majority_age', 18);
        if (Carbon::parse($data['date_of_birth'])->age >= $majority) {
            $message = __('family_api.dependent_is_adult', ['age' => $majority]);

            return response()->json([
                'message'    => $message,
                'error_code' => 'dependent_is_adult',
                'errors'     => ['date_of_birth' => [$message]],
            ], 422);
        }

        $fullName  = trim((string) ($data['full_name'] ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''))));
        $nameParts = ! empty($data['first_name'])
            ? [trim($data['first_name']), trim((string) ($data['last_name'] ?? ''))]
            : explode(' ', $fullName, 2);
        $firstName = $nameParts[0];
        $lastName  = $nameParts[1] ?? '';

        // Same rule as the web family screen: a person who already holds a
        // Health ID is invited, not re-created. Auto-linking them here would
        // hand one person's record to another on a name+dob guess.
        if (Patient::findExistingIdentity($firstName, $lastName, $data['date_of_birth'], $data['phone'] ?? null)) {
            return response()->json(['message' => __('flash.family_member_already_registered')], 409);
        }

        // The child lives on the guardian's side of the demo partition, not on
        // whatever side this host's config happens to be — a hard-coded flag
        // made children invisible to the very parent who registered them.
        $guardianPatient = Patient::withoutGlobalScopes()->find($request->attributes->get('patient_id'));

        try {
            $link = DB::transaction(function () use ($data, $firstName, $lastName, $user, $guardianPatient) {
                $patient = app(PatientIdentityService::class)->createPatientCandidate([
                    'first_name'      => $firstName,
                    'last_name'       => $lastName,
                    'date_of_birth'   => $data['date_of_birth'],
                    'sex'             => $data['sex'],
                    'phone_number'    => $data['phone'] ?? null,
                    'identity_status' => 'provisional',
                    'country_code'    => $guardianPatient?->country_code ?? 'CM',
                ], actorId: $user->id);

                $patient->forceFill([
                    'blood_group' => $data['blood_group'] ?? null,
                    'is_demo'     => (bool) ($guardianPatient?->is_demo ?? config('demo.enabled', false)),
                ])->save();

                return FamilyLink::create([
                    'guardian_user_id'     => $user->id,
                    'dependent_patient_id' => $patient->id,
                    'relationship'         => $data['relationship'],
                    'access_level'         => $data['access_level'] ?? FamilyLink::ACCESS_FULL,
                    'status'               => FamilyLink::STATUS_ACTIVE,
                    'created_by'           => FamilyLink::CREATED_BY_SELF_REGISTERED,
                ]);
            });
        } catch (DuplicateIdentityFound) {
            return response()->json(['message' => __('flash.family_member_already_registered')], 409);
        }

        $link->load('dependentPatient');

        return response()->json([
            'message' => __('api.family_member_added'),
            'data'    => $this->formatLink($link),
        ], 201);
    }

    /**
     * GET /api/mobile/family/invitations
     * Invitations this guardian has sent and that are still pending.
     */
    public function invitations(Request $request): JsonResponse
    {
        $user = $this->guardianUser($request);
        if (! $user) {
            return response()->json(['data' => []]);
        }

        $links = FamilyLink::where('guardian_user_id', $user->id)
            ->pendingInvite()
            ->with('dependentPatient')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (FamilyLink $link) => [
                'id'           => $link->id,
                // The invited person as "First L." — enough to confirm the
                // right person was invited, without handing the inviter an
                // identifier (email, Health ID) they may not have had.
                'contact'      => $link->dependentPatient
                    ? $this->shortName($link->dependentPatient)
                    : __('family_api.unknown_contact'),
                'relationship' => $link->relationship,
                'access_level' => $link->access_level,
                'status'       => $link->status,
                'method'       => 'invite',
                'sent_at'      => $link->created_at?->toDateString(),
                'expires_at'   => $link->invite_expires_at?->toDateString(),
            ])
            ->values();

        return response()->json(['data' => FamilyLinkResource::collection(collect($links))]);
    }

    /**
     * POST /api/mobile/family/invitations
     * Invite an existing OpesCare patient — an adult, typically an older
     * relative — by exact Health ID, email or phone. Three exact lookups, no
     * LIKE, and one neutral refusal: the answer must not reveal whether a
     * given identifier belongs to anyone.
     */
    public function sendInvitation(Request $request): JsonResponse
    {
        $user = $this->guardianUser($request);
        if (! $user) {
            return response()->json(['message' => __('api.no_user_linked')], 403);
        }

        $data = $request->validate([
            'health_id'    => 'nullable|string|max:64|required_without_all:email,phone,contact',
            'email'        => 'nullable|email|max:180|required_without_all:health_id,phone,contact',
            'phone'        => 'nullable|string|max:30|required_without_all:health_id,email,contact',
            // Legacy single field from the first app release: resolved by the
            // same three exact lookups.
            'contact'      => 'nullable|string|max:255|required_without_all:health_id,email,phone',
            'method'       => 'nullable|in:phone,email,qr',
            'relationship' => ['required', Rule::in(FamilyLink::RELATIONSHIPS)],
            'access_level' => ['nullable', Rule::in(array_merge(FamilyLink::ACCESS_LEVELS, array_keys(FamilyLink::LEGACY_ACCESS_LEVELS)))],
        ]);

        [$patient, $contact] = $this->resolveInvitee($data);

        // Uniform answer: whether or not the contact belongs to an OpesCare
        // patient, the caller hears the same thing, so this endpoint cannot be
        // used to test phone numbers or e-mail addresses for an account.
        if (! $patient) {
            return response()->json(['message' => __('family_api.invitation_queued')], 202);
        }

        if ($patient->id === $user->patient_id) {
            return response()->json(['message' => __('api.cannot_link_self')], 422);
        }

        $existing = FamilyLink::where('guardian_user_id', $user->id)
            ->where('dependent_patient_id', $patient->id)
            ->whereIn('status', [FamilyLink::STATUS_ACTIVE, FamilyLink::STATUS_PENDING_INVITE])
            ->exists();

        if ($existing) {
            return response()->json(['message' => __('api.family_link_exists')], 409);
        }

        $rawToken = Str::random(64);
        $link = FamilyLink::create([
            'guardian_user_id'     => $user->id,
            'dependent_patient_id' => $patient->id,
            'relationship'         => $data['relationship'],
            // An adult keeps control of their own record unless they are
            // explicitly asked for more.
            'access_level'         => FamilyLink::normalizeAccessLevel($data['access_level'] ?? FamilyLink::ACCESS_READ_ONLY),
            'status'               => FamilyLink::STATUS_PENDING_INVITE,
            'created_by'           => FamilyLink::CREATED_BY_GUARDIAN_INVITED,
            'invite_token'         => hash('sha256', $rawToken),
            'invite_expires_at'    => Carbon::now()->addHours((int) config('family.invite_ttl_hours', 48)),
        ]);
        $link->setRelation('guardianUser', $user);

        // In-app bell for the invited person (works with or without a login) …
        FamilyInvitationNotifier::invited($link, $patient);

        // … and the e-mail with the web accept link when they have an account.
        $dependentUser = User::where('patient_id', $patient->id)->first();
        if ($dependentUser && class_exists(\App\Notifications\FamilyInviteNotification::class)) {
            $dependentUser->notify(new \App\Notifications\FamilyInviteNotification($link, $rawToken));
        }

        // Same body and status as the not-found branch (see above). The
        // pending row appears in GET /family/invitations, by short name only.
        return response()->json(['message' => __('family_api.invitation_queued')], 202);
    }

    /**
     * DELETE /api/mobile/family/invitations/{id}
     * Cancel a pending invitation this guardian sent.
     */
    public function cancelInvitation(Request $request, string $id): JsonResponse
    {
        $user = $this->guardianUser($request);
        if (! $user) {
            return response()->json(['message' => __('api.unauthorized')], 403);
        }

        $link = FamilyLink::where('guardian_user_id', $user->id)
            ->where('id', $id)
            ->pendingInvite()
            ->firstOrFail();

        $link->update(['status' => FamilyLink::STATUS_CANCELLED, 'invite_token' => null]);

        return response()->json(['message' => __('api.invitation_cancelled')]);
    }

    // ── Dependent side ───────────────────────────────────────────────────────

    /**
     * GET /api/mobile/family/invitations/incoming
     * Invitations addressed to ME (the signed-in patient) that can still be
     * answered. Matched on my patient id, never on an email string.
     */
    public function incomingInvitations(Request $request): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');

        $links = FamilyLink::query()
            ->openInvite()
            ->where('dependent_patient_id', $patientId)
            ->with('guardianUser')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (FamilyLink $link) => [
                'id'            => $link->id,
                'guardian_name' => $link->guardianUser?->name ?? __('family_api.someone'),
                'relationship'  => $link->relationship,
                'access_level'  => $link->access_level,
                'created_at'    => $link->created_at?->toIso8601String(),
                'expires_at'    => $link->invite_expires_at?->toIso8601String(),
            ])
            ->values();

        return response()->json(['data' => FamilyLinkResource::collection(collect($links))]);
    }

    /**
     * POST /api/mobile/family/invitations/{id}/accept
     * I consent: the link becomes active, stamped with the moment I said yes.
     */
    public function acceptInvitation(Request $request, string $id): JsonResponse
    {
        $link = $this->myOpenInvitation($request, $id);
        if (! $link) {
            return $this->invitationNotFound();
        }

        $link->update([
            'status'                 => FamilyLink::STATUS_ACTIVE,
            'created_by'             => FamilyLink::CREATED_BY_INVITE_ACCEPTED,
            'dependent_consented_at' => now(),
            'invite_token'           => null,
            'invite_expires_at'      => null,
        ]);

        $me = Patient::find($request->attributes->get('patient_id'));
        if ($me) {
            FamilyInvitationNotifier::answered($link, $me, accepted: true);
        }

        $guardianName = $link->guardianUser?->name ?? __('family_api.someone');

        return response()->json([
            'message' => __('family_api.invitation_accepted', ['name' => $guardianName]),
            'data'    => [
                'id'                     => $link->id,
                'status'                 => $link->status,
                'relationship'           => $link->relationship,
                'access_level'           => $link->access_level,
                'guardian_name'          => $guardianName,
                'dependent_consented_at' => $link->dependent_consented_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * POST /api/mobile/family/invitations/{id}/decline
     * I refuse: the pair may be invited again later (terminal status).
     */
    public function declineInvitation(Request $request, string $id): JsonResponse
    {
        $link = $this->myOpenInvitation($request, $id);
        if (! $link) {
            return $this->invitationNotFound();
        }

        $link->update([
            'status'       => FamilyLink::STATUS_DECLINED,
            'invite_token' => null,
        ]);

        $me = Patient::find($request->attributes->get('patient_id'));
        if ($me) {
            FamilyInvitationNotifier::answered($link, $me, accepted: false);
        }

        return response()->json([
            'message' => __('family_api.invitation_declined'),
            'data'    => ['id' => $link->id, 'status' => $link->status],
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function myOpenInvitation(Request $request, string $id): ?FamilyLink
    {
        return FamilyLink::query()
            ->openInvite()
            ->where('id', $id)
            ->where('dependent_patient_id', $request->attributes->get('patient_id'))
            ->with('guardianUser')
            ->first();
    }

    /**
     * Exact lookups only. Returns the patient (or null) and the identifier the
     * caller typed, so the response can echo their own input back.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: ?Patient, 1: string}
     */
    private function resolveInvitee(array $data): array
    {
        $healthId = trim((string) ($data['health_id'] ?? ''));
        $email    = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $phone    = trim((string) ($data['phone'] ?? ''));
        $contact  = trim((string) ($data['contact'] ?? ''));

        if ($healthId !== '') {
            return [Patient::resolveHealthId($healthId), $healthId];
        }
        if ($email !== '') {
            return [$this->patientByEmail($email), $email];
        }
        if ($phone !== '') {
            return [Patient::findByPhone($phone), $phone];
        }

        // Legacy `contact`: the same three exact lookups, in turn.
        $patient = Patient::resolveHealthId($contact)
            ?? $this->patientByEmail(mb_strtolower($contact))
            ?? Patient::findByPhone($contact);

        return [$patient, $contact];
    }

    private function patientByEmail(string $email): ?Patient
    {
        if ($email === '') {
            return null;
        }

        return Patient::query()->whereRaw('lower(email) = ?', [$email])->first();
    }

    private function shortName(Patient $patient): string
    {
        return trim($patient->first_name . ' ' . mb_substr((string) $patient->last_name, 0, 1) . '.');
    }

    private function memberNotFound(): JsonResponse
    {
        return response()->json([
            'message'    => __('family_api.member_not_found'),
            'error_code' => 'member_not_found',
        ], 404);
    }

    private function invitationNotFound(): JsonResponse
    {
        return response()->json([
            'message'    => __('family_api.invitation_not_found'),
            'error_code' => 'invitation_not_found',
        ], 404);
    }

    /**
     * One member row of GET /mobile/family. The `patient` block carries the
     * card fields under the names /mobile/health-id-card uses (dob, blood_type,
     * photo_url, status) so the app can draw the right credential — child or
     * adult, boy or girl — straight from the list.
     */
    private function formatLink(FamilyLink $link): array
    {
        $patient = $link->dependentPatient;

        return [
            'id'                        => $link->id,
            'relationship'              => $link->relationship,
            'access_level'              => $link->access_level,
            'is_read_only'              => $link->isReadOnly(),
            'status'                    => $link->status,
            'is_pending'                => $link->status === FamilyLink::STATUS_PENDING_INVITE,
            'created_by'                => $link->created_by,
            'age_transition_expires_at' => $link->age_transition_expires_at?->toIso8601String(),
            'dependent_consented_at'    => $link->dependent_consented_at?->toIso8601String(),
            // Before the person has accepted, the guardian learns nothing about
            // them beyond a short name — no Health ID, no demographics, no photo.
            'patient'                   => $patient ? ($link->status !== FamilyLink::STATUS_ACTIVE ? [
                'display_name' => $this->shortName($patient),
                'full_name'    => $this->shortName($patient),
            ] : [
                'id'              => $patient->id,
                'health_id'       => $patient->health_id,
                'full_name'       => trim("{$patient->first_name} {$patient->last_name}"),
                'display_name'    => trim("{$patient->first_name} {$patient->last_name}"),
                'first_name'      => $patient->first_name,
                'last_name'       => $patient->last_name,
                'date_of_birth'   => $patient->date_of_birth?->toDateString(),
                'dob'             => $patient->date_of_birth?->toDateString(),
                'age'             => $patient->date_of_birth ? (int) $patient->date_of_birth->age : null,
                'sex'             => $patient->sex,
                'blood_group'     => $patient->blood_group,
                'blood_type'      => $patient->blood_group,
                'photo_url'       => PatientPhoto::urlFor($patient),
                'identity_status' => $patient->identity_status ?? 'active',
                'status'          => $patient->identity_status ?? 'active',
            ]) : null,
        ];
    }
}
