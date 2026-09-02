<?php

namespace App\Modules\CareMap\Services;

use App\Enums\FacilityImportReviewStatus;
use App\Models\CareFacility;
use App\Models\FacilityImportReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The other half of OsmFacilityImporter: what happens to the candidates it
 * refused to decide about.
 *
 * The importer has been parking uncertain candidates in
 * `facility_import_reviews` since the OpenStreetMap run, on the correct
 * principle that a duplicated hospital is worse than a missing one. 439 of them
 * are still `pending` because nothing has ever read the table. This service is
 * what an administrator's accept / merge / reject actually does.
 */
class FacilityImportReviewService
{
    public const SOURCE = 'facility_import_review';

    /**
     * Accept the candidate as a new, distinct facility.
     *
     * Provenance is carried onto the new row (source_system / source_ref /
     * source_attribution) so the importer's partial UNIQUE index on source_ref
     * recognises it next run and does not insert it a second time.
     */
    public function accept(FacilityImportReview $review, string $adminId, ?string $notes = null): CareFacility
    {
        if ($review->status->isDecided()) {
            throw new \Exception('IMPORT_REVIEW_ALREADY_DECIDED');
        }

        $name = $review->displayName();

        if ($name === '') {
            // The reviewer must supply a name for an unnamed element before it
            // can enter a directory people search by name.
            throw new \Exception('IMPORT_REVIEW_NEEDS_NAME');
        }

        return DB::transaction(function () use ($review, $adminId, $notes, $name) {
            $city = $review->candidate_city ?: '';

            // The candidate's own contact details, when it brought any. This
            // used to be dropped: every accepted row was written with the 'N/A'
            // phone placeholder and an address that was only the town, even
            // when the payload held a real number and a real street. Harmless
            // while the queue was all OpenStreetMap (8 of those 439 rows carry
            // a phone) and expensive afterwards -- the national master records
            // carry a phone two times in three, and a phone number is the whole
            // point of a directory entry for someone who needs a pharmacy.
            $phone   = $this->payloadPhone($review);
            $address = $this->payloadAddress($review)
                ?? ($city !== '' ? $city . ', Cameroon' : 'Cameroon');

            $listing = CareFacility::create([
                'facility_name'       => $name,
                'facility_type'       => $review->candidate_type ?: 'clinic',
                'country_code'        => 'CM',
                'region'              => $review->candidate_region,
                'city'                => $city,
                'address'             => $address,
                'latitude'            => $review->latitude,
                'longitude'           => $review->longitude,
                'phone_primary'       => $phone ?? CareFacility::PHONE_PLACEHOLDER,
                'listing_status'      => 'active',
                // Untouched on purpose. An import is not a verification.
                'verification_status' => 'unverified',
                'source_system'       => $review->source_system,
                'source_ref'          => $review->source_ref,
                'source_attribution'  => $review->source_attribution,
                'source_synced_at'    => now(),
            ]);

            $review->update([
                'status'       => FacilityImportReviewStatus::Imported->value,
                'reviewed_by'  => $adminId,
                'reviewed_at'  => now(),
                'review_notes' => $notes,
            ]);

            DB::table('facility_update_audits')->insert([
                'id'              => (string) Str::uuid(),
                'facility_id'     => $listing->id,
                'actor_id'        => $adminId,
                'actor_type'      => 'admin',
                'field_changed'   => 'listing_created',
                'old_value'       => null,
                'new_value'       => $review->source_system . ':' . $review->source_ref,
                'source'          => self::SOURCE,
                'requires_review' => false,
                'created_at'      => now(),
            ]);

            return $listing;
        });
    }

    /**
     * Fill empty fields on the merged-into listing from the candidate.
     *
     * Writes only where the existing row has nothing usable, and records each
     * field it touches in `facility_update_audits` so the change is traceable
     * to the review that caused it.
     */
    private function fillGapsFromCandidate(
        CareFacility $listing,
        FacilityImportReview $review,
        string $adminId
    ): void {
        $changes = [];

        if (CareFacility::realValue($listing->phone_primary) === null) {
            $phone = $this->payloadPhone($review);

            if ($phone !== null) {
                $changes['phone_primary'] = $phone;
            }
        }

        // An address that merely repeats the town carries no more information
        // than the city column already does.
        $currentAddress = CareFacility::realValue($listing->address);
        $addressIsJustTheTown = $currentAddress === null
            || mb_strtolower(trim($currentAddress)) === mb_strtolower(trim((string) $listing->city));

        if ($addressIsJustTheTown) {
            $address = $this->payloadAddress($review);

            if ($address !== null) {
                $changes['address'] = $address;
            }
        }

        if ($listing->latitude === null && $review->latitude !== null) {
            $changes['latitude']  = $review->latitude;
            $changes['longitude'] = $review->longitude;
        }

        if ($changes === []) {
            return;
        }

        $before = $listing->only(array_keys($changes));
        $listing->forceFill($changes)->save();

        foreach ($changes as $field => $value) {
            DB::table('facility_update_audits')->insert([
                'id'              => (string) Str::uuid(),
                'facility_id'     => $listing->id,
                'actor_id'        => $adminId,
                'actor_type'      => 'admin',
                'field_changed'   => $field,
                'old_value'       => $before[$field] === null ? null : (string) $before[$field],
                'new_value'       => (string) $value,
                'source'          => self::SOURCE,
                'requires_review' => false,
                'created_at'      => now(),
            ]);
        }
    }

