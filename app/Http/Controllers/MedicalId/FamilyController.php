<?php
namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Http\Middleware\GuardianAccessMiddleware;
use App\Models\Appointment;
use App\Models\FamilyLink;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\User;
use App\Modules\PatientIdentity\Exceptions\DuplicateIdentityFound;
use App\Modules\PatientIdentity\Services\PatientIdentityService;
use App\Services\Identity\HealthIdGeneratorService;
use App\Services\Portal\PortalContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Family links: who may see whose record.
 *
 * Two ways a link comes to exist, and they are deliberately different:
 *
 *   - a CHILD (under config('family.majority_age')) who has no Health ID yet is
 *     REGISTERED by the guardian — a new Patient through PatientIdentityService,
 *     linked at once, because a minor's parent is the one who consents;
 *   - an ADULT, or anyone who already holds a Health ID, is INVITED — the link
 *     stays pending until THAT person accepts from their own account, because
 *     only they can agree to share their record. There is no third path.
 *
 * `relationship` is the guardian's role toward the dependent ("parent" when the
 * dependent is my child, "child" when the dependent is my mother). Access is
 * either `full` or `read_only`; nothing else is written here.
 */
class FamilyController extends Controller
{
    /** The guardian's role toward the dependent. One list for every form. */
    public const RELATIONSHIPS = ['parent', 'child', 'grandparent', 'spouse', 'sibling', 'caregiver', 'legal_guardian', 'other'];

    /** Canonical access levels. Anything else that sits in the column is read as read_only. */
    public const ACCESS_LEVELS = ['full', 'read_only'];

    /**
     * Where "Open record" may land right after the switch. Keys come from the
     * form, values are route NAMES — a URL from the body is never followed.
     */
    private const SWITCH_TARGETS = [
        'dashboard'     => 'portals.patient',
        'appointments'  => 'portals.patient.appointments',
        'prescriptions' => 'portals.patient.prescriptions',
        'immunizations' => 'portals.patient.immunizations',
        'timeline'      => 'portals.patient.timeline',
        'documents'     => 'portals.patient.documents',
        'profile'       => 'portals.patient.profile',
    ];

    public function __construct(private readonly PortalContextService $ctx) {}

    // ── Family index (patient-role users) ───────────────────────────────

    public function index(Request $request)
    {
        // Family pages sit outside `guardian.context`; bind the session here so
        // the "you are viewing …" banner still knows which record is open.
        GuardianAccessMiddleware::bind($request);

        // Links this user manages as guardian (outgoing)
        $links = $this->outgoingLinks();

        // Links where this user's patient record is the dependent AND grace period is active (incoming consent needed)
        $myPatientId = Auth::user()?->patient_id;
        $incomingConsent = $myPatientId
            ? FamilyLink::where('dependent_patient_id', $myPatientId)
                ->where('status', 'active')
                ->whereNotNull('age_transition_expires_at')
                ->where('age_transition_expires_at', '>', now())
                ->with('guardianUser')
                ->get()
            : collect([]);

        // Invitations addressed to this user's Health ID, still open.
        $incomingInvites = $myPatientId ? $this->pendingInvitesFor($myPatientId)->get() : collect([]);

        return view('portals.patient.family.index', [
            'links'           => $links,
            'incomingConsent' => $incomingConsent,
            'incomingInvites' => $incomingInvites,
            'majorityAge'     => (int) config('family.majority_age', 18),
            'graceDays'       => (int) config('family.age_grace_days', 30),
            'warningDays'     => (int) config('family.age_warning_days', 60),
        ]);
    }

    // ── Guardian dashboard ──────────────────────────────────────────────

