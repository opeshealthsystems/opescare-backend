<?php

namespace App\Console\Commands;

use App\Modules\CareMap\Services\CameroonCityGazetteer;
use App\Modules\CareMap\Services\OsmFacilityImporter;
use App\Modules\CareMap\Services\OverpassClient;
use Illuminate\Console\Command;
use Throwable;

/**
 * Import Cameroonian health facilities from OpenStreetMap.
 *
 * ## Why OpenStreetMap and not Google
 *
 * The obvious source is Google Maps, and it is not available to us. Scraping it
 * breaches Google's terms of service outright, and the Places API — the licensed
 * route — forbids storing Place data or using it to build a competing directory,
 * which is exactly what OpesCare is. Neither is a technicality we could design
 * around: the product is the prohibited use.
 *
 * OpenStreetMap is ODbL-licensed. Bulk extraction and redistribution are
 * expressly permitted, and the condition is attribution — which this importer
 * records on every row it touches, in the data itself. See
 * OsmFacilityImporter::ATTRIBUTION.
 *
 * ## What it is for
 *
 * 903 facilities came from the MINSANTE national registry. 503 of them have no
 * coordinates, which means no "near me" search can ever return them — a patient
 * standing outside one of those buildings cannot find it. OSM has coordinates
 * for every one of its 2,083 Cameroonian health features, and roughly 1,200 of
 * those are facilities we simply do not have.
 *
 * Contact details are not the win here and this command does not chase them:
 * OSM carries a phone for 9% of features and an email for 2%. Coordinates and
 * coverage are the point.
 *
 * ## Running it
 *
 *   php artisan facilities:import-osm --list-cities
 *   php artisan facilities:import-osm --city=Douala --dry-run
 *   php artisan facilities:import-osm --city=Douala
 *   php artisan facilities:import-osm --bbox=3.90,9.55,4.20,9.95 --limit=50
 *   php artisan facilities:import-osm                       # whole country
 *
 * City by city is the intended way to run it. It keeps each Overpass query
 * small (a good-citizen concern — the endpoint is free and shared), keeps each
 * review queue reviewable by one person, and means a mistake in one city is a
 * mistake in one city.
 *
 * Every response is cached on disk, so a re-run after a crash costs Overpass
 * nothing and the import picks up where it left off. Re-running is always safe:
 * rows already linked to an OSM element are matched by that link, not by
 * guesswork, and a run that changes nothing writes nothing.
 */
class ImportOpenStreetMapFacilities extends Command
{
    protected $signature = 'facilities:import-osm
        {--city= : Import one town (see --list-cities). Sets the bounding box and the city/region for new rows.}
        {--bbox= : Explicit bounding box "south,west,north,east". Overrides --city geography.}
        {--limit=0 : Stop after this many OSM elements. 0 = no limit.}
        {--dry-run : Report exactly what would change and write nothing.}
        {--max-cache-age=24 : Reuse a cached Overpass response younger than this many hours. 0 forces a fresh query.}
        {--endpoint= : Alternative Overpass endpoint (e.g. a mirror).}
        {--list-cities : Print the towns --city accepts, then exit.}
        {--reviewed : Import only the features cleared by the reviewed decision list, and skip the rest.}';

    protected $description = 'Import Cameroonian health facilities from OpenStreetMap (ODbL) via the Overpass API';

