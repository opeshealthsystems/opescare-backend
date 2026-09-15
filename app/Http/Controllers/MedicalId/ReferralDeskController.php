<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Patient;
use App\Models\ReferralCase;
use App\Modules\Governance\Services\AccessLogService;
use App\Services\Portal\DeskFacilityContext;
use App\Services\Referral\ReferralDeskService;
use App\Support\ClinicalRelationshipGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The web referral desk (C-3 / HC-3) — refer a patient out, and receive,
 * accept, reject and complete referrals addressed to this facility.
 *
 * NOTHING about the lifecycle is decided here. The legal moves, which party
 * makes each one, the consent decision on send, grant revocation and the
 * patient's notification all live in App\Services\Referral\ReferralDeskService,
 * which the clinician app's MobileDoctorReferralController calls too.
 *
 *   clinician  ← the session user
 *   facility   ← DeskFacilityContext, and nothing else (never the request)
 *
 * A referral this facility is neither sending nor receiving answers 404.
 * A patient is addressed by EXACT Health ID only; there is no patient search.
 * Raising one requires the same full-record basis the mobile path requires
 * (ClinicalRelationshipGate); a patient with no basis reads exactly like a
 * Health ID nobody holds, and the refusal is access-logged.
 */
class ReferralDeskController extends Controller
{
    private const PER_PAGE = 25;

    /** Bounded list offered as suggestions for the receiving facility name. */
    private const FACILITY_SUGGESTIONS = 1000;

    public function __construct(private readonly ReferralDeskService $referrals)
    {
    }

    // ── Reading ───────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();

        $tab = $request->query('tab') === 'received' ? 'received' : 'sent';

        $status = (string) $request->query('status', '');
        if (! array_key_exists($status, ReferralDeskService::TRANSITIONS)) {
            $status = '';
        }

        $referrals = ReferralCase::query()
            ->when($tab === 'sent', fn ($q) => $q->where('referring_facility_id', $facilityId))
            ->when($tab === 'received', fn ($q) => $q->where('receiving_facility_id', $facilityId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->with(['referringFacility:id,name', 'receivingFacility:id,name', 'patient:id,health_id'])
            ->orderByDesc('created_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('portals.referrals.index', [
            'tab'       => $tab,
            'status'    => $status,
            'statuses'  => array_keys(ReferralDeskService::TRANSITIONS),
            'referrals' => $referrals,
            'service'   => $this->referrals,
        ]);
    }

    public function show(string $id): View
    {
        $facilityId = $this->facilityId();

        $referral = $this->referrals->findForFacility($id, $facilityId);

        if (! $referral) {
            abort(404);
        }

        if (! $this->audit($referral->patient_id, 'read', $referral->id, $facilityId)) {
            abort(503, __('referral_desk.audit_unavailable'));
        }

        $referral->load(['referringFacility:id,name', 'receivingFacility:id,name', 'patient']);

        return view('portals.referrals.show', [
            'referral'  => $referral,
            'status'    => $this->referrals->currentStatus($referral),
            'direction' => $referral->referring_facility_id === $facilityId ? 'sent' : 'received',
            'moves'     => $this->referrals->movesFor($referral, $facilityId),
        ]);
    }

    public function create(): View
    {
        $this->facilityId();

        return view('portals.referrals.create', $this->formData());
    }

    // ── Writing ───────────────────────────────────────────────────────────

