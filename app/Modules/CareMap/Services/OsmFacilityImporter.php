<?php

namespace App\Modules\CareMap\Services;

/**
 * Reconciles OpenStreetMap health facilities against the OpesCare directory.
 *
 * The matching engine itself now lives in ExternalFacilityImporter, because a
 * second national dataset (see CameroonMasterFacilityImporter) has to make the
 * same decisions and a second copy of these thresholds would drift from this
 * one. What stays here is what is true of OSM and of nothing else: the tag
 * vocabulary, the ODbL notice, and how an Overpass element becomes a candidate.
 * The reasoning below is still the specification for both.
 *
 * ## The asymmetry this class is built around
 *
 * `care_facilities` holds 903 rows extracted from the MINSANTE national
 * registry. They are institutional records: a name and a category the ministry
 * stands behind. What they mostly lack is the operational detail a patient
 * needs — 503 have no coordinates and so can never appear in a "near me"
 * search, none has an email, and 692 carry the literal string 'N/A' where a
 * phone number should be.
 *
 * OSM has the opposite profile: good coordinates on everything, patchy names,
 * no institutional authority. So the job is enrichment, not replacement, and
 * the two failure modes are not symmetric:
 *
 *   - Failing to enrich a row costs a patient a filter they could have used.
 *   - Merging two different hospitals, or listing one hospital twice, sends a
 *     patient to a building that cannot treat them.
 *
 * The second is much worse, and it is irreversible in the way that matters: a
 * missing coordinate is visibly missing, while a wrong merge looks exactly like
 * a correct one. Every threshold in ExternalFacilityImporter is therefore set so
 * that uncertainty produces a review row and no write at all — not a "probably
 * fine" merge and not a speculative insert.
 *
 * ## The matching rule
 *
 * A candidate is compared only against facilities of a compatible type (a
 * pharmacy is never matched to a laboratory) that are geographically plausible.
 * For each, we compute a name score (see OsmFacilityNormalizer) and, when both
 * sides have coordinates, a distance. A match is STRONG when name and place
 * corroborate each other:
 *
 *   ≤ 200 m and name ≥ 0.70   — right doorstep, recognisable name
 *   ≤ 1 km  and name ≥ 0.88   — near enough, and the name is nearly exact
 *   no coordinates on our side, same city, name ≥ 0.90
 *
 * Exactly one STRONG match updates that row. Two or more is ambiguous and goes
 * to review. Zero STRONG but something WEAK — a recognisable-ish name, or any
 * facility within 100 m — also goes to review, because "close but not
 * convincing" is precisely the case where guessing is expensive. Only a
 * candidate with nothing resembling it anywhere nearby is inserted.
 *
 * ## What is never overwritten
 *
 * An existing non-empty value always wins. The single exception is the literal
 * 'N/A' in `phone_primary`, which is not data but a placeholder the registry
 * extract left behind. Beyond the field level, a whole facility is off limits
 * when it has stopped being a stub someone else's dataset can improve:
 * verified, claimed by its operator, integrated, or linked to a live facility
 * record. And any single field a human or partner has edited — visible in
 * `facility_update_audits` — is off limits even on an otherwise open row.
 *
 * ## Provenance (ODbL)
 *
 * OSM data is ODbL-licensed: bulk extraction and redistribution are expressly
 * permitted, and attribution is the condition. Every row this class writes
 * carries `source_ref` (the OSM element), `source_attribution` (the licence
 * notice), and `source_synced_at`. Rows we CREATE additionally carry
 * `source_system = 'openstreetmap'`; rows we merely enriched keep their own
 * origin, because enrichment is not authorship. Each individual field write is
 * also recorded in `facility_update_audits`, which gives per-field attribution
 * and puts the change in front of the same audit surface as any other edit.
 *
 * The one case where this importer enriches a row WITHOUT stamping the OSM
 * reference on it is a row that already carries another dataset's reference —
 * `source_ref` is UNIQUE, and taking it would strip that dataset's attribution
 * off a row derived from it. The per-field audit rows carry the OSM element in
 * that case, which is where per-field provenance belongs anyway.
 *
 * ## Two provenances, two levels of authority
 *
 * OSM has 2,083 Cameroonian health features against our 903 rows, so most of
 * what this importer does is ADD facilities we do not have — and those rows do
 * not carry the same weight as the ones they sit beside. A MINSANTE row is a
 * licensing record from the ministry. An OSM row is a volunteer's observation.
 * Both belong in a directory; conflating them does not. An imported row is
 * therefore distinguishable three ways, all queryable:
 *
 *   source_system = 'openstreetmap'  — the row's origin
 *   license_status = 'unknown'       — we have seen no licence, and say so
 *                                      rather than defaulting to 'active'
 *   verification_status = 'unverified'
 *
 * `WHERE source_system = 'openstreetmap'` is the line between them, and a later
 * verification or claim workflow can use it to decide what still needs proving.
 */