    public function handle(
        OverpassClient $defaultClient,
        OsmFacilityImporter $importer,
        CameroonCityGazetteer $gazetteer,
    ): int {
        if ($this->option('list-cities')) {
            $this->line(implode(', ', $gazetteer->names()));

            return self::SUCCESS;
        }

        $city = null;

        if ($cityOption = $this->option('city')) {
            $city = $gazetteer->find((string) $cityOption);

            if ($city === null) {
                $this->error("Unknown city \"{$cityOption}\". Run with --list-cities to see the accepted values.");

                return self::FAILURE;
            }
        }

        $bbox = $this->resolveBoundingBox($city, $gazetteer);

        if ($bbox === false) {
            return self::FAILURE;
        }

        $endpoint = (string) ($this->option('endpoint') ?: OverpassClient::DEFAULT_ENDPOINT);
        $client   = $endpoint === $defaultClient->endpoint() ? $defaultClient : new OverpassClient($endpoint);

        $dryRun = (bool) $this->option('dry-run');
        $query  = $this->buildQuery($bbox);

        $this->components->info(sprintf(
            'OpenStreetMap facility import — %s%s',
            $city !== null ? $city['name'] : ($bbox !== null ? 'bbox ' . implode(',', $bbox) : 'all of Cameroon'),
            $dryRun ? ' (DRY RUN — nothing will be written)' : ''
        ));

        try {
            $result = $client->query(
                $query,
                (int) $this->option('max-cache-age'),
                fn (string $message) => $this->components->warn($message),
            );
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $elements = $result['elements'];

        $this->line(sprintf(
            '  %d elements %s.',
            count($elements),
            $result['from_cache']
                ? 'replayed from cache (' . $result['cache_path'] . ')'
                : 'fetched from ' . $client->endpoint()
        ));

        $limit = (int) $this->option('limit');

        if ($limit > 0 && count($elements) > $limit) {
            $elements = array_slice($elements, 0, $limit);
            $this->line("  --limit applied: processing the first {$limit}.");
        }

        // The ODbL notice, echoed where an operator running the import will see
        // it — the same string is written onto every row it touches.
        $this->line('  ' . ($result['copyright'] ?? OsmFacilityImporter::ATTRIBUTION));
        $this->newLine();

        /*
         * The reviewed decision list.
         *
         * Ten per-region passes plus a national reconciliation classified all
         * 2,083 Cameroonian OSM health features. The excluded ones are not
         * import errors — they are morgues, a stadium, veterinary practices,
         * NGO offices, a pharmacy OSM records as burnt down and now a bar,
         * unnamed building polygons, and 61 duplicate clusters that only a
         * cross-region pass could see. The importer's own matching cannot make
         * those calls, because they are judgements about what a facility IS,
         * not about whether two rows are the same.
         *
         * Honouring the list makes that review reproducible on any environment
         * instead of being a one-off someone ran locally.
         */
        if ($this->option('reviewed')) {
            $path = resource_path('data/osm_facility_decisions.json');

            if (! is_file($path)) {
                $this->error('  --reviewed needs resources/data/osm_facility_decisions.json');

                return self::FAILURE;
            }

            $decisions = json_decode((string) file_get_contents($path), true);
            $include   = $decisions['include'] ?? [];
            $before    = count($elements);

            $elements = array_values(array_filter(
                $elements,
                static fn (array $e): bool => isset($include[($e['type'] ?? '') . '/' . ($e['id'] ?? '')])
            ));

            $this->line(sprintf(
                '  --reviewed: %d of %d elements cleared for import, %d held for human review.',
                count($elements), $before, $before - count($elements)
            ));
            $this->newLine();
        }

        $counts = $importer->import($elements, [
            'dry_run' => $dryRun,
            'city'    => $city === null ? null : ['name' => $city['name'], 'region' => $city['region']],
        ]);

        $this->report($counts, $dryRun);

        return self::SUCCESS;
    }

    /**
     * @param  array{name:string,region:string,latitude:float,longitude:float,radius_km:int}|null $city
     * @return array{0:float,1:float,2:float,3:float}|null|false  null = whole country, false = bad input
     */
    private function resolveBoundingBox(?array $city, CameroonCityGazetteer $gazetteer): array|null|false
    {
        $raw = $this->option('bbox');

        if ($raw) {
            $parts = array_map('trim', explode(',', (string) $raw));

            if (count($parts) !== 4 || count(array_filter($parts, 'is_numeric')) !== 4) {
                $this->error('--bbox must be four numbers: "south,west,north,east".');

                return false;
            }

            [$south, $west, $north, $east] = array_map('floatval', $parts);

            if ($south >= $north || $west >= $east) {
                $this->error('--bbox is inverted — expected "south,west,north,east" with south < north and west < east.');

                return false;
            }

            return [$south, $west, $north, $east];
        }

        return $city === null ? null : $gazetteer->boundingBox($city);
    }

    /**
     * Overpass QL for "every health facility in this area".
     *
     * Both tagging schemes are queried because Cameroon uses both: 253 of 255
     * Douala elements carry `amenity`, only 67 carry `healthcare`, and neither
     * set contains the other. `nwr` covers nodes, ways and relations — a
     * hospital is usually mapped as a building footprint, not a point — and
     * `out center` collapses each footprint to a single coordinate, which is
     * what `care_facilities.latitude/longitude` holds.
     *
     * @param  array{0:float,1:float,2:float,3:float}|null $bbox
     */
    private function buildQuery(?array $bbox): string
    {
        $timeout = 180;

        if ($bbox !== null) {
            $filter = '(' . implode(',', $bbox) . ')';

            return "[out:json][timeout:{$timeout}];"
                 . "(nwr[\"amenity\"~\"^(pharmacy|hospital|clinic|doctors|dentist|health_post)$\"]{$filter};"
                 . "nwr[\"healthcare\"]{$filter};);"
                 . 'out center tags;';
        }

        // Country-wide: resolve Cameroon's admin boundary to an Overpass area.
        return "[out:json][timeout:{$timeout}];"
             . 'area["ISO3166-1"="CM"][admin_level=2]->.cm;'
             . '(nwr["amenity"~"^(pharmacy|hospital|clinic|doctors|dentist|health_post)$"](area.cm);'
             . 'nwr["healthcare"](area.cm););'
             . 'out center tags;';
    }

    /**
     * @param  array<string,mixed> $counts
     */
    private function report(array $counts, bool $dryRun): void
    {
        $verb = $dryRun ? 'would be' : 'were';

        $this->components->twoColumnDetail('<fg=gray>OSM elements considered</>', (string) $counts['elements']);
        $this->components->twoColumnDetail("Inserted as new facilities ({$verb})", '<fg=green>' . $counts['inserted'] . '</>');
        $this->components->twoColumnDetail("Existing facilities enriched ({$verb})", '<fg=green>' . $counts['updated'] . '</>');
        $this->components->twoColumnDetail('Matched, nothing to add', (string) $counts['matched_unchanged']);
        $this->components->twoColumnDetail('Skipped — facility owns its data', (string) $counts['skipped_protected']);
        $this->components->twoColumnDetail('Held for human review', '<fg=yellow>' . $counts['review'] . '</>');
        $this->components->twoColumnDetail('Skipped — unmappable OSM type', (string) $counts['skipped_unmapped_type']);
        $this->components->twoColumnDetail('Skipped — no coordinates', (string) $counts['skipped_no_coords']);

        if ($counts['review_by_reason'] !== []) {
            $this->newLine();
            $this->line('  <options=bold>Held for review, by reason</>');

            foreach ($counts['review_by_reason'] as $reason => $n) {
                $this->components->twoColumnDetail('  ' . $reason, (string) $n);
            }
        }

        if ($counts['fields_written'] !== []) {
            $this->newLine();
            $this->line("  <options=bold>Fields {$verb} filled on existing facilities</>");

            foreach ($counts['fields_written'] as $field => $n) {
                $this->components->twoColumnDetail('  ' . $field, (string) $n);
            }
        }

        $this->newLine();

        if ($dryRun) {
            $this->components->info('Dry run complete — no rows were written. Re-run without --dry-run to apply.');

            return;
        }

        if ($counts['review'] > 0) {
            $this->components->info(sprintf(
                '%d candidate(s) are in facility_import_reviews with status = pending. '
                . 'Nothing was merged or inserted for them.',
                $counts['review']
            ));
        }
    }
}
