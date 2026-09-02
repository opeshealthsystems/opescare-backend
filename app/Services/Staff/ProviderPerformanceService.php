<?php
namespace App\Services\Staff;

use App\Models\Appointment;

class ProviderPerformanceService
{
    public function getMetrics(string $providerId, string $fromDate, string $toDate): array
    {
        $query = Appointment::where('provider_id', $providerId)
            ->whereDate('scheduled_at', '>=', $fromDate)
            ->whereDate('scheduled_at', '<=', $toDate);

        $total     = (clone $query)->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $noShow    = (clone $query)->where('status', 'no_show')->count();
        $cancelled = (clone $query)->where('status', 'cancelled')->count();

        return [
            'provider_id'            => $providerId,
            'from_date'              => $fromDate,
            'to_date'                => $toDate,
            'total_appointments'     => $total,
            'completed_appointments' => $completed,
            'no_show_appointments'   => $noShow,
            'cancelled_appointments' => $cancelled,
            'no_show_rate_pct'       => $total > 0 ? round($noShow / $total * 100, 1) : 0.0,
            'completion_rate_pct'    => $total > 0 ? round($completed / $total * 100, 1) : 0.0,
        ];
    }
}
