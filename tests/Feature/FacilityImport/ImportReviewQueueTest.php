<?php

namespace Tests\Feature\FacilityImport;

use App\Enums\FacilityImportReviewStatus;
use App\Models\CareFacility;
use App\Models\FacilityImportReview;
use App\Models\Role;
use App\Models\User;
use App\Modules\CareMap\Services\FacilityImportReviewService;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The import-review queue as a person actually works it.
 *
 * 439 OpenStreetMap candidates have been sitting at `pending` because the
 * importer refused to decide them — correctly, since this platform does not
 * probabilistically auto-merge identity data. These tests cover the part that
 * makes the human decision possible rather than the part that makes it:
 *
 *   - the reviewer can SEE both records before judging a duplicate;
 *   - candidates that resolve to the same listing announce each other;
 *   - a candidate can be parked without being decided;
 *   - parking is not approving — nothing is created, and the row stays open;
 *   - the ODbL attribution is on the screen that displays OSM data;
 *   - only a platform administrator gets any of it.
 */
class ImportReviewQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    // ─── Fixtures ────────────────────────────────────────────────────────────

    private function platformAdmin(): User
    {
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);

        $role = Role::where('name', 'super_admin')->firstOrFail();
        $user = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $user->role_id = $role->id;
        $user->save();

        return $user;
    }

    private function listing(array $overrides = []): CareFacility
    {
        return CareFacility::create(array_merge([
            'facility_name'       => 'Hôpital de District de Test ' . Str::random(6),
            'facility_type'       => 'hospital',
            'country_code'        => 'CM',
            'region'              => 'Centre',
            'city'                => 'Yaoundé',
            'address'             => 'Yaoundé, Cameroon',
            'phone_primary'       => CareFacility::PHONE_PLACEHOLDER,
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'license_status'      => 'active',
            'integration_status'  => 'none',
        ], $overrides));
    }

    private function review(array $overrides = []): FacilityImportReview
    {
        return FacilityImportReview::create(array_merge([
            'source_system'      => 'openstreetmap',
            'source_ref'         => 'node/' . random_int(100000, 999999) . Str::random(4),
            'source_attribution' => '© OpenStreetMap contributors, ODbL',
            'reason'             => 'uncertain_match',
            'status'             => FacilityImportReviewStatus::Pending->value,
            'candidate_name'     => 'Pharmacie du Carrefour',
            'candidate_type'     => 'pharmacy',
            'candidate_city'     => 'Douala',
            'candidate_region'   => 'Littoral',
            'latitude'           => 4.0511,
            'longitude'          => 9.7679,
            'payload'            => ['amenity' => 'pharmacy'],
        ], $overrides));
    }

    // ─── 1. A duplicate cannot be judged from one record ─────────────────────

    public function test_a_suspected_duplicate_shows_both_records_side_by_side(): void
    {
        $admin = $this->platformAdmin();

        $existing = $this->listing([
            'facility_name' => 'Pharmacie Carrefour Bonamoussadi',
            'facility_type' => 'pharmacy',
            'city'          => 'Douala',
            'region'        => 'Littoral',
            'latitude'      => 4.0515,
            'longitude'     => 9.7681,
            'phone_primary' => '+237 233 000 111',
        ]);

        $this->review([
            'matched_facility_id'   => $existing->id,
            'matched_facility_name' => $existing->facility_name,
            'match_score'           => 0.780,
            'match_distance_m'      => 48,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.care-map.review', ['tab' => 'imports']))
            ->assertOk();

        // Both column headings, so the reviewer knows which side is which.
        $response->assertSee(__('facility_review.compare_candidate'));
        $response->assertSee(__('facility_review.compare_existing'));

        // The candidate's own data …
        $response->assertSee('Pharmacie du Carrefour');
        $response->assertSee('4.05110, 9.76790');

        // … next to the existing listing's, which is the only way to tell them
        // apart: same town, 48 m away, one letter of name in common.
        $response->assertSee('Pharmacie Carrefour Bonamoussadi');
        $response->assertSee('4.05150, 9.76810');
        $response->assertSee('+237 233 000 111');

        $response->assertSee(__('facility_review.match_distance', ['metres' => 48]));
    }

    public function test_a_candidate_with_no_match_says_so_instead_of_showing_a_blank_column(): void
    {
        $admin = $this->platformAdmin();
        $this->review(['reason' => 'generic_name', 'matched_facility_id' => null]);

        $this->actingAs($admin)
            ->get(route('admin.care-map.review', ['tab' => 'imports']))
            ->assertOk()
            ->assertSee(__('facility_review.no_match'));
    }

    // ─── 2. Candidates that resolve to one listing announce each other ───────

    public function test_candidates_pointing_at_the_same_listing_warn_the_reviewer(): void
    {
        $admin    = $this->platformAdmin();
        $existing = $this->listing(['facility_name' => 'Centre Médical de Bafoussam']);

        // 59 real clusters look exactly like this: one hospital, several OSM
        // elements. Accepting them one at a time creates the duplicate the
        // whole queue exists to prevent.
        foreach (['Centre Medical Bafoussam', 'CM Bafoussam', 'Centre Médical Bafoussam'] as $name) {
            $this->review([
                'candidate_name'        => $name,
                'matched_facility_id'   => $existing->id,
                'matched_facility_name' => $existing->facility_name,
                'match_score'           => 0.72,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.care-map.review', ['tab' => 'imports']))
            ->assertOk()
            ->assertSee(__('facility_review.cluster_warning', ['count' => 3]));
    }

    public function test_a_lone_candidate_gets_no_cluster_warning(): void
    {
        $admin    = $this->platformAdmin();
        $existing = $this->listing();

        $this->review([
            'matched_facility_id'   => $existing->id,
            'matched_facility_name' => $existing->facility_name,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.care-map.review', ['tab' => 'imports']))
            ->assertOk()
            ->assertDontSee(__('facility_review.cluster_warning', ['count' => 1]));
    }

    // ─── 3. Deferring is not deciding ────────────────────────────────────────

    public function test_an_admin_can_defer_a_candidate_and_nothing_is_created(): void
    {
        $admin  = $this->platformAdmin();
        $review = $this->review();

        $before = CareFacility::count();

        $this->actingAs($admin)
            ->post(route('admin.care-map.review.imports.defer', $review->id), [
                'review_notes' => 'Calling the district office to confirm it still operates.',
            ])
            ->assertRedirect();

        $review->refresh();

        $this->assertSame(FacilityImportReviewStatus::Deferred, $review->status);
        $this->assertSame($admin->id, $review->reviewed_by);
        $this->assertNotNull($review->reviewed_at);
        $this->assertSame('Calling the district office to confirm it still operates.', $review->review_notes);

        // Deferring is emphatically not approving.
        $this->assertSame($before, CareFacility::count());
        $this->assertDatabaseMissing('care_facilities', ['source_ref' => $review->source_ref]);
    }

    public function test_a_deferred_candidate_is_still_open_and_can_be_decided_later(): void
    {
        $admin  = $this->platformAdmin();
        $review = $this->review(['candidate_name' => 'Clinique Espoir']);

        app(FacilityImportReviewService::class)->defer($review, $admin->id, 'need a phone call');

        $review->refresh();
        $this->assertTrue($review->status->isOpen());
        $this->assertFalse($review->status->isDecided());

        // …and the decision still lands afterwards.
        $this->actingAs($admin)
            ->post(route('admin.care-map.review.imports.accept', $review->id))
            ->assertRedirect();

        $this->assertSame(FacilityImportReviewStatus::Imported, $review->fresh()->status);
        $this->assertDatabaseHas('care_facilities', ['facility_name' => 'Clinique Espoir']);
    }

    public function test_a_decided_candidate_cannot_be_deferred(): void
    {
        $admin  = $this->platformAdmin();
        $review = $this->review();

        app(FacilityImportReviewService::class)->reject($review, $admin->id);

        $this->actingAs($admin)
            ->post(route('admin.care-map.review.imports.defer', $review->id))
            ->assertSessionHas('error', __('caremap_claim.error_import_decided'));

        $this->assertSame(FacilityImportReviewStatus::Rejected, $review->fresh()->status);
    }

    public function test_a_deferred_candidate_leaves_the_default_queue_but_stays_reachable(): void
    {
        $admin  = $this->platformAdmin();
        $review = $this->review(['candidate_name' => 'Dispensaire de Kribi']);

        app(FacilityImportReviewService::class)->defer($review, $admin->id);

        // Gone from the default (pending) view …
        $this->actingAs($admin)
            ->get(route('admin.care-map.review', ['tab' => 'imports']))
            ->assertOk()
            ->assertDontSee('Dispensaire de Kribi');

        // … but not lost: "defer" must not be a synonym for "discard".
        $this->actingAs($admin)
            ->get(route('admin.care-map.review', [
                'tab'    => 'imports',
                'status' => FacilityImportReviewStatus::Deferred->value,
            ]))
            ->assertOk()
            ->assertSee('Dispensaire de Kribi')
            ->assertSee(__('facility_review.deferred_meta', [
                'name' => $admin->name,
                'date' => now()->format('Y-m-d'),
            ]));
    }

    // ─── 4. Filters ──────────────────────────────────────────────────────────

    public function test_the_queue_filters_by_reason(): void
    {
        $admin = $this->platformAdmin();

        $this->review(['reason' => 'generic_name',    'candidate_name' => 'Centre de Santé']);
        $this->review(['reason' => 'uncertain_match', 'candidate_name' => 'Clinique Bonanjo']);

        $this->actingAs($admin)
            ->get(route('admin.care-map.review', ['tab' => 'imports', 'reason' => 'generic_name']))
            ->assertOk()
            ->assertSee('Centre de Santé')
            ->assertDontSee('Clinique Bonanjo');
    }

    public function test_an_unknown_status_filter_falls_back_to_pending_rather_than_erroring(): void
    {
        $admin = $this->platformAdmin();
        $this->review(['candidate_name' => 'Hôpital Laquintinie']);

        $this->actingAs($admin)
            ->get(route('admin.care-map.review', ['tab' => 'imports', 'status' => 'nonsense']))
            ->assertOk()
            ->assertSee('Hôpital Laquintinie');
    }

    // ─── 5. Licence and honesty about what a listing is ──────────────────────

    public function test_the_odbl_attribution_is_shown_wherever_osm_data_is(): void
    {
        $admin = $this->platformAdmin();
        $this->review(['source_attribution' => '© OpenStreetMap contributors, ODbL']);

        $this->actingAs($admin)
            ->get(route('admin.care-map.review', ['tab' => 'imports']))
            ->assertOk()
            ->assertSee('OpenStreetMap contributors', false)
            ->assertSee(__('facility_review.attribution_note'));
    }

    public function test_the_queue_never_claims_a_listing_is_verified(): void
    {
        $admin = $this->platformAdmin();
        $this->review();

        $this->actingAs($admin)
            ->get(route('admin.care-map.review', ['tab' => 'imports']))
            ->assertOk()
            ->assertSee(__('facility_review.never_verified'));
    }

    // ─── 6. Merging stamps provenance instead of duplicating ─────────────────

    public function test_merging_stamps_the_upstream_reference_on_the_existing_listing(): void
    {
        $admin    = $this->platformAdmin();
        $existing = $this->listing();

        $review = $this->review([
            'matched_facility_id'   => $existing->id,
            'matched_facility_name' => $existing->facility_name,
            'match_score'           => 0.91,
        ]);

        $before = CareFacility::count();

        $this->actingAs($admin)
            ->post(route('admin.care-map.review.imports.merge', $review->id))
            ->assertRedirect();

        // No second row for the same real hospital.
        $this->assertSame($before, CareFacility::count());

        $existing->refresh();
        $this->assertSame($review->source_ref, $existing->source_ref);
        $this->assertSame('openstreetmap', $existing->source_system);
        // Enrichment is not authorship, but the ODbL notice travels with the row.
        $this->assertNotNull($existing->source_attribution);
        // And a merge is still not a verification.
        $this->assertSame('unverified', $existing->verification_status);

        $this->assertSame(FacilityImportReviewStatus::Merged, $review->fresh()->status);
        $this->assertSame($admin->id, $review->fresh()->reviewed_by);
    }

    // ─── 7. National registry data is platform-tier only ─────────────────────

    public function test_a_non_platform_user_cannot_reach_or_decide_the_queue(): void
    {
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);

        $review  = $this->review();
        $outsider = User::factory()->create(['status' => 'active']);

        $this->actingAs($outsider)
            ->get(route('admin.care-map.review', ['tab' => 'imports']))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->post(route('admin.care-map.review.imports.defer', $review->id))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->post(route('admin.care-map.review.imports.accept', $review->id))
            ->assertForbidden();

        $this->assertSame(FacilityImportReviewStatus::Pending, $review->fresh()->status);
    }

    public function test_a_guest_is_sent_to_login_not_the_queue(): void
    {
        $this->get(route('admin.care-map.review'))->assertRedirect();
    }
}
