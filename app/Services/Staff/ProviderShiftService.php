<?php
namespace App\Services\Staff;

use App\Models\ProviderShift;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ProviderShiftService
{
    public function scheduleShift(array $data): ProviderShift
    {
        $data['is_confirmed'] = $data['is_confirmed'] ?? false;
        return ProviderShift::create($data);
    }

    public function getWeeklySchedule(string $facilityId, Carbon $weekStart): Collection
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        return ProviderShift::where('facility_id', $facilityId)
            ->whereBetween('shift_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->with('provider')
            ->orderBy('shift_date')
            ->orderBy('start_time')
            ->get();
    }

    public function getProviderSchedule(string $providerId, Carbon $from, Carbon $to): Collection
    {
        return ProviderShift::where('provider_id', $providerId)
            ->whereBetween('shift_date', [$from->toDateString(), $to->toDateString()])
            ->with('facility')
            ->orderBy('shift_date')
            ->orderBy('start_time')
            ->get();
    }

    public function requestSwap(string $shiftId, string $targetProviderId): ProviderShift
    {
        $shift = ProviderShift::findOrFail($shiftId);
        $shift->update(['swap_requested_with' => $targetProviderId]);
        return $shift->fresh();
    }

    public function confirmSwap(string $shiftId): ProviderShift
    {
        return DB::transaction(function () use ($shiftId) {
            $shift = ProviderShift::findOrFail($shiftId);

            if ($shift->swap_requested_with === null) {
                throw new \RuntimeException("No swap request pending for shift {$shiftId}.");
            }

            $shift->update([
                'provider_id'         => $shift->swap_requested_with,
                'swap_requested_with' => null,
                'is_confirmed'        => true,
            ]);

            return $shift->fresh();
        });
    }
}
