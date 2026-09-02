<?php

namespace App\Modules\Messaging\Services;

use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\MessageThreadParticipant;

class MessagePermissionService
{
    public function canCreateThread(string $userId, string $userRole, array $details): bool
    {
        $threadType = $details['thread_type'] ?? '';

        // 1. Patient to provider messaging: requires an active care relationship
        if ($threadType === 'patient_provider') {
            if ($userRole === 'patient') {
                // Must specify a doctor they have an active care context with
                if (empty($details['doctor_id']) || empty($details['context_id'])) {
                    return false;
                }
            }
            if ($userRole === 'doctor') {
                // Doctor cannot message random patient without active encounter/triage context
                if (empty($details['patient_id']) || empty($details['context_id'])) {
                    return false;
                }
            }
        }

        // 2. Hospital-to-hospital: requires clinical referral, transfer or blood request context
        if ($threadType === 'hospital_hospital') {
            $allowedContexts = ['referral', 'transfer', 'blood_request'];
            if (!in_array($details['context_type'] ?? '', $allowedContexts)) {
                return false;
            }
        }

        // 3. Insurance to Facility: requires active claim or preauth context
        if ($threadType === 'insurance_facility') {
            $allowedContexts = ['claim', 'preauth'];
            if (!in_array($details['context_type'] ?? '', $allowedContexts)) {
                return false;
            }
        }

        // 4. Public Health: protect patient identity by default
        if ($threadType === 'public_health') {
            if (isset($details['expose_patient_id']) && $details['expose_patient_id'] === true) {
                return false; // Identity cannot be exposed by default
            }
        }

        return true;
    }

    public function canViewThread(string $userId, string $threadUuid): bool
    {
        $thread = MessageThread::where('uuid', $threadUuid)->first();
        if (!$thread) {
            return false;
        }

        // If thread has legal hold, only compliance officers / legal counsel may view it.
        // Any other user is denied — legal hold must never be a pass-all gate.
        if ($thread->legal_hold) {
            $user = \App\Models\User::find($userId);
            $complianceRoles = ['compliance_officer', 'legal_counsel', 'platform_admin', 'super_admin'];
            return $user && in_array($user->role?->name ?? '', $complianceRoles);
        }

        return MessageThreadParticipant::where('thread_id', $thread->id)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->exists();
    }
}
