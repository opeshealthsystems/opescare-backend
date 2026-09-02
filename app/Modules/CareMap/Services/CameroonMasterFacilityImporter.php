<?php

namespace App\Modules\CareMap\Services;

/**
 * Reconciles the national facility master list against the OpesCare directory.
 *
 * ## What this dataset is
 *
 * 1,016 Cameroonian health facilities across all ten regions, converted from the
 * workbook the project owner supplied (`Cameroon_National_Health_Facilities_
 * Master.xlsx`) into `resources/data/cameroon_master_facilities.json`. It is
 * institutional data, not demo data: these are real buildings, they belong in
 * the public finder, and nothing here may ever be stamped `demo_seed`.
 *
 * ## What it is NOT
 *
 * Verified. The workbook has a `Verified?` column and an `Operating status
 * verified?` column and both are blank on all 1,030 of its rows, so every row
 * this importer writes stays `verification_status = 'unverified'` and
 * `last_verified_at` stays null. The underlying source is Google Maps / Google
 * Places, not MINSANTE — a facility being real is not the same as a ministry
 * standing behind its licence, and this platform must never describe its
 * directory as verified. Inserted rows also carry `license_status = 'unknown'`
 * for the same reason: we have seen no licence.
 *
 * Both facts are true at once and both are queryable:
 *
 *   source_system = 'google_places'   — the row's origin
 *   license_status = 'unknown'        — no licence seen
 *   verification_status = 'unverified'
 *
 * ## An open question this importer does not answer
 *
 * `ImportOpenStreetMapFacilities` documents the opposite decision, in terms:
 * OpenStreetMap was chosen over Google *because* the Places API "forbids storing
 * Place data or using it to build a competing directory, which is exactly what
 * OpesCare is". This dataset is Google-sourced and this importer stores Place
 * content — names, addresses, coordinates, phone numbers — indefinitely.
 *
 * That is not a contradiction this class can resolve. The workbook was supplied
 * by the project owner as institutional data and imported on their instruction,
 * and the provenance is recorded exactly as given rather than laundered:
 * `source_system = 'google_places'`, `source_ref = 'gplaces:<place_id>'`,
 * `source_attribution` from the file. Which means the question is answerable
 * later with one query, and every row it touches is reversible by the same
 * reference. Whether the licence permits the storage is a decision for the
 * owner and their counsel, not for the importer — but it should be a decision
 * someone makes, rather than one that happens.
 *
 * ## Enrich, do not duplicate
 *
 * The directory already holds 1,863 rows — 903 from the MINSANTE registry and
 * 960 from OpenStreetMap — and this list overlaps both heavily. The owner's
 * instruction was "update what already exists rather than duplicate", which is
 * exactly what ExternalFacilityImporter's matching already does, so this class
 * adds no matching of its own. It supplies a candidate and inherits the rule:
 * one strong match enriches, anything less certain goes to
 * `facility_import_reviews` for a person, and only a record with nothing
 * resembling it nearby is inserted.
 *
 * The biggest single win is the telephone. 1,571 of the 1,863 existing rows
 * carry the literal string 'N/A' in `phone_primary` — a NOT NULL column with
 * nothing to put in it — and 673 of these 1,016 records carry a real,
 * dialable number.
 *
 * ## Idempotency
 *
 * Every record has a Google Place ID and all 1,016 are distinct, so the row
 * reference is `gplaces:<place_id>` and `care_facilities.source_ref` is UNIQUE.
 * A second run finds each record by that link, computes no changes, and writes
 * nothing at all — not even `updated_at`.
 *
 * ## The 21 approximate pins
 *
 * 21 records are flagged `coordinates_approximate`: a town centroid, or a pin
 * shared with a neighbouring facility, which the workbook itself marks "needs
 * field check". Such a coordinate is inside the right town and nowhere near the
 * right doorstep, so it is treated as NO coordinate for matching purposes —
 * `candidateHasReliableGeo()` returns false and the candidate must clear the
 * stricter no-geography bar (same city, name ≥ 0.90) to match anything. It is
 * still stored when there is no coordinate at all to lose, stamped
 * `geocoding_accuracy = 'city_level'` so the imprecision travels with the data
 * instead of being forgotten. It can never overwrite a precise coordinate,
 * because the inherited rule only writes coordinates onto a row that has none.
 *
 * ## email and website
 *
 * The workbook has both columns and neither has a single value in it. They stay
 * in the enrichable map so a later regeneration of the sheet is picked up, but
 * as of this dataset they write nothing, and the importer does not pretend
 * otherwise.
 */
