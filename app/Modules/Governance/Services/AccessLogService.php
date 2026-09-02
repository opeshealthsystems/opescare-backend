<?php

namespace App\Modules\Governance\Services;

use App\Models\AccessLog;
use Illuminate\Support\Facades\Request;

class AccessLogService
{
    public static function log(
        ?string $patientId,
        string $actorId,
        string $actorType,
        ?string $organizationId,
        ?string $facilityId,
        string $purpose,
        string $dataCategory,
        string $resourceType,
        ?string $resourceId,
        string $accessType,
        bool $emergencyAccess = false
    ): AccessLog {
        $log = new AccessLog();
        $log->patient_id = $patientId;
        $log->actor_id = $actorId;
        $log->actor_type = $actorType;
        $log->organization_id = $organizationId;
        $log->facility_id = $facilityId;
        $log->purpose = $purpose;
        $log->data_category = $dataCategory;
        $log->resource_type = $resourceType;
        $log->resource_id = $resourceId;
        $log->access_type = $accessType;
        $log->emergency_access = $emergencyAccess;
        $log->ip_address = Request::ip();
        $log->user_agent = Request::userAgent();
        $log->save();

        return $log;
    }
}
