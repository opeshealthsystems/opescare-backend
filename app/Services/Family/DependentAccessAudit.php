<?php

namespace App\Services\Family;

use App\Models\FamilyLink;
use App\Modules\Governance\Services\AccessLogService;
use Illuminate\Support\Facades\Log;

/**
 * Every look a guardian takes at a dependent's record lands in the
 * dependent's access log, attributed to the guardian (actor = the person
 * acting, never the dependent). Best-effort: a logging failure is reported,
 * not surfaced to the caller — the read itself was authorised by the link.
 */
final class DependentAccessAudit
{
    public const PURPOSE    = 'guardian_access';
    public const ACTOR_TYPE = 'guardian';

    public static function record(
        FamilyLink $link,
        string $guardianUserId,
        string $dataCategory,
        string $resourceType,
        ?string $resourceId = null,
        string $accessType = 'read',
    ): void {
        try {
            AccessLogService::log(
                patientId: $link->dependent_patient_id,
                actorId: $guardianUserId,
                actorType: self::ACTOR_TYPE,
                organizationId: null,
                facilityId: null,
                purpose: self::PURPOSE,
                dataCategory: $dataCategory,
                resourceType: $resourceType,
                resourceId: $resourceId,
                accessType: $accessType,
            );
        } catch (\Throwable $e) {
            Log::warning('Guardian access to a dependent was not access-logged.', [
                'family_link_id' => $link->id,
                'patient_id'     => $link->dependent_patient_id,
                'actor_id'       => $guardianUserId,
                'data_category'  => $dataCategory,
                'error'          => $e->getMessage(),
            ]);
        }
    }
}
