<?php

namespace Tests\Feature\CareMap;

use App\Models\CareFacility;
use App\Modules\CareMap\Services\OsmFacilityImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * What the OpenStreetMap importer is allowed to do to the facility directory.
 *
 * WHY THIS MATTERS
 * ----------------
 * `care_facilities` holds 903 rows from the MINSANTE national registry, and
 * this is the directory a patient searches when they need care now. OSM has
 * 2,083 Cameroonian health features against those 903, so an import is mostly
 * ADDING facilities — which makes the dangerous failure a false merge or a
 * false duplicate, not a missed row.
 *
 * The asymmetry is the point. A facility we fail to enrich is a filter a
 * patient did not get. A hospital listed twice, or two hospitals merged into
 * one, is a patient sent to a building that cannot treat them — and unlike a
 * missing coordinate, a wrong merge looks exactly like a correct one from the
 * outside. Nobody finds it by reading the table.
 *
 * So these tests are mostly about restraint: what the importer must REFUSE to
 * do. It must not duplicate any of the 903. It must not overwrite a value
 * better than the one it is holding. It must not touch a facility that has
 * been verified, claimed, or connected. And when it cannot tell, it must do
 * nothing at all and leave the question for a person.
 *
 * The one placeholder it may overwrite is the literal 'N/A' sitting in
 * `phone_primary` on 692 rows — that is not a phone number, it is a NOT NULL
 * column that had nothing to put in it.
 */
class OsmFacilityImportTest extends TestCase
{
    use RefreshDatabase;

    private const DOUALA_LAT = 4.0511;
    private const DOUALA_LNG = 9.7679;