class CameroonMasterFacilityImporter extends ExternalFacilityImporter
{
    public const SOURCE_SYSTEM = 'google_places';

    /** Every `source_ref` this importer writes begins with it. */
    public const SOURCE_REF_PREFIX = 'gplaces:';

    /** Used only if the dataset file carries no attribution of its own. */
    public const DEFAULT_ATTRIBUTION = 'Google Maps / Google Places';

    /** `geocoding_accuracy` for the 21 pins the workbook flags as approximate. */
    public const ACCURACY_APPROXIMATE = 'city_level';

    public const ACCURACY_EXACT = 'exact';

    private string $attribution = self::DEFAULT_ATTRIBUTION;

    /**
     * @param  list<array<string,mixed>>  $records   the `facilities` array of the master JSON
     * @param  array<string,mixed>        $options   dry_run, attribution
     * @return array<string,mixed>
     */
    public function import(array $records, array $options = []): array
    {
        $attribution = trim((string) ($options['attribution'] ?? ''));

        // varchar(255), and the notice belongs on the row rather than in a
        // template footer — a truncated credit is not a credit.
        $this->attribution = $attribution === ''
            ? self::DEFAULT_ATTRIBUTION
            : mb_substr($attribution, 0, 255);

        return parent::import($records, $options);
    }

    public function sourceSystem(): string
    {
        return self::SOURCE_SYSTEM;
    }

    public function sourceRefPrefix(): string
    {
        return self::SOURCE_REF_PREFIX;
    }

    public function attribution(): string
    {
        return $this->attribution;
    }

    /**
     * The sheet carries no coordinate-free records, so there is no
     * `skipped_no_coords` bucket to fill — but a regenerated sheet might, and a
     * counter that is always zero is cheaper than a report that crashes.
     *
     * @return list<string>
     */
    protected function skipCounters(): array
    {
        return ['skipped_unmapped_type', 'skipped_no_coords'];
    }

    /**
     * What this dataset is allowed to fill in on a row it did not create.
     *
     * `city` and `address` are NOT NULL columns, so "empty" means the empty
     * string rather than null — 16 existing rows are in exactly that state. The
     * inherited rule still holds everywhere: an existing non-empty value wins,
     * and the sole exception is the 'N/A' placeholder in `phone_primary`.
     *
     * @return array<string,string>
     */
    protected function enrichableColumns(): array
    {
        return [
            'phone_primary' => 'phone',
            'email'         => 'email',
            'website'       => 'website',
            'address'       => 'address',
            'region'        => 'region',
            'city'          => 'city',
        ];
    }

