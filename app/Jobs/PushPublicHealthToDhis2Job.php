<?php
namespace App\Jobs;

use App\Models\AuditEvent;
use App\Models\PublicHealthReport;
use App\Services\Interoperability\Dhis2PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Imported from the platform, where it carried one raw data point. The Finder
 * job carries only the id of an APPROVED aggregate report; Dhis2PushService
 * refuses anything else, so no queued job can send an unapproved draft.
 * A failed push leaves the report in submission_failed and the admin can push
 * again; values DHIS2 already imported unchanged are not re-sent.
 */
class PushPublicHealthToDhis2Job implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(public readonly string $reportId, public readonly ?string $userId = null) {}

    public function uniqueId(): string
    {
        return 'dhis2-push-' . $this->reportId;
    }

    public function handle(Dhis2PushService $service): void
    {
        $report = PublicHealthReport::find($this->reportId);
        if (! $report) {
            return;
        }

        $result = $service->push($report, $this->userId);

        AuditEvent::create([
            'actor_id' => $this->userId,
            'actor_role' => 'platform_admin',
            'facility_id' => $report->facility_id,
            'action_type' => 'dhis2.push.' . $result['outcome'],
            'resource_type' => 'public_health_report',
            'resource_id' => $report->id,
            'source_system' => 'finder_dhis2',
            'after_state' => $result, // counts + DHIS2's safe import summary; never credentials
            'created_at' => now(),
        ]);

        if ($result['outcome'] !== 'pushed' && $result['outcome'] !== 'nothing_to_send') {
            Log::warning('DHIS2 push unsuccessful', ['report_id' => $report->id, 'outcome' => $result['outcome']]);
        }
    }
}