    public function store(Request $request): RedirectResponse|View
    {
        $facilityId  = $this->facilityId();
        $clinicianId = (string) auth()->id();

        $validated = $request->validate([
            'health_id'               => ['required', 'string', 'max:64'],
            'receiving_facility_name' => ['nullable', 'string', 'max:255'],
            'receiving_specialty'     => ['nullable', 'string', 'max:120'],
            'receiving_provider_name' => ['nullable', 'string', 'max:200'],
            'urgency'                 => ['required', Rule::in(ReferralDeskService::URGENCIES)],
            'reason'                  => ['required', 'string', 'max:2000'],
            'clinical_summary'        => ['nullable', 'string', 'max:8000'],
            'included_record_types'   => ['nullable', 'array', 'max:' . count(ReferralDeskService::RECORD_TYPES)],
            'included_record_types.*' => ['string', Rule::in(ReferralDeskService::RECORD_TYPES)],
        ]);

        // EXACT Health ID — never a name, never a partial match.
        $patient = Patient::resolveHealthId($validated['health_id']);

        if ($patient !== null) {
            $verdict = ClinicalRelationshipGate::evaluate($clinicianId, $facilityId, $patient->id);

            if (! $verdict->holds() || ! $verdict->coversFullRecord()) {
                $this->auditDenial($patient->id, $clinicianId, $facilityId);

                // Preview-only access is told what it needs; no basis at all
                // reads like a Health ID nobody holds.
                $message = $verdict->holds() ? __('referral_desk.consent_required') : __('referral_desk.patient_not_found');

                return back()->withInput()->withErrors(['health_id' => $message]);
            }
        } else {
            return back()->withInput()->withErrors(['health_id' => __('referral_desk.patient_not_found')]);
        }

        $facilityName = trim((string) ($validated['receiving_facility_name'] ?? ''));

        if ($facilityName === ''
            && blank($validated['receiving_specialty'] ?? null)
            && blank($validated['receiving_provider_name'] ?? null)) {
            return back()->withInput()->withErrors(['receiving_facility_name' => __('referral_desk.destination_required')]);
        }

        $receivingFacilityId = null;

        if ($facilityName !== '') {
            // Exact name among registered facilities (case-insensitive
            // equality — no LIKE). Two facilities sharing a name are refused
            // rather than guessed between.
            $matches = Facility::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($facilityName)])
                ->limit(2)
                ->pluck('id');

            if ($matches->isEmpty()) {
                return back()->withInput()->withErrors(['receiving_facility_name' => __('referral_desk.receiving_facility_not_found')]);
            }

            if ($matches->count() > 1) {
                return back()->withInput()->withErrors(['receiving_facility_name' => __('referral_desk.receiving_facility_ambiguous')]);
            }

            $receivingFacilityId = (string) $matches->first();

