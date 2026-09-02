<?php

namespace App\Modules\Analytics\Services;

use App\Models\KpiExport;
use App\Models\MetricDefinition;
use App\Models\MetricSnapshot;
use App\Models\ProductEvent;
use App\Models\Patient;
use App\Models\Visit;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\LabOrder;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * ProductAnalyticsService
 *
 * Computes KPI metric snapshots and records product events.
 * All methods are side-effect-free (read-only) unless they write snapshots.
 *
 * Event recording is done via ProductEvent::record() — callers can
 * invoke that directly or use the convenience method here.
 */
class ProductAnalyticsService
{
    // ── Core KPI Metric Slugs ─────────────────────────────────────────────────

    public const METRIC_DAILY_ACTIVE_PATIENTS    = 'daily_active_patients';
    public const METRIC_DAILY_VISITS             = 'daily_visits';
    public const METRIC_VISIT_COMPLETION_RATE    = 'visit_completion_rate';
    public const METRIC_AVG_VISIT_DURATION       = 'avg_visit_duration_min';
    public const METRIC_LAB_TURNAROUND           = 'lab_turnaround_avg_min';
    public const METRIC_PRESCRIPTION_DISPENSE_RATE = 'prescription_dispense_rate';
    public const METRIC_APPOINTMENT_NO_SHOW_RATE = 'appointment_no_show_rate';
    public const METRIC_DAILY_INVOICES           = 'daily_invoices';
    public const METRIC_REVENUE_DAILY            = 'revenue_daily';
    public const METRIC_PATIENT_REGISTRATIONS    = 'patient_registrations';

    // ── Seeding Default Metric Definitions ────────────────────────────────────

    /**
     * Ensure all core metric definitions exist in the database.
     * Safe to call multiple times — uses updateOrCreate.
     */
    public function seedCoreMetrics(): void
    {
        $definitions = [
            [
                'slug'           => self::METRIC_DAILY_ACTIVE_PATIENTS,
                'name'           => 'Daily Active Patients',
                'description'    => 'Number of unique patients with a visit or appointment on a given day',
                'category'       => 'volume',
                'unit'           => 'count',
                'aggregation'    => 'count',
                'granularity'    => 'daily',
                'scope'          => 'facility',
                'display_format' => 'number',
            ],
            [
                'slug'           => self::METRIC_DAILY_VISITS,
                'name'           => 'Daily Visits',
                'description'    => 'Total visits opened on a given day',
                'category'       => 'volume',
                'unit'           => 'count',
                'aggregation'    => 'count',
                'granularity'    => 'daily',
                'scope'          => 'facility',
                'display_format' => 'number',
            ],
            [
                'slug'           => self::METRIC_VISIT_COMPLETION_RATE,
                'name'           => 'Visit Completion Rate',
                'description'    => 'Percentage of visits completed (not cancelled or abandoned)',
                'category'       => 'quality',
                'unit'           => 'percentage',
                'aggregation'    => 'rate',
                'granularity'    => 'daily',
                'scope'          => 'facility',
                'display_format' => 'percentage',
                'target_value'   => 85.0,
                'warning_threshold' => 70.0,
                'critical_threshold' => 50.0,
            ],
            [
                'slug'           => self::METRIC_AVG_VISIT_DURATION,
                'name'           => 'Average Visit Duration',
                'description'    => 'Average minutes from visit start to completion',
                'category'       => 'efficiency',
                'unit'           => 'minutes',
                'aggregation'    => 'avg',
                'granularity'    => 'daily',
                'scope'          => 'facility',
                'display_format' => 'duration',
            ],
            [
                'slug'           => self::METRIC_LAB_TURNAROUND,
                'name'           => 'Lab Turnaround Time',
                'description'    => 'Average minutes from lab order creation to result release',
                'category'       => 'efficiency',
                'unit'           => 'minutes',
                'aggregation'    => 'avg',
                'granularity'    => 'daily',
                'scope'          => 'facility',
                'display_format' => 'duration',
                'warning_threshold' => 120.0,
                'critical_threshold' => 240.0,
            ],
            [
                'slug'           => self::METRIC_PRESCRIPTION_DISPENSE_RATE,
                'name'           => 'Prescription Dispense Rate',
                'description'    => 'Percentage of prescriptions fully dispensed same day',
                'category'       => 'quality',
                'unit'           => 'percentage',
                'aggregation'    => 'rate',
                'granularity'    => 'daily',
                'scope'          => 'facility',
                'display_format' => 'percentage',
                'target_value'   => 90.0,
            ],
            [
                'slug'           => self::METRIC_APPOINTMENT_NO_SHOW_RATE,
                'name'           => 'Appointment No-Show Rate',
                'description'    => 'Percentage of booked appointments that result in no-show',
                'category'       => 'quality',
                'unit'           => 'percentage',
                'aggregation'    => 'rate',
                'granularity'    => 'daily',
                'scope'          => 'facility',
                'display_format' => 'percentage',
                'warning_threshold' => 15.0,
                'critical_threshold' => 30.0,
            ],
            [
                'slug'           => self::METRIC_DAILY_INVOICES,
                'name'           => 'Daily Invoices',
                'description'    => 'Number of invoices issued on a given day',
                'category'       => 'financial',
                'unit'           => 'count',
                'aggregation'    => 'count',
                'granularity'    => 'daily',
                'scope'          => 'facility',
                'display_format' => 'number',
            ],
            [
                'slug'           => self::METRIC_REVENUE_DAILY,
                'name'           => 'Daily Revenue',
                'description'    => 'Total payments received on a given day',
                'category'       => 'financial',
                'unit'           => 'currency',
                'aggregation'    => 'sum',
                'granularity'    => 'daily',
                'scope'          => 'facility',
                'display_format' => 'currency',
            ],
            [
                'slug'           => self::METRIC_PATIENT_REGISTRATIONS,
                'name'           => 'Patient Registrations',
                'description'    => 'New patients registered on a given day',
                'category'       => 'volume',
                'unit'           => 'count',
                'aggregation'    => 'count',
                'granularity'    => 'daily',
                'scope'          => 'facility',
                'display_format' => 'number',
            ],
        ];

        foreach ($definitions as $def) {
            MetricDefinition::updateOrCreate(['slug' => $def['slug']], array_merge($def, ['is_active' => true]));
        }
    }

