<?php
namespace App\Services\Lab;

use App\Models\RadiologyReport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use RuntimeException;

class RadiologyReportService {
    public function createDraft(array $data): RadiologyReport {
        return RadiologyReport::create(array_merge($data, [
            'status'       => 'draft',
            'distributed_to' => [],
        ]));
    }

    public function finalize(string $reportId, string $radiologistId): RadiologyReport {
        $report = RadiologyReport::findOrFail($reportId);
        if (in_array($report->status, ['final','amended','corrected'])) {
            throw new RuntimeException("Report {$reportId} cannot be finalized from status '{$report->status}'.");
        }
        $report->update([
            'status'      => 'final',
            'reported_by' => $radiologistId,
            'finalized_at'=> now(),
        ]);
        return $report->fresh();
    }

    public function amend(string $reportId, string $reason, array $changes): RadiologyReport {
        $report = RadiologyReport::findOrFail($reportId);
        if (!in_array($report->status, ['final','corrected'])) {
            throw new RuntimeException("Only finalized reports can be amended. Current status: '{$report->status}'.");
        }
        $allowed = ['findings','impression','recommendation','clinical_indication'];
        $safe    = array_intersect_key($changes, array_flip($allowed));
        $report->update(array_merge($safe, [
            'status'           => 'amended',
            'amended_at'       => now(),
            'amendment_reason' => $reason,
        ]));
        return $report->fresh();
    }

    public function distribute(string $reportId, array $userIds): RadiologyReport {
        $report = RadiologyReport::findOrFail($reportId);
        if (!in_array($report->status, ['final','amended','corrected'])) {
            throw new RuntimeException("Only finalized reports can be distributed. Current status: '{$report->status}'.");
        }
        $existing = $report->distributed_to ?? [];
        $merged   = array_values(array_unique(array_merge($existing, $userIds)));
        $report->update([
            'distributed_to' => $merged,
            'distributed_at' => now(),
        ]);
        if (class_exists(\App\Events\RadiologyReportDistributed::class)) {
            Event::dispatch(new \App\Events\RadiologyReportDistributed($report, $userIds));
        }
        return $report->fresh();
    }

    public function getPendingForFacility(string $facilityId): Collection {
        return RadiologyReport::where('facility_id', $facilityId)
            ->whereIn('status', ['draft','preliminary'])
            ->with(['patient','reportedBy'])
            ->orderBy('study_date')
            ->get();
    }
}
