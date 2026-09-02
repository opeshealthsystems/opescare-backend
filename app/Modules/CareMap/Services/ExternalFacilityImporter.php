<?php

namespace App\Modules\CareMap\Services;

use App\Models\CareFacility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reconciling an outside dataset against the OpesCare facility directory.
 *
 * This class is the OpenStreetMap importer's matching engine, lifted out so a
 * second dataset can drive it instead of copying it. The reasoning it encodes
 * was written for OSM (see OsmFacilityImporter's docblock, which remains the
 * specification) and none of it is OSM-specific: it is about what may be done
 * to a national health directory by a dataset that has no authority over it.
 *
 * ## The asymmetry every threshold is set against
 *
 *   - Failing to enrich a row costs a patient a filter they could have used.
 *   - Merging two different hospitals, or listing one hospital twice, sends a
 *     patient to a building that cannot treat them.
 *
 * The second is much worse, and it is irreversible in the way that matters: a
 * missing coordinate is visibly missing, while a wrong merge looks exactly like
 * a correct one. So uncertainty produces a review row and no write at all.
 *
 * ## The matching rule
 *
 * A candidate is compared only against facilities of a compatible type (a
 * pharmacy is never matched to a laboratory) that are geographically plausible.
 * A match is STRONG when name and place corroborate each other:
 *
 *   ≤ 200 m and name ≥ 0.70   — right doorstep, recognisable name
 *   ≤ 1 km  and name ≥ 0.88   — near enough, and the name is nearly exact
 *   no usable coordinates, same city, name ≥ 0.90
 *
 * Exactly one STRONG match updates that row. Two or more is ambiguous and goes
 * to review. Zero STRONG but something WEAK — a recognisable-ish name, or any
 * facility within 100 m — also goes to review. Only a candidate with nothing
 * resembling it anywhere nearby is inserted.
 *
 * ## What a subclass supplies
 *
 * Everything that differs between datasets, and nothing else:
 *
 *   sourceSystem()       'openstreetmap' | 'google_places'
 *   sourceRefPrefix()    'osm:'          | 'gplaces:'
 *   attribution()        the licence / credit notice carried onto every row
 *   toCandidate()        one upstream record → the shape below
 *   enrichableColumns()  which columns this dataset is allowed to fill
 *
 * A candidate is an array with: source_ref, name, type, latitude, longitude,
 * city, region, address, phone, email, website, accuracy, payload (a JSON
 * string for the review queue), coordinates_reliable (bool), skip (a counter
 * key, or null).
 *
 * ## Two source systems, one directory
 *
 * `care_facilities.source_ref` is UNIQUE, so a row can carry the reference of
 * exactly one upstream record. When a second dataset recognises a row that
 * already belongs to another dataset's element, it enriches the row's empty
 * fields but does NOT take the reference or the attribution — those describe
 * where the row came from, and enrichment is not authorship. The fields it did
 * write are attributed individually in `facility_update_audits`, which is where
 * per-field provenance lives anyway.
 *
 * The "already linked" review reason is therefore scoped to THIS source: two
 * OSM elements landing on one row is a genuine ambiguity (duplicate mapping, or
 * a second branch?) and a human has to settle it. A Google record landing on a
 * row an OSM element created is not ambiguous at all — it is the ordinary case
 * of two datasets describing one building.
 */
abstract class ExternalFacilityImporter
{
    /** The placeholder the MINSANTE extract left in phone_primary. Not data. */
    public const PHONE_PLACEHOLDER = CareFacility::PHONE_PLACEHOLDER;

    // ── Match thresholds ────────────────────────────────────────────────────
    protected const STRONG_NEAR_METRES   = 200;
    protected const STRONG_NEAR_NAME     = 0.70;
    protected const STRONG_FAR_METRES    = 1000;
    protected const STRONG_FAR_NAME      = 0.88;
    protected const STRONG_NOGEO_NAME    = 0.90;

    /** Below a STRONG match but too close to ignore — a human decides. */
    protected const WEAK_NAME            = 0.55;
    protected const WEAK_COLOCATED_M     = 100;

    /** Only compare against facilities this far away or nearer. */
    protected const POOL_RADIUS_METRES   = 25000;

    // ── Review reasons ──────────────────────────────────────────────────────
    public const REASON_GENERIC_NAME     = 'generic_name';
    public const REASON_UNNAMED          = 'unnamed_element';
    public const REASON_UNCERTAIN_MATCH  = 'uncertain_match';
    public const REASON_MULTIPLE_MATCHES = 'multiple_matches';
    public const REASON_TYPE_CONFLICT    = 'type_conflict';
    public const REASON_UNRESOLVED_CITY  = 'unresolved_city';
    public const REASON_ALREADY_LINKED   = 'already_linked_to_other_element';