    protected function setUp(): void
    {
        parent::setUp();

        // The importer caches Overpass responses on disk so a re-run costs the
        // shared free endpoint nothing. Fake the disk so tests never replay
        // each other's payloads.
        Storage::fake('local');
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
     * Build one Overpass element.
     *
     * @param  array<string,string> $tags
     * @return array<string,mixed>
     */
    private function element(array $tags, float $lat = self::DOUALA_LAT, float $lng = self::DOUALA_LNG, int $id = 1, string $type = 'node'): array
    {
        return ['type' => $type, 'id' => $id, 'lat' => $lat, 'lon' => $lng, 'tags' => $tags];
    }

    /**
     * @param  list<array<string,mixed>> $elements
     */
    private function fakeOverpass(array $elements): void
    {
        Http::fake([
            '*overpass*' => Http::response([
                'version'   => 0.6,
                'generator' => 'Overpass API',
                'osm3s'     => ['copyright' => 'The data ... is made available under ODbL.'],
                'elements'  => $elements,
            ], 200),
        ]);
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

    /**
     * @param  array<string,mixed> $options
     */
    private function runImport(array $options = []): void
    {
        $this->artisan('facilities:import-osm', array_merge([
            '--city'          => 'Douala',
            '--max-cache-age' => 0,
        ], $options))->assertSuccessful();
    }

    // ── Inserting what we do not have ───────────────────────────────────────

    public function test_it_inserts_an_unmatched_facility_with_osm_provenance_and_odbl_attribution(): void
    {
        $this->fakeOverpass([
            $this->element(['amenity' => 'pharmacy', 'name' => 'Pharmacie Bonanjo Nord']),
        ]);

        $this->runImport();

        $inserted = CareFacility::where('facility_name', 'Pharmacie Bonanjo Nord')->sole();

        $this->assertSame('pharmacy', $inserted->facility_type);
        $this->assertSame('Douala', $inserted->city);
        $this->assertSame('Littoral', $inserted->region);
        $this->assertEqualsWithDelta(self::DOUALA_LAT, (float) $inserted->latitude, 0.00001);

        // ODbL requires attribution, and it has to survive a table dump — so it
        // lives on the row, not in a template footer.
        $this->assertSame('openstreetmap', $inserted->source_system);
        $this->assertSame('osm:node/1', $inserted->source_ref);
        $this->assertSame(OsmFacilityImporter::ATTRIBUTION, $inserted->source_attribution);
        $this->assertNotNull($inserted->source_synced_at);
        $this->assertStringContainsString('OpenStreetMap', (string) $inserted->source_attribution);
        $this->assertStringContainsString('ODbL', (string) $inserted->source_attribution);

        // Still gets a facility_code like every other row in the directory.
        $this->assertNotNull($inserted->facility_code);
    }

    public function test_an_imported_row_is_distinguishable_from_a_minsante_row(): void
    {
        $registry = $this->facility(['facility_name' => 'Hôpital Laquintinie']);

        $this->fakeOverpass([
            $this->element(['amenity' => 'pharmacy', 'name' => 'Pharmacie Bonanjo Nord']),
        ]);

        $this->runImport();

        $imported = CareFacility::where('source_system', 'openstreetmap')->sole();

        // MINSANTE is the licensing registry; OSM is a volunteer with a GPS.
        // Defaulting an OSM row's licence to 'active' would assert something we
        // have never seen evidence of.
        $this->assertSame('unknown', $imported->license_status);
        $this->assertSame('unverified', $imported->verification_status);
        $this->assertSame('active', $registry->fresh()->license_status);
        $this->assertNull($registry->fresh()->source_system);
    }

    // ── Not duplicating what we already have ────────────────────────────────

    public function test_it_enriches_a_coordinateless_facility_instead_of_duplicating_it(): void
    {
        // The exact shape of 503 of the 903 rows: real name, no coordinates,
        // so it can never appear in a "near me" search.
        $existing = $this->facility([
            'facility_name' => 'Hôpital de District de Bassa',
            'latitude'      => null,
            'longitude'     => null,
        ]);

        $this->fakeOverpass([
            $this->element(['amenity' => 'hospital', 'name' => 'Bassa District Hospital']),
        ]);

        $this->runImport();

        $this->assertSame(1, CareFacility::count(), 'The importer must not create a second row for a facility we already list.');

        $existing->refresh();
        $this->assertEqualsWithDelta(self::DOUALA_LAT, (float) $existing->latitude, 0.00001);
        $this->assertSame('osm:node/1', $existing->source_ref);

        // Enrichment is not authorship: the row's origin is still MINSANTE's.
        $this->assertNull($existing->source_system);
    }

    public function test_it_matches_across_french_and_english_naming(): void
    {
        $this->facility([
            'facility_name' => 'Centre Médical de Bonabéri',
            'facility_type' => 'clinic',
            'latitude'      => self::DOUALA_LAT,
            'longitude'     => self::DOUALA_LNG,
        ]);

        $this->fakeOverpass([
            $this->element(['amenity' => 'clinic', 'name' => 'Bonaberi Medical Center'], self::DOUALA_LAT, self::DOUALA_LNG),
        ]);

        $this->runImport();

        $this->assertSame(1, CareFacility::count());
    }

    public function test_two_similarly_named_facilities_far_apart_are_not_merged(): void
    {
        // 'Hôpital de District de Bassa' and '... de Bonabéri' share every word
        // but the last. A plain string comparison scores them 0.73 — enough to
        // merge under any naive threshold. They are different hospitals.
        $this->facility([
            'facility_name' => 'Hôpital de District de Bonabéri',
            'latitude'      => 4.0700,
            'longitude'     => 9.6900,
        ]);

        $this->fakeOverpass([
            $this->element(['amenity' => 'hospital', 'name' => 'Hôpital de District de Bassa'], 4.0300, 9.7600),
        ]);

        $this->runImport();

        $this->assertSame(2, CareFacility::count(), 'Two different district hospitals must stay two rows.');
    }

    // ── Never overwriting better data ───────────────────────────────────────

    public function test_existing_values_are_never_replaced_with_osm_values(): void
    {
        $existing = $this->facility([
            'facility_name' => 'Clinique Bel Air',
            'facility_type' => 'clinic',
            'latitude'      => self::DOUALA_LAT,
            'longitude'     => self::DOUALA_LNG,
            'phone_primary' => '+237 699000111',
            'email'         => 'contact@belair.cm',
            'website'       => 'https://belair.cm',
        ]);

        $this->fakeOverpass([
            $this->element([
                'amenity'         => 'clinic',
                'name'            => 'Clinique Bel Air',
                'phone'           => '+237 677999888',
                'contact:email'   => 'osm@example.com',
                'website'         => 'https://osm-suggested.example',
            ]),
        ]);

        $this->runImport();

        $existing->refresh();
        $this->assertSame('+237 699000111', $existing->phone_primary);
        $this->assertSame('contact@belair.cm', $existing->email);
        $this->assertSame('https://belair.cm', $existing->website);
    }

    public function test_the_na_placeholder_is_the_only_value_a_phone_may_replace(): void
    {
        // 692 of the 903 rows carry the literal string 'N/A' — a NOT NULL column
        // with nothing to put in it, not a number anyone can dial.
        $existing = $this->facility([
            'facility_name' => 'Pharmacie Akwa Centre',
            'facility_type' => 'pharmacy',
            'latitude'      => self::DOUALA_LAT,
            'longitude'     => self::DOUALA_LNG,
            'phone_primary' => 'N/A',
        ]);

        $this->fakeOverpass([
            $this->element([
                'amenity' => 'pharmacy',
                'name'    => 'Pharmacie Akwa Centre',
                'phone'   => '+237 6 99 12 34 56',
            ]),
        ]);

        $this->runImport();

        $this->assertSame('+237 699123456', $existing->fresh()->phone_primary);
    }

    public function test_an_undialable_phone_is_rejected_rather_than_normalised(): void
    {
        $existing = $this->facility([
            'facility_name' => 'Pharmacie Akwa Centre',
            'facility_type' => 'pharmacy',
            'latitude'      => self::DOUALA_LAT,
            'longitude'     => self::DOUALA_LNG,
            'phone_primary' => 'N/A',
        ]);

        $this->fakeOverpass([
            $this->element([
                'amenity' => 'pharmacy',
                'name'    => 'Pharmacie Akwa Centre',
                'phone'   => '1234',          // not a Cameroonian number
            ]),
        ]);

        $this->runImport();

        // An honest placeholder beats a number that rings nowhere.
        $this->assertSame('N/A', $existing->fresh()->phone_primary);
    }

    public function test_a_facility_that_is_not_unverified_is_never_touched(): void
    {
        $existing = $this->facility([
            'facility_name'       => 'Hôpital Général de Douala',
            'latitude'            => null,
            'longitude'           => null,
            'verification_status' => 'government_verified',
        ]);

        $this->fakeOverpass([
            $this->element(['amenity' => 'hospital', 'name' => 'Hôpital Général de Douala']),
        ]);

        $this->runImport();

        $existing->refresh();
        $this->assertNull($existing->latitude, 'A verified facility is authoritative — an external dataset may not edit it.');
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
            'id'                => (string) Str::uuid(),
            'facility_id'       => $operationalId,
            'claimant_user_id'  => null,
            'claim_status'      => 'approved',
            'claim_reason'      => 'We operate this clinic.',
            'submitted_at'      => now(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $this->fakeOverpass([
            $this->element(['amenity' => 'clinic', 'name' => 'Clinique Saint Thomas']),
        ]);

        $this->runImport();

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

        // Somebody looked at this row and set the phone to 'N/A' deliberately —
        // the audit trail says so, so it is a decision, not a leftover.
        DB::table('facility_update_audits')->insert([
            'id'            => (string) Str::uuid(),
            'facility_id'   => $existing->id,
            'actor_type'    => 'user',
            'field_changed' => 'phone_primary',
            'old_value'     => '+237 600000000',
            'new_value'     => 'N/A',
            'created_at'    => now(),
        ]);

        $this->fakeOverpass([
            $this->element([
                'amenity' => 'pharmacy',
                'name'    => 'Pharmacie Deido',
                'phone'   => '+237 699123456',
            ]),
        ]);

        $this->runImport();

        $this->assertSame('N/A', $existing->fresh()->phone_primary);
    }

    public function test_a_booking_link_does_not_make_a_facility_off_limits(): void
    {
        // Regression. `care_facilities.facility_id` is the appointment-slot link
        // that BookableFacilityNetworkSeeder writes in bulk — 468 of the 903
        // rows have it, including 89 with no coordinates. Reading it as
        // "somebody owns this row" locked the importer out of half the directory
        // while protecting nobody.
        $operationalId = (string) Str::uuid();

        DB::table('facilities')->insert([
            'id'         => $operationalId,
            'name'       => 'Hôpital de District de Bassa',
            'type'       => 'hospital',
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $existing = $this->facility([
            'facility_name' => 'Hôpital de District de Bassa',
            'facility_id'   => $operationalId,
            'latitude'      => null,
            'longitude'     => null,
        ]);

        $this->fakeOverpass([
            $this->element(['amenity' => 'hospital', 'name' => 'Hôpital de District de Bassa']),
        ]);

        $this->runImport();

        $this->assertNotNull($existing->fresh()->latitude, 'A bookable facility with no coordinates is exactly what this import is for.');
    }

    // ── Refusing to decide ──────────────────────────────────────────────────

    public function test_an_ambiguous_candidate_is_neither_merged_nor_inserted(): void
    {
        // Same name, 3 km apart: either OSM has the pin wrong or we do. Merging
        // moves a hospital; inserting duplicates it. Neither is knowable here.
        $existing = $this->facility([
            'facility_name' => 'Hôpital Général de Douala',
            'latitude'      => 4.0511,
            'longitude'     => 9.7679,
        ]);

        $this->fakeOverpass([
            $this->element(['amenity' => 'hospital', 'name' => 'Hôpital Général de Douala'], 4.0800, 9.7900),
        ]);

        $this->runImport();

        $this->assertSame(1, CareFacility::count());
        $this->assertNull($existing->fresh()->source_ref, 'Nothing may be merged into it.');

        $review = DB::table('facility_import_reviews')->sole();
        $this->assertSame(OsmFacilityImporter::REASON_UNCERTAIN_MATCH, $review->reason);
        $this->assertSame('pending', $review->status);
        $this->assertSame($existing->id, $review->matched_facility_id);
        $this->assertNotNull($review->match_distance_m);
        $this->assertSame(OsmFacilityImporter::ATTRIBUTION, $review->source_attribution);
    }

    public function test_a_generic_name_is_held_for_review_not_listed(): void
    {
        // Ten Douala elements are called exactly 'Centre de Santé'. The name
        // identifies a category, not a building — it is not something a patient
        // can act on, and it is not something a matcher can compare.
        $this->fakeOverpass([
            $this->element(['amenity' => 'clinic', 'name' => 'Centre de Santé'], 4.0511, 9.7679, 1),
            $this->element(['amenity' => 'clinic', 'name' => 'Clinique'], 4.0611, 9.7779, 2),
        ]);

        $this->runImport();

        $this->assertSame(0, CareFacility::count());
        $this->assertSame(2, DB::table('facility_import_reviews')
            ->where('reason', OsmFacilityImporter::REASON_GENERIC_NAME)->count());
    }

    public function test_an_unnamed_element_is_never_merged_into_a_named_facility(): void
    {
        // 227 of OSM's 2,083 Cameroonian features have no name. Proximity alone
        // would merge this into the hospital next door.
        $existing = $this->facility([
            'facility_name' => 'Hôpital de District de Bassa',
            'latitude'      => self::DOUALA_LAT,
            'longitude'     => self::DOUALA_LNG,
        ]);

        $this->fakeOverpass([
            $this->element(['amenity' => 'hospital', 'dispensing' => 'Some Clinic'], self::DOUALA_LAT, self::DOUALA_LNG),
        ]);

        $this->runImport();

        $this->assertSame(1, CareFacility::count(), 'A nameless element must not be listed.');
        $this->assertNull($existing->fresh()->source_ref, 'And must not be merged into the facility beside it.');

        $review = DB::table('facility_import_reviews')->sole();
        $this->assertSame(OsmFacilityImporter::REASON_UNNAMED, $review->reason);
        $this->assertNull($review->candidate_name);

        // The reviewer needs the raw tags: the name is often sitting in a
        // mistagged key that a person can read and a heuristic should not guess.
        $this->assertStringContainsString('Some Clinic', (string) $review->payload);
    }

    public function test_two_osm_elements_cannot_both_bind_to_one_facility(): void
    {
        // Douala really has two nodes called 'Pharmacie de Bonamoussadi', 595 m
        // apart, with one of our rows on top of one of them. Duplicate mapping,
        // or a second branch? Opposite handling, and the tags cannot say which.
        $existing = $this->facility([
            'facility_name' => 'Pharmacie de Bonamoussadi',
            'facility_type' => 'pharmacy',
            'latitude'      => 4.0900,
            'longitude'     => 9.7400,
        ]);

        $this->fakeOverpass([
            // The first carries a phone, so it actually writes and the binding
            // is persisted rather than only held for the duration of the run.
            $this->element(['amenity' => 'pharmacy', 'name' => 'Pharmacie de Bonamoussadi', 'phone' => '+237 699111222'], 4.0900, 9.7400, 1),
            $this->element(['amenity' => 'pharmacy', 'name' => 'Pharmacie de Bonamoussadi'], 4.0947, 9.7420, 2),
        ]);

        $this->runImport();

        $this->assertSame(1, CareFacility::count(), 'The second element must not silently become a duplicate listing.');
        $this->assertSame('osm:node/1', $existing->fresh()->source_ref);

        $review = DB::table('facility_import_reviews')->sole();
        $this->assertSame(OsmFacilityImporter::REASON_ALREADY_LINKED, $review->reason);
        $this->assertSame('osm:node/2', $review->source_ref);
    }

    public function test_a_matching_name_in_an_incompatible_category_is_held_not_merged(): void
    {
        // A hospital and a pharmacy 100 m apart, both named after the same
        // neighbourhood. High name score, different kind of place.
        $this->facility([
            'facility_name' => 'Pharmacie de la Cité des Palmiers',
            'facility_type' => 'pharmacy',
            'latitude'      => self::DOUALA_LAT,
            'longitude'     => self::DOUALA_LNG,
        ]);

        $this->fakeOverpass([
            $this->element(
                ['amenity' => 'hospital', 'name' => 'Hôpital de District de la Cité des Palmiers'],
                self::DOUALA_LAT + 0.0009,
                self::DOUALA_LNG,
            ),
        ]);

        $this->runImport();

        $this->assertSame(1, DB::table('facility_import_reviews')
            ->where('reason', OsmFacilityImporter::REASON_TYPE_CONFLICT)->count());
        $this->assertSame(1, CareFacility::count());
    }

    // ── Idempotency and dry run ─────────────────────────────────────────────

    public function test_running_twice_neither_duplicates_nor_churns(): void
    {
        $existing = $this->facility([
            'facility_name' => 'Hôpital de District de Bassa',
            'latitude'      => null,
            'longitude'     => null,
        ]);

        $elements = [
            $this->element(['amenity' => 'hospital', 'name' => 'Hôpital de District de Bassa'], self::DOUALA_LAT, self::DOUALA_LNG, 1),
            $this->element(['amenity' => 'pharmacy', 'name' => 'Pharmacie Bonanjo Nord'], 4.0400, 9.7000, 2),
            $this->element(['amenity' => 'clinic', 'name' => 'Centre de Santé'], 4.0600, 9.7100, 3),
        ];

        $this->fakeOverpass($elements);
        $this->runImport();

        $facilitiesAfterFirst = CareFacility::count();
        $reviewsAfterFirst    = DB::table('facility_import_reviews')->count();
        $auditsAfterFirst     = DB::table('facility_update_audits')->count();

        // `updated_at` across the whole table: catches a silent rewrite that
        // leaves the row count unchanged. Read raw, so the comparison is of
        // timestamps and not of Carbon instances.
        $stampsAfterFirst = $this->updatedStamps('care_facilities');
        $reviewStamps     = $this->updatedStamps('facility_import_reviews');

        $this->assertSame(2, $facilitiesAfterFirst);
        $this->assertSame(1, $reviewsAfterFirst);

        $this->fakeOverpass($elements);
        $this->runImport();

        $this->assertSame($facilitiesAfterFirst, CareFacility::count());
        $this->assertSame($reviewsAfterFirst, DB::table('facility_import_reviews')->count());
        $this->assertSame($auditsAfterFirst, DB::table('facility_update_audits')->count(), 'A second run must not append duplicate audit rows.');
        $this->assertSame($stampsAfterFirst, $this->updatedStamps('care_facilities'), 'A second run must not touch a single row.');
        $this->assertSame($reviewStamps, $this->updatedStamps('facility_import_reviews'));

        $this->assertSame('osm:node/1', $existing->fresh()->source_ref);
    }

    public function test_a_human_decision_on_a_review_is_not_relitigated(): void
    {
        $this->fakeOverpass([
            $this->element(['amenity' => 'clinic', 'name' => 'Centre de Santé']),
        ]);
        $this->runImport();

        DB::table('facility_import_reviews')->update(['status' => 'rejected']);

        $this->fakeOverpass([
            $this->element(['amenity' => 'clinic', 'name' => 'Centre de Santé']),
        ]);
        $this->runImport();

        $this->assertSame('rejected', DB::table('facility_import_reviews')->sole()->status);
        $this->assertSame(0, CareFacility::count());
    }

    public function test_a_dry_run_writes_nothing_at_all(): void
    {
        $existing = $this->facility(['latitude' => null, 'longitude' => null]);

        $this->fakeOverpass([
            $this->element(['amenity' => 'hospital', 'name' => 'Hôpital de District de Bassa'], self::DOUALA_LAT, self::DOUALA_LNG, 1),
            $this->element(['amenity' => 'pharmacy', 'name' => 'Pharmacie Bonanjo Nord'], 4.0400, 9.7000, 2),
            $this->element(['amenity' => 'clinic', 'name' => 'Clinique'], 4.0600, 9.7100, 3),
        ]);

        $this->runImport(['--dry-run' => true]);

        $this->assertSame(1, CareFacility::count());
        $this->assertNull($existing->fresh()->latitude);
        $this->assertSame(0, DB::table('facility_import_reviews')->count());
        $this->assertSame(0, DB::table('facility_update_audits')->count());
    }

    // ── Provenance trail ────────────────────────────────────────────────────

    public function test_every_field_written_is_attributed_to_its_osm_element(): void
    {
        $existing = $this->facility([
            'latitude'  => null,
            'longitude' => null,
        ]);

        $this->fakeOverpass([
            $this->element(['amenity' => 'hospital', 'name' => 'Hôpital de District de Bassa'], self::DOUALA_LAT, self::DOUALA_LNG, 42),
        ]);

        $this->runImport();

        $audits = DB::table('facility_update_audits')->where('facility_id', $existing->id)->get();

        $this->assertGreaterThan(0, $audits->count());

        foreach ($audits as $audit) {
            $this->assertSame('system', $audit->actor_type);
            $this->assertSame('osm:node/42', $audit->source, 'Every field write names the OSM element it came from.');
        }

        $this->assertEqualsCanonicalizing(
            ['latitude', 'longitude', 'geocoding_accuracy'],
            $audits->pluck('field_changed')->all(),
        );
    }

    // ── Being a good guest on a free shared API ─────────────────────────────

    public function test_it_backs_off_and_retries_when_overpass_rate_limits(): void
    {
        Http::fake([
            '*overpass*' => Http::sequence()
                ->push('rate limited', 429, ['Retry-After' => '1'])
                ->push([
                    'osm3s'    => ['copyright' => 'ODbL'],
                    'elements' => [$this->element(['amenity' => 'pharmacy', 'name' => 'Pharmacie Bonanjo Nord'])],
                ], 200),
        ]);

        $this->runImport();

        // A 429 means "come back later", not "there are no facilities".
        $this->assertSame(1, CareFacility::where('source_system', 'openstreetmap')->count());
        Http::assertSentCount(2);
    }

    public function test_it_identifies_itself_to_the_overpass_operator(): void
    {
        $this->fakeOverpass([]);

        $this->runImport();

        // Overpass is free and shared, with no API key. A contactable
        // User-Agent is how an operator asks a client to slow down instead of
        // null-routing its IP.
        Http::assertSent(function ($request) {
            $agent = $request->header('User-Agent')[0] ?? '';

            return str_contains($agent, 'OpesCare')
                && str_contains($agent, 'opescare.cloud');
        });
    }

    public function test_a_non_retryable_overpass_error_fails_loudly_rather_than_importing_nothing(): void
    {
        // A 400 means our query is malformed. Reporting "0 facilities found"
        // would look exactly like a city with no pharmacies.
        Http::fake(['*overpass*' => Http::response('malformed query', 400)]);

        $this->artisan('facilities:import-osm', ['--city' => 'Douala', '--max-cache-age' => 0])
             ->assertFailed();

        $this->assertSame(0, CareFacility::count());
    }

    public function test_an_unknown_city_is_rejected_before_any_request_is_made(): void
    {
        Http::fake();

        $this->artisan('facilities:import-osm', ['--city' => 'Nowhereville'])->assertFailed();

        Http::assertNothingSent();
    }
}