    /**
     * The landing page for a guardian: every dependent as a tile with the
     * facts a parent looks for first (next appointment, immunisations,
     * identity state), an "Open record" switch, and the two ways to add
     * someone. It also carries the navigation the role needs, so it stands
     * on its own even where the role sidebar has not caught up.
     */
    public function guardianDashboard(Request $request)
    {
        GuardianAccessMiddleware::bind($request);

        $links      = $this->outgoingLinks();
        $patientIds = $links->pluck('dependent_patient_id')->all();

        $nextAppointments = empty($patientIds) ? collect() : Appointment::whereIn('patient_id', $patientIds)
            ->whereIn('status', ['requested', 'scheduled', 'confirmed', 'rescheduled'])
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->with('facility:id,name')
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy('patient_id')
            ->map(fn ($group) => $group->first());

        $immunisationCounts = empty($patientIds) ? collect() : ImmunizationRecord::whereIn('patient_id', $patientIds)
            ->selectRaw('patient_id, COUNT(*) AS shots')
            ->groupBy('patient_id')
            ->pluck('shots', 'patient_id');

        $myPatientId   = Auth::user()?->patient_id;
        $incomingCount = $myPatientId ? $this->pendingInvitesFor($myPatientId)->count() : 0;

        return view('portals.guardian.dashboard', [
            'links'              => $links,
            'nextAppointments'   => $nextAppointments,
            'immunisationCounts' => $immunisationCounts,
            'incomingCount'      => $incomingCount,
            'viewingId'          => session('guardian_viewing_patient_id'),
            'majorityAge'        => (int) config('family.majority_age', 18),
            'graceDays'          => (int) config('family.age_grace_days', 30),
            'warningDays'        => (int) config('family.age_warning_days', 60),
            'switchTargets'      => array_keys(self::SWITCH_TARGETS),
        ]);
    }

    // ── Register a child ────────────────────────────────────────────────

    public function addForm()
    {
        return view('portals.patient.family.add', [
            'relationships' => self::RELATIONSHIPS,
            'majorityAge'   => (int) config('family.majority_age', 18),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'date_of_birth'=> 'required|date|before:today',
            'sex'          => 'required|in:male,female',
            'relationship' => 'required|in:' . implode(',', self::RELATIONSHIPS),
            'access_level' => 'required|in:' . implode(',', self::ACCESS_LEVELS),
        ]);

        // An adult is never registered by someone else. Only they can agree to
        // share their record, so they go through the invitation instead — with
        // the relationship and access level carried over so nothing is retyped.
        $majorityAge = (int) config('family.majority_age', 18);
        if (Carbon::parse($data['date_of_birth'])->age >= $majorityAge) {
            return redirect()->route('portals.patient.family.invite')
                ->withInput(['relationship' => $data['relationship'], 'access_level' => $data['access_level']])
                ->with('error', __('family.age_adult_must_invite', ['age' => $majorityAge]));
        }

        // Someone who already holds a Health ID must not be given a second one
        // as a dependent. The invite flow (sendInvite/acceptInvite) is the path
        // for a person who already exists — it asks THEM, which is the only way
        // one person's record may become visible to another.
        if (Patient::findExistingIdentity($data['first_name'], $data['last_name'], $data['date_of_birth'])) {
            return back()->withInput()->withErrors([
                'first_name' => __('flash.family_member_already_registered'),
            ]);
        }

        $guardian        = Auth::user();
        $guardianPatient = $guardian?->patient;
        $countryCode     = $guardianPatient?->country_code ?? 'CM';

        // The child lives on the same side of the demo partition as the guardian:
        // registered from a demo account it is demo data, from a real account it
        // never is. Left to the IsDemoRecord hook it would take the HOST's mode,
        // which on the demo host made every real child invisible to its parent.
        $isDemo = $guardianPatient !== null
            ? (bool) $guardianPatient->is_demo
            : (bool) ($guardian?->is_demo ?? config('demo.enabled', false));

        $identity = [
            'first_name'      => $data['first_name'],
            'last_name'       => $data['last_name'],
            'date_of_birth'   => $data['date_of_birth'],
            'sex'             => $data['sex'],
            'identity_status' => 'provisional',
            'country_code'    => $countryCode,
        ];

        DB::transaction(function () use ($data, $identity, $countryCode, $isDemo) {
            try {
                $patient = app(PatientIdentityService::class)->createPatientCandidate($identity, Auth::id());
            } catch (DuplicateIdentityFound) {
                // The MPI saw a look-alike the exact-identity check above did not
                // (a namesake, a shared household phone). A child registered by
                // their parent is a new person, not a merge candidate: mint the
                // Health ID directly — the pattern DemoChildrenSeeder uses — and
                // let the nightly MPI scan raise the pair for human review if it
                // still looks alike. Nothing is merged automatically.
                $patient = app(HealthIdGeneratorService::class)->generate(
                    $countryCode,
                    fn (string $healthId) => Patient::create($identity + ['health_id' => $healthId])
                );
            }

            if ((bool) $patient->is_demo !== $isDemo) {
                $patient->forceFill(['is_demo' => $isDemo])->save();
            }

            $link = FamilyLink::create([
                'guardian_user_id'     => Auth::id(),
                'dependent_patient_id' => $patient->id,
                'relationship'         => $data['relationship'],
                'access_level'         => $data['access_level'],
                'status'               => 'active',
                'created_by'           => 'self_registered',
            ]);

            $this->ctx->auditPatientAccess(
                actionType:   'guardian_link_created',
                resourceType: 'FamilyLink',
                resourceId:   $link->id,
                patientId:    $patient->id,
            );
        });

        return redirect()->route('portals.patient.family')
            ->with('success', __('flash.dependent_added'));
    }

