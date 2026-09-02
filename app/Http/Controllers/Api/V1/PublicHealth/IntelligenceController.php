<?php

namespace App\Http\Controllers\Api\V1\PublicHealth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\PublicHealthSignal;
use App\Models\SignalReview;
use App\Models\PublicHealthBaseline;
use App\Modules\PublicHealth\Services\SignalDetectionService;
use App\Http\Resources\PublicHealthSignalResource;

class IntelligenceController extends Controller
{
    public function getSignals(Request $request)
    {
        $status = $request->query('status');
        $query = PublicHealthSignal::with('reviews');
        if ($status) {
            $query->where('status', $status);
        }
        return response()->json(PublicHealthSignalResource::collection($query->get()));
    }

    public function getSignal($id)
    {
        $signal = PublicHealthSignal::with(['reviews', 'facility'])->find($id);
        if (!$signal) {
            return response()->json(['error' => __('api.signal_not_found')], 404);
        }
        return response()->json(PublicHealthSignalResource::make($signal));
    }

    public function triggerDetection(Request $request)
    {
        // [IDOR FIX] facility_id from middleware only — never from request body
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['error' => 'FACILITY_UNRESOLVABLE', 'message' => __('api.facility_unresolved_bearer_token')], 403);
        }

        $validated = $request->validate([
            'indicator_code' => 'required|string|max:100',
        ]);

        $service = app(SignalDetectionService::class);
        $signal = $service->detectSignals(
            $facilityId,
            $validated['indicator_code']
        );

        if ($signal) {
            return response()->json([
                'status'  => 'signal_detected',
                'message' => __('api.spike_alert_triggered'),
                'signal'  => PublicHealthSignalResource::make($signal)
            ], 201);
        }

        return response()->json([
            'status'  => 'normal',
            'message' => __('api.no_abnormal_spikes')
        ], 200);
    }

    public function reviewSignal($id, Request $request)
    {
        $request->validate([
            'action'  => 'required|string|in:confirm,dismiss,escalate,resolve',
            'comment' => 'nullable|string|max:2000',
        ]);

        $signal = PublicHealthSignal::find($id);
        if (!$signal) {
            return response()->json(['error' => __('api.signal_not_found')], 404);
        }

        // Resolve operator identity from middleware-set attributes only
        $operatorId = null;
        if ($uid = $request->user()?->id) {
            $operatorId = $uid;
        } elseif (($clientId = $request->attributes->get('integration_client_id')) && Str::isUuid($clientId)) {
            $operatorId = $clientId;
        } elseif (($providerId = $request->attributes->get('provider_id')) && Str::isUuid($providerId)) {
            $operatorId = $providerId;
        }

        if (!$operatorId) {
            return response()->json(['error' => 'ACTOR_UNRESOLVABLE', 'message' => __('api.actor_unresolved_request')], 403);
        }

        $action  = $request->input('action');
        $comment = $request->input('comment', 'Reviewed.');

        // Map action to signal status
        $statusMap = [
            'confirm'  => 'confirmed',
            'dismiss'  => 'dismissed',
            'escalate' => 'escalated',
            'resolve'  => 'resolved',
        ];

        $signal->status = $statusMap[$action];
        if ($action === 'resolve') {
            $signal->resolved_at = now();
        }
        $signal->reviewed_at = now();
        $signal->save();

        SignalReview::create([
            'signal_id'   => $signal->id,
            'reviewer_id' => $operatorId,
            'action'      => $action,
            'comment'     => $comment,
            'reviewed_at' => now()
        ]);

        return response()->json([
            'status'  => $signal->status,
            'message' => __('api.signal_review_registered')
        ]);
    }

    public function getIntelligenceDashboard()
    {
        return response()->json([
            'active_signals'      => PublicHealthSignal::whereIn('status', ['new_signal', 'under_review'])->count(),
            'confirmed_outbreaks' => PublicHealthSignal::where('status', 'confirmed')->count(),
            'dismissed_signals'   => PublicHealthSignal::where('status', 'dismissed')->count()
        ]);
    }

    public function getTrends()
    {
        // Positivity trend summaries
        $signals = PublicHealthSignal::where('signal_type', 'lab_positivity_spike')->get();
        return response()->json(PublicHealthSignalResource::collection($signals));
    }

    public function getShortages()
    {
        // Medicine and blood shortage cluster summaries
        $shortages = PublicHealthSignal::whereIn('signal_type', [
            'medicine_stock_out_cluster',
            'blood_shortage_cluster'
        ])->get();
        return response()->json(PublicHealthSignalResource::collection($shortages));
    }
}