    // ── Snapshot Computation ──────────────────────────────────────────────────

    /**
     * Compute and store daily snapshots for a facility for a given date.
     * Idempotent — re-computing the same date overwrites the existing snapshot.
     */
    public function computeDailySnapshots(?string $facilityId, Carbon $date): void
    {
        // Platform-wide (no facility selected): compute for every facility.
        if ($facilityId === null) {
            \App\Models\Facility::query()->pluck('id')->each(function ($id) use ($date) {
                $this->computeDailySnapshots((string) $id, $date->copy());
            });

            return;
        }

        $from = $date->copy()->startOfDay();
        $to   = $date->copy()->endOfDay();

        $metrics = MetricDefinition::active()->get()->keyBy('slug');

        $computations = [
            self::METRIC_DAILY_ACTIVE_PATIENTS    => fn() => $this->computeDailyActivePatients($facilityId, $from, $to),
            self::METRIC_DAILY_VISITS             => fn() => $this->computeDailyVisits($facilityId, $from, $to),
            self::METRIC_VISIT_COMPLETION_RATE    => fn() => $this->computeVisitCompletionRate($facilityId, $from, $to),
            self::METRIC_AVG_VISIT_DURATION       => fn() => $this->computeAvgVisitDuration($facilityId, $from, $to),
            self::METRIC_LAB_TURNAROUND           => fn() => $this->computeLabTurnaround($facilityId, $from, $to),
            self::METRIC_PRESCRIPTION_DISPENSE_RATE => fn() => $this->computePrescriptionDispenseRate($facilityId, $from, $to),
            self::METRIC_APPOINTMENT_NO_SHOW_RATE => fn() => $this->computeAppointmentNoShowRate($facilityId, $from, $to),
            self::METRIC_DAILY_INVOICES           => fn() => $this->computeDailyInvoices($facilityId, $from, $to),
            self::METRIC_PATIENT_REGISTRATIONS    => fn() => $this->computePatientRegistrations($facilityId, $from, $to),
        ];

        foreach ($computations as $slug => $computeFn) {
            $definition = $metrics->get($slug);
            if (!$definition) {
                continue;
            }

            $value = $computeFn();

            // Determine status relative to thresholds
            $status = $this->computeStatus($definition, $value);

            // Fetch previous period for change %
            $previous = MetricSnapshot::where('metric_definition_id', $definition->id)
                ->where('facility_id', $facilityId)
                ->where('period_date', $date->copy()->subDay()->toDateString())
                ->where('period_granularity', 'daily')
                ->value('value');

            $changePct = ($previous !== null && $previous != 0)
                ? round((($value - $previous) / $previous) * 100, 2)
                : null;

            MetricSnapshot::updateOrCreate(
                [
                    'metric_definition_id' => $definition->id,
                    'facility_id'          => $facilityId,
                    'period_date'          => $date->toDateString(),
                    'period_granularity'   => 'daily',
                ],
                [
                    'value'          => $value,
                    'previous_value' => $previous,
                    'change_pct'     => $changePct,
                    'status'         => $status,
                    'computed_at'    => now(),
                ]
            );
        }
    }