    // ── Invite an adult / existing patient ──────────────────────────────

    public function inviteForm()
    {
        return view('portals.patient.family.invite', [
            'relationships' => self::RELATIONSHIPS,
            'majorityAge'   => (int) config('family.majority_age', 18),
        ]);
    }

    public function sendInvite(Request $request)
    {
        $data = $request->validate([
            'health_id_or_email' => 'required|string|max:255',
            'relationship'       => 'required|in:' . implode(',', self::RELATIONSHIPS),
            'access_level'       => 'required|in:' . implode(',', self::ACCESS_LEVELS),
        ]);

        $patient = $this->resolveInviteContact(trim($data['health_id_or_email']));

        if (!$patient) {
            return back()->withInput()->withErrors(['health_id_or_email' => __('family.invite_no_match')]);
        }

        if ($patient->id === Auth::user()?->patient_id) {
            return back()->withInput()->withErrors(['health_id_or_email' => __('flash.family_cannot_link_self')]);
        }

        $existing = FamilyLink::where('guardian_user_id', Auth::id())
            ->where('dependent_patient_id', $patient->id)
            ->whereIn('status', ['active', 'pending_invite'])
            ->exists();

        if ($existing) {
            return back()->withInput()->withErrors(['health_id_or_email' => __('flash.family_link_exists')]);
        }

        $rawToken = Str::random(64);
        $attrs    = [
            'relationship'               => $data['relationship'],
            'access_level'               => $data['access_level'],
            'status'                     => 'pending_invite',
            'created_by'                 => 'guardian_invited',
            'invite_token'               => hash('sha256', $rawToken),
            'invite_expires_at'          => now()->addHours(config('family.invite_ttl_hours', 48)),
            'age_transition_notified_at' => null,
            'age_transition_expires_at'  => null,
        ];

        // A declined or cancelled invitation for the same pair is re-issued on
        // the same row rather than inserted again: the partial unique index on
        // (guardian, dependent) only excludes revoked/expired, so a second
        // INSERT would be a 23505 the person sees as a 500.
        $closed = FamilyLink::where('guardian_user_id', Auth::id())
            ->where('dependent_patient_id', $patient->id)
            ->whereIn('status', ['declined', 'cancelled'])
            ->orderByDesc('updated_at')
            ->first();

        if ($closed) {
            $closed->update($attrs);
            $link = $closed;
        } else {
            $link = FamilyLink::create($attrs + [
                'guardian_user_id'     => Auth::id(),
                'dependent_patient_id' => $patient->id,
            ]);
        }

        // Mail the dependent's own account if one exists. The portal also lists
        // the invitation under "Invitations for me", so a person whose mailbox
        // is not working can still answer once signed in.
        $dependentUser = User::where('patient_id', $patient->id)->first();
        if ($dependentUser && class_exists(\App\Notifications\FamilyInviteNotification::class)) {
            $dependentUser->notify(new \App\Notifications\FamilyInviteNotification($link, $rawToken));
        }

        return redirect()->route('portals.patient.family')
            ->with('success', __('flash.family_invite_sent'));
    }

    /**
     * Exact-match only: a Health ID (aliases followed through merges), the
     * email on the patient record, or the phone number via its keyed hash.
     * No LIKE, no name search — one person cannot enumerate another.
     */
    private function resolveInviteContact(string $search): ?Patient
    {
        if ($search === '') {
            return null;
        }

        $patient = Patient::resolveHealthId($search);

        if (!$patient && str_contains($search, '@')) {
            $patient = Patient::where('email', $search)->first();
        }

        if (!$patient && preg_match('/^\+?[\d\s().-]{6,}$/', $search)) {
            $patient = Patient::findByPhone($search);
        }

        return $patient;
    }

    // ── Invitation accepted by token (mail link) ────────────────────────

