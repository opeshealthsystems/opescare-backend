<?php

namespace App\Console\Commands;

use App\Models\MedicalIdAccessEvent;
use App\Models\Patient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * GenerateMinsanteMonthlyReport
 *
 * Generates and stores the MINSANTE Digital Health monthly audit report.
 * Runs on the 1st of each month to cover the previous month's data.
 *
 * Output is saved to storage/app/reports/minsante/YYYY-MM.json
 * so it can be downloaded by platform admins or auto-submitted to MINSANTE.
 *
 * Schedule: monthly on the 1st at 06:00
 *
 *     $schedule->command('health-id:generate-minsante-report')->monthlyOn(1, '06:00');
 */
class GenerateMinsanteMonthlyReport extends Command
{
    protected $signature = 'health-id:generate-minsante-report
                            {--month= : Target month in YYYY-MM format (default: last month)}
                            {--dry-run : Generate and display report without saving to disk}';

    protected $description = 'Generate the MINSANTE Digital Health monthly audit report.';

    public function handle(): int
    {
        $targetMonth = $this->option('month');
        if ($targetMonth && preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
            $periodStart = \Carbon\Carbon::createFromFormat('Y-m', $targetMonth)->startOfMonth();
        } else {
            $periodStart = now()->subMonth()->startOfMonth();
        }
        $periodEnd = $periodStart->copy()->endOfMonth();

        $this->info('Generating MINSANTE report for ' . $periodStart->format('F Y') . '…');

        $report = $this->buildReport($periodStart, $periodEnd);

        $json     = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'reports/minsante/' . $periodStart->format('Y-m') . '.json';

        if ($this->option('dry-run')) {
            $this->line($json);
            $this->info('Dry run — report NOT saved.');
            return self::SUCCESS;
        }

        Storage::put($filename, $json);

        $this->info("Report saved to storage/app/{$filename}");

        Log::info('minsante_monthly_report_generated', [
            'period'   => $periodStart->format('Y-m'),
            'path'     => $filename,
            'patients' => $report['patient_registration']['new_registrations'],
            'accesses' => $report['access_events']['total'],
        ]);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Period', $periodStart->format('F Y')],
                ['New Registrations', $report['patient_registration']['new_registrations']],
                ['Newly Verified', $report['patient_registration']['newly_verified']],
                ['Total Access Events', $report['access_events']['total']],
                ['Emergency Accesses', $report['access_events']['emergency_accesses']],
                ['Denied Accesses', $report['access_events']['denied']],
                ['Data Exports', $report['data_subject_rights']['data_exports']],
                ['Erasure Requests', $report['data_subject_rights']['erasure_requests']],
            ]
        );

        return self::SUCCESS;
    }

    private function buildReport(\Carbon\Carbon $periodStart, \Carbon\Carbon $periodEnd): array
    {
        $newPatients = Patient::whereBetween('created_at', [$periodStart, $periodEnd])
            ->where('is_demo', false)->count();

        $verifiedPatients = Patient::whereBetween('verified_at', [$periodStart, $periodEnd])
            ->where('is_demo', false)->count();

        $activePatientsTotal = Patient::where('is_demo', false)
            ->where('verification_status', 'verified')->count();

        $accessEvents = MedicalIdAccessEvent::whereBetween('created_at', [$periodStart, $periodEnd])
            ->select('access_type', 'purpose', 'result', DB::raw('COUNT(*) as cnt'))
            ->groupBy('access_type', 'purpose', 'result')
            ->get();

        $byType = $accessEvents->groupBy('access_type')->map(fn ($g) => $g->sum('cnt'))->toArray();

        $consentStats = DB::table('consent_grants')
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->select('status', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $dataRightsEvents = MedicalIdAccessEvent::whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereIn('access_type', ['data_export', 'data_rectification_request', 'data_erasure_request'])
            ->select('access_type', DB::raw('COUNT(*) as cnt'))
            ->groupBy('access_type')
            ->pluck('cnt', 'access_type')
            ->toArray();

        $demographicSplit = Patient::whereBetween('created_at', [$periodStart, $periodEnd])
            ->where('is_demo', false)
            ->select('sex', DB::raw('COUNT(*) as cnt'))
            ->groupBy('sex')
            ->pluck('cnt', 'sex')
            ->toArray();

        return [
            'report_type'    => 'MINSANTE_DIGITAL_HEALTH_MONTHLY_AUDIT',
            'report_version' => '2.0',
            'generated_at'   => now()->toIso8601String(),
            'generated_by'   => config('app.name') . ' v' . config('app.version', '1.0'),
            'period'         => [
                'month' => $periodStart->format('m'),
                'year'  => $periodStart->format('Y'),
                'start' => $periodStart->toDateString(),
                'end'   => $periodEnd->toDateString(),
            ],
            'platform' => [
                'name'       => config('app.name'),
                'country'    => 'CM',
                'regulation' => 'Cameroon Law No. 2010/012',
                'strategy'   => 'MINSANTE Digital Health Strategy 2026–2030',
            ],
            'patient_registration' => [
                'new_registrations'     => $newPatients,
                'newly_verified'        => $verifiedPatients,
                'total_active_verified' => $activePatientsTotal,
                'demographic_split'     => $demographicSplit,
            ],
            'access_events' => [
                'total'              => $accessEvents->sum('cnt'),
                'successful'         => $accessEvents->where('result', 'success')->sum('cnt'),
                'denied'             => $accessEvents->where('result', 'denied')->sum('cnt'),
                'emergency_accesses' => $accessEvents->where('purpose', 'emergency_access')->sum('cnt'),
                'by_access_type'     => $byType,
            ],
            'consent_management'  => $consentStats,
            'data_subject_rights' => [
                'data_exports'           => $dataRightsEvents['data_export'] ?? 0,
                'rectification_requests' => $dataRightsEvents['data_rectification_request'] ?? 0,
                'erasure_requests'       => $dataRightsEvents['data_erasure_request'] ?? 0,
            ],
        ];
    }
}
