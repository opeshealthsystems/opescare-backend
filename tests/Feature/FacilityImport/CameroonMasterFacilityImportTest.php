<?php

namespace Tests\Feature\FacilityImport;

use App\Models\CareFacility;
use App\Modules\CareMap\Services\CameroonMasterFacilityImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * What the national master-list import is allowed to do to the directory.
 *
 * WHY THIS MATTERS
 * ----------------
 * The project owner supplied 1,016 facilities across all ten regions and asked
 * for one thing above all else: "update what already exists rather than
 * duplicate". The directory already holds 1,863 rows — 903 from the MINSANTE
 * registry, 960 from OpenStreetMap — and this list overlaps both. So the
 * dangerous failure is not a missed enrichment, it is a hospital listed twice or
 * two hospitals merged into one: a patient sent to a building that cannot treat
 * them, in a record that looks exactly as correct as any other.
 *
 * The second thing the owner said is that this is INSTITUTIONAL data, not demo
 * data. Both halves of that have to stay true at once:
 *
 *   - it is real, so it is never `demo_seed` and it is visible in the finder;
 *   - it is not government-verified, because the workbook's own `Verified?` and
 *     `Operating status verified?` columns are blank on all 1,030 rows and the
 *     underlying source is Google Maps, not MINSANTE.
 *
 * These tests are therefore mostly about restraint, and about the two facts
 * above never quietly drifting into each other.
 */
class CameroonMasterFacilityImportTest extends TestCase
{
    use RefreshDatabase;

    private const DOUALA_LAT = 4.0511;
    private const DOUALA_LNG = 9.7679;

    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->tempFiles = [];

