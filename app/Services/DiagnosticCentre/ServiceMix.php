<?php

namespace App\Services\DiagnosticCentre;

use App\Models\CareFacility;
use App\Models\ClinicalCodeDictionary;
use App\Models\FacilityCatalogueItem;
use App\Models\LabTestAvailability;
use App\Services\Imaging\ImagingExamSearch;
use App\Services\Lab\LabCatalogueProjector;

/**
 * ServiceMix — which public finder a catalogue item reaches, lab or imaging.
 *
 * A diagnostic centre publishes laboratory tests AND imaging exams from one
 * catalogue. Both are LOINC codes, both are projected into
 * `lab_test_availability`, and nothing on the catalogue screen said which
 * finder a row ends up on. This class answers that from the dictionary alone
 * (`common_reason` starting "Imaging:", the same rule ImagingExamSearch uses)
 * and, for WHO ICHI procedures, from the code's own action axis (`BA`,
 * diagnostic imaging — ImagingExamSearch::isImagingProcedureCode). Nothing is
 * guessed from titles: a LOINC code the dictionary does not mark as imaging is
 * a lab test, an ICHI code that is not a diagnostic-imaging stem reaches no
 * finder, and any other system reaches no finder at all.
 *
 * Read-only. No schema.
 */
class ServiceMix
{
    public const LAB     = 'lab';
    public const IMAGING = 'imaging';

    /** Which finder a catalogue row reaches: lab, imaging, or null (none). */
    public static function finderFor(string $system, ?ClinicalCodeDictionary $entry, ?string $code = null): ?string
    {
        if ($system === ImagingExamSearch::ICHI_SYSTEM) {
            return ImagingExamSearch::isImagingProcedureCode($code ?? $entry?->code) ? self::IMAGING : null;
        }

        if ($system !== LabCatalogueProjector::LAB_TEST_SYSTEM) {
            return null;
        }

        return self::isImagingReason($entry?->common_reason) ? self::IMAGING : self::LAB;
    }

    public static function isImagingReason(?string $reason): bool
    {
        return $reason !== null && str_starts_with($reason, ImagingExamSearch::IMAGING_REASON_PREFIX);
    }

    /**
     * Counts for one tenant: active LOINC catalogue items and portal-published
     * available rows, each split lab / imaging.
     *
     * @return array{lab_catalogue:int, imaging_catalogue:int, lab_published:int, imaging_published:int}
     */
    public function forFacility(string $tenantFacilityId): array
    {
        $imagingCodes = ImagingExamSearch::imagingCodesQuery();

        // Every item the projector can publish: LOINC tests and ICHI
        // diagnostic-imaging procedures. Other ICHI stems reach no finder and
        // are not counted on either side.
        $catalogue = FacilityCatalogueItem::query()
            ->where('facility_id', $tenantFacilityId)
            ->whereIn('system', LabCatalogueProjector::PUBLISHED_SYSTEMS)
            ->where(function ($q) {
                $q->where('system', LabCatalogueProjector::LAB_TEST_SYSTEM)
                    ->orWhere('code', 'like', ImagingExamSearch::ICHI_IMAGING_CODE_LIKE);
            })
            ->where('is_active', true);

        $total   = (clone $catalogue)->count();
        $imaging = (clone $catalogue)->whereIn('code', $imagingCodes)->count();

        $out = [
            'lab_catalogue'     => $total - $imaging,
            'imaging_catalogue' => $imaging,
            'lab_published'     => 0,
            'imaging_published' => 0,
        ];

        $listing = CareFacility::query()->where('facility_id', $tenantFacilityId)->first();
        if (! $listing) {
            return $out;
        }

        $published = LabTestAvailability::query()
            ->where('facility_id', $listing->id)
            ->where('source_system', LabTestAvailability::SOURCE_PORTAL)
            ->where('availability_status', LabTestAvailability::STATUS_AVAILABLE);

        $pubTotal   = (clone $published)->count();
        $pubImaging = (clone $published)->whereIn('loinc_code', $imagingCodes)->count();

        $out['lab_published']     = $pubTotal - $pubImaging;
        $out['imaging_published'] = $pubImaging;

        return $out;
    }
}