    /**
     * @param  array<string,mixed> $record
     * @param  array<string,mixed> $options
     * @return array<string,mixed>|null
     */
    protected function toCandidate(mixed $record, array $options): ?array
    {
        if (! is_array($record)) {
            return null;
        }

        $placeId = trim((string) ($record['place_id'] ?? ''));

        if ($placeId === '') {
            // Without the Place ID there is no stable reference, and without a
            // stable reference a re-run would insert the row a second time.
            return null;
        }

        $name = trim((string) ($record['name'] ?? ''));
        $town = trim((string) ($record['town'] ?? ''));

        $latitude  = $this->coordinate($record['latitude'] ?? null);
        $longitude = $this->coordinate($record['longitude'] ?? null);
        $approx    = (bool) ($record['coordinates_approximate'] ?? false);

        $candidate = [
            'source_ref'           => self::SOURCE_REF_PREFIX . $placeId,
            'name'                 => $name === '' ? null : mb_substr($name, 0, 255),
            'type'                 => $this->facilityType($record),
            'latitude'             => $latitude,
            'longitude'            => $longitude,
            'coordinates_reliable' => ! $approx && $latitude !== null && $longitude !== null,
            'accuracy'             => $approx ? self::ACCURACY_APPROXIMATE : self::ACCURACY_EXACT,
            'city'                 => $town === '' ? null : mb_substr($town, 0, 255),
            'region'               => $this->text($record['region'] ?? null, 255),
            'address'              => $this->address($record),
            'phone'                => $this->normalizer->phone($this->rawPhone($record)),
            // The workbook has these columns and not one value in either.
            'email'                => null,
            'website'              => null,
            'payload'              => $this->payload($record),
            'skip'                 => null,
        ];

        if ($candidate['type'] === null) {
            $candidate['skip'] = 'skipped_unmapped_type';

            return $candidate;
        }

        if ($latitude === null || $longitude === null) {
            $candidate['skip'] = 'skipped_no_coords';

            return $candidate;
        }

        return $candidate;
    }

    /**
     * The sheet's `facility_type` is already mapped into the directory's own
     * vocabulary, so this validates rather than translates: a value the
     * normalizer cannot place in a type family cannot be matched safely against
     * anything (that is the check that stops a pharmacy merging into a lab), so
     * it is skipped rather than guessed at.
     *
     * @param  array<string,mixed> $record
     */
    private function facilityType(array $record): ?string
    {
        $type = strtolower(trim((string) ($record['facility_type'] ?? '')));

        if ($type === '' || $this->normalizer->typeFamily($type) === null) {
            return null;
        }

        return $type;
    }

    /**
     * `care_facilities` has no column for a division or a locality, so the parts
     * of the postal identity the sheet carries are folded into the one free-text
     * address line, in the order a Cameroonian address is written: street, then
     * neighbourhood, then division. A part already contained in what has been
     * built so far is dropped, because 105 of the sheet's addresses are simply
     * the town name again.
     *
     * @param  array<string,mixed> $record
     */
    private function address(array $record): ?string
    {
        $line = '';

        foreach (['address', 'locality', 'division'] as $key) {
            $part = trim((string) ($record[$key] ?? ''));

            if ($part === '' || ($line !== '' && mb_stripos($line, $part) !== false)) {
                continue;
            }

            $line = $line === '' ? $part : $line . ', ' . $part;
        }

        if ($line === '') {
            $line = trim((string) ($record['town'] ?? ''));
        }

        return $line === '' ? null : $line;
    }

    /**
     * The sheet's `phone` is already E.164; `phone_raw` is what the operator
     * typed. Both go through the directory's own normaliser, which renders
     * '+237 6XXXXXXXX' — the form the existing rows use — and rejects anything
     * that is not a dialable Cameroonian number rather than storing a string
     * that rings nowhere.
     *
     * @param  array<string,mixed> $record
     */
    private function rawPhone(array $record): ?string
    {
        foreach (['phone', 'phone_raw'] as $key) {
            $value = trim((string) ($record[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * What a reviewer sees when this record lands in the queue. Everything the
     * sheet knows that the candidate itself does not carry — the Google Maps
     * link most of all, which is how a person settles "is this the same
     * building?" in about ten seconds.
     *
     * @param  array<string,mixed> $record
     */
    private function payload(array $record): string
    {
        $keys = [
            'place_id', 'name', 'category', 'category_group', 'facility_type',
            'town', 'division', 'locality', 'region', 'region_source', 'address',
            'latitude', 'longitude', 'coordinate_precision', 'coordinates_approximate',
            'phone', 'phone_raw', 'maps_url', 'source_file', 'notes',
        ];

        $payload = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $record) && $record[$key] !== '' && $record[$key] !== null) {
                $payload[$key] = $record[$key];
            }
        }

        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function coordinate(mixed $value): ?float
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function text(mixed $value, int $limit): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }
}