class OsmFacilityImporter extends ExternalFacilityImporter
{
    public const SOURCE_SYSTEM = 'openstreetmap';

    /** Every `source_ref` this importer writes begins with it. */
    public const SOURCE_REF_PREFIX = 'osm:';

    /** The ODbL condition, carried by the data rather than by a template. */
    public const ATTRIBUTION = '© OpenStreetMap contributors — ODbL 1.0 (opendatacommons.org/licenses/odbl)';

    public function __construct(
        OsmFacilityNormalizer $normalizer,
        private readonly CameroonCityGazetteer $gazetteer,
    ) {
        parent::__construct($normalizer);
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
        return self::ATTRIBUTION;
    }

    /**
     * @param  array<string,mixed> $record   an Overpass element, `out center tags`
     * @param  array{dry_run?: bool, city?: array{name:string,region:string}|null} $options
     * @return array<string,mixed>|null
     */
    protected function toCandidate(mixed $record, array $options): ?array
    {
        $element     = is_array($record) ? $record : [];
        $defaultCity = $options['city'] ?? null;

        $tags = is_array($element['tags'] ?? null) ? $element['tags'] : [];
        $type = (string) ($element['type'] ?? '');
        $id   = $element['id'] ?? null;

        if ($type === '' || $id === null || $type === 'area') {
            return null;
        }

        $latitude  = $element['lat'] ?? ($element['center']['lat'] ?? null);
        $longitude = $element['lon'] ?? ($element['center']['lon'] ?? null);

        $candidate = [
            'source_ref'  => self::SOURCE_REF_PREFIX . $type . '/' . $id,
            'osm_type'    => $type,
            'tags'        => $tags,
            'payload'     => json_encode($tags),
            'name'        => $this->normalizer->displayName($tags),
            'type'        => $this->normalizer->facilityType($tags),
            'latitude'    => $latitude === null ? null : (float) $latitude,
            'longitude'   => $longitude === null ? null : (float) $longitude,
            'city'        => null,
            'region'      => null,
            'skip'        => null,
        ];

        if ($candidate['type'] === null) {
            $candidate['skip'] = 'skipped_unmapped_type';

            return $candidate;
        }

        if ($candidate['latitude'] === null || $candidate['longitude'] === null) {
            // Coordinates are the entire reason for this import.
            $candidate['skip'] = 'skipped_no_coords';

            return $candidate;
        }

        $candidate['phone']   = $this->normalizer->phone($tags['phone'] ?? $tags['contact:phone'] ?? null);
        $candidate['email']   = $this->normalizer->email($tags['email'] ?? $tags['contact:email'] ?? null);
        $candidate['website'] = $this->normalizer->website($tags['website'] ?? $tags['contact:website'] ?? null);
        $candidate['address'] = $this->normalizer->address($tags);

        // City/region: OSM's own tag if it names a town we know, else the town
        // whose radius contains the point, else the --city we were asked for.
        $resolved = null;

        if (! empty($tags['addr:city'])) {
            $resolved = $this->gazetteer->find((string) $tags['addr:city']);
        }

        if ($resolved === null) {
            $resolved = $this->gazetteer->nearest($candidate['latitude'], $candidate['longitude']);
        }

        $candidate['city']   = $resolved['name'] ?? ($defaultCity['name'] ?? null);
        $candidate['region'] = $resolved['region'] ?? ($defaultCity['region'] ?? null);

        // A way/relation coordinate is the centroid of a footprint, which for a
        // hospital compound is still the right place to send someone.
        $candidate['accuracy'] = $type === 'relation' ? 'area_level' : 'exact';

        return $candidate;
    }
}
