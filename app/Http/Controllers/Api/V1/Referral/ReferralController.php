<?php

namespace App\Http\Controllers\Api\V1\Referral;

use App\Http\Controllers\Controller;
use App\Models\ReferralCase;
use App\Modules\Referral\Services\ReferralService;
use App\Services\Documents\DocumentIssuanceService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $query = ReferralCase::query()->with(['patient', 'referringFacility', 'receivingFacility']);

        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->query('patient_id'));
        }
        if ($request->filled('referring_facility_id')) {
            $query->where('referring_facility_id', $request->query('referring_facility_id'));
        }
        if ($request->filled('receiving_facility_id')) {
            $query->where('receiving_facility_id', $request->query('receiving_facility_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        return response()->json(['data' => $query->orderByDesc('created_at')->get()->map(fn ($r) => $this->serialize($r))]);
    }

    public function show(ReferralCase $referral)
    {
        return response()->json(['data' => $this->serialize($referral->load(['patient', 'referringFacility', 'receivingFacility', 'accessGrants']))]);
    }

    public function store(Request $request, ReferralService $service)
    {
        $validated = $request->validate([
            'patient_id'              => ['required', 'uuid'],
            'referring_facility_id'   => ['required', 'uuid'],
            'referring_provider_id'   => ['nullable', 'uuid'],
            'receiving_facility_id'   => ['nullable', 'uuid'],
            'receiving_specialty'     => ['nullable', 'string', 'max:120'],
            'receiving_provider_name' => ['nullable', 'string', 'max:200'],
            'urgency'                 => ['nullable', 'in:routine,urgent,emergency'],
            'reason'                  => ['required', 'string'],
            'clinical_summary'        => ['nullable', 'string'],
            'included_record_types'   => ['nullable', 'array'],
            'included_record_types.*' => ['string'],
            'consent_grant_id'        => ['nullable', 'uuid'],
            'expires_at'              => ['nullable', 'date', 'after:now'],
            'created_by_id'           => ['nullable', 'uuid'],
        ]);

        $referral = $service->create($validated);

        return response()->json(['data' => $this->serialize($referral)], 201);
    }

    public function send(Request $request, ReferralCase $referral, ReferralService $service, DocumentIssuanceService $issuance)
    {
        $facilityId = $request->attributes->get('facility_id');
        $validated  = $request->validate(['actor_id' => ['nullable', 'uuid']]);

        $result = $service->send($referral, $validated['actor_id'] ?? null);

        if ($facilityId) {
            try {
                $issuance->issueFromModel(
                    'REF',
                    'Referral Letter',
                    ['referral_id' => $referral->id, 'patient_id' => $referral->patient_id, 'urgency' => $referral->urgency, 'reason' => $referral->reason, 'receiving_specialty' => $referral->receiving_specialty, 'receiving_facility_id' => $referral->receiving_facility_id],
                    $facilityId,
                    $referral->patient_id,
                    null,
                    $validated['actor_id'] ?? null
                );
            } catch (\Throwable) {}
        }

        return response()->json(['data' => $this->serialize($result)]);
    }

    public function accept(Request $request, ReferralCase $referral, ReferralService $service, DocumentIssuanceService $issuance)
    {
        $facilityId = $request->attributes->get('facility_id');
        $validated  = $request->validate(['accepted_by_id' => ['required', 'uuid']]);

        $result = $service->accept($referral, $validated['accepted_by_id']);

        if ($facilityId) {
            try {
                $issuance->issueFromModel(
                    'RAL',
                    'Referral Acknowledgement',
                    ['referral_id' => $referral->id, 'patient_id' => $referral->patient_id, 'accepted_by_id' => $validated['accepted_by_id'], 'accepted_at' => now()->toISOString()],
                    $facilityId,
                    $referral->patient_id,
                    null,
                    $validated['accepted_by_id']
                );
            } catch (\Throwable) {}
        }

        return response()->json(['data' => $this->serialize($result)]);
    }

    public function reject(Request $request, ReferralCase $referral, ReferralService $service)
    {
        $validated = $request->validate(['reason' => ['required', 'string']]);

        return response()->json(['data' => $this->serialize($service->reject($referral, $validated['reason']))]);
    }

    public function complete(Request $request, ReferralCase $referral, ReferralService $service)
    {
        $validated = $request->validate(['feedback' => ['nullable', 'string']]);

        return response()->json(['data' => $this->serialize($service->complete($referral, $validated['feedback'] ?? null))]);
    }

    public function cancel(Request $request, ReferralCase $referral, ReferralService $service)
    {
        $validated = $request->validate(['reason' => ['required', 'string']]);

        return response()->json(['data' => $this->serialize($service->cancel($referral, $validated['reason']))]);
    }

    public function expireStale(ReferralService $service)
    {
        $count = $service->expireStale();

        return response()->json(['status' => 'ok', 'expired' => $count]);
    }

    private function serialize(ReferralCase $referral): array
    {
        return [
            'id'                      => $referral->id,
            'patient_id'              => $referral->patient_id,
            'referring_facility_id'   => $referral->referring_facility_id,
            'receiving_facility_id'   => $referral->receiving_facility_id,
            'receiving_specialty'     => $referral->receiving_specialty,
            'urgency'                 => $referral->urgency,
            'status'                  => $referral->status,
            'reason'                  => $referral->reason,
            'expires_at'              => $referral->expires_at?->toISOString(),
            'accepted_at'             => $referral->accepted_at?->toISOString(),
            'rejected_at'             => $referral->rejected_at?->toISOString(),
            'completed_at'            => $referral->completed_at?->toISOString(),
            'cancelled_at'            => $referral->cancelled_at?->toISOString(),
            'is_expired'              => $referral->isExpired(),
            'created_at'              => $referral->created_at?->toISOString(),
        ];
    }
}
