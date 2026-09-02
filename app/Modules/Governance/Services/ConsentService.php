<?php

namespace App\Modules\Governance\Services;

use App\Models\ConsentRequest;
use App\Models\ConsentGrant;
use App\Models\ConsentRevocation;
use App\Modules\Notifications\Services\NotificationService;
use Carbon\Carbon;

class ConsentService
{
    public function __construct(private NotificationService $notificationService) {}

    public function requestConsent(
        string $patientId,
        string $facilityId,
        ?string $userId,
        string $purpose,
        array $scopes,
        int $durationMinutes
    ): ConsentRequest {
        $request = new ConsentRequest();
        $request->patient_id = $patientId;
        $request->requesting_facility_id = $facilityId;
        $request->requesting_user_id = $userId;
        $request->purpose = $purpose;
        $request->requested_scope = $scopes;
        $request->duration_minutes = $durationMinutes;
        $request->status = 'pending_patient_approval';
        $request->save();

        // Notify patient that a consent request is pending their approval (non-fatal)
        try {
            $this->notificationService->sendNotification(
                $patientId,
                'consent.request.pending',
                [
                    'facility_name' => 'A facility',
                    'purpose'       => $purpose,
                ],
                'urgent',
                'account_and_security'
            );
        } catch (\Throwable) {
            // Never block consent request creation due to notification failure
        }

        return $request;
    }

    public function approveConsent(string $requestId, string $userId): ConsentGrant
    {
        $request = ConsentRequest::findOrFail($requestId);
        $request->status = 'approved';
        $request->save();

        $grant = new ConsentGrant();
        $grant->consent_request_id = $request->id;
        $grant->patient_id = $request->patient_id;
        $grant->facility_id = $request->requesting_facility_id;
        $grant->authorizing_actor = 'patient';
        $grant->scope = $request->requested_scope;
        $grant->status = 'active';
        $grant->expires_at = Carbon::now()->addMinutes($request->duration_minutes);
        $grant->save();

        return $grant;
    }

    public function denyConsent(string $requestId): ConsentRequest
    {
        $request = ConsentRequest::findOrFail($requestId);
        $request->status = 'denied';
        $request->save();

        return $request;
    }

    public function revokeConsent(string $grantId, string $userId): ConsentGrant
    {
        $grant = ConsentGrant::findOrFail($grantId);
        $grant->status = 'revoked';
        $grant->save();

        ConsentRevocation::create([
            'consent_grant_id' => $grant->id,
            'revoked_by' => $userId,
            'reason' => 'Patient revoked via mobile app'
        ]);

        if ($grant->consent_request_id) {
            $request = ConsentRequest::find($grant->consent_request_id);
            if ($request && $request->status === 'approved') {
                $request->status = 'expired';
                $request->save();
            }
        }

        return $grant;
    }

    public function verifyAccess(
        string $patientId,
        string $facilityId,
        ?string $userId,
        string $scope,
        string $purpose
    ): bool {
        $now = Carbon::now();

        $grants = ConsentGrant::where('consent_grants.patient_id', $patientId)
            ->where('consent_grants.facility_id', $facilityId)   // scope to requesting facility
            ->where('consent_grants.status', 'active')
            ->where('consent_grants.expires_at', '>=', $now)
            ->join('consent_requests', 'consent_grants.consent_request_id', '=', 'consent_requests.id')
            ->where('consent_requests.purpose', $purpose)
            ->select('consent_grants.*')
            ->get();

        foreach ($grants as $grant) {
            $scopes = $grant->scope ?? [];
            if (in_array($scope, $scopes) || in_array('*', $scopes)) {
                return true;
            }
        }

        return false;
    }
}
