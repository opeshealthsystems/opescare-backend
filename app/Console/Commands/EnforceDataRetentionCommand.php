<?php
namespace App\Console\Commands;

use App\Services\Compliance\DataRetentionService;
use Illuminate\Console\Command;

class EnforceDataRetentionCommand extends Command
{
    protected $signature   = 'opescare:enforce-data-retention {--dry-run : Log what would be purged without deleting}';
    protected $description = 'Enforce data retention policies per Cameroon Law 2010/012 and internal data governance rules';

    public function handle(DataRetentionService $service): int
    {
        $dryRun = $this->option('dry-run');
        $this->info('OpesCare Data Retention Enforcement' . ($dryRun ? ' [DRY RUN]' : ''));

        $summary = $service->enforce($dryRun);

        if (empty($summary)) {
            $this->line('  No active retention policies found.');
        }

        foreach ($summary as $table => $result) {
            $this->line(sprintf(
                '  %-40s  %-10s  %d records  cutoff: %s',
                $table,
                $result['action'],
                $result['count'],
                $result['cutoff'],
            ));
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