            if ($receivingFacilityId === $facilityId) {
                return back()->withInput()->withErrors(['receiving_facility_name' => __('referral_desk.self_referral')]);
            }
        }

        if (! $this->audit($patient->id, 'create', null, $facilityId)) {
            abort(503, __('referral_desk.audit_unavailable'));
        }

        $referral = $this->referrals->createDraft($patient, $facilityId, $clinicianId, $receivingFacilityId, [
            'receiving_specialty'     => $validated['receiving_specialty'] ?? null,
            'receiving_provider_name' => $validated['receiving_provider_name'] ?? null,
            'urgency'                 => $validated['urgency'],
            'reason'                  => $validated['reason'],
            'clinical_summary'        => $validated['clinical_summary'] ?? null,
            'included_record_types'   => $validated['included_record_types'] ?? [],
        ]);

        return redirect()->route('portals.referrals.show', $referral->id)
            ->with('success', __('referral_desk.created'));
    }

    /** send | accept | reject | complete | cancel */
    public function transition(Request $request, string $id, string $action): RedirectResponse
    {
        $facilityId  = $this->facilityId();
        $clinicianId = (string) auth()->id();

        $target = match ($action) {
            'send'     => 'sent',
            'accept'   => 'accepted',
            'reject'   => 'rejected',
            'complete' => 'completed',
            'cancel'   => 'cancelled',
            default    => abort(404),
        };

        $referral = $this->referrals->findForFacility($id, $facilityId);

        if (! $referral) {
            abort(404);
        }

        $validated = $request->validate(match ($action) {
            'send'           => ['expires_in_days' => ['nullable', 'integer', 'min:1', 'max:' . ReferralDeskService::MAX_EXPIRY_DAYS]],
            'reject', 'cancel' => ['reason' => ['required', 'string', 'max:2000']],
            'complete'       => ['feedback' => ['nullable', 'string', 'max:8000']],
            default          => [],
        });

        $refusal = $this->referrals->refusal($referral, $facilityId, $target, ReferralDeskService::SIDES[$target]);

        if ($refusal !== null && $refusal['error'] === 'WRONG_PARTY') {
            abort(403, __('referral_desk.wrong_party'));
        }

        if ($refusal !== null) {
            return redirect()->route('portals.referrals.show', $referral->id)
                ->with('error', __('referral_desk.illegal_transition'));
        }

        if (! $this->audit($referral->patient_id, 'update', $referral->id, $facilityId)) {
            abort(503, __('referral_desk.audit_unavailable'));
        }

        $flash = __('referral_desk.moved.' . $target);

        match ($action) {
            'send'     => $consent = $this->referrals->send($referral, isset($validated['expires_in_days']) ? (int) $validated['expires_in_days'] : null, $clinicianId),
            'accept'   => $this->referrals->accept($referral, $clinicianId),
            'reject'   => $this->referrals->reject($referral, $validated['reason']),
            'complete' => $this->referrals->complete($referral, $validated['feedback'] ?? null),
            'cancel'   => $this->referrals->cancel($referral, $validated['reason']),
        };

        $redirect = redirect()->route('portals.referrals.show', $referral->id)->with('success', $flash);

        // The clinician is TOLD whether records travel, never left to assume.
        if ($action === 'send') {
            $redirect->with('record_access', ($consent['record_types'] ?? []) !== []
                ? __('referral_desk.records_shared')
                : __('referral_desk.records_not_shared'));
        }

        return $redirect;
    }

    // ── internals ─────────────────────────────────────────────────────────

    /** @return array<string,mixed> */
    private function formData(): array
    {
        return [
            'urgencies'   => ReferralDeskService::URGENCIES,
            'recordTypes' => ReferralDeskService::RECORD_TYPES,
            // Facility names are directory data, not patient data.
            'facilityNames' => Facility::query()
                ->orderBy('name')
                ->limit(self::FACILITY_SUGGESTIONS)
                ->pluck('name')
                ->unique()
                ->values(),
        ];
    }

    private function facilityId(): string
    {
        $facilityId = app(DeskFacilityContext::class)->resolve();

        if (! $facilityId) {
            abort(403, __('referral_desk.no_facility_context'));
        }

        return (string) $facilityId;
    }

    /** Access-logged exactly as the clinician app logs it. False → 503. */
    private function audit(string $patientId, string $accessType, ?string $resourceId, string $facilityId): bool
    {
        $clinicianId = auth()->id();

        if (! $clinicianId) {
            return false;
        }

        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: (string) $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: 'treatment:referral',
                dataCategory: 'referral',
                resourceType: 'ReferralCase',
                resourceId: $resourceId,
                accessType: $accessType,
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Web referral action refused: access log could not be written.', [
                'patient_id'  => $patientId,
                'access_type' => $accessType,
                'error'       => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function auditDenial(string $patientId, string $clinicianId, string $facilityId): void
    {
        try {
            AccessLogService::log(
                patientId: $patientId,
                actorId: $clinicianId,
                actorType: 'provider',
                organizationId: null,
                facilityId: $facilityId,
                purpose: ClinicalRelationshipGate::NO_RELATIONSHIP_PURPOSE,
                dataCategory: 'referral',
                resourceType: 'ReferralCase',
                resourceId: null,
                accessType: 'denied',
            );
        } catch (\Throwable $e) {
            Log::warning('Web referral denial was not access-logged.', ['patient_id' => $patientId, 'error' => $e->getMessage()]);
        }
    }
}