    // ── Individual Metric Computations ────────────────────────────────────────

    private function computeDailyActivePatients(string $facilityId, Carbon $from, Carbon $to): int
    {
        return Visit::where('facility_id', $facilityId)
            ->whereBetween('created_at', [$from, $to])
            ->distinct('patient_id')
            ->count('patient_id');
    }

    private function computeDailyVisits(string $facilityId, Carbon $from, Carbon $to): int
    {
        return Visit::where('facility_id', $facilityId)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function computeVisitCompletionRate(string $facilityId, Carbon $from, Carbon $to): float
    {
        $total = Visit::where('facility_id', $facilityId)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        if ($total === 0) {
            return 0.0;
        }

        $completed = Visit::where('facility_id', $facilityId)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'completed')
            ->count();

        return round(($completed / $total) * 100, 2);
    }

    private function computeAvgVisitDuration(string $facilityId, Carbon $from, Carbon $to): ?float
    {
        $visits = Visit::where('facility_id', $facilityId)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'completed')
            ->whereNotNull('ended_at')
            ->get(['started_at', 'ended_at']);

        if ($visits->isEmpty()) {
            return null;
        }

        $avgMin = $visits->map(function ($v) {
            return Carbon::parse($v->started_at)->diffInMinutes(Carbon::parse($v->ended_at));
        })->avg();

