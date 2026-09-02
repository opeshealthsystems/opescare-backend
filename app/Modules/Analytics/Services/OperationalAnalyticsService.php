<?php

namespace App\Modules\Analytics\Services;

use App\Models\Visit;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\PharmacyInventory;
use App\Models\BloodInventory;
use App\Models\LeaveRequest;
use App\Models\StaffProfile;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OperationalAnalyticsService
{
    // ── Visit Analytics ───────────────────────────────────────

    public function visitSummary(string $facilityId, Carbon $from, Carbon $to): array
    {
        $visits = Visit::where('facility_id', $facilityId)
            ->whereBetween('started_at', [$from, $to])
            ->get();

        $completed = $visits->where('status', 'completed');
        $avgDuration = $completed->filter(fn($v) => $v->ended_at && $v->started_at)
            ->map(fn($v) => Carbon::parse($v->started_at)->diffInMinutes(Carbon::parse($v->ended_at)))
            ->avg();

        return [
            'total'         => $visits->count(),
            'completed'     => $completed->count(),
            'cancelled'     => $visits->where('status', 'cancelled')->count(),
            'active'        => $visits->whereNotIn('status', ['completed', 'cancelled', 'abandoned'])->count(),
            'avg_duration_min' => $avgDuration ? round($avgDuration) : null,
            'by_type'       => $visits->groupBy('visit_type')
                ->map(fn($g) => $g->count())
                ->sortDesc()
                ->toArray(),
        ];
    }

    public function visitTrend(string $facilityId, Carbon $from, Carbon $to): array
    {
        return Visit::where('facility_id', $facilityId)
            ->whereBetween('started_at', [$from, $to])
            ->selectRaw('DATE(started_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day')
            ->map(fn($r) => (int) $r->total)
            ->toArray();
    }

    // ── Appointment Analytics ─────────────────────────────────

    public function appointmentSummary(string $facilityId, Carbon $from, Carbon $to): array
    {
        $appts = Appointment::where('facility_id', $facilityId)
            ->whereBetween('scheduled_at', [$from, $to])
            ->get();

        return [
            'total'        => $appts->count(),
            'confirmed'    => $appts->where('status', 'confirmed')->count(),
            'completed'    => $appts->where('status', 'completed')->count(),
            'cancelled'    => $appts->where('status', 'cancelled')->count(),
            'no_show'      => $appts->where('status', 'no_show')->count(),
            'show_rate'    => $appts->count() > 0
                ? round($appts->whereIn('status', ['completed', 'confirmed'])->count() / $appts->count() * 100, 1)
                : null,
            'by_type'      => $appts->groupBy('appointment_type')
                ->map(fn($g) => $g->count())
                ->sortDesc()
                ->toArray(),
        ];
    }

    // ── Queue Analytics ───────────────────────────────────────

    public function queueSummary(string $facilityId, Carbon $from, Carbon $to): array
    {
        $tickets = \App\Models\QueueTicket::where('facility_id', $facilityId)
            ->whereBetween('checked_in_at', [$from, $to])
            ->get();

        $served = $tickets->filter(fn ($t) => $t->checked_in_at && $t->service_started_at);
        $avgWait = $served
            ->map(fn ($t) => Carbon::parse($t->checked_in_at)->diffInMinutes(Carbon::parse($t->service_started_at)))
            ->avg();

        return [
            'total'            => $tickets->count(),
            'completed'        => $tickets->where('status', 'completed')->count(),
            'cancelled'        => $tickets->where('status', 'cancelled')->count(),
            'waiting'          => $tickets->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'avg_wait_min'     => $avgWait !== null ? round($avgWait) : null,
            'by_queue'         => $tickets->groupBy('current_queue')->map(fn ($g) => $g->count())->sortDesc()->toArray(),
            'by_priority'      => $tickets->groupBy('priority_level')->map(fn ($g) => $g->count())->toArray(),
        ];
    }

    // ── Revenue Analytics ─────────────────────────────────────

    public function revenueSummary(string $facilityId, Carbon $from, Carbon $to): array
    {
        $invoices = Invoice::where('facility_id', $facilityId)
            ->whereBetween('issued_at', [$from, $to])
            ->get();

        $paid     = $invoices->whereIn('status', ['paid', 'partially_paid']);
        $overdue  = $invoices->where('status', 'overdue');

        return [
            'total_invoiced'      => round($invoices->sum('subtotal_amount'), 2),
            'total_collected'     => round($invoices->sum('paid_amount'), 2),
            'total_outstanding'   => round($invoices->sum('balance_amount'), 2),
            'insurance_covered'   => round($invoices->sum('insurance_covered_amount'), 2),
            'invoice_count'       => $invoices->count(),
            'paid_count'          => $paid->count(),
            'overdue_count'       => $overdue->count(),
            'collection_rate'     => $invoices->sum('subtotal_amount') > 0
                ? round($invoices->sum('paid_amount') / $invoices->sum('subtotal_amount') * 100, 1)
                : null,
        ];
    }

    public function revenueTrend(string $facilityId, Carbon $from, Carbon $to): array
    {
        return Invoice::where('facility_id', $facilityId)
            ->whereBetween('issued_at', [$from, $to])
            ->selectRaw('DATE(issued_at) as day, SUM(paid_amount) as collected, SUM(subtotal_amount) as invoiced')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day')
            ->map(fn($r) => ['collected' => (float) $r->collected, 'invoiced' => (float) $r->invoiced])
            ->toArray();
    }

    // ── Patient Analytics ─────────────────────────────────────

    public function patientSummary(string $facilityId, Carbon $from, Carbon $to): array
    {
        $newPatients = Patient::where('facility_id', $facilityId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $totalPatients = Patient::where('facility_id', $facilityId)->count();

        $returningVisitPatients = Visit::where('facility_id', $facilityId)
            ->whereBetween('started_at', [$from, $to])
            ->distinct('patient_id')
            ->count('patient_id');

        return [
            'total_registered' => $totalPatients,
            'new_in_period'    => $newPatients,
            'active_in_period' => $returningVisitPatients,
        ];
    }

    // ── Staff Analytics ───────────────────────────────────────

    public function staffSummary(string $facilityId): array
    {
        $staff = StaffProfile::where('facility_id', $facilityId)->get();
        $leaves = LeaveRequest::whereHas('staffProfile', fn($q) => $q->where('facility_id', $facilityId))->get();

        return [
            'total'          => $staff->count(),
            'active'         => $staff->where('status', 'active')->count(),
            'on_leave'       => $staff->where('status', 'on_leave')->count(),
            'inactive'       => $staff->whereIn('status', ['inactive', 'suspended', 'terminated'])->count(),
            'by_category'    => $staff->groupBy('staff_category')
                ->map(fn($g) => $g->count())
                ->toArray(),
            'pending_leaves' => $leaves->where('status', 'pending')->count(),
        ];
    }

    // ── Inventory Analytics ───────────────────────────────────

    public function inventorySummary(string $facilityId): array
    {
        $pharma = PharmacyInventory::where('facility_id', $facilityId)->get();
        $blood  = BloodInventory::where('facility_id', $facilityId)->get();

        return [
            'pharma_total'      => $pharma->count(),
            'pharma_low'        => $pharma->where('stock_status', 'low_stock')->count(),
            'pharma_out'        => $pharma->where('stock_status', 'out_of_stock')->count(),
            'pharma_expired'    => $pharma->where('is_expired', true)->count(),
            'blood_total_units' => $blood->sum('available_units'),
            'blood_groups'      => $blood->where('available_units', '>', 0)->pluck('blood_group')->unique()->count(),
        ];
    }

    // ── Composite Dashboard ───────────────────────────────────

    public function dashboardSnapshot(string $facilityId, string $period = '30d'): array
    {
        [$from, $to] = $this->parsePeriod($period);

        return [
            'period'       => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'label' => $period],
            'visits'       => $this->visitSummary($facilityId, $from, $to),
            'appointments' => $this->appointmentSummary($facilityId, $from, $to),
            'revenue'      => $this->revenueSummary($facilityId, $from, $to),
            'patients'     => $this->patientSummary($facilityId, $from, $to),
            'staff'        => $this->staffSummary($facilityId),
            'inventory'    => $this->inventorySummary($facilityId),
            'visit_trend'  => $this->visitTrend($facilityId, $from, $to),
            'revenue_trend'=> $this->revenueTrend($facilityId, $from, $to),
        ];
    }

    private function parsePeriod(string $period): array
    {
        $to   = Carbon::today()->endOfDay();
        $from = match ($period) {
            '7d'   => Carbon::today()->subDays(6)->startOfDay(),
            '30d'  => Carbon::today()->subDays(29)->startOfDay(),
            '90d'  => Carbon::today()->subDays(89)->startOfDay(),
            '1y'   => Carbon::today()->subYear()->startOfDay(),
            default => Carbon::today()->subDays(29)->startOfDay(),
        };
        return [$from, $to];
    }
}
