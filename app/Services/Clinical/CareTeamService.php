<?php
namespace App\Services\Clinical;

use App\Models\CareTeamMember;
use App\Models\HandoffNote;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class CareTeamService
{
    public function addMember(
        string $patientId,
        string $providerId,
        string $role,
        ?string $visitId = null
    ): CareTeamMember {
        $isPrimary = false;
        if ($role === 'attending') {
            $existingAttending = CareTeamMember::where('patient_id', $patientId)
                ->where('role', 'attending')
                ->whereNull('left_at')
                ->exists();
            $isPrimary = !$existingAttending;
        }

        return CareTeamMember::create([
            'patient_id'  => $patientId,
            'visit_id'    => $visitId,
            'provider_id' => $providerId,
            'role'        => $role,
            'is_primary'  => $isPrimary,
            'joined_at'   => now(),
        ]);
    }

    public function removeMember(string $memberId): void
    {
        $member = CareTeamMember::findOrFail($memberId);
        $member->update(['left_at' => now()]);
    }

    public function getCareTeam(string $patientId): Collection
    {
        return CareTeamMember::where('patient_id', $patientId)
            ->whereNull('left_at')
            ->with('provider')
            ->orderByRaw("CASE role WHEN 'attending' THEN 0 WHEN 'consulting' THEN 1 ELSE 2 END")
            ->get();
    }

    public function createHandoff(array $data): HandoffNote
    {
        return HandoffNote::create($data);
    }

    public function getHandoffsForProvider(string $providerId, Carbon $since): Collection
    {
        return HandoffNote::where('to_provider_id', $providerId)
            ->where('handed_off_at', '>=', $since)
            ->with(['fromProvider', 'facility'])
            ->orderByDesc('handed_off_at')
            ->get();
    }
}
