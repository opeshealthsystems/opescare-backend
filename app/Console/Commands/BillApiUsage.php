<?php

namespace App\Console\Commands;

use App\Services\ApiBilling\ApiBillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Generates monthly API-plan invoices from metered usage and flags overdue
 * ones. Scheduled on the 1st of each month; can be re-run for a given month.
 */
class BillApiUsage extends Command
{
    protected $signature = 'opescare:bill-api-usage {--month= : YYYY-MM (defaults to last month)}';
    protected $description = 'Generate monthly API plan invoices from metered usage and flag overdue ones';

    public function handle(ApiBillingService $billing): int
    {
        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : now()->subMonth();

        $created = $billing->generateForMonth($month);
        $overdue = $billing->markOverdue();

        $this->info("Generated/updated {$created} API invoice(s) for {$month->format('Y-m')}; flagged {$overdue} overdue.");

        return self::SUCCESS;
    }
}
