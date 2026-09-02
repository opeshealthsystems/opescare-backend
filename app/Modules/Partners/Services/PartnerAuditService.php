<?php

namespace App\Modules\Partners\Services;

use App\Modules\Partners\Models\PartnerAuditLog;
use Illuminate\Support\Facades\Request;

class PartnerAuditService
{
    /**
     * Log a partner governance action immutably.
     */
    public function log(
        int $partnerId,
        string $action,
        ?string $oldValue = null,
        ?string $newValue = null,
        ?string $reason = null,
        ?string $actorId = null,
        ?string $actorRole = null
    ): PartnerAuditLog {
        return PartnerAuditLog::create([
            'partner_id' => $partnerId,
            'actor_id' => $actorId ?? auth()->id(),
            'actor_role' => $actorRole ?? (auth()->check() ? 'admin' : 'system'),
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'reason' => $reason,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
