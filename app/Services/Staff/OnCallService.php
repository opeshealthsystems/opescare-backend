<?php
namespace App\Services\Staff;

use App\Models\OnCallSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class OnCallService
{
    public function schedule(array $data): OnCallSchedule
    {
        $data['is_confirmed'] = $data['is_confirmed'] ?? false;
        return OnCallSchedule::create($data);
    }

    public function getOnCallProviders(string $facilityId, Carbon $datetime, ?string $specialty = null): Collection
    {
        $dateStr = $datetime->toDateString();
        $timeStr = $datetime->format('H:i:s');

        $query = OnCallSchedule::where('facility_id', $facilityId)
            ->whereDate('on_call_date', $dateStr)
            ->where('start_time', '<=', $timeStr)
            ->where('end_time', '>=', $timeStr)
            ->with(['provider', 'backupProvider']);

        if ($specialty !== null) {
            $query->where('specialty', $specialty);
        }

        return $query->get();
    }

    public function getCurrentOnCall(string $facilityId): Collection
    {
        return $this->getOnCallProviders($facilityId, Carbon::now());
    }

    public function getMonthlyRoster(string $facilityId, int $year, int $month): Collection
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
        $end   = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        return OnCallSchedule::where('facility_id', $facilityId)
            ->whereDate('on_call_date', '>=', $start)
            ->whereDate('on_call_date', '<=', $end)
            ->with(['provider', 'backupProvider'])
            ->orderBy('on_call_date')
            ->orderBy('start_time')
            ->get();
    }
}
