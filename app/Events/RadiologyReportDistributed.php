<?php

namespace App\Events;

use App\Models\RadiologyReport;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched by App\Services\Lab\RadiologyReportService::distribute() — the
 * service already fired it behind class_exists(); neither the platform nor
 * the Finder had the class, so a distributed report reached nobody.
 * Listener: App\Listeners\NotifyPatientOfRadiologyReport.
 */
class RadiologyReportDistributed
{
    use Dispatchable, SerializesModels;

    /** @param  array<int, string>  $userIds  the staff the report was distributed to */
    public function __construct(
        public readonly RadiologyReport $report,
        public readonly array $userIds = [],
    ) {
    }
}