    /**
     * A usable phone number from the candidate's payload, or null.
     *
     * Two payload shapes reach here: the national master records use `phone`,
     * while OpenStreetMap candidates carry raw OSM tags (`contact:phone`).
     * `CareFacility::realValue()` nulls blanks and the literal 'N/A', and the
     * digit floor rejects fragments -- an unusable string in `phone_primary` is
     * exactly the placeholder problem this is meant to avoid, so anything that
     * does not look dialable falls back to the placeholder rather than becoming
     * a Call button that dials nothing.
     */
    private function payloadPhone(FacilityImportReview $review): ?string
    {
        foreach (['phone', 'contact:phone', 'phone_raw'] as $key) {
            $value = CareFacility::realValue(data_get($review->payload, $key));

            if ($value !== null && strlen((string) preg_replace('/\D/', '', $value)) >= 8) {
                return $value;
            }
        }

        return null;
    }

    /**
     * A street address from the payload, or null to fall back to the town.
     *
     * Master records carry a single `address`; OSM candidates carry the
     * `addr:street` / `addr:city` tag pair.
     */
    private function payloadAddress(FacilityImportReview $review): ?string
    {
        $direct = CareFacility::realValue(data_get($review->payload, 'address'));

        if ($direct !== null) {
            return $direct;
        }

        $parts = array_filter([
            CareFacility::realValue(data_get($review->payload, 'addr:street')),
            CareFacility::realValue(data_get($review->payload, 'addr:city')),
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * The candidate is the matched listing under another name. Stamp the
     * upstream reference onto the existing row so the next import run updates
     * that row instead of asking again.
     */
    public function merge(FacilityImportReview $review, string $adminId, ?string $notes = null): CareFacility
    {
        if ($review->status->isDecided()) {
            throw new \Exception('IMPORT_REVIEW_ALREADY_DECIDED');
        }

        $listing = CareFacility::find($review->matched_facility_id);

        if ($listing === null) {
            throw new \Exception('IMPORT_REVIEW_NO_MATCH');
        }

        return DB::transaction(function () use ($review, $listing, $adminId, $notes) {
            // A merge says "this is the facility we already list, under another
            // name" -- so the candidate's details should fill the gaps on that
            // row. Stamping the reference alone left the phone number sitting
            // in the payload until some later import run happened to pick it
            // up, which is a strange outcome for an action whose entire purpose
            // is to improve the row it points at.
            //
            // Gaps only. An existing real value always wins; the exceptions are
            // the literal 'N/A' placeholder in phone_primary and an address
            // that is only the town name, neither of which is data. Same rule
            // the importers apply, for the same reason: a human or a partner
            // may have edited this row, and an import must not overwrite them.
            $this->fillGapsFromCandidate($listing, $review, $adminId);

            // Only claim the source_ref if the row does not already carry one —
            // the partial UNIQUE index allows exactly one row per upstream element.
            if ($listing->source_ref === null) {
                $listing->forceFill([
                    'source_system'      => $review->source_system,
                    'source_ref'         => $review->source_ref,
                    'source_attribution' => $review->source_attribution,
                    'source_synced_at'   => now(),
                ])->save();
            }

            $review->update([
                'status'       => FacilityImportReviewStatus::Merged->value,
                'reviewed_by'  => $adminId,
                'reviewed_at'  => now(),
                'review_notes' => $notes,
            ]);

            DB::table('facility_update_audits')->insert([
                'id'              => (string) Str::uuid(),
                'facility_id'     => $listing->id,
                'actor_id'        => $adminId,
                'actor_type'      => 'admin',
                'field_changed'   => 'source_ref',
                'old_value'       => null,
                'new_value'       => $review->source_system . ':' . $review->source_ref,
                'source'          => self::SOURCE,
                'requires_review' => false,
                'created_at'      => now(),
            ]);

            return $listing;
        });
    }

    /**
     * Park the candidate without deciding it.
     *
     * The queue is 439 rows deep and a good proportion of them cannot be
     * settled from a screen — they need a phone call to the district, or
     * someone who knows the town. Without this, a reviewer's only ways to clear
     * such a row are to guess or to leave it, and guessing on a national
     * facility registry is exactly what this queue exists to prevent.
     *
     * Deferred is not terminal: `isOpen()` still holds, so accept / merge /
     * reject all remain available afterwards.
     */
    public function defer(FacilityImportReview $review, string $adminId, ?string $notes = null): FacilityImportReview
    {
        if ($review->status->isDecided()) {
            throw new \Exception('IMPORT_REVIEW_ALREADY_DECIDED');
        }

        $review->update([
            'status'       => FacilityImportReviewStatus::Deferred->value,
            'reviewed_by'  => $adminId,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);

        return $review->fresh();
    }

    /** Not a facility, or not one this directory lists. Nothing is created. */
    public function reject(FacilityImportReview $review, string $adminId, ?string $notes = null): FacilityImportReview
    {
        if ($review->status->isDecided()) {
            throw new \Exception('IMPORT_REVIEW_ALREADY_DECIDED');
        }

        $review->update([
            'status'       => FacilityImportReviewStatus::Rejected->value,
            'reviewed_by'  => $adminId,
            'reviewed_at'  => now(),
            'review_notes' => $notes,
        ]);

        return $review->fresh();
    }
}
