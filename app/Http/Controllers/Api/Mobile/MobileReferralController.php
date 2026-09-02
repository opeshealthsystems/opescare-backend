<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ReferralCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileReferralController extends Controller
{
    /**
     * List the authenticated patient's referrals.
     * GET /api/mobile/referrals
     */
    public function index(Request $request): JsonResponse
    {
        $patientId = $request->attributes->get('patient_id');

        $referrals = ReferralCase::where('patient_id', $patientId)
            ->with([
                'referringFacility:id,name',
                'receivingFacility:id,name',
            ])
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id'                  => $r->id,
                'status'              => $r->status,
                'reason'              => $r->reason,
                'notes'               => $r->clinical_summary ?? null,
                'urgency'             => $r->urgency ?? 'routine',
                'referring_facility'  => $r->referringFacility?->name ?? 'Unknown',
                'receiving_facility'  => $r->receivingFacility?->name ?? 'Unknown',
                'referred_at'         => $r->created_at?->toIso8601String(),
                'accepted_at'         => $r->accepted_at?->toIso8601String(),
                'completed_at'        => $r->completed_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $referrals]);
    }
}