        parent::tearDown();
    }

    // ── Fixtures ────────────────────────────────────────────────────────────

    /**
     * @param  array<string,mixed> $attributes
     */
    private function facility(array $attributes = []): CareFacility
    {
        $facility = new CareFacility();

        $facility->forceFill(array_merge([
            'facility_name'       => 'Hôpital de District de Bassa',
            'facility_type'       => 'hospital',
            'country_code'        => 'CMR',
            'region'              => 'Littoral',
            'city'                => 'Douala',
            'address'             => 'Bassa',
            'latitude'            => null,
            'longitude'           => null,
            'phone_primary'       => 'N/A',
            'email'               => null,
            'website'             => null,
            'license_status'      => 'active',
            'verification_status' => 'unverified',
            'listing_status'      => 'active',
            'integration_status'  => 'none',
        ], $attributes))->save();

        return $facility->refresh();
    }

    /**
     * One row of the master workbook, in the shape the converted JSON uses.
     *
     * @param  array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function record(array $overrides = []): array
    {
        return array_merge([
            'place_id'                => 'ChIJ' . Str::random(19),
            'name'                    => 'Pharmacie Bonanjo Nord',
            'name_normalised'         => 'pharmacie bonanjo nord',
            'category'                => 'Pharmacy',
            'category_group'          => 'Pharmacy',
            'facility_type'           => 'pharmacy',
            'town'                    => 'Douala',
            'division'                => 'Wouri',
            'region_source'           => 'Littoral',
            'region'                  => 'Littoral',
            'locality'                => 'Bonanjo',
            'address'                 => 'Rue Joss, Douala',
            'latitude'                => self::DOUALA_LAT,
            'longitude'               => self::DOUALA_LNG,
            'coordinate_precision'    => 'Precise pin',
            'coordinates_approximate' => false,
            'phone'                   => '',
            'phone_raw'               => '',
            'maps_url'                => 'https://www.google.com/maps/place/?q=place_id:X',
            'source_file'             => 'Littoral_Douala_Health_Facilities',
            'notes'                   => '',
        ], $overrides);
    }

    /**
     * Write a dataset file in the shape the converter produces and return its path.
     *
     * @param  list<array<string,mixed>> $records
     */
    private function dataset(array $records): string
    {
        $path = tempnam(sys_get_temp_dir(), 'master') . '.json';
        $this->tempFiles[] = $path;

        file_put_contents($path, (string) json_encode([
            'source'      => 'Google Maps / Google Places',
            'attribution' => 'Google Maps / Google Places',
            'retrieved'   => '2026-09-02',
            'count'       => count($records),
            'skipped'     => [],
            'facilities'  => $records,
        ]));

        return $path;
    }

    /**
     * @param  list<array<string,mixed>> $records
     * @param  array<string,mixed>       $options
     */
    private function runImport(array $records, array $options = ['--apply' => true]): string
    {
        $path = $this->dataset($records);

        $this->artisan('facilities:import-master', array_merge(['--file' => $path], $options))
             ->assertSuccessful();

        return $path;
    }

    /**
     * Raw `updated_at` per row — the fingerprint a churning re-run would change.
     *
     * @return array<string,string|null>
     */
    private function updatedStamps(string $table): array
    {
        return DB::table($table)->orderBy('id')->pluck('updated_at', 'id')->toArray();
    }

    // ── Institutional, but not verified ─────────────────────────────────────

    public function test_it_inserts_an_unmatched_facility_with_google_places_provenance(): void
    {
        $this->runImport([
            $this->record(['place_id' => 'ChIJ_bonanjo_nord', 'phone' => '+237651020423']),
        ]);

        $inserted = CareFacility::where('facility_name', 'Pharmacie Bonanjo Nord')->sole();

        $this->assertSame('pharmacy', $inserted->facility_type);
        $this->assertSame('Douala', $inserted->city, 'city is NOT NULL and comes from the sheet town.');
        $this->assertSame('Littoral', $inserted->region);
        $this->assertEqualsWithDelta(self::DOUALA_LAT, (float) $inserted->latitude, 0.00001);

        // The reference is the Google Place ID, which is what makes a re-run
        // idempotent — `source_ref` is UNIQUE, and it cannot collide with an
        // OSM reference.
        $this->assertSame('google_places', $inserted->source_system);
        $this->assertSame('gplaces:ChIJ_bonanjo_nord', $inserted->source_ref);
        $this->assertSame('Google Maps / Google Places', $inserted->source_attribution);
        $this->assertNotNull($inserted->source_synced_at);

        // Real data, so it is listed and findable — never a demo seed.
        $this->assertSame('active', $inserted->listing_status);
        $this->assertNotSame('demo_seed', $inserted->source_system);
        $this->assertNotNull($inserted->facility_code);

        // The address folds in the parts the schema has no column for.
        $this->assertStringContainsString('Rue Joss', (string) $inserted->address);
        $this->assertStringContainsString('Bonanjo', (string) $inserted->address);
        $this->assertStringContainsString('Wouri', (string) $inserted->address);
    }

    public function test_nothing_in_this_dataset_is_ever_marked_verified(): void
    {
        // The workbook's `Verified?` and `Operating status verified?` columns are
        // blank on all 1,030 rows. The source is Google Maps, not MINSANTE. A
        // directory that calls this verified is lying to a patient.
        $existing = $this->facility(['facility_name' => 'Hôpital de District de Bassa']);

        $this->runImport([
            $this->record([
                'name'          => 'Hôpital de District de Bassa',
                'facility_type' => 'hospital',
                'phone'         => '+237699123456',
            ]),
            $this->record(['place_id' => 'ChIJ_new_one', 'name' => 'Pharmacie Bonanjo Nord']),
        ]);

        foreach (CareFacility::all() as $facility) {
            $this->assertSame('unverified', $facility->verification_status);
            $this->assertNull($facility->last_verified_at, 'An import is not a verification.');
        }

        // A row we CREATED has seen no licence, and says so instead of taking
        // the column default of 'active'.
        $created = CareFacility::where('source_system', 'google_places')->sole();
        $this->assertSame('unknown', $created->license_status);

        // A row we merely enriched keeps its own origin — enrichment is not
        // authorship — and keeps its own licence status.
        $existing->refresh();
        $this->assertNull($existing->source_system);
        $this->assertSame('active', $existing->license_status);
    }

    // ── Enriching rather than duplicating ───────────────────────────────────

    public function test_it_enriches_a_coordinateless_registry_row_instead_of_duplicating_it(): void
    {
        $existing = $this->facility([
            'facility_name' => 'Hôpital de District de Bassa',
            'latitude'      => null,
            'longitude'     => null,
        ]);

        $this->runImport([
            $this->record([
                'place_id'      => 'ChIJ_bassa',
                'name'          => 'Bassa District Hospital',
                'facility_type' => 'hospital',
                'phone'         => '+237233421111',
            ]),
        ]);

        $this->assertSame(1, CareFacility::count(), 'The importer must not create a second row for a facility we already list.');

        $existing->refresh();
        $this->assertEqualsWithDelta(self::DOUALA_LAT, (float) $existing->latitude, 0.00001);
        $this->assertSame('exact', $existing->geocoding_accuracy);
        $this->assertSame('gplaces:ChIJ_bassa', $existing->source_ref);
        $this->assertNull($existing->source_system, 'Enrichment is not authorship.');
    }

    public function test_the_na_placeholder_is_the_only_value_a_phone_may_replace(): void
    {
        // 1,571 of the 1,863 existing rows carry the literal 'N/A' — a NOT NULL
        // column with nothing to put in it. 673 of these 1,016 records carry a
        // real number. That is the single biggest win in the dataset.
        $placeholder = $this->facility([
            'facility_name' => 'Pharmacie Akwa Centre',
            'facility_type' => 'pharmacy',
            'latitude'      => self::DOUALA_LAT,
            'longitude'     => self::DOUALA_LNG,
            'phone_primary' => 'N/A',
        ]);

        $real = $this->facility([
            'facility_name' => 'Clinique Bel Air',
            'facility_type' => 'clinic',
            'latitude'      => 4.0300,
            'longitude'     => 9.7300,
            'phone_primary' => '+237 699000111',
        ]);

        $this->runImport([
            $this->record([
                'place_id'      => 'ChIJ_akwa',
                'name'          => 'Pharmacie Akwa Centre',
                'facility_type' => 'pharmacy',
                'phone'         => '+237699123456',
            ]),
            $this->record([
                'place_id'      => 'ChIJ_belair',
                'name'          => 'Clinique Bel Air',
                'facility_type' => 'clinic',
                'latitude'      => 4.0300,
                'longitude'     => 9.7300,
                'phone'         => '+237677999888',
            ]),
        ]);

        $this->assertSame('+237 699123456', $placeholder->fresh()->phone_primary);
        $this->assertSame('+237 699000111', $real->fresh()->phone_primary, 'An existing real number always wins.');
        $this->assertSame(2, CareFacility::count());
    }

    public function test_an_undialable_number_is_rejected_rather_than_stored(): void
    {
        $existing = $this->facility([
            'facility_name' => 'Pharmacie Akwa Centre',
            'facility_type' => 'pharmacy',
            'latitude'      => self::DOUALA_LAT,
            'longitude'     => self::DOUALA_LNG,
            'phone_primary' => 'N/A',
        ]);

        $this->runImport([
            $this->record([
                'name'          => 'Pharmacie Akwa Centre',
                'facility_type' => 'pharmacy',
                'phone'         => '1234',
                'phone_raw'     => '1234',
            ]),
        ]);

        // An honest placeholder beats a number that rings nowhere.
        $this->assertSame('N/A', $existing->fresh()->phone_primary);
    }

    public function test_a_record_with_no_phone_is_inserted_with_the_directory_placeholder(): void
    {
        // `phone_primary` is NOT NULL. 343 of the 1,016 records have no number,
        // and the placeholder the rest of the table already uses is what the
        // eventual cleanup pass will look for.
        $this->runImport([
            $this->record(['name' => 'Pharmacie Bonanjo Nord', 'phone' => '', 'phone_raw' => '']),
        ]);

        $this->assertSame(
            CareFacility::PHONE_PLACEHOLDER,
            CareFacility::sole()->phone_primary,
        );
    }

    public function test_it_fills_an_empty_city_and_address_but_never_replaces_a_populated_one(): void
    {
        // 16 existing rows carry an empty string in the NOT NULL city and
        // address columns.
        $blank = $this->facility([
            'facility_name' => 'Pharmacie Akwa Centre',
            'facility_type' => 'pharmacy',
            'city'          => '',
            'address'       => '',
            'region'        => 'Littoral',
            'latitude'      => self::DOUALA_LAT,
            'longitude'     => self::DOUALA_LNG,
        ]);

        $populated = $this->facility([
            'facility_name' => 'Clinique Bel Air',
            'facility_type' => 'clinic',
            'city'          => 'Douala',
            'address'       => 'Boulevard de la Liberté',
            'latitude'      => 4.0300,
            'longitude'     => 9.7300,
        ]);

        $this->runImport([
            $this->record([
                'name'          => 'Pharmacie Akwa Centre',
                'facility_type' => 'pharmacy',
                'address'       => 'Rue Joss, Douala',
            ]),
            $this->record([
                'place_id'      => 'ChIJ_belair',
                'name'          => 'Clinique Bel Air',
                'facility_type' => 'clinic',
                'latitude'      => 4.0300,
                'longitude'     => 9.7300,
                'address'       => 'Rue Google, Douala',
            ]),
        ]);

        $blank->refresh();
        $this->assertSame('Douala', $blank->city);
        $this->assertStringContainsString('Rue Joss', (string) $blank->address);

        $this->assertSame('Boulevard de la Liberté', $populated->fresh()->address);
    }

    // ── The 21 approximate pins ─────────────────────────────────────────────

    public function test_an_approximate_pin_is_not_evidence_that_two_records_are_the_same_place(): void
    {
        // 21 records sit on a town centroid or a pin shared with a neighbour,
        // which the workbook itself flags "needs field check".
        //
        // These two names score 0.77. On a real pin, 0 m apart, that is a STRONG
        // match under the ≤200 m / ≥0.70 rule and the row would be merged
        // outright. But the distance here is an artefact of two facilities being
        // handed the same centroid, so it is no evidence at all — the candidate
        // has to clear the no-geography bar instead (same city, name ≥ 0.90),
        // which 0.77 does not. It lands in review, and a person decides.
        $existing = $this->facility([
            'facility_name' => 'Clinique Sainte Yvette Bonaberi',
            'facility_type' => 'clinic',
            'latitude'      => self::DOUALA_LAT,
            'longitude'     => self::DOUALA_LNG,
        ]);

        $this->runImport([
            $this->record([
                'place_id'                => 'ChIJ_approx',
                'name'                    => 'Clinique Sainte Yvette Bonaberi Nord',
                'facility_type'           => 'clinic',
                'latitude'                => self::DOUALA_LAT,
                'longitude'               => self::DOUALA_LNG,
                'coordinates_approximate' => true,
                'coordinate_precision'    => 'Approx. (city centroid – needs field check)',
            ]),
        ]);

        $this->assertSame(1, CareFacility::count(), 'Nothing may be inserted on a centroid pin alone.');
        $this->assertNull($existing->fresh()->source_ref, 'And nothing may be merged into it.');

        $review = DB::table('facility_import_reviews')->sole();
        $this->assertSame('google_places', $review->source_system);
        $this->assertSame(CameroonMasterFacilityImporter::REASON_UNCERTAIN_MATCH, $review->reason);
        $this->assertSame($existing->id, $review->matched_facility_id);

        // The reviewer gets the Google Maps link — the fastest way a person
        // settles "is this the same building?".
        $this->assertStringContainsString('maps', (string) $review->payload);
    }

    public function test_an_approximate_pin_never_overwrites_a_precise_coordinate(): void
    {
        $existing = $this->facility([
            'facility_name' => 'Hôpital de District de Bassa',
            'latitude'      => 4.0300,
            'longitude'     => 9.7600,
            'phone_primary' => 'N/A',
        ]);

        $this->runImport([
            $this->record([
                'name'                    => 'Hôpital de District de Bassa',
                'facility_type'           => 'hospital',
                'latitude'                => 4.0300,
                'longitude'               => 9.7600,
                'town'                    => 'Douala',
                'coordinates_approximate' => true,
                'phone'                   => '+237233421111',
            ]),
        ]);

        $existing->refresh();

        // Same city and an all-but-identical name clears the no-geography bar,
        // so the phone lands — but the pin the row already had is untouched.
        $this->assertSame('+237 233421111', $existing->phone_primary);
        $this->assertEqualsWithDelta(4.0300, (float) $existing->latitude, 0.00001);
        $this->assertEqualsWithDelta(9.7600, (float) $existing->longitude, 0.00001);
    }

    public function test_an_approximate_pin_is_stored_as_city_level_when_there_was_no_pin_at_all(): void
    {
        $this->runImport([
            $this->record([
                'name'                    => 'Pharmacie Bonanjo Nord',
                'coordinates_approximate' => true,
            ]),
        ]);

        // A town centroid is worth having when the alternative is a row nobody
        // can place at all — but the imprecision travels with the data.
        $this->assertSame('city_level', CareFacility::sole()->geocoding_accuracy);
    }

    // ── Refusing to decide ──────────────────────────────────────────────────

    public function test_an_ambiguous_candidate_is_neither_merged_nor_inserted(): void
    {
        $existing = $this->facility([
            'facility_name' => 'Hôpital Général de Douala',
            'latitude'      => 4.0511,
            'longitude'     => 9.7679,
        ]);

        $this->runImport([
            $this->record([
                'name'          => 'Hôpital Général de Douala',
                'facility_type' => 'hospital',
                'latitude'      => 4.0800,
                'longitude'     => 9.7900,
            ]),
        ]);

        $this->assertSame(1, CareFacility::count());
        $this->assertNull($existing->fresh()->source_ref);

        $review = DB::table('facility_import_reviews')->sole();
        $this->assertSame(CameroonMasterFacilityImporter::REASON_UNCERTAIN_MATCH, $review->reason);
        $this->assertSame('pending', $review->status);
        $this->assertSame('Google Maps / Google Places', $review->source_attribution);
    }

    public function test_two_records_cannot_both_bind_to_one_facility(): void
    {
        $existing = $this->facility([
            'facility_name' => 'Pharmacie de Bonamoussadi',
            'facility_type' => 'pharmacy',
            'latitude'      => 4.0900,
            'longitude'     => 9.7400,
        ]);

        $this->runImport([
            $this->record([
                'place_id'      => 'ChIJ_bona_one',
                'name'          => 'Pharmacie de Bonamoussadi',
                'facility_type' => 'pharmacy',
                'latitude'      => 4.0900,
                'longitude'     => 9.7400,
                'phone'         => '+237699111222',
            ]),
            $this->record([
                'place_id'      => 'ChIJ_bona_two',
                'name'          => 'Pharmacie de Bonamoussadi',
                'facility_type' => 'pharmacy',
                'latitude'      => 4.0947,
                'longitude'     => 9.7420,
            ]),
        ]);

        $this->assertSame(1, CareFacility::count(), 'The second record must not silently become a duplicate listing.');
        $this->assertSame('gplaces:ChIJ_bona_one', $existing->fresh()->source_ref);

        $review = DB::table('facility_import_reviews')->sole();
        $this->assertSame(CameroonMasterFacilityImporter::REASON_ALREADY_LINKED, $review->reason);
        $this->assertSame('gplaces:ChIJ_bona_two', $review->source_ref);
    }

    public function test_a_record_with_no_town_is_held_for_review(): void
    {
        // `city` is NOT NULL, and no city we can stand behind means no row.
        $this->runImport([
            $this->record(['town' => '', 'name' => 'Pharmacie Bonanjo Nord']),
        ]);

        $this->assertSame(0, CareFacility::count());
        $this->assertSame(
            CameroonMasterFacilityImporter::REASON_UNRESOLVED_CITY,
            DB::table('facility_import_reviews')->sole()->reason,
        );
    }

    public function test_a_generic_name_is_held_for_review_not_listed(): void
    {
        $this->runImport([
            $this->record(['name' => 'Centre de Santé', 'facility_type' => 'health_center']),
        ]);

        $this->assertSame(0, CareFacility::count());
        $this->assertSame(
            CameroonMasterFacilityImporter::REASON_GENERIC_NAME,
            DB::table('facility_import_reviews')->sole()->reason,
        );
    }

    public function test_its_review_rows_do_not_disturb_the_openstreetmap_queue(): void
    {
        // 439 OSM candidates are already pending. The two importers share the
        // table, the reasons and the admin screen, and must not share state.
        DB::table('facility_import_reviews')->insert([
            'id'            => (string) Str::uuid(),
            'source_system' => 'openstreetmap',
            'source_ref'    => 'osm:node/1',
            'reason'        => 'generic_name',
            'status'        => 'pending',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $this->runImport([
            $this->record(['name' => 'Clinique', 'facility_type' => 'clinic']),
        ]);

        $this->assertSame(1, DB::table('facility_import_reviews')->where('source_system', 'openstreetmap')->count());
        $this->assertSame(1, DB::table('facility_import_reviews')->where('source_system', 'google_places')->count());
    }

    // ── Rows that are off limits ────────────────────────────────────────────

    public function test_a_facility_that_is_not_unverified_is_never_touched(): void
    {
        $existing = $this->facility([
            'facility_name'       => 'Hôpital Général de Douala',
            'latitude'            => null,
            'longitude'           => null,
            'verification_status' => 'government_verified',
        ]);

        $this->runImport([
            $this->record([
                'name'          => 'Hôpital Général de Douala',
                'facility_type' => 'hospital',
                'phone'         => '+237233421111',
            ]),
        ]);

        $existing->refresh();
        $this->assertNull($existing->latitude, 'A verified facility is authoritative — an external dataset may not edit it.');
        $this->assertSame('N/A', $existing->phone_primary);
        $this->assertNull($existing->source_ref);
        $this->assertSame(1, CareFacility::count(), 'It must not be duplicated either.');
    }

    public function test_a_facility_claimed_by_its_operator_is_never_touched(): void
    {
        $operationalId = (string) Str::uuid();

        DB::table('facilities')->insert([
            'id'         => $operationalId,
            'name'       => 'Clinique Saint Thomas',
            'type'       => 'clinic',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $existing = $this->facility([
            'facility_name' => 'Clinique Saint Thomas',
            'facility_type' => 'clinic',
            'facility_id'   => $operationalId,
            'latitude'      => null,
            'longitude'     => null,
        ]);

        DB::table('facility_claims')->insert([
            'id'               => (string) Str::uuid(),
            'facility_id'      => $operationalId,
            'claimant_user_id' => null,
            'claim_status'     => 'approved',
            'claim_reason'     => 'We operate this clinic.',
            'submitted_at'     => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $this->runImport([
            $this->record([
                'name'          => 'Clinique Saint Thomas',
                'facility_type' => 'clinic',
                'phone'         => '+237699123456',
            ]),
        ]);

        $this->assertNull($existing->fresh()->latitude, 'A claimed facility owns its own profile.');
    }

    public function test_a_field_a_human_has_edited_is_never_overwritten(): void
    {
        $existing = $this->facility([
            'facility_name' => 'Pharmacie Deido',
            'facility_type' => 'pharmacy',
            'latitude'      => self::DOUALA_LAT,
            'longitude'     => self::DOUALA_LNG,
            'phone_primary' => 'N/A',
        ]);

        DB::table('facility_update_audits')->insert([
            'id'            => (string) Str::uuid(),
            'facility_id'   => $existing->id,
            'actor_type'    => 'user',
            'field_changed' => 'phone_primary',
            'old_value'     => '+237 600000000',
            'new_value'     => 'N/A',
            'created_at'    => now(),
        ]);

        $this->runImport([
            $this->record([
                'name'          => 'Pharmacie Deido',
                'facility_type' => 'pharmacy',
                'phone'         => '+237699123456',
            ]),
        ]);

        $this->assertSame('N/A', $existing->fresh()->phone_primary);
    }

    // ── Sharing the directory with the OpenStreetMap import ─────────────────

    public function test_it_enriches_a_row_created_by_osm_without_taking_its_reference(): void
    {
        // 960 of the 1,863 rows were created by the OSM import and 881 of those
        // carry the 'N/A' phone placeholder — so this is the ordinary case, not
        // an edge one. `source_ref` is UNIQUE: taking it would strip the ODbL
        // attribution off a row derived from ODbL data. The phone still lands,
        // and the audit trail says where it came from.
        $osmRow = $this->facility([
            'facility_name'      => 'Pharmacie du Wouri',
            'facility_type'      => 'pharmacy',
            'latitude'           => self::DOUALA_LAT,
            'longitude'          => self::DOUALA_LNG,
            'phone_primary'      => 'N/A',
            'source_system'      => 'openstreetmap',
            'source_ref'         => 'osm:node/12345',
            'source_attribution' => '© OpenStreetMap contributors — ODbL 1.0 (opendatacommons.org/licenses/odbl)',
        ]);

        $this->runImport([
            $this->record([
                'place_id'      => 'ChIJ_wouri',
                'name'          => 'Pharmacie du Wouri',
                'facility_type' => 'pharmacy',
                'phone'         => '+237699123456',
            ]),
        ]);

        $osmRow->refresh();

        $this->assertSame(1, CareFacility::count(), 'Enrich the OSM row — do not list the pharmacy twice.');
        $this->assertSame('+237 699123456', $osmRow->phone_primary);
        $this->assertSame('osm:node/12345', $osmRow->source_ref, 'The OSM element keeps the row reference.');
        $this->assertSame('openstreetmap', $osmRow->source_system);
        $this->assertStringContainsString('ODbL', (string) $osmRow->source_attribution);

        $audit = DB::table('facility_update_audits')
            ->where('facility_id', $osmRow->id)
            ->where('field_changed', 'phone_primary')
            ->sole();

        $this->assertSame('gplaces:ChIJ_wouri', $audit->source, 'Per-field provenance is where this write is attributed.');
    }

    // ── Provenance trail ────────────────────────────────────────────────────

    public function test_every_field_written_is_attributed_to_its_place_id(): void
    {
        $existing = $this->facility(['latitude' => null, 'longitude' => null]);

        $this->runImport([
            $this->record([
                'place_id'      => 'ChIJ_bassa42',
                'name'          => 'Hôpital de District de Bassa',
                'facility_type' => 'hospital',
                'phone'         => '+237233421111',
            ]),
        ]);

        $audits = DB::table('facility_update_audits')->where('facility_id', $existing->id)->get();

        $this->assertGreaterThan(0, $audits->count());

        foreach ($audits as $audit) {
            $this->assertSame('system', $audit->actor_type);
            $this->assertSame('gplaces:ChIJ_bassa42', $audit->source);
        }

        $this->assertEqualsCanonicalizing(
            ['latitude', 'longitude', 'geocoding_accuracy', 'phone_primary'],
            $audits->pluck('field_changed')->all(),
        );
    }

    // ── Idempotency and dry run ─────────────────────────────────────────────

    public function test_a_second_run_inserts_nothing_and_updates_nothing(): void
    {
        $existing = $this->facility([
            'facility_name' => 'Hôpital de District de Bassa',
            'latitude'      => null,
            'longitude'     => null,
        ]);

        $this->facility([
            'facility_name' => 'Clinique Sainte Yvette Bonaberi',
            'facility_type' => 'clinic',
            'latitude'      => 4.0511,
            'longitude'     => 9.7679,
        ]);

        $records = [
            $this->record([
                'place_id'      => 'ChIJ_bassa',
                'name'          => 'Hôpital de District de Bassa',
                'facility_type' => 'hospital',
                'phone'         => '+237233421111',
            ]),
            $this->record([
                'place_id'  => 'ChIJ_bonanjo',
                'name'      => 'Pharmacie Bonanjo Nord',
                'latitude'  => 4.0400,
                'longitude' => 9.7000,
                'phone'     => '+237699111222',
            ]),
            $this->record([
                'place_id'      => 'ChIJ_generic',
                'name'          => 'Centre de Santé',
                'facility_type' => 'health_center',
                'latitude'      => 4.0600,
                'longitude'     => 9.7100,
            ]),
            // A review row that carries a match_score and a coordinate — the
            // ones that churn if the fingerprint is taken at a precision the
            // columns cannot store. These two names score 0.7708 and
            // `match_score` is numeric(4,3), so what goes in is not what comes
            // back out unless both sides are rounded to what the column holds.
            $this->record([
                'place_id'      => 'ChIJ_ambiguous',
                'name'          => 'Clinique Sainte Yvette Bonaberi Nord',
                'facility_type' => 'clinic',
                'latitude'      => 4.0800,
                'longitude'     => 9.7900,
            ]),
        ];

        $this->runImport($records);

        $facilitiesAfterFirst = CareFacility::count();
        $reviewsAfterFirst    = DB::table('facility_import_reviews')->count();
        $auditsAfterFirst     = DB::table('facility_update_audits')->count();

        // `updated_at` across the whole table: catches a silent rewrite that
        // leaves the row count unchanged. Read raw, so the comparison is of
        // timestamps and not of Carbon instances.
        $stampsAfterFirst = $this->updatedStamps('care_facilities');
        $reviewStamps     = $this->updatedStamps('facility_import_reviews');

        $this->assertSame(3, $facilitiesAfterFirst);
        $this->assertSame(2, $reviewsAfterFirst);
        $this->assertNotNull(
            DB::table('facility_import_reviews')->whereNotNull('match_score')->first(),
            'One review must carry a score, or this test cannot see score-precision churn.',
        );

        // `updated_at` is timestamp(0). Two runs inside the same second write
        // the same stamp, so a re-write would be invisible and this test would
        // pass against an importer that rewrites every row on every run. Moving
        // the clock is what makes the assertion mean anything.
        $this->travel(1)->hours();

        $this->runImport($records);

        $this->assertSame($facilitiesAfterFirst, CareFacility::count());
        $this->assertSame($reviewsAfterFirst, DB::table('facility_import_reviews')->count());
        $this->assertSame($auditsAfterFirst, DB::table('facility_update_audits')->count(), 'A second run must not append duplicate audit rows.');
        $this->assertSame($stampsAfterFirst, $this->updatedStamps('care_facilities'), 'A second run must not touch a single row.');
        $this->assertSame($reviewStamps, $this->updatedStamps('facility_import_reviews'), 'Nor rewrite a pending review row.');

        $this->assertSame('gplaces:ChIJ_bassa', $existing->fresh()->source_ref);
    }

    public function test_a_run_is_a_dry_run_unless_apply_is_given(): void
    {
        // The safeguard has to be the default, not a flag you can forget. This
        // is a national directory; the report is the thing you read first.
        $existing = $this->facility(['latitude' => null, 'longitude' => null]);

        $records = [
            $this->record([
                'name'          => 'Hôpital de District de Bassa',
                'facility_type' => 'hospital',
                'phone'         => '+237233421111',
            ]),
            $this->record(['place_id' => 'ChIJ_two', 'latitude' => 4.0400, 'longitude' => 9.7000]),
            $this->record(['place_id' => 'ChIJ_three', 'name' => 'Clinique', 'facility_type' => 'clinic', 'latitude' => 4.0600, 'longitude' => 9.7100]),
        ];

        // No --apply at all.
        $this->runImport($records, []);

        $this->assertSame(1, CareFacility::count());
        $this->assertNull($existing->fresh()->latitude);
        $this->assertSame(0, DB::table('facility_import_reviews')->count());
        $this->assertSame(0, DB::table('facility_update_audits')->count());

        // --dry-run wins even when --apply is passed as well.
        $this->runImport($records, ['--apply' => true, '--dry-run' => true]);

        $this->assertSame(1, CareFacility::count());
        $this->assertSame(0, DB::table('facility_import_reviews')->count());
    }

    public function test_a_human_decision_on_a_review_is_not_relitigated(): void
    {
        $records = [$this->record(['name' => 'Centre de Santé', 'facility_type' => 'health_center'])];

        $this->runImport($records);

        DB::table('facility_import_reviews')->update(['status' => 'rejected']);

        $this->runImport($records);

        $this->assertSame('rejected', DB::table('facility_import_reviews')->sole()->status);
        $this->assertSame(0, CareFacility::count());
    }

    // ── The command's own contract ──────────────────────────────────────────

    public function test_a_missing_dataset_file_fails_loudly(): void
    {
        // "0 facilities imported" and "the file was not there" must not look the
        // same from the outside.
        $this->artisan('facilities:import-master', ['--file' => 'C:/nowhere/does-not-exist.json'])
             ->assertFailed();

        $this->assertSame(0, CareFacility::count());
    }

    public function test_an_unknown_region_is_rejected_rather_than_importing_nothing(): void
    {
        $path = $this->dataset([$this->record()]);

        $this->artisan('facilities:import-master', ['--file' => $path, '--region' => 'Atlantique'])
             ->assertFailed();

        $this->assertSame(0, CareFacility::count());
    }

    public function test_the_region_filter_imports_only_that_region(): void
    {
        $path = $this->dataset([
            $this->record(['place_id' => 'ChIJ_littoral', 'name' => 'Pharmacie Bonanjo Nord', 'region' => 'Littoral']),
            $this->record([
                'place_id'  => 'ChIJ_ouest',
                'name'      => 'Pharmacie de la MIFI',
                'region'    => 'Ouest',
                'town'      => 'Bafoussam',
                'latitude'  => 5.4737,
                'longitude' => 10.4179,
            ]),
        ]);

        $this->artisan('facilities:import-master', ['--file' => $path, '--region' => 'Ouest', '--apply' => true])
             ->assertSuccessful();

        $this->assertSame(['Pharmacie de la MIFI'], CareFacility::pluck('facility_name')->all());
        $this->assertSame('Bafoussam', CareFacility::sole()->city);
    }
}