    /** The by-region bucket for a candidate whose region we could not name. */
    public const REGION_UNKNOWN = '';

    /** @var list<array<string,mixed>> in-memory mirror of the directory */
    protected array $pool = [];

    /** @var array<string,string> source_ref => facility id (every source system) */
    protected array $bySourceRef = [];

    /** @var array<string,string> facility id => the source_ref stamped on the row */
    protected array $linkedRefByFacility = [];

    /** @var array<string,string> facility id => the ref from THIS source bound to it */
    protected array $ownRefByFacility = [];

    /** @var array<string,true> facility ids that own their own data */
    protected array $protectedFacilities = [];

    /** @var array<string,true> "{facilityId}|{field}" a human or partner has edited */
    protected array $humanEditedFields = [];

    /** @var array<string,array{status:string,fingerprint:string,id:string}> source_ref => existing review row */
    protected array $existingReviews = [];

    public function __construct(
        protected readonly OsmFacilityNormalizer $normalizer,
    ) {
    }

    // ── What a subclass must answer ─────────────────────────────────────────

    /** The value written to `source_system` on rows this importer CREATES. */
    abstract public function sourceSystem(): string;

    /** The prefix that marks a `source_ref` as belonging to this importer. */
    abstract public function sourceRefPrefix(): string;

    /** The licence / credit notice carried onto every row this importer writes. */
    abstract public function attribution(): string;

    /**
     * One upstream record → a candidate, or null if it is not a record at all.
     *
     * @param  mixed                $record
     * @param  array<string,mixed>  $options
     * @return array<string,mixed>|null
     */
    abstract protected function toCandidate(mixed $record, array $options): ?array;

    /**
     * Extra `skip` counters this importer can report, zeroed up front so the
     * command's report never has to guard for a missing key.
     *
     * @return list<string>
     */
    protected function skipCounters(): array
    {
        return ['skipped_unmapped_type', 'skipped_no_coords'];
    }

    /**
     * Columns this dataset may fill on an EXISTING row, as column => candidate
     * key. Coordinates are handled separately because they move as a pair.
     *
     * @return array<string,string>
     */
    protected function enrichableColumns(): array
    {
        return ['phone_primary' => 'phone', 'email' => 'email', 'website' => 'website'];
    }

    /**
     * Whether a candidate's coordinates are good enough to be used as evidence
     * that it is the same building as an existing row. A city-centroid pin is
     * inside the right town and nowhere near the right doorstep, so it must not
     * corroborate a distance match.
     *
     * @param  array<string,mixed> $candidate
     */
    protected function candidateHasReliableGeo(array $candidate): bool
    {
        return $candidate['latitude'] !== null
            && $candidate['longitude'] !== null
            && ($candidate['coordinates_reliable'] ?? true);
    }

    // ── The run ─────────────────────────────────────────────────────────────

    /**
     * @param  list<mixed>          $records
     * @param  array<string,mixed>  $options   at least ['dry_run' => bool]
     * @return array<string,mixed>  counters
     */
    public function import(array $records, array $options = []): array
    {
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $this->loadDirectory();

        $counts = array_merge([
            'elements'          => count($records),
            'inserted'          => 0,
            'updated'           => 0,
            'matched_unchanged' => 0,
            'skipped_protected' => 0,
            'review'            => 0,
            'review_by_reason'  => [],
            'fields_written'    => [],
            'by_region'         => [],
        ], array_fill_keys($this->skipCounters(), 0));

        foreach ($records as $record) {
            $candidate = $this->toCandidate($record, $options);

            if ($candidate === null) {
                continue;
            }

            if (($candidate['skip'] ?? null) !== null) {
                $counts[$candidate['skip']]++;
                $this->tally($candidate, $candidate['skip'], $counts);

                continue;
            }

            $this->processCandidate($candidate, $dryRun, $counts);
        }

        return $counts;
    }

    // ── Decision + write ────────────────────────────────────────────────────

