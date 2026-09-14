<?php

namespace Tests\Feature\Pharmacy;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Modules\CareMap\Services\FacilityClaimService;
use App\Models\Role;
use App\Models\User;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The "My pharmacy" settings page (routes/pharmacy_settings.php).
 *
 * A pharmacy is two records — the tenant (`facilities`) the staff log in to
 * and the public listing (`care_facilities`) patients search — and until
 * this page the only editor of the listing sat behind an approved claim
 * almost no manager had, and could not set a position at all. These hold
 * the gate (service_catalogue), the tenant-only resolution, the coordinate
 * write that makes ISSUE_NO_COORDINATES disappear, and the rule that an
 * unlinked tenant is explained to, never auto-linked.
 */
class PharmacySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** @return array{0: Facility, 1: User} */
    private function tenant(string $roleName = 'pharmacy_manager', string $name = 'Pharmacie du Marché'): array
    {
        $facility = Facility::factory()->create(['type' => 'pharmacy', 'name' => $name]);
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id]);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return [$facility, $user];
    }

    private function listing(Facility $facility, array $overrides = []): CareFacility
    {
        return CareFacility::create(array_merge([
            'facility_id' => $facility->id, 'facility_name' => $facility->name, 'facility_type' => 'pharmacy',
            'listing_status' => 'active', 'country_code' => 'CM', 'region' => 'Littoral', 'city' => 'Douala',
            'address' => 'Rue Joss, Akwa', 'latitude' => null, 'longitude' => null, 'phone_primary' => 'N/A',
        ], $overrides));
    }

    private function as(User $user, Facility $facility)
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    // ── Gate ────────────────────────────────────────────────────────────────

    public function test_a_pharmacy_manager_opens_the_page(): void
    {
        [$facility, $user] = $this->tenant();
        $this->listing($facility);

        $this->as($user, $facility)->get(route('portals.pharmacy.settings.index'))
            ->assertOk()
            ->assertSee(__('pharmacy_settings.title'))
            ->assertSee('Pharmacie du Marché')
            ->assertSee(__('pharmacy_settings.warn_no_coordinates'));
    }

    public function test_a_pharmacist_is_refused_with_403(): void
    {
        [$facility, $user] = $this->tenant('pharmacist');
        $this->listing($facility);

        $this->as($user, $facility)->get(route('portals.pharmacy.settings.index'))->assertForbidden();
        $this->as($user, $facility)->post(route('portals.pharmacy.settings.location'), [
            'address' => 'x', 'city' => 'y', 'latitude' => 4, 'longitude' => 9,
        ])->assertForbidden();
    }

    // ── Location ────────────────────────────────────────────────────────────

    public function test_saving_a_position_clears_the_no_coordinates_issue_on_the_stock_page(): void
    {
        [$facility, $user] = $this->tenant();
        $listing = $this->listing($facility);
        $report  = app(PharmacyStockReportService::class);

        $this->assertContains(PharmacyStockReportService::ISSUE_NO_COORDINATES, $report->listingIssues($listing));

        $this->as($user, $facility)->post(route('portals.pharmacy.settings.location'), [
            'address' => 'Rue Joss, Akwa', 'city' => 'Douala', 'region' => 'Littoral',
            'latitude' => '4.052000', 'longitude' => '9.769000',
        ])->assertRedirect(route('portals.pharmacy.settings.index'))->assertSessionHasNoErrors();

        $listing->refresh();
        $this->assertEqualsWithDelta(4.052, $listing->latitude, 1e-6);
        $this->assertEqualsWithDelta(9.769, $listing->longitude, 1e-6);
        $this->assertSame([], $report->listingIssues($listing));
        $this->assertNotNull($listing->last_profile_update_at);

        // Recorded for review, by the manager, as the facility.
        $this->assertDatabaseHas('facility_update_audits', [
            'facility_id' => $listing->id, 'actor_id' => $user->id, 'actor_type' => 'facility',
            'field_changed' => 'latitude', 'new_value' => '4.052', 'requires_review' => true,
        ]);
    }

    public function test_an_invalid_latitude_or_a_lone_longitude_is_rejected(): void
    {
        [$facility, $user] = $this->tenant();
        $listing = $this->listing($facility);

        $this->as($user, $facility)->post(route('portals.pharmacy.settings.location'), [
            'address' => 'Rue Joss', 'city' => 'Douala', 'latitude' => '95', 'longitude' => '9.7',
        ])->assertSessionHasErrors('latitude');

        $this->as($user, $facility)->post(route('portals.pharmacy.settings.location'), [
            'address' => 'Rue Joss', 'city' => 'Douala', 'longitude' => '9.7',
        ])->assertSessionHasErrors('latitude');

        $this->assertNull($listing->refresh()->latitude);
    }

    // ── Identity ────────────────────────────────────────────────────────────

    public function test_identity_writes_the_listing_and_the_tenant_follows_and_a_verified_listing_becomes_self_reported(): void
    {
        [$facility, $user] = $this->tenant();
        $listing = $this->listing($facility, ['verification_status' => 'license_verified']);

        $this->as($user, $facility)->post(route('portals.pharmacy.settings.identity'), [
            'facility_name' => 'Pharmacie du Marché Central', 'license_number' => 'MINSANTE-123',
            'phone_primary' => '+237 690 00 00 01', 'email' => 'contact@marche.cm',
        ])->assertRedirect(route('portals.pharmacy.settings.index'))->assertSessionHasNoErrors();

        $listing->refresh();
        $facility->refresh();
        $this->assertSame('Pharmacie du Marché Central', $listing->facility_name);
        $this->assertSame('Pharmacie du Marché Central', $facility->name);
        $this->assertSame('MINSANTE-123', $listing->license_number);
        $this->assertSame('MINSANTE-123', $facility->license_number);
        $this->assertSame('+237 690 00 00 01', $listing->phone_primary);
        $this->assertSame('contact@marche.cm', $listing->email);
        $this->assertSame('self_reported', $listing->verification_status, 'a renamed listing must not stay verified on the facility\'s say-so');
        $this->assertSame('active', $listing->listing_status, 'fixing a name never removes a pharmacy from search');
        $this->assertDatabaseHas('facility_update_audits', [
            'facility_id' => $listing->id, 'field_changed' => 'facility_name', 'requires_review' => true,
        ]);
        $this->assertDatabaseHas('facility_update_audits', [
            'facility_id' => $listing->id, 'field_changed' => 'verification_status', 'new_value' => 'self_reported',
        ]);
    }

    public function test_an_unverified_listing_stays_unverified_after_a_rename(): void
    {
        [$facility, $user] = $this->tenant();
        $listing = $this->listing($facility);

        $this->as($user, $facility)->post(route('portals.pharmacy.settings.identity'), ['facility_name' => 'Renamed'])
            ->assertSessionHasNoErrors();

        $this->assertSame('unverified', $listing->refresh()->verification_status);
    }

    // ── Hours: both paths write identical rows ──────────────────────────────

    public function test_hours_saved_here_match_the_rows_the_claimed_listing_route_writes(): void
    {
        $week = ['hours' => [
            1 => ['opens_at' => '08:00', 'closes_at' => '17:00'],
            2 => ['opens_at' => '08:00', 'closes_at' => '17:00'],
            0 => ['is_closed' => '1'],
            6 => ['is_24_hours' => '1'],
        ]];

        // Path A: the pharmacy settings page, resolved from the session tenant.
        [$facilityA, $manager] = $this->tenant();
        $listingA = $this->listing($facilityA);
        $this->as($manager, $facilityA)->post(route('portals.pharmacy.settings.hours'), $week)
            ->assertRedirect(route('portals.pharmacy.settings.index'));

        // Path B: the claimed-listing editor, resolved from an approved claim.
        $listingB  = $this->listing(Facility::factory()->create(['type' => 'pharmacy', 'name' => 'Other']));
        $claimant  = User::factory()->create();
        $claims    = app(FacilityClaimService::class);
        $claim     = $claims->submitDirectoryClaim($listingB->id, $claimant->id, [
            'claimant_name' => 'Dr Ada Ngu', 'claimant_role' => 'owner',
            'claimant_email' => 'ada@example.cm', 'claimant_phone' => '+237 600 000 001',
        ]);
        $claims->approveClaim($claim->id, User::factory()->create()->id);
        $this->actingAs($claimant)->post(route('portals.listing.hours.update'), $week)
            ->assertRedirect(route('portals.listing.edit'));

        $shape = fn (string $listingId) => DB::table('care_facility_hours')->where('facility_id', $listingId)
            ->orderBy('day_of_week')
            ->get(['day_of_week', 'opens_at', 'closes_at', 'is_closed', 'is_24_hours', 'service_context'])
            ->map(fn ($r) => (array) $r)->all();

        $this->assertCount(4, $shape($listingA->id));
        $this->assertSame($shape($listingB->id), $shape($listingA->id));
    }

    // ── Unlinked tenant ─────────────────────────────────────────────────────

    public function test_an_unlinked_tenant_is_explained_to_and_never_auto_linked(): void
    {
        [$facility, $user] = $this->tenant('pharmacy_manager', 'Pharmacie Sans Fiche');

        $this->as($user, $facility)->get(route('portals.pharmacy.settings.index'))
            ->assertOk()
            ->assertSee('Pharmacie Sans Fiche')
            ->assertSee(__('pharmacy_settings.not_linked_title'))
            ->assertSee(route('portals.listing.claims'));

        $this->as($user, $facility)->post(route('portals.pharmacy.settings.identity'), ['facility_name' => 'Anything'])
            ->assertNotFound();

        $this->assertSame(0, CareFacility::count(), 'no listing may be created or linked from this page');
        $this->assertSame('Pharmacie Sans Fiche', $facility->refresh()->name);
    }

    // ── Scope ───────────────────────────────────────────────────────────────

    public function test_another_facilitys_listing_is_untouched_whatever_ids_are_posted(): void
    {
        [$mine, $manager] = $this->tenant();
        $myListing = $this->listing($mine);
        $theirs    = $this->listing(Facility::factory()->create(['type' => 'pharmacy', 'name' => 'Theirs']), [
            'facility_name' => 'Theirs', 'latitude' => 3.8, 'longitude' => 11.5,
        ]);

        $this->as($manager, $mine)->post(route('portals.pharmacy.settings.location'), [
            'address' => 'Nouvelle adresse', 'city' => 'Douala', 'latitude' => '4.1', 'longitude' => '9.8',
            'id' => $theirs->id, 'facility_id' => $theirs->facility_id, 'care_facility_id' => $theirs->id,
        ])->assertSessionHasNoErrors();

        $theirs->refresh();
        $this->assertSame('Theirs', $theirs->facility_name);
        $this->assertEqualsWithDelta(3.8, $theirs->latitude, 1e-6);
        $this->assertNull($theirs->last_profile_update_at);
        $this->assertSame('Nouvelle adresse', $myListing->refresh()->address);
        $this->assertSame(0, DB::table('facility_update_audits')->where('facility_id', $theirs->id)->count());
    }
}
