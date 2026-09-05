<?php

namespace App\Console\Commands;

use App\Modules\MasterPatientIndex\Services\MasterPatientIndexService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * mpi:detect — queue likely duplicate patients for a human to review.
 *
 * The Master Patient Index had a detection routine and nothing that ever ran
 * it. This is the nightly run: newest registrations first, paired against the
 * whole register across every facility, only the ones that arrived since the
 * last pass. It writes candidates and nothing else — no merge happens here,
 * or anywhere, without a person choosing a survivor on the review desk.
 */
class DetectDuplicatePatients extends Command
{
    protected $signature = 'mpi:detect
        {--since= : Only patients created or updated since, e.g. "2 days" or 2026-09-01}
        {--limit=500 : Most patients to focus on in one run}
        {--facility= : Focus only on patients registered at this facility id}
        {--dry-run : Score everything, write nothing}';

    protected $description = 'Find likely duplicate patient records and queue them for review';

    public function handle(MasterPatientIndexService $mpi): int
    {
        $since = null;
        if ($raw = $this->option('since')) {
            try {
                $since = Carbon::parse($raw);
                // "2 days" parses as a point in the FUTURE; a relative period
                // here always means "this long ago".
                if ($since->isFuture()) {
                    $since = Carbon::now()->sub($raw);
                }
            } catch (\Throwable) {
                $this->error("Could not read --since='{$raw}'.");

                return self::INVALID;
            }
        }

        $report = $mpi->detectDuplicates(
            facilityId: $this->option('facility') ?: null,
            limit: max(1, (int) $this->option('limit')),
            since: $since,
            dryRun: (bool) $this->option('dry-run'),
        );

        $this->table(
            ['scanned', 'pairs scored', 'queued', 'already known', 'below threshold'],
            [[$report['scanned'], $report['pairs_scored'], $report['created'], $report['already_known'], $report['below_threshold']]],
        );

        if ($this->option('dry-run')) {
            $this->comment('Dry run — nothing was written.');
        } elseif ($report['created'] > 0) {
            $this->info("{$report['created']} pair(s) waiting on the review desk.");
        }

        return self::SUCCESS;
    }
}