        return round($avgMin, 1);
    }

    private function computeLabTurnaround(string $facilityId, Carbon $from, Carbon $to): ?float
    {
        $orders = LabOrder::where('facility_id', $facilityId)
            ->whereBetween('ordered_at', [$from, $to])
            ->whereNotNull('resulted_at')
            ->get(['ordered_at', 'resulted_at']);

        if ($orders->isEmpty()) {
            return null;
        }

        $avgMin = $orders->map(function ($o) {
            return Carbon::parse($o->ordered_at)->diffInMinutes(Carbon::parse($o->resulted_at));
        })->avg();

        return round($avgMin, 1);
    }

    private function computePrescriptionDispenseRate(string $facilityId, Carbon $from, Carbon $to): float
    {
        $total = Prescription::where('facility_id', $facilityId)
            ->whereBetween('prescribed_at', [$from, $to])
            ->count();

        if ($total === 0) {
            return 0.0;
        }

        $dispensed = Prescription::where('facility_id', $facilityId)
            ->whereBetween('prescribed_at', [$from, $to])
            ->where('status', 'dispensed')
            ->count();

        return round(($dispensed / $total) * 100, 2);
    }

    private function computeAppointmentNoShowRate(string $facilityId, Carbon $from, Carbon $to): float
    {
        // The column is scheduled_at. There has never been an appointment_date
        // column or accessor, so both queries below threw SQLSTATE[42703] and
        // took the whole admin KPI page down with a 500 on every request.
        $total = Appointment::where('facility_id', $facilityId)
            ->whereBetween('scheduled_at', [$from, $to])
            ->count();

        if ($total === 0) {
            return 0.0;
        }

        $noShow = Appointment::where('facility_id', $facilityId)
            ->whereBetween('scheduled_at', [$from, $to])
            ->where('status', 'no_show')
            ->count();

        return round(($noShow / $total) * 100, 2);
    }

    private function computeDailyInvoices(string $facilityId, Carbon $from, Carbon $to): int
    {
        return Invoice::where('facility_id', $facilityId)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    private function computePatientRegistrations(string $facilityId, Carbon $from, Carbon $to): int
    {
        // Patients verified by this facility on this date
        return Patient::where('verified_by_facility_id', $facilityId)
            ->whereBetween('verified_at', [$from, $to])
            ->count();
    }

    // ── KPI Dashboard Data ────────────────────────────────────────────────────

    /**
     * Get latest daily snapshots for a facility — used by KPI dashboard.
     * Returns one snapshot per metric for the most recent available date.
     */
    public function latestDailySnapshots(?string $facilityId, ?string $category = null): Collection
    {
        $latestDate = MetricSnapshot::where('period_granularity', 'daily')
            ->when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))
            ->max('period_date') ?? now()->toDateString();

        $query = MetricSnapshot::with('metricDefinition')
            ->when($facilityId, fn ($q) => $q->where('facility_id', $facilityId))
            ->where('period_granularity', 'daily')
            ->where('period_date', $latestDate);

        if ($category) {
            $query->whereHas('metricDefinition', fn($q) => $q->where('category', $category));
        }

        return $query->get()->keyBy(fn($s) => $s->metricDefinition->slug ?? $s->metric_definition_id);
    }

    /**
     * Trend data for a metric over a date range.
     */
    public function metricTrend(?string $facilityId, string $metricSlug, Carbon $from, Carbon $to): array
    {
        $definition = MetricDefinition::where('slug', $metricSlug)->first();
        if (!$definition) {
            return [];
        }

        // Platform-wide (no facility): aggregate (sum) across all facilities per day.
        if ($facilityId === null) {
            return MetricSnapshot::where('metric_definition_id', $definition->id)
                ->where('period_granularity', 'daily')
                ->whereBetween('period_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw('period_date, SUM(value) as value')
                ->groupBy('period_date')
                ->orderBy('period_date')
                ->get()
                ->mapWithKeys(fn($s) => [Carbon::parse($s->period_date)->toDateString() => (float) $s->value])
                ->toArray();
        }

        return MetricSnapshot::where('metric_definition_id', $definition->id)
            ->where('facility_id', $facilityId)
            ->where('period_granularity', 'daily')
            ->whereBetween('period_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('period_date')
            ->get()
            ->mapWithKeys(fn($s) => [$s->period_date->toDateString() => $s->value])
            ->toArray();
    }

    /**
     * Platform-wide summary for super_admin / hospital_director dashboards.
     */
    public function platformSummary(Carbon $date): array
    {
        $from = $date->copy()->startOfDay();
        $to   = $date->copy()->endOfDay();

        return [
            'date'                => $date->toDateString(),
            'total_visits'        => Visit::whereBetween('created_at', [$from, $to])->count(),
            'total_patients'      => Visit::whereBetween('created_at', [$from, $to])->distinct('patient_id')->count('patient_id'),
            'total_invoices'      => Invoice::whereBetween('created_at', [$from, $to])->count(),
            'total_lab_orders'    => LabOrder::whereBetween('ordered_at', [$from, $to])->count(),
            'total_prescriptions' => Prescription::whereBetween('prescribed_at', [$from, $to])->count(),
            'active_facilities'   => \App\Models\Facility::where('status', 'active')->count(),
        ];
    }

    // ── Status Helper ─────────────────────────────────────────────────────────

    private function computeStatus(MetricDefinition $definition, ?float $value): string
    {
        if ($value === null) {
            return 'normal';
        }

        if ($definition->critical_threshold !== null && $value <= $definition->critical_threshold) {
            return 'critical';
        }

        if ($definition->warning_threshold !== null && $value <= $definition->warning_threshold) {
            return 'warning';
        }

        return 'normal';
    }

    // ── Export ────────────────────────────────────────────────────────────────

    /**
     * Create an export job record for the given metrics/period.
     * Actual file generation is handled by a queued job reading this record.
     */
    public function requestExport(
        string $requestedBy,
        array $metricSlugs,
        Carbon $from,
        Carbon $to,
        string $exportType = 'csv',
        ?string $facilityId = null,
    ): KpiExport {
        return KpiExport::create([
            'export_type'  => $exportType,
            'facility_id'  => $facilityId,
            'period_from'  => $from->toDateString(),
            'period_to'    => $to->toDateString(),
            'metric_slugs' => $metricSlugs,
            'status'       => 'pending',
            'requested_by' => $requestedBy,
            'requested_at' => now(),
        ]);
    }
}
