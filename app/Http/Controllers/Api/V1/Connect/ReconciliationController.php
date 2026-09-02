<?php

namespace App\Http\Controllers\Api\V1\Connect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\OpesCareErrorCode;
use App\Models\ReconciliationCase;
use App\Services\AuditLogger;

/**
 * ReconciliationController
 *
 * FIX H-1 (audit 2026-06-07): listCases() previously returned ReconciliationCase::all()
 * with no facility scope — every authenticated client could enumerate every pending case
 * from every facility (cross-facility IDOR, OWASP API1 / ISO 27001 A.9.1).
 *
 * Both listCases() and resolveCase() now scope exclusively to the requesting
 * client's facility_id, resolved from bearer-token middleware attributes.
 */
class ReconciliationController extends Controller
{
    public function listCases(Request $request)
    {
        $facilityId = $request->attributes->get('facility_id');
        $clientId   = $request->attributes->get('integration_client_id', 'unknown_client');

        if (empty($facilityId)) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::AUTHENTICATION_FAILED->value,
                'message'    => __('api.facility_unresolved_bearer'),
            ], 403);
        }

        AuditLogger::log(
            $request,
            'reconciliation_cases_listed',
            'reconciliation_case',
            null
        );

        // [FIX H-1] Scope to the requesting facility — never return all cases.
        $cases = ReconciliationCase::where('facility_id', $facilityId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'cases' => $cases->map(fn ($c) => [
                'case_id'            => $c->id,
                'status'             => $c->status,
                'mismatch_reason'    => $c->mismatch_reason,
                'external_reference' => $c->external_reference,
                // [FIX] MD5 replaced with SHA-256 for payload integrity fingerprint
                'submitted_payload_hash' => hash('sha256', json_encode($c->submitted_payload)),
                'created_at'         => $c->created_at?->toIso8601String(),
            ]),
        ], 200);
    }

    public function resolveCase(Request $request, $caseId)
    {
        $facilityId = $request->attributes->get('facility_id');
        $clientId   = $request->attributes->get('integration_client_id', 'unknown_client');

        if (empty($facilityId)) {
            return response()->json([
                'status'     => 'rejected',
                'error_code' => OpesCareErrorCode::AUTHENTICATION_FAILED->value,
                'message'    => __('api.facility_unresolved_bearer'),
            ], 403);
        }

        $request->validate([
            'resolution'          => 'required|in:match,no_match,manual_override',
            'confirmed_health_id' => 'required|string|max:64',
        ]);

        $resolution        = $request->input('resolution');
        $confirmedHealthId = $request->input('confirmed_health_id');

        // [FIX H-1] Scope to facility — a client may only resolve its own cases.
        $case = ReconciliationCase::where('id', $caseId)
            ->where('facility_id', $facilityId)
            ->first();

        if (! $case) {
            return response()->json([
                'status'     => 'not_found',
                'error_code' => OpesCareErrorCode::RESOURCE_NOT_FOUND->value,
                'message'    => __('api.reconciliation_case_not_found'),
            ], 404);
        }

        $case->update([
            'status'      => 'resolved',
            'resolved_at' => now(),
        ]);

        AuditLogger::log(
            $request,
            'reconciliation_resolved',
            'reconciliation_case',
            $caseId,
            null,
            false,
            null,
            [
                'case_id'              => $caseId,
                'resolution'           => $resolution,
                'confirmed_health_id'  => $confirmedHealthId,
                'resolved_by_client'   => $clientId,
                'facility_id'          => $facilityId,
            ]
        );

        return response()->json([
            'status'             => 'resolved',
            'case_id'            => $caseId,
            'resolved_at'        => now()->toIso8601String(),
            'attached_health_id' => $confirmedHealthId,
            'message'            => __('api.reconciliation_resolved'),
        ], 200);
    }
}