    /**
     * @param  array<string,mixed> $candidate
     * @param  array<string,mixed> $counts
     */
    protected function processCandidate(array $candidate, bool $dryRun, array &$counts): void
    {
        // 1. Already linked to a row from an earlier run — definitive, no fuzzy
        //    matching. This is the hard floor under idempotency.
        $linkedId = $this->bySourceRef[$candidate['source_ref']] ?? null;

        if ($linkedId !== null) {
            $existing = $this->poolEntry($linkedId);

            if ($existing !== null) {
                $this->applyUpdate($candidate, $existing, $dryRun, $counts);

                return;
            }
        }

        // 2. A human has already ruled on this record — do not re-litigate.
        $review = $this->existingReviews[$candidate['source_ref']] ?? null;

        if ($review !== null && $review['status'] !== 'pending') {
            $counts['review_by_reason']['already_decided'] =
                ($counts['review_by_reason']['already_decided'] ?? 0) + 1;

            return;
        }

        // 3. No name at all. Name similarity is the only evidence that two
        //    records describe the same building; without it, proximity alone
        //    would merge a nameless record into whichever facility happened to
        //    be nearest, and inserting it would put a nameless row in a
        //    directory people search by name.
        if ($candidate['name'] === null) {
            $this->queueForReview(
                $candidate,
                self::REASON_UNNAMED,
                $this->nearestCompatible($candidate),
                $dryRun,
                $counts
            );

            return;
        }

        // 4. A name that names a category, not a facility.
        if ($this->normalizer->isGenericName($candidate['name'])) {
            $this->queueForReview($candidate, self::REASON_GENERIC_NAME, null, $dryRun, $counts);

            return;
        }

        // 5. No city we can stand behind, and `city` is NOT NULL.
        if ($candidate['city'] === null) {
            $this->queueForReview($candidate, self::REASON_UNRESOLVED_CITY, null, $dryRun, $counts);

            return;
        }

        [$strong, $weak, $conflict] = $this->scorePool($candidate);

        if (count($strong) > 1) {
            $this->queueForReview($candidate, self::REASON_MULTIPLE_MATCHES, $strong[0], $dryRun, $counts);

            return;
        }

        if (count($strong) === 1) {
            $facility = $strong[0]['facility'];
            $boundTo  = $this->ownRefByFacility[$facility['id']] ?? null;

            // Two records FROM THIS DATASET resolving to one directory row.
            // Either the dataset duplicates the facility or it is a second
            // branch of the same business — opposite treatment, and the record
            // cannot say which. Absorbing it loses a real location; inserting it
            // duplicates one. Neither is done.
            if ($boundTo !== null && $boundTo !== $candidate['source_ref']) {
                $this->queueForReview($candidate, self::REASON_ALREADY_LINKED, $strong[0], $dryRun, $counts);

                return;
            }

            $this->applyUpdate($candidate, $facility, $dryRun, $counts);

            return;
        }

        if ($conflict !== null) {
            // Same name, same doorstep, incompatible category. Either the
            // dataset or we have mistyped it; a machine cannot tell which.
            $this->queueForReview($candidate, self::REASON_TYPE_CONFLICT, $conflict, $dryRun, $counts);

            return;
        }

        if ($weak !== null) {
            $this->queueForReview($candidate, self::REASON_UNCERTAIN_MATCH, $weak, $dryRun, $counts);

            return;
        }

        $this->applyInsert($candidate, $dryRun, $counts);
    }

