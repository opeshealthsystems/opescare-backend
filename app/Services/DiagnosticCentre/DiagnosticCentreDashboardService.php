<?php

namespace App\Services\DiagnosticCentre;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\ImagingOrder;
use App\Models\LabTestAvailability;
use App\Services\Imaging\ImagingDeskService;
use App\Services\Imaging\ImagingExamSearch;
use App\Services\Lab\LabManagerDeskService;
use Carbon\Carbon;

/**
 * DiagnosticCentreDashboardService — the centre landing (/facility/diagnostic).
 *
 * A diagnostic centre is a laboratory and an imaging service under one roof
 * (`facilities.type` = `diagnostic_center`; `imaging_center` is the imaging-only
 * case and lands here too, since its lab half is simply empty). Each half
 * already has a desk with its own figures; this class puts both on one page
 * and adds nothing of its own:
 *
 *   - lab figures come from LabManagerDeskService (the same counts, today and
 *     turnaround the lab dashboard shows — never recomputed here);
 *   - imaging figures come from ImagingDeskService::counts(), the worklist's
 *     own tab counts, so the tile and the column never disagree;
 *   - publication is split lab / imaging with ServiceMix, read from the
 *     projection itself (`lab_test_availability`), never from the catalogue.
 *
 * Facility scope is the id the controller got from PortalContextService.
 * Nothing here reads billing: care is never gated on payment.
 */
class DiagnosticCentreDashboardService
{
    /** `facilities.type` values that land on the centre dashboard. */
    public const CENTRE_TYPES = ['diagnostic_center', 'imaging_center'];

    public function __construct(
        private readonly LabManagerDeskService $lab,
        private readonly ImagingDeskService $imaging,
        private readonly ServiceMix $mix,
    ) {
    }

    /** Whether a facility type is a diagnostic or imaging centre. */
    public static function appliesToType(?string $type): bool
    {
        return $type !== null && in_array($type, self::CENTRE_TYPES, true);
    }

    /**
     * The tenant's type, read outside the demo scope so a demo centre and a
     * live one answer alike. Null when the facility is unknown.
     */
    public static function facilityType(?string $facilityId): ?string
    {
        if ($facilityId === null) {
            return null;
        }

        $type = Facility::withoutGlobalScopes()->whereKey($facilityId)->value('type');

        return $type === null ? null : (string) $type;
    }

    /** Whether this facility lands on the centre dashboard rather than the lab one. */
    public static function appliesTo(?string $facilityId): bool
    {
        return self::appliesToType(self::facilityType($facilityId));
    }

    /**
     * Everything the landing page shows.
     *
     * @return array{
     *   lab: array{open:int, resulted_today:int, awaiting_validation:int, critical_open:int, requests_pending:int, turnaround:array, has_work:bool},
     *   imaging: array{pending:int, scheduled:int, in_progress:int, today:int, awaiting_report:int, reported:int, has_work:bool},
     *   publication: array{linked:bool, listing_name:?string, listing_active:bool, lab_catalogue:int, imaging_catalogue:int, lab_published:int, imaging_published:int, lab_last_published_at:?Carbon, imaging_last_published_at:?Carbon}
     * }
     */
    public function dashboard(string $facilityId): array
    {
        return [
            'lab'         => $this->lab($facilityId),
            'imaging'     => $this->imaging($facilityId),
            'publication' => $this->publication($facilityId),
        ];
    }

    /** The lab half: the lab manager's own figures, condensed to one row. */
    public function lab(string $facilityId): array
    {
        $d      = $this->lab->dashboard($facilityId);
        $counts = $d['counts'];

        return [
            'open'                => (int) ($counts['ordered'] ?? 0) + (int) ($counts['collected'] ?? 0) + (int) ($counts['in_progress'] ?? 0),
            'resulted_today'      => (int) $d['today']['released'],
            'awaiting_validation' => (int) $d['awaiting_validation'],
            'critical_open'       => (int) $d['critical_open'],
            'requests_pending'    => (int) $d['requests_pending'],
            'turnaround'          => $d['turnaround'],
            'has_work'            => $this->lab->hasAnyWork($facilityId),
        ];
    }

    /** The imaging half: the worklist's own tab counts plus today's intake. */
    public function imaging(string $facilityId): array
    {
        $counts = $this->imaging->counts($facilityId);

        return [
            'pending'         => (int) ($counts['pending'] ?? 0),
            'scheduled'       => (int) ($counts['scheduled'] ?? 0),
            'in_progress'     => (int) ($counts['in_progress'] ?? 0),
            'today'           => ImagingOrder::query()
                ->where('facility_id', $facilityId)
                ->where('ordered_at', '>=', now()->startOfDay())
                ->count(),
            'awaiting_report' => (int) ($counts['awaiting_report'] ?? 0),
            'reported'        => (int) ($counts['reported'] ?? 0),
            'has_work'        => ImagingOrder::query()->where('facility_id', $facilityId)->exists(),
        ];
    }

    /**
     * What the two public finders show for this centre, from the projection
     * itself. `linked` false means nothing CAN be published until the tenant
     * is linked to a directory listing — an admin decision, never taken here.
     */
    public function publication(string $facilityId): array
    {
        $mix     = $this->mix->forFacility($facilityId);
        $listing = CareFacility::query()->where('facility_id', $facilityId)->first();

        $out = $mix + [
            'linked'                    => $listing !== null,
            'listing_name'              => $listing?->facility_name,
            'listing_active'            => $listing?->listing_status === 'active',
            'lab_last_published_at'     => null,
            'imaging_last_published_at' => null,
        ];

        if (! $listing) {
            return $out;
        }

        $rows = LabTestAvailability::query()
            ->where('facility_id', $listing->id)
            ->where('source_system', LabTestAvailability::SOURCE_PORTAL);

        $labLast     = (clone $rows)->excludingImaging()->max('last_published_at');
        $imagingLast = (clone $rows)->whereIn('loinc_code', ImagingExamSearch::imagingCodesQuery())->max('last_published_at');

        $out['lab_last_published_at']     = $labLast ? Carbon::parse($labLast) : null;
        $out['imaging_last_published_at'] = $imagingLast ? Carbon::parse($imagingLast) : null;

        return $out;
    }
}