    public function acceptInvite(string $token)
    {
        $link = $this->findPendingByToken($token);
        if (!$link) {
            return view('portals.patient.family.invite-accept', [
                'error' => __('flash.family_invite_invalid'),
                'link'  => null,
                'token' => null,
            ]);
        }
        return view('portals.patient.family.invite-accept', [
            'link'  => $link,
            'error' => null,
            'token' => $token,
        ]);
    }

    public function confirmInvite(Request $request, string $token)
    {
        // Must be authenticated to accept a family invite
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', __('flash.family_login_required'));
        }

        $link = $this->findPendingByToken($token);
        if (!$link) {
            return redirect()->route('login')
                ->with('error', __('flash.family_invite_invalid'));
        }

        // Only the dependent patient (the person being linked) may accept
        $userPatientId = Auth::user()->patient_id;
        if (!$userPatientId || $userPatientId !== $link->dependent_patient_id) {
            abort(403, __('family.inv_not_for_you'));
        }

        $this->activate($link);

        return redirect()->route('portals.patient')
            ->with('success', __('flash.guardian_access_granted'));
    }

    // ── Invitations addressed to me (the invited adult, signed in) ──────

    public function invitationsForMe(Request $request)
    {
        GuardianAccessMiddleware::bind($request);

        $myPatientId = Auth::user()?->patient_id;
        $invites     = $myPatientId ? $this->pendingInvitesFor($myPatientId)->get() : collect([]);

        return view('portals.patient.family.invitations', [
            'invites'    => $invites,
            'hasPatient' => $myPatientId !== null,
        ]);
    }

    public function acceptIncoming(string $id)
    {
        $link = $this->myPendingInvite($id);
        if (!$link) {
            return redirect()->route('portals.patient.family.invitations')
                ->with('error', __('family.inv_not_found'));
        }

        $this->activate($link);

        $this->ctx->auditPatientAccess(
            actionType:   'guardian_link_accepted',
            resourceType: 'FamilyLink',
            resourceId:   $link->id,
            patientId:    $link->dependent_patient_id,
        );

        return redirect()->route('portals.patient.family.invitations')
            ->with('success', __('family.inv_accepted', ['name' => $this->guardianName($link)]));
    }

    public function declineIncoming(string $id)
    {
        $link = $this->myPendingInvite($id);
        if (!$link) {
            return redirect()->route('portals.patient.family.invitations')
                ->with('error', __('family.inv_not_found'));
        }

        $link->update([
            'status'            => 'declined',
            'invite_token'      => null,
            'invite_expires_at' => null,
        ]);

        $this->ctx->auditPatientAccess(
            actionType:   'guardian_link_declined',
            resourceType: 'FamilyLink',
            resourceId:   $link->id,
            patientId:    $link->dependent_patient_id,
        );

        return redirect()->route('portals.patient.family.invitations')
            ->with('success', __('family.inv_declined'));
    }

    // ── Edit / revoke a link I own ──────────────────────────────────────

    public function editForm(string $id)
    {
        $link = FamilyLink::where('id', $id)
            ->where('guardian_user_id', Auth::id())
            ->with('dependentPatient')
            ->firstOrFail();

        return view('portals.patient.family.edit', [
            'link'          => $link,
            'relationships' => self::RELATIONSHIPS,
            // A legacy value (view_only / guardian from early mobile builds) is
            // shown as read_only — the level it actually grants — never as full.
            'isLegacyLevel' => !in_array($link->access_level, self::ACCESS_LEVELS, true),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $link = FamilyLink::where('id', $id)
            ->where('guardian_user_id', Auth::id())
            ->firstOrFail();

        $data = $request->validate([
            'relationship' => 'required|in:' . implode(',', self::RELATIONSHIPS),
            'access_level' => 'required|in:' . implode(',', self::ACCESS_LEVELS),
        ]);

        // Normalize checkbox prefs: absent key = unchecked = false.
        // HTML checkboxes don't submit when unchecked, so we must fill in explicit false
        // for all missing event/channel combinations to avoid silently reverting to defaults.
        $allEventKeys = ['lab_result', 'appointment', 'consent_request', 'age_transition'];
        $allChannels  = ['portal', 'email', 'sms'];
        $rawPrefs     = $request->input('notification_prefs', []);

        $normalizedPrefs = [];
        foreach ($allEventKeys as $eventKey) {
            foreach ($allChannels as $channel) {
                $normalizedPrefs[$eventKey][$channel] = (bool) ($rawPrefs[$eventKey][$channel] ?? false);
            }
        }

        $link->update([
            'relationship'       => $data['relationship'],
            'access_level'       => $data['access_level'],
            'notification_prefs' => $normalizedPrefs,
        ]);

        return redirect()->route('portals.patient.family')
            ->with('success', __('flash.family_link_updated'));
    }

    public function revoke(string $id)
    {
        $link = FamilyLink::where('id', $id)->first();
        abort_if(!$link, 404);
        abort_if($link->guardian_user_id !== Auth::id(), 403);

        $link->update(['status' => 'revoked']);

        $this->ctx->auditPatientAccess(
            actionType:   'guardian_link_revoked',
            resourceType: 'FamilyLink',
            resourceId:   $link->id,
            patientId:    $link->dependent_patient_id,
        );

        session()->forget('guardian_viewing_patient_id');

        return redirect()->route('portals.patient.family')
            ->with('success', __('flash.guardian_access_revoked'));
    }

    // ── Switching ───────────────────────────────────────────────────────

    public function switchTo(Request $request, string $patientId)
    {
        $link = FamilyLink::active()
            ->where('guardian_user_id', Auth::id())
            ->where('dependent_patient_id', $patientId)
            ->first();

        abort_if(!$link, 403);

        session(['guardian_viewing_patient_id' => $patientId]);

        $this->ctx->auditPatientAccess(
            actionType:   'guardian_switch_to',
            resourceType: 'FamilyLink',
            resourceId:   $link->id,
            patientId:    $patientId,
        );

        // The dashboard honours the switch, so it is where "Open record" lands;
        // a tile may ask for one specific page instead.
        $target = self::SWITCH_TARGETS[(string) $request->input('next', 'dashboard')] ?? 'portals.patient';

        return redirect()->route($target);
    }

    public function switchBack()
    {
        session()->forget('guardian_viewing_patient_id');
        return redirect()->route('portals.patient');
    }

    // ── Age transition: the dependent decides ───────────────────────────

    public function guardianConsentApprove(string $id)
    {
        // Dependent approves continued guardian access after age transition
        $myPatientId = Auth::user()?->patient_id;
        $link = FamilyLink::where('id', $id)
            ->where('dependent_patient_id', $myPatientId)
            ->firstOrFail();

        $link->update(['age_transition_expires_at' => null]);

        return redirect()->route('portals.patient')
            ->with('success', __('flash.guardian_access_regranted'));
    }

    public function guardianConsentDeny(string $id)
    {
        $myPatientId = Auth::user()?->patient_id;
        $link = FamilyLink::where('id', $id)
            ->where('dependent_patient_id', $myPatientId)
            ->firstOrFail();

        $link->update(['status' => 'revoked']);

        return redirect()->route('portals.patient')
            ->with('success', __('flash.guardian_access_removed'));
    }

    // ── Helpers ─────────────────────────────────────────────────────────

    private function outgoingLinks()
    {
        return FamilyLink::where('guardian_user_id', Auth::id())
            ->whereIn('status', ['active', 'pending_invite'])
            ->with('dependentPatient')
            ->orderByDesc('created_at')
            ->get();
    }

    /** Open invitations addressed to one patient's Health ID. */
    private function pendingInvitesFor(string $patientId)
    {
        return FamilyLink::pendingInvite()
            ->where('dependent_patient_id', $patientId)
            ->where(fn ($q) => $q->whereNull('invite_expires_at')->orWhere('invite_expires_at', '>', now()))
            ->with('guardianUser')
            ->orderByDesc('created_at');
    }

    private function myPendingInvite(string $id): ?FamilyLink
    {
        $myPatientId = Auth::user()?->patient_id;
        if (!$myPatientId) {
            return null;
        }
        return $this->pendingInvitesFor($myPatientId)->where('id', $id)->first();
    }

    /** The one transition from pending to active: the dependent said yes. */
    private function activate(FamilyLink $link): void
    {
        $link->update([
            'status'            => 'active',
            'created_by'        => 'invite_accepted',
            'invite_token'      => null,
            'invite_expires_at' => null,
        ]);
    }

    private function guardianName(FamilyLink $link): string
    {
        return $link->guardianUser?->name ?? $link->guardianUser?->email ?? __('public.portal.unknown_guardian');
    }

    private function findPendingByToken(string $rawToken): ?FamilyLink
    {
        $hashed = hash('sha256', $rawToken);
        return FamilyLink::where('invite_token', $hashed)
            ->where('status', 'pending_invite')
            ->where('invite_expires_at', '>', now())
            ->with('dependentPatient', 'guardianUser')
            ->first();
    }
}
