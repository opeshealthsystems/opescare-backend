<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\FamilyLink;
use App\Models\Patient;
use App\Models\User;
use App\Services\Identity\HealthIdGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MobileFamilyController extends Controller
{
    /**
     * Resolve the guardian User from the request's patient_id attribute.
     * Returns null if no User account is linked to the patient.
     */
    private function guardianUser(Request $request): ?User
    {
        $patientId = $request->attributes->get('patient_id');
        return User::where('patient_id', $patientId)->first();
    }

    /**
     * GET /api/mobile/family
     * Returns active + pending_invite family links managed by the authenticated guardian.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $this->guardianUser($request);
        if (!$user) {
            return response()->json(['data' => []]);
        }

        $links = FamilyLink::where('guardian_user_id', $user->id)
            ->whereIn('status', ['active', 'pending_invite'])
            ->with('dependentPatient')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (FamilyLink $link) => $this->formatLink($link));

        return response()->json(['data' => $links]);
    }

    /**
     * POST /api/mobile/family
     * Register a new dependent (creates a Patient + FamilyLink).
     */
    public function store(Request $request): JsonResponse
    {
        $user = $this->guardianUser($request);
        if (!$user) {
            return response()->json(['message' => __('api.no_user_linked')], 403);
        }

        $data = $request->validate([
            'full_name'     => 'required|string|max:200',
            'date_of_birth' => 'required|date|before:today',
            'sex'           => 'required|in:male,female',
            'relationship'  => 'required|in:parent,grandparent,spouse,sibling,caregiver,legal_guardian,child,other',
            'blood_group'   => 'nullable|string|max:20',
            'phone'         => 'nullable|string|max:30',
        ]);

        $nameParts = explode(' ', trim($data['full_name']), 2);
        $firstName = $nameParts[0];
        $lastName  = $nameParts[1] ?? '';

        $gen         = new HealthIdGeneratorService();
        $countryCode = $user->patient?->country_code ?? 'CM';

        $link = DB::transaction(function () use ($data, $firstName, $lastName, $gen, $countryCode, $user) {
            $patient = Patient::create([
                'health_id'       => $gen->generate($countryCode),
                'first_name'      => $firstName,
                'last_name'       => $lastName,
                'date_of_birth'   => $data['date_of_birth'],
                'sex'             => $data['sex'],
                'identity_status' => 'provisional',
                'is_demo'         => false,
            ]);

            return FamilyLink::create([
                'guardian_user_id'     => $user->id,
                'dependent_patient_id' => $patient->id,
                'relationship'         => $data['relationship'],
                'access_level'         => 'full',
                'status'               => 'active',
                'created_by'           => 'self_registered',
            ]);
        });

        $link->load('dependentPatient');

        return response()->json([
            'message' => __('api.family_member_added'),
            'data'    => $this->formatLink($link),
        ], 201);
    }

    /**
     * GET /api/mobile/family/invitations
     * Returns pending invites sent by the authenticated guardian.
     */
    public function invitations(Request $request): JsonResponse
    {
        $user = $this->guardianUser($request);
        if (!$user) {
            return response()->json(['data' => []]);
        }

        $links = FamilyLink::where('guardian_user_id', $user->id)
            ->where('status', 'pending_invite')
            ->with('dependentPatient')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (FamilyLink $link) => [
                'id'           => $link->id,
                'contact'      => $link->dependentPatient?->email
                                  ?? $link->dependentPatient?->health_id
                                  ?? 'Unknown contact',
                'relationship' => $link->relationship,
                'method'       => 'invite',
                'sent_at'      => $link->created_at?->toDateString(),
                'expires_at'   => $link->invite_expires_at?->toDateString() ?? 'N/A',
            ]);

        return response()->json(['data' => $links]);
    }

    /**
     * POST /api/mobile/family/invitations
     * Send an invite to an existing OpesCare patient by Health ID, email, or phone.
     */
    public function sendInvitation(Request $request): JsonResponse
    {
        $user = $this->guardianUser($request);
        if (!$user) {
            return response()->json(['message' => __('api.no_user_linked')], 403);
        }

        $data = $request->validate([
            'contact'      => 'required|string|max:255',
            'method'       => 'nullable|in:phone,email,qr',
            'relationship' => 'required|string|max:50',
            'access_level' => 'nullable|in:view_only,guardian,full',
        ]);

        $search  = $data['contact'];
        $patient = Patient::where('is_demo', false)
            ->where(function ($q) use ($search) {
                $q->where('health_id', $search)
                  ->orWhere('email', $search)
                  ->orWhere('phone', $search);
            })
            ->first();

        if (!$patient) {
            return response()->json([
                'message' => __('api.patient_not_found_contact'),
            ], 404);
        }

        if ($patient->id === $user->patient_id) {
            return response()->json(['message' => __('api.cannot_link_self')], 422);
        }

        $existing = FamilyLink::where('guardian_user_id', $user->id)
            ->where('dependent_patient_id', $patient->id)
            ->whereIn('status', ['active', 'pending_invite'])
            ->exists();

        if ($existing) {
            return response()->json(['message' => __('api.family_link_exists')], 409);
        }

        $rawToken = Str::random(64);
        $link = FamilyLink::create([
            'guardian_user_id'     => $user->id,
            'dependent_patient_id' => $patient->id,
            'relationship'         => $data['relationship'],
            'access_level'         => $data['access_level'] ?? 'view_only',
            'status'               => 'pending_invite',
            'created_by'           => 'guardian_invited',
            'invite_token'         => hash('sha256', $rawToken),
            'invite_expires_at'    => Carbon::now()->addHours(48),
        ]);

        $dependentUser = User::where('patient_id', $patient->id)->first();
        if ($dependentUser && class_exists(\App\Notifications\FamilyInviteNotification::class)) {
            $dependentUser->notify(new \App\Notifications\FamilyInviteNotification($link, $rawToken));
        }

        return response()->json([
            'message' => __('api.invitation_sent'),
            'data'    => [
                'id'           => $link->id,
                'contact'      => $search,
                'relationship' => $link->relationship,
                'sent_at'      => $link->created_at?->toDateString(),
                'expires_at'   => $link->invite_expires_at?->toDateString(),
            ],
        ], 201);
    }

    /**
     * DELETE /api/mobile/family/invitations/{id}
     * Cancel a pending invitation.
     */
    public function cancelInvitation(Request $request, string $id): JsonResponse
    {
        $user = $this->guardianUser($request);
        if (!$user) {
            return response()->json(['message' => __('api.unauthorized')], 403);
        }

        $link = FamilyLink::where('guardian_user_id', $user->id)
            ->where('id', $id)
            ->where('status', 'pending_invite')
            ->firstOrFail();

        $link->update(['status' => 'cancelled']);

        return response()->json(['message' => __('api.invitation_cancelled')]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function formatLink(FamilyLink $link): array
    {
        $patient = $link->dependentPatient;
        return [
            'id'           => $link->id,
            'relationship' => $link->relationship,
            'access_level' => $link->access_level,
            'status'       => $link->status,
            'is_pending'   => $link->status === 'pending_invite',
            'patient'      => $patient ? [
                'id'            => $patient->id,
                'health_id'     => $patient->health_id,
                'full_name'     => trim("{$patient->first_name} {$patient->last_name}"),
                'date_of_birth' => $patient->date_of_birth?->toDateString(),
                'age'           => $patient->date_of_birth
                                     ? (int) Carbon::parse($patient->date_of_birth)->age
                                     : null,
            ] : null,
        ];
    }
}
