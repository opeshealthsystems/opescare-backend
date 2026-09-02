<?php
namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\FamilyLink;
use App\Models\Patient;
use App\Services\Identity\HealthIdGeneratorService;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FamilyController extends Controller
{
    public function __construct(private readonly PortalContextService $ctx) {}

    public function index()
    {
        // Links this user manages as guardian (outgoing)
        $links = FamilyLink::where('guardian_user_id', Auth::id())
            ->whereIn('status', ['active', 'pending_invite'])
            ->with('dependentPatient')
            ->orderByDesc('created_at')
            ->get();

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

        return view('portals.patient.family.index', compact('links', 'incomingConsent'));
    }

    public function addForm()
    {
        return view('portals.patient.family.add');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'   => 'required|string|max:100',
            'last_name'    => 'required|string|max:100',
            'date_of_birth'=> 'required|date|before:today',
            'sex'          => 'required|in:male,female',
            'relationship' => 'required|in:parent,grandparent,spouse,sibling,caregiver,legal_guardian,other',
            'access_level' => 'required|in:full,read_only',
        ]);

        $gen         = new HealthIdGeneratorService();
        $countryCode = Auth::user()?->patient?->country_code ?? 'CM';

        DB::transaction(function () use ($data, $gen, $countryCode) {
            $healthId = $gen->generate($countryCode);

            $patient = Patient::create([
                'health_id'       => $healthId,
                'first_name'      => $data['first_name'],
                'last_name'       => $data['last_name'],
                'date_of_birth'   => $data['date_of_birth'],
                'sex'             => $data['sex'],
                'identity_status' => 'provisional',
                'is_demo'         => false,
            ]);

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

    public function inviteForm()
    {
        return view('portals.patient.family.invite');
    }

    public function sendInvite(Request $request)
    {
        $data = $request->validate([
            'health_id_or_email' => 'required|string|max:255',
            'relationship'       => 'required|in:parent,grandparent,spouse,sibling,caregiver,legal_guardian,other',
            'access_level'       => 'required|in:full,read_only',
        ]);

        $search  = $data['health_id_or_email'];
        $patient = Patient::where('is_demo', false)
            ->where(function ($q) use ($search) {
                $q->where('health_id', $search)
                  ->orWhere('email', $search);
            })
            ->first();

        if (!$patient) {
            return back()->withErrors(['health_id_or_email' => __('flash.family_no_patient_found')]);
        }

        if ($patient->id === Auth::user()?->patient_id) {
            return back()->withErrors(['health_id_or_email' => __('flash.family_cannot_link_self')]);
        }

        $existing = FamilyLink::where('guardian_user_id', Auth::id())
            ->where('dependent_patient_id', $patient->id)
            ->whereIn('status', ['active', 'pending_invite'])
            ->exists();

        if ($existing) {
            return back()->withErrors(['health_id_or_email' => __('flash.family_link_exists')]);
        }

        $rawToken = Str::random(64);
        $link = FamilyLink::create([
            'guardian_user_id'     => Auth::id(),
            'dependent_patient_id' => $patient->id,
            'relationship'         => $data['relationship'],
            'access_level'         => $data['access_level'],
            'status'               => 'pending_invite',
            'created_by'           => 'guardian_invited',
            'invite_token'         => hash('sha256', $rawToken),
            'invite_expires_at'    => now()->addHours(config('family.invite_ttl_hours', 48)),
        ]);

        // Notify dependent's user account if one exists
        $dependentUser = \App\Models\User::where('patient_id', $patient->id)->first();
        if ($dependentUser && class_exists(\App\Notifications\FamilyInviteNotification::class)) {
            $dependentUser->notify(new \App\Notifications\FamilyInviteNotification($link, $rawToken));
        }

        return redirect()->route('portals.patient.family')
            ->with('success', __('flash.family_invite_sent'));
    }

    public function acceptInvite(string $token)
    {
        $link = $this->findPendingByToken($token);
        if (!$link) {
            return view('portals.patient.family.invite-accept', [
                'error' => 'This invite link is invalid or has expired.',
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
            abort(403, 'You are not the patient this invite was sent to.');
        }

        $link->update([
            'status'            => 'active',
            'created_by'        => 'invite_accepted',
            'invite_token'      => null,
            'invite_expires_at' => null,
        ]);

        return redirect()->route('portals.patient')
            ->with('success', __('flash.guardian_access_granted'));
    }

    public function editForm(string $id)
    {
        $link = FamilyLink::where('id', $id)
            ->where('guardian_user_id', Auth::id())
            ->with('dependentPatient')
            ->firstOrFail();

        return view('portals.patient.family.edit', compact('link'));
    }

    public function update(Request $request, string $id)
    {
        $link = FamilyLink::where('id', $id)
            ->where('guardian_user_id', Auth::id())
            ->firstOrFail();

        $data = $request->validate([
            'relationship' => 'required|in:parent,grandparent,spouse,sibling,caregiver,legal_guardian,other',
            'access_level' => 'required|in:full,read_only',
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

    public function switchTo(string $patientId)
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

        return redirect()->route('portals.patient.appointments');
    }

    public function switchBack()
    {
        session()->forget('guardian_viewing_patient_id');
        return redirect()->route('portals.patient');
    }

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
