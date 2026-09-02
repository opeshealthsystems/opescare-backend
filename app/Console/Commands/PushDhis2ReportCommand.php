<?php

namespace App\Console\Commands;

use App\Services\Integration\Dhis2Service;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PushDhis2ReportCommand extends Command
{
    protected $signature = 'opescare:push-dhis2
                            {--month= : Year-month to push (YYYY-MM). Defaults to last month.}
                            {--facility= : Facility ID to aggregate. Defaults to all facilities.}
                            {--dry-run : Print payload without pushing to DHIS2.}
                            {--test-connection : Test DHIS2 connection only.}';

    protected $description = 'Push monthly aggregate health data to DHIS2 (MINSANTE Cameroon)';

    public function __construct(private readonly Dhis2Service $dhis2)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('test-connection')) {
            $result = $this->dhis2->testConnection();
            if ($result['connected']) {
                $this->info("✓ DHIS2 connected as: {$result['user']}");
                return self::SUCCESS;
            }
            $this->error("✗ DHIS2 connection failed: " . ($result['error'] ?? $result['reason'] ?? 'unknown'));
            return self::FAILURE;
        }

        $month      = $this->option('month') ?? Carbon::now()->subMonth()->format('Y-m');
        $facilityId = $this->option('facility');
        $isDryRun   = $this->option('dry-run');

        try {
            $from = Carbon::parse("{$month}-01")->startOfDay();
            $to   = $from->copy()->endOfMonth()->endOfDay();
        } catch (\Throwable) {
            $this->error("Invalid month format. Use YYYY-MM, e.g. 2026-05");
            return self::FAILURE;
        }

        $this->info("Building aggregate for period: {$month}" . ($facilityId ? " (facility: {$facilityId})" : " (all facilities)"));

        $counts = $this->buildAggregate($from, $to, $facilityId);

        $this->table(
            ['Metric', 'Count'],
            collect($counts)->map(fn($v, $k) => [$k, $v])->values()->all()
        );

        if ($isDryRun) {
            $this->warn('[DRY RUN] — payload built but not pushed to DHIS2.');
            return self::SUCCESS;
        }

        $result = $this->dhis2->pushMonthlySummary($month, $counts);

        if ($result['success']) {
            $this->info("✓ DHIS2 push successful: {$result['imported']} imported, {$result['updated']} updated, {$result['ignored']} ignored");
            return self::SUCCESS;
        }

        $this->error("✗ DHIS2 push failed: " . ($result['error'] ?? $result['reason'] ?? 'unknown error'));
        return self::FAILURE;
    }

    private function buildAggregate(Carbon $from, Carbon $to, ?string $facilityId): array
    {
        $visitsQuery = DB::table('visits')
            ->whereBetween('created_at', [$from, $to]);

        if ($facilityId) {
            $visitsQuery->where('facility_id', $facilityId);
        }

        $patientsQuery = DB::table('patients')
            ->join('visits', 'patients.id', '=', 'visits.patient_id')
            ->whereBetween('visits.created_at', [$from, $to]);

        if ($facilityId) {
            $patientsQuery->where('visits.facility_id', $facilityId);
        }

        $opdTotal  = (clone $visitsQuery)->count();
        $opdMale   = (clone $patientsQuery)->where('patients.sex', 'male')->distinct('patients.id')->count();
        $opdFemale = (clone $patientsQuery)->where('patients.sex', 'female')->distinct('patients.id')->count();

        // Under-5: DOB within last 5 years — note: DOB is encrypted, so we use age_years if available
        // or fall back to a visit-level age field if present
        $opdUnder5 = 0; // Requires decrypted age — set to 0 until age_years column is added

        $diagnosesQuery = DB::table('diagnoses')
            ->join('visits', 'diagnoses.visit_id', '=', 'visits.id')
            ->whereBetween('visits.created_at', [$from, $to]);

        if ($facilityId) {
            $diagnosesQuery->where('visits.facility_id', $facilityId);
        }

        $malariaConfirmed = (clone $diagnosesQuery)
            ->where(function ($q) {
                $q->where('diagnoses.code', 'like', 'B50%')   // ICD-10 Malaria
                  ->orWhere('diagnoses.code', 'like', 'B51%')
                  ->orWhere('diagnoses.code', 'like', 'B52%')
                  ->orWhere('diagnoses.code', 'like', 'B53%')
                  ->orWhere('diagnoses.code', 'like', 'B54%');
            })->count();

        $hypertensionNew = (clone $diagnosesQuery)
            ->where('diagnoses.code', 'like', 'I10%')
            ->where('diagnoses.status', 'active')
            ->count();

        $diabetesNew = (clone $diagnosesQuery)
            ->where(function ($q) {
                $q->where('diagnoses.code', 'like', 'E10%')
                  ->orWhere('diagnoses.code', 'like', 'E11%')
                  ->orWhere('diagnoses.code', 'like', 'E12%')
                  ->orWhere('diagnoses.code', 'like', 'E13%')
                  ->orWhere('diagnoses.code', 'like', 'E14%');
            })
            ->where('diagnoses.status', 'active')
            ->count();

        $immunizationsGiven = DB::table('immunization_records')
            ->when($facilityId, fn($q) => $q->where('facility_id', $facilityId))
            ->whereBetween('created_at', [$from, $to])
            ->count();

        return [
            'opd_total'         => $opdTotal,
            'opd_male'          => $opdMale,
            'opd_female'        => $opdFemale,
            'opd_under5'        => $opdUnder5,
            'malaria_confirmed' => $malariaConfirmed,
            'hypertension_new'  => $hypertensionNew,
            'diabetes_new'      => $diabetesNew,
            'maternal_visits'   => 0, // Populated after Phase 2 (Maternity module)
            'immunizations'     => $immunizationsGiven,
        ];
    }
}