    /**
     * Compare a candidate against every plausible existing facility.
     *
     * @param  array<string,mixed> $candidate
     * @return array{0: list<array<string,mixed>>, 1: array<string,mixed>|null, 2: array<string,mixed>|null}
     */
    protected function scorePool(array $candidate): array
    {
        $strong      = [];
        $weak        = null;
        $conflict    = null;
        $candidateGeo = $this->candidateHasReliableGeo($candidate);

        foreach ($this->pool as $facility) {
            $distance = null;

            if ($candidateGeo && $facility['latitude'] !== null && $facility['longitude'] !== null) {
                $distance = CameroonCityGazetteer::haversineMetres(
                    $candidate['latitude'],
                    $candidate['longitude'],
                    $facility['latitude'],
                    $facility['longitude'],
                );

                if ($distance > self::POOL_RADIUS_METRES) {
                    continue;
                }
            } elseif ($this->foldEquals($facility['city'], $candidate['city']) === false) {
                // No usable geography on one side or the other, and a different
                // city: nothing to compare on.
                continue;
            }

            $score = $this->normalizer->nameSimilarity($candidate['name'], $facility['facility_name']);

            $hit = [
                'facility'   => $facility,
                'score'      => $score,
                'distance_m' => $distance === null ? null : (int) round($distance),
            ];

            if (! $this->normalizer->typesAreCompatible($candidate['type'], $facility['facility_type'])) {
                // Wrong family — never a match. But an all-but-identical name on
                // the same doorstep is worth a human's attention.
                if ($score >= self::STRONG_NEAR_NAME
                    && $distance !== null
                    && $distance <= self::STRONG_NEAR_METRES
                    && ($conflict === null || $score > $conflict['score'])) {
                    $conflict = $hit;
                }

                continue;
            }

            if ($this->isStrong($score, $distance, $candidate, $facility)) {
                $strong[] = $hit;

                continue;
            }

            $isWeak = $score >= self::WEAK_NAME
                   || ($distance !== null && $distance <= self::WEAK_COLOCATED_M);

            if ($isWeak && ($weak === null || $score > $weak['score'])) {
                $weak = $hit;
            }
        }

        usort($strong, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return [$strong, $weak, $conflict];
    }

    /**
     * The closest facility of a compatible type — context for a reviewer
     * looking at an unnamed record, never grounds for a merge. A candidate whose
     * own pin is a town centroid has no "closest" worth showing.
     *
     * @param  array<string,mixed> $candidate
     * @return array<string,mixed>|null
     */
    protected function nearestCompatible(array $candidate): ?array
    {
        if (! $this->candidateHasReliableGeo($candidate)) {
            return null;
        }

        $nearest = null;

        foreach ($this->pool as $facility) {
            if ($facility['latitude'] === null || $facility['longitude'] === null) {
                continue;
            }

            if (! $this->normalizer->typesAreCompatible($candidate['type'], $facility['facility_type'])) {
                continue;
            }

            $distance = CameroonCityGazetteer::haversineMetres(
                $candidate['latitude'],
                $candidate['longitude'],
                $facility['latitude'],
                $facility['longitude'],
            );

            if ($distance > self::POOL_RADIUS_METRES) {
                continue;
            }

            if ($nearest === null || $distance < $nearest['distance_m']) {
                $nearest = [
                    'facility'   => $facility,
                    'score'      => 0.0,
                    'distance_m' => (int) round($distance),
                ];
            }
        }

        return $nearest;
    }

    /**
     * @param  array<string,mixed> $candidate
     * @param  array<string,mixed> $facility
     */
    protected function isStrong(float $score, ?float $distance, array $candidate, array $facility): bool
    {
        if ($distance !== null) {
            return ($distance <= self::STRONG_NEAR_METRES && $score >= self::STRONG_NEAR_NAME)
                || ($distance <= self::STRONG_FAR_METRES && $score >= self::STRONG_FAR_NAME);
        }

        // No geography to corroborate the name on one side or the other, so the
        // name has to be near-exact and the city has to agree.
        return $score >= self::STRONG_NOGEO_NAME
            && $this->foldEquals($facility['city'], $candidate['city']);
    }

    /**
     * @param  array<string,mixed> $candidate
     * @param  array<string,mixed> $facility
     * @param  array<string,mixed> $counts
     */
    protected function applyUpdate(array $candidate, array $facility, bool $dryRun, array &$counts): void
    {
        if (isset($this->protectedFacilities[$facility['id']])) {
            $counts['skipped_protected']++;
            $this->tally($candidate, 'skipped_protected', $counts);

            return;
        }

        $changes = [];

        // Coordinates move as a pair or not at all — half a fix is a wrong pin.
        if ($facility['latitude'] === null && $facility['longitude'] === null
            && $candidate['latitude'] !== null && $candidate['longitude'] !== null
            && ! $this->isHumanEdited($facility['id'], 'latitude')
            && ! $this->isHumanEdited($facility['id'], 'longitude')) {
            $changes['latitude']           = $candidate['latitude'];
            $changes['longitude']          = $candidate['longitude'];
            $changes['geocoding_accuracy'] = $candidate['accuracy'];
        }

        foreach ($this->enrichableColumns() as $column => $key) {
            $incoming = $candidate[$key] ?? null;

            if ($incoming === null || trim((string) $incoming) === '' || ! $this->isWritable($facility, $column)) {
                continue;
            }

            $changes[$column] = $incoming;
        }

        // Whether this row's `source_ref` is ours to claim. It is UNIQUE, so a
        // row already carrying another dataset's reference keeps it — along with
        // that dataset's attribution.
        $stamped   = $this->linkedRefByFacility[$facility['id']] ?? null;
        $claimable = $stamped === null || $stamped === $candidate['source_ref'];

        if ($changes === []) {
            // Nothing this dataset knows that we do not. Writing provenance
            // anyway would churn `updated_at` on every run for no gain, and we
            // took nothing from it, so there is nothing to attribute.
            //
            // The link is still recorded IN MEMORY (no write), so that a second
            // record landing on this same row later in the run is caught by the
            // already-linked check rather than quietly vanishing.
            $this->ownRefByFacility[$facility['id']] ??= $candidate['source_ref'];
            $counts['matched_unchanged']++;
            $this->tally($candidate, 'matched_unchanged', $counts);

            return;
        }

        foreach (array_keys($changes) as $column) {
            $counts['fields_written'][$column] = ($counts['fields_written'][$column] ?? 0) + 1;
        }

        $counts['updated']++;
        $this->tally($candidate, 'updated', $counts);

        if ($dryRun) {
            $this->rememberInPool(array_merge($facility, $changes), $candidate['source_ref'], $claimable);

            return;
        }

        DB::transaction(function () use ($facility, $changes, $candidate, $claimable) {
            $model = CareFacility::find($facility['id']);

            if ($model === null) {
                return;
            }

            $before = $model->only(array_keys($changes));

            $provenance = $claimable ? [
                'source_ref'         => $candidate['source_ref'],
                'source_attribution' => $this->attribution(),
                'source_synced_at'   => now(),
            ] : [];

            $model->forceFill(array_merge($changes, $provenance))->save();

            // Per-field attribution, on the same audit trail as every other edit
            // to a facility profile — which is how a row enriched by a dataset
            // that does not own its `source_ref` still says where each value
            // came from.
            $this->auditFieldWrites($facility['id'], $before, $changes, $candidate['source_ref']);
        });

        $this->rememberInPool(array_merge($facility, $changes), $candidate['source_ref'], $claimable);
    }

    /**
     * @param  array<string,mixed> $candidate
     * @param  array<string,mixed> $counts
     */
    protected function applyInsert(array $candidate, bool $dryRun, array &$counts): void
    {
        $counts['inserted']++;
        $this->tally($candidate, 'inserted', $counts);

        $row = [
            'id'                  => (string) Str::uuid(),
            'facility_name'       => $candidate['name'],
            'facility_type'       => $candidate['type'],
            'country_code'        => 'CMR',
            'region'              => $candidate['region'],
            'city'                => $candidate['city'],
            'address'             => $candidate['address'] ?? $candidate['city'],
            'latitude'            => $candidate['latitude'],
            'longitude'           => $candidate['longitude'],
            'geocoding_accuracy'  => $candidate['accuracy'],
            // NOT NULL with no default. 'N/A' is the placeholder the existing
            // rows already use, so the same cleanup pass finds all of them.
            'phone_primary'       => $candidate['phone'] ?? self::PHONE_PLACEHOLDER,
            'email'               => $candidate['email'] ?? null,
            'website'             => $candidate['website'] ?? null,
            // NOT 'active'. The registry rows come from the MINSANTE licensing
            // registry, where 'active' is a statement the ministry stands behind.
            // We have seen no licence for this row, and the column default would
            // have us assert one — so it says so.
            'license_status'      => 'unknown',
            'verification_status' => 'unverified',
            'listing_status'      => 'active',
            'integration_status'  => 'none',
            'source_system'       => $this->sourceSystem(),
            'source_ref'          => $candidate['source_ref'],
            'source_attribution'  => $this->attribution(),
            'source_synced_at'    => now(),
        ];

        if (! $dryRun) {
            DB::transaction(function () use ($row) {
                // Through the model, so facility_code is generated the same way
                // it is for every other facility in the table.
                $facility = new CareFacility();
                $facility->forceFill($row)->save();
            });
        }

        $this->rememberInPool([
            'id'            => $row['id'],
            'facility_name' => $row['facility_name'],
            'facility_type' => $row['facility_type'],
            'city'          => $row['city'],
            'region'        => $row['region'],
            'latitude'      => $row['latitude'],
            'longitude'     => $row['longitude'],
            'phone_primary' => $row['phone_primary'],
            'email'         => $row['email'],
            'website'       => $row['website'],
            'address'       => $row['address'],
        ], $candidate['source_ref'], true);
    }

    /**
     * Park a candidate for a human. Nothing is inserted and nothing is merged.
     *
     * @param  array<string,mixed>      $candidate
     * @param  array<string,mixed>|null $match
     * @param  array<string,mixed>      $counts
     */
    protected function queueForReview(array $candidate, string $reason, ?array $match, bool $dryRun, array &$counts): void
    {
        $counts['review']++;
        $counts['review_by_reason'][$reason] = ($counts['review_by_reason'][$reason] ?? 0) + 1;
        $this->tally($candidate, 'review', $counts);

        if ($dryRun) {
            return;
        }

        $payload = [
            'source_system'         => $this->sourceSystem(),
            'source_ref'            => $candidate['source_ref'],
            'source_attribution'    => $this->attribution(),
            'reason'                => $reason,
            'candidate_name'        => $candidate['name'],
            'candidate_type'        => $candidate['type'],
            'candidate_city'        => $candidate['city'],
            'candidate_region'      => $candidate['region'],
            'latitude'              => $candidate['latitude'],
            'longitude'             => $candidate['longitude'],
            'payload'               => $candidate['payload'] ?? null,
            'matched_facility_id'   => $match['facility']['id'] ?? null,
            'matched_facility_name' => $match['facility']['facility_name'] ?? null,
            'match_score'           => $match['score'] ?? null,
            'match_distance_m'      => $match['distance_m'] ?? null,
        ];

        $existing    = $this->existingReviews[$candidate['source_ref']] ?? null;
        $fingerprint = $this->fingerprint($payload);

        if ($existing === null) {
            DB::table('facility_import_reviews')->insert(array_merge($payload, [
                'id'         => (string) Str::uuid(),
                'status'     => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            $this->existingReviews[$candidate['source_ref']] = [
                'id'          => $payload['source_ref'],
                'status'      => 'pending',
                'fingerprint' => $fingerprint,
            ];

            return;
        }

        // Re-running must not churn: only write when the dataset actually changed.
        if ($existing['fingerprint'] === $fingerprint) {
            return;
        }

        DB::table('facility_import_reviews')
            ->where('id', $existing['id'])
            ->update(array_merge($payload, ['updated_at' => now()]));

        $this->existingReviews[$candidate['source_ref']]['fingerprint'] = $fingerprint;
    }

    // ── Write policy ────────────────────────────────────────────────────────

    /**
     * @param  array<string,mixed> $facility
     */
    protected function isWritable(array $facility, string $column): bool
    {
        if ($this->isHumanEdited($facility['id'], $column)) {
            return false;
        }

        $current = $facility[$column] ?? null;

        if ($current === null || trim((string) $current) === '') {
            return true;
        }

        // The one documented exception: 'N/A' is a placeholder the registry
        // extract left in a NOT NULL column, not a phone number anyone can dial.
        return $column === 'phone_primary'
            && strcasecmp(trim((string) $current), self::PHONE_PLACEHOLDER) === 0;
    }

    protected function isHumanEdited(string $facilityId, string $column): bool
    {
        return isset($this->humanEditedFields[$facilityId . '|' . $column]);
    }

    /**
     * Per-field provenance, on the same audit trail as every other edit to a
     * facility profile — so "where did this phone number come from?" has one
     * answer, whoever asks it.
     *
     * @param  array<string,mixed> $before
     * @param  array<string,mixed> $changes
     */
    protected function auditFieldWrites(string $facilityId, array $before, array $changes, string $sourceRef): void
    {
        $rows = [];

        foreach ($changes as $column => $value) {
            $rows[] = [
                'id'             => (string) Str::uuid(),
                'facility_id'    => $facilityId,
                'actor_id'       => null,
                'actor_type'     => 'system',
                'field_changed'  => $column,
                'old_value'      => ($before[$column] ?? null) === null ? null : (string) $before[$column],
                'new_value'      => $value === null ? null : (string) $value,
                'source'         => $sourceRef,
                'requires_review'=> false,
                'created_at'     => now(),
            ];
        }

        if ($rows !== []) {
            DB::table('facility_update_audits')->insert($rows);
        }
    }

    // ── Directory snapshot ──────────────────────────────────────────────────

    /**
     * The directory is small enough to hold in memory, and holding it means
     * every candidate is compared against the directory as it stands *including
     * rows inserted earlier in this same run* — otherwise two records for one
     * pharmacy become two directory entries.
     */
    protected function loadDirectory(): void
    {
        $this->pool = [];
        $this->bySourceRef = [];
        $this->linkedRefByFacility = [];
        $this->ownRefByFacility = [];
        $this->protectedFacilities = [];
        $this->humanEditedFields = [];

        DB::table('care_facilities')
            ->select([
                'id', 'facility_name', 'facility_type', 'city', 'region',
                'latitude', 'longitude', 'phone_primary', 'email', 'website',
                'address', 'verification_status', 'integration_status', 'facility_id',
                'partner_id', 'organization_id', 'source_ref', 'listing_status',
            ])
            ->orderBy('id')
            ->chunk(1000, function ($rows) {
                foreach ($rows as $row) {
                    $facility = [
                        'id'            => $row->id,
                        'facility_name' => $row->facility_name,
                        'facility_type' => $row->facility_type,
                        'city'          => $row->city,
                        'region'        => $row->region,
                        'address'       => $row->address,
                        'latitude'      => $row->latitude === null ? null : (float) $row->latitude,
                        'longitude'     => $row->longitude === null ? null : (float) $row->longitude,
                        'phone_primary' => $row->phone_primary,
                        'email'         => $row->email,
                        'website'       => $row->website,
                    ];

                    $this->pool[] = $facility;

                    if ($row->source_ref !== null) {
                        $this->bySourceRef[$row->source_ref] = $row->id;
                        $this->linkedRefByFacility[$row->id] = $row->source_ref;

                        if ($this->isOwnRef($row->source_ref)) {
                            $this->ownRefByFacility[$row->id] = $row->source_ref;
                        }
                    }

                    if ($this->facilityOwnsItsData($row)) {
                        $this->protectedFacilities[$row->id] = true;
                    }
                }
            });

        $this->loadProtectedByClaim();
        $this->loadHumanEditedFields();
        $this->loadExistingReviews();
    }

    /**
     * A row that has stopped being an unverified stub. Anything here is beyond
     * the reach of an external dataset.
     */
    protected function facilityOwnsItsData(object $row): bool
    {
        // `care_facilities.verification_status` is a plain string column with no
        // enum cast on the model, so this compares strings deliberately —
        // 'unverified' is the only status an importer may write over.
        if (trim((string) $row->verification_status) !== 'unverified') {
            return true;
        }

        // Connected to a live system, or owned by a partner/organisation: its
        // own systems are the authority on its contact details. `partner_id` is
        // what FacilityClaimService::approveClaim() stamps when an operator's
        // claim is granted, so this covers claimed listings too.
        //
        // NOTE: `care_facilities.facility_id` is deliberately NOT in this list,
        // and that is not an oversight. It is the booking link
        // (care_facilities -> facilities -> appointment_slots) that
        // BookableFacilityNetworkSeeder writes in bulk, by type and region,
        // creating the operational tenant itself from the registry row. It means
        // "this listing has appointment slots", not "somebody maintains this
        // listing" — and it is set on rows with no coordinates and placeholder
        // phones, which are precisely the rows an import exists to repair.
        return $row->integration_status !== 'none'
            || $row->partner_id !== null
            || $row->organization_id !== null;
    }

    /**
     * Facilities whose operator has had a claim approved.
     *
     * `facility_claims.facility_id` is read BOTH ways on purpose. Its foreign
     * key points at `care_facilities.id`, but FacilityClaimService's CareMap
     * flow looks it up as `CareFacility::where('facility_id', ...)` — i.e. as an
     * operational `facilities.id`. The two readings disagree, and resolving that
     * ambiguity is not an importer's job. Protecting the union of both costs
     * nothing and cannot leave a claimed facility exposed.
     */
    protected function loadProtectedByClaim(): void
    {
        $claimed = DB::table('facility_claims')
            ->where('claim_status', 'approved')
            ->distinct()
            ->pluck('facility_id')
            ->filter()
            ->all();

        if ($claimed === []) {
            return;
        }

        foreach ($claimed as $id) {
            $this->protectedFacilities[$id] = true;
        }

        DB::table('care_facilities')
            ->whereIn('facility_id', $claimed)
            ->pluck('id')
            ->each(function ($id) {
                $this->protectedFacilities[$id] = true;
            });
    }

    /**
     * Any field a person or a partner system has edited is theirs. The audit
     * table already records exactly this, so no new bookkeeping is invented.
     */
    protected function loadHumanEditedFields(): void
    {
        DB::table('facility_update_audits')
            ->select('facility_id', 'field_changed')
            ->whereIn('actor_type', ['user', 'api_partner', 'facility', 'admin'])
            ->distinct()
            ->orderBy('facility_id')
            ->orderBy('field_changed')
            ->chunk(5000, function ($rows) {
                foreach ($rows as $row) {
                    $this->humanEditedFields[$row->facility_id . '|' . $row->field_changed] = true;
                }
            });
    }

    protected function loadExistingReviews(): void
    {
        $this->existingReviews = [];

        DB::table('facility_import_reviews')
            ->where('source_system', $this->sourceSystem())
            ->select([
                'id', 'source_ref', 'status', 'reason', 'source_attribution',
                'candidate_name', 'candidate_type', 'candidate_city', 'candidate_region',
                'latitude', 'longitude', 'payload', 'matched_facility_id',
                'matched_facility_name', 'match_score', 'match_distance_m', 'source_system',
            ])
            ->orderBy('id')
            ->chunk(2000, function ($rows) {
                foreach ($rows as $row) {
                    $this->existingReviews[$row->source_ref] = [
                        'id'          => $row->id,
                        'status'      => $row->status,
                        'fingerprint' => $this->fingerprint([
                            'source_system'         => $row->source_system,
                            'source_ref'            => $row->source_ref,
                            'source_attribution'    => $row->source_attribution,
                            'reason'                => $row->reason,
                            'candidate_name'        => $row->candidate_name,
                            'candidate_type'        => $row->candidate_type,
                            'candidate_city'        => $row->candidate_city,
                            'candidate_region'      => $row->candidate_region,
                            'latitude'              => $row->latitude === null ? null : (float) $row->latitude,
                            'longitude'             => $row->longitude === null ? null : (float) $row->longitude,
                            'payload'               => $row->payload,
                            'matched_facility_id'   => $row->matched_facility_id,
                            'matched_facility_name' => $row->matched_facility_name,
                            'match_score'           => $row->match_score === null ? null : (float) $row->match_score,
                            'match_distance_m'      => $row->match_distance_m,
                        ]),
                    ];
                }
            });
    }

    /**
     * The fingerprint that decides whether a pending review row needs rewriting.
     *
     * `payload` is a jsonb column, and PostgreSQL stores jsonb as a parsed
     * structure: it drops insignificant whitespace and reorders object keys (by
     * key length, then bytewise). What comes back out is therefore rarely the
     * byte string that went in, so comparing the raw strings reports a change on
     * every run and rewrites every pending review row forever. Both sides are
     * canonicalised — decoded, recursively key-sorted, re-encoded — so the
     * comparison is of the payload's MEANING, which is what "did the upstream
     * record change?" actually asks.
     *
     * The numeric columns need the same treatment for the same reason. A name
     * score is computed to four decimal places and `match_score` is
     * numeric(4,3), so 0.7708 is stored as 0.771 and comes back as a value the
     * importer never produced; `latitude`/`longitude` are numeric(_,8). Rounding
     * both sides to what the column can actually hold is what makes "unchanged"
     * mean unchanged — otherwise every review row with a match is rewritten on
     * every run, forever.
     *
     * @param  array<string,mixed> $payload
     */
    protected function fingerprint(array $payload): string
    {
        $payload['payload'] = $this->canonicalJson($payload['payload'] ?? null);

        $payload['match_score'] = $this->rounded($payload['match_score'] ?? null, 3);
        $payload['latitude']    = $this->rounded($payload['latitude'] ?? null, 8);
        $payload['longitude']   = $this->rounded($payload['longitude'] ?? null, 8);

        return md5(serialize($payload));
    }

    private function rounded(mixed $value, int $precision): ?float
    {
        return $value === null ? null : round((float) $value, $precision);
    }

    protected function canonicalJson(?string $json): ?string
    {
        if ($json === null || $json === '') {
            return $json;
        }

        $decoded = json_decode($json, true);

        if ($decoded === null) {
            return $json;
        }

        $sort = function (mixed $value) use (&$sort): mixed {
            if (! is_array($value)) {
                return $value;
            }

            $value = array_map($sort, $value);

            if (! array_is_list($value)) {
                ksort($value);
            }

            return $value;
        };

        return json_encode($sort($decoded));
    }

    /** @return array<string,mixed>|null */
    protected function poolEntry(string $id): ?array
    {
        foreach ($this->pool as $facility) {
            if ($facility['id'] === $id) {
                return $facility;
            }
        }

        return null;
    }

    /**
     * @param  array<string,mixed> $facility
     * @param  bool $claimed  whether the row now carries this source_ref in the DB
     */
    protected function rememberInPool(array $facility, string $sourceRef, bool $claimed): void
    {
        $this->bySourceRef[$sourceRef] = $facility['id'];
        $this->ownRefByFacility[$facility['id']] ??= $sourceRef;

        if ($claimed) {
            $this->linkedRefByFacility[$facility['id']] ??= $sourceRef;
        }

        foreach ($this->pool as $index => $existing) {
            if ($existing['id'] === $facility['id']) {
                $this->pool[$index] = $facility;

                return;
            }
        }

        $this->pool[] = $facility;
    }

    protected function isOwnRef(string $sourceRef): bool
    {
        return str_starts_with($sourceRef, $this->sourceRefPrefix());
    }

    /**
     * @param  array<string,mixed> $candidate
     * @param  array<string,mixed> $counts
     */
    protected function tally(array $candidate, string $outcome, array &$counts): void
    {
        $region = $candidate['region'] ?? null;
        $region = ($region === null || trim((string) $region) === '') ? self::REGION_UNKNOWN : (string) $region;

        $counts['by_region'][$region][$outcome] = ($counts['by_region'][$region][$outcome] ?? 0) + 1;
    }

    protected function foldEquals(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null) {
            return false;
        }

        return Str::lower(Str::ascii(trim($a))) === Str::lower(Str::ascii(trim($b)));
    }
}
