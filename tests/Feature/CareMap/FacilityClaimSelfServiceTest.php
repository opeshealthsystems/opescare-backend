<?php

namespace Tests\Feature\CareMap;

use App\Enums\FacilityClaimStatus;
use App\Enums\FacilityImportReviewStatus;
use App\Models\CareFacility;
use App\Models\CareFacilityService;
use App\Models\FacilityClaim;
use App\Models\FacilityImportReview;
use App\Models\Role;
use App\Models\User;
use App\Modules\CareMap\Services\FacilityClaimService;
use App\Modules\CareMap\Services\FacilityImportReviewService;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The claim → review → self-service-edit pipeline, and the directory review
 * desk that decides both kinds of question about a facility.
 *
 * The tests that matter most are the negative ones. A directory of 1,863
 * facilities where anyone who submits a form can edit anyone's listing is worse
 * than no directory, so:
 *
 *   - a claim cannot approve itself;
 *   - an approved claimant reaches exactly one listing;
 *   - putting another facility's id in the request changes nothing;
 *   - approval never touches verification_status.
 */
class FacilityClaimSelfServiceTest extends TestCase
{
    use RefreshDatabase;

    private FacilityClaimService $claims;

    protected function setUp(): void
    {
        parent::setUp();
        $this->claims = app(FacilityClaimService::class);
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    // ─── Fixtures ────────────────────────────────────────────────────────────

    private function listing(array $overrides = []): CareFacility
    {
        return CareFacility::create(array_merge([
            'facility_name'       => 'Hôpital de District de Test ' . Str::random(6),
            'facility_type'       => 'hospital',
            'country_code'        => 'CM',
            'region'              => 'Centre',
            'city'                => 'Yaoundé',
            'address'             => 'Yaoundé, Cameroon',
            // Exactly what 1,571 of the 1,863 real rows carry.
            'phone_primary'       => CareFacility::PHONE_PLACEHOLDER,
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'license_status'      => 'active',
            'integration_status'  => 'none',
        ], $overrides));
    }

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

    /** A claimant with an approved claim on $listing. */
    private function approvedClaimant(CareFacility $listing, ?User $admin = null): User
    {
        $user  = User::factory()->create();
        $admin ??= User::factory()->create();

        $claim = $this->claims->submitDirectoryClaim($listing->id, $user->id, [
            'claimant_name'  => 'Dr Ada Ngu',
            'claimant_role'  => 'owner',
            'claimant_email' => 'ada@example.cm',
            'claimant_phone' => '+237 600 000 001',
        ]);

        $this->claims->approveClaim($claim->id, $admin->id);

        return $user;
    }

    // ─── 1. A claim cannot auto-approve ──────────────────────────────────────

    public function test_a_submitted_claim_does_not_auto_approve(): void
    {
        $listing = $this->listing();
        $user    = User::factory()->create();

        $response = $this->actingAs($user)->post("/care-map/facility/{$listing->id}/claim", [
            'claimant_name'  => 'Jean Mbarga',
            'claimant_role'  => 'manager',
            'claimant_email' => 'jean@example.cm',
            'claimant_phone' => '+237 690 000 000',
            'claim_reason'   => 'I manage this hospital.',
        ]);

        $response->assertRedirect(route('portals.listing.claims'));

        $claim = FacilityClaim::where('care_facility_id', $listing->id)->firstOrFail();

        $this->assertSame(FacilityClaimStatus::Submitted, $claim->claim_status);
        $this->assertFalse($claim->claim_status->grantsEditAccess());
        $this->assertNull($claim->reviewed_by);
        $this->assertNull($claim->reviewed_at);

        // Nothing on the listing moved.
        $listing->refresh();
        $this->assertNull($listing->claimed_by_user_id);
        $this->assertFalse($listing->isClaimed());
        $this->assertSame('unverified', $listing->verification_status);

        // And the claimant has no edit access.
        $this->assertNull($this->claims->approvedListingFor($user->id));

        $this->actingAs($user)
            ->post(route('portals.listing.update'), ['phone_primary' => '+237 222 000 000'])
            ->assertForbidden();

        $listing->refresh();
        $this->assertSame(CareFacility::PHONE_PLACEHOLDER, $listing->phone_primary);
    }

    public function test_the_claim_page_renders_and_is_behind_auth(): void
    {
        $listing = $this->listing(['facility_name' => 'Hôpital Laquintinie']);

        $this->get("/care-map/facility/{$listing->id}/claim")->assertRedirect();

        $this->actingAs(User::factory()->create())
            ->get(route('public.care-map.claim', $listing->id))
            ->assertOk()
            ->assertSee('Hôpital Laquintinie')
            ->assertSee(__('caremap_claim.claim_review_notice'))
            ->assertSee(__('caremap_claim.hint_false_claim'));
    }

    public function test_no_status_other_than_approved_grants_edit_access(): void
    {
        foreach (FacilityClaimStatus::cases() as $status) {
            $this->assertSame(
                $status === FacilityClaimStatus::Approved,
                $status->grantsEditAccess(),
                "{$status->value} must not grant edit access"
            );
        }
    }

    // ─── 2. Approval never touches verification_status ───────────────────────

    public function test_approving_a_claim_does_not_alter_verification_status(): void
    {
        $listing = $this->listing();
        $admin   = User::factory()->create();
        $user    = User::factory()->create();

        $before = $listing->verification_status;

        $claim = $this->claims->submitDirectoryClaim($listing->id, $user->id, [
            'claimant_name' => 'Marie Fotso',
            'claimant_role' => 'owner',
        ]);

        $this->claims->approveClaim($claim->id, $admin->id);

        $listing->refresh();

        $this->assertSame($before, $listing->verification_status);
        $this->assertSame('unverified', $listing->verification_status);

        // "Claimed" is recorded, and it is a different column than "verified".
        $this->assertTrue($listing->isClaimed());
        $this->assertSame($user->id, $listing->claimed_by_user_id);
        $this->assertNotNull($listing->claimed_at);
    }

    public function test_no_edit_path_can_write_verification_status(): void
    {
        $listing   = $this->listing();
        $claimant  = $this->approvedClaimant($listing);

        $this->actingAs($claimant)->post(route('portals.listing.update'), [
            'phone_primary'       => '+237 233 400 100',
            'verification_status' => 'government_verified',   // ignored on purpose
            'listing_status'      => 'suspended',
        ])->assertRedirect(route('portals.listing.edit'));

        $listing->refresh();
        $this->assertSame('+237 233 400 100', $listing->phone_primary);
        $this->assertSame('unverified', $listing->verification_status);
        $this->assertSame('active', $listing->listing_status);
    }

    // ─── 3. An approved claimant edits their own listing ─────────────────────

    public function test_an_approved_claimant_can_edit_their_own_listing(): void
    {
        $listing  = $this->listing();
        $claimant = $this->approvedClaimant($listing);

        $this->actingAs($claimant)->post(route('portals.listing.update'), [
            'phone_primary'   => '+237 222 234 567',
            'phone_secondary' => '+237 699 111 222',
            'email'           => 'contact@hopital-test.cm',
            'website'         => 'https://hopital-test.cm',
            'description'     => 'District hospital serving Yaoundé V.',
        ])->assertRedirect(route('portals.listing.edit'));

        $listing->refresh();

        $this->assertSame('+237 222 234 567', $listing->phone_primary);
        $this->assertSame('+237 699 111 222', $listing->phone_secondary);
        $this->assertSame('contact@hopital-test.cm', $listing->email);
        $this->assertSame('https://hopital-test.cm', $listing->website);
        $this->assertNotNull($listing->last_profile_update_at);
    }

    public function test_the_editor_page_renders_for_an_approved_claimant(): void
    {
        $listing  = $this->listing(['facility_name' => 'Clinique Bastos']);
        $claimant = $this->approvedClaimant($listing);

        $this->actingAs($claimant)
            ->get(route('portals.listing.edit'))
            ->assertOk()
            ->assertSee('Clinique Bastos');
    }

    public function test_a_user_with_no_approved_claim_sees_the_empty_state_not_an_editor(): void
    {
        $this->listing();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('portals.listing.edit'))
            ->assertOk()
            ->assertSee(__('caremap_claim.none_title'));

        $this->actingAs($user)
            ->post(route('portals.listing.hours.update'), [])
            ->assertForbidden();
    }

    // ─── 4. A claimant can never reach a second facility ─────────────────────

    public function test_a_claimant_cannot_edit_a_second_facility_by_passing_an_id(): void
    {
        $mine     = $this->listing(['facility_name' => 'My Clinic']);
        $theirs   = $this->listing(['facility_name' => 'Somebody Elses Hospital', 'phone_primary' => '+237 111 111 111']);
        $claimant = $this->approvedClaimant($mine);

        // Every id shape an attacker might reach for, in one request.
        $this->actingAs($claimant)->post(route('portals.listing.update'), [
            'phone_primary'    => '+237 999 999 999',
            'id'               => $theirs->id,
            'facility_id'      => $theirs->id,
            'care_facility_id' => $theirs->id,
        ])->assertRedirect(route('portals.listing.edit'));

        $mine->refresh();
        $theirs->refresh();

        $this->assertSame('+237 999 999 999', $mine->phone_primary, 'The claimed listing should have been updated');
        $this->assertSame('+237 111 111 111', $theirs->phone_primary, 'Another facility must be untouched');
        $this->assertNull($theirs->claimed_by_user_id);
        $this->assertNull($theirs->last_profile_update_at);

        // Not one audit row names the other facility.
        $this->assertSame(0, DB::table('facility_update_audits')->where('facility_id', $theirs->id)->count());
    }

    public function test_a_claimant_cannot_delete_another_facilitys_service(): void
    {
        $mine     = $this->listing();
        $theirs   = $this->listing();
        $claimant = $this->approvedClaimant($mine);

        $foreign = CareFacilityService::create([
            'facility_id'         => $theirs->id,
            'service_name'        => 'Dialysis',
            'service_category'    => 'diagnostic',
            'availability_status' => 'available',
        ]);

        $this->actingAs($claimant)
            ->delete(route('portals.listing.services.destroy', $foreign->id))
            ->assertRedirect(route('portals.listing.edit'));

        $this->assertDatabaseHas('care_facility_services', ['id' => $foreign->id]);
    }

    public function test_the_facility_is_resolved_from_the_claim_and_survives_revocation(): void
    {
        $listing  = $this->listing();
        $admin    = User::factory()->create();
        $claimant = $this->approvedClaimant($listing, $admin);

        $this->assertSame($listing->id, $this->claims->approvedListingFor($claimant->id)?->id);

        // Somebody with no claim at all resolves to nothing — never to "the
        // first facility in the table".
        $stranger = User::factory()->create();
        $this->assertNull($this->claims->approvedListingFor($stranger->id));
        $this->assertNull($this->claims->approvedListingFor(null));
        $this->assertNull($this->claims->approvedListingFor(''));

        $claim = FacilityClaim::where('claimant_user_id', $claimant->id)->firstOrFail();
        $this->claims->revokeClaim($claim->id, $admin->id, 'Could not confirm authority');

        $this->assertNull($this->claims->approvedListingFor($claimant->id));
        $this->actingAs($claimant)
            ->post(route('portals.listing.update'), ['email' => 'x@example.cm'])
            ->assertForbidden();
    }

    public function test_a_second_person_cannot_claim_an_already_claimed_listing(): void
    {
        $listing = $this->listing();
        $this->approvedClaimant($listing);

        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->post("/care-map/facility/{$listing->id}/claim", [
                'claimant_name'  => 'Not The Owner',
                'claimant_role'  => 'owner',
                'claimant_email' => 'nope@example.cm',
                'claimant_phone' => '+237 600 000 999',
            ])
            ->assertRedirect(route('public.care-map.claim', $listing->id))
            ->assertSessionHas('error', __('caremap_claim.error_already_claimed'));

        $this->assertSame(1, FacilityClaim::where('care_facility_id', $listing->id)->count());
        $this->assertNull($this->claims->approvedListingFor($intruder->id));
    }

    // ─── 5. Edits are attributable ───────────────────────────────────────────

    public function test_edits_are_audited_with_the_actor_and_both_values(): void
    {
        $listing  = $this->listing(['email' => 'old@example.cm']);
        $claimant = $this->approvedClaimant($listing);

        $this->actingAs($claimant)->post(route('portals.listing.update'), [
            'phone_primary' => '+237 233 000 111',
            'email'         => 'new@example.cm',
        ]);

        $phoneAudit = DB::table('facility_update_audits')
            ->where('facility_id', $listing->id)
            ->where('field_changed', 'phone_primary')
            ->where('source', 'facility_self_service')
            ->first();

        $this->assertNotNull($phoneAudit, 'A phone edit must be recorded in facility_update_audits');
        $this->assertSame('facility', $phoneAudit->actor_type);
        $this->assertSame($claimant->id, $phoneAudit->actor_id);
        // 'N/A' is a placeholder, not a number: the audit records that it was
        // empty rather than pretending a real number was overwritten.
        $this->assertNull($phoneAudit->old_value);
        $this->assertSame('+237 233 000 111', $phoneAudit->new_value);

        $emailAudit = DB::table('facility_update_audits')
            ->where('facility_id', $listing->id)
            ->where('field_changed', 'email')
            ->first();

        $this->assertNotNull($emailAudit);
        $this->assertSame('old@example.cm', $emailAudit->old_value);
        $this->assertSame('new@example.cm', $emailAudit->new_value);

        // actor_type 'facility' is what OsmFacilityImporter reads as
        // "a human owns this field" — the audit row is what protects the edit.
        $this->assertContains($phoneAudit->actor_type, ['user', 'api_partner', 'facility', 'admin']);
    }

    public function test_an_unchanged_submission_writes_no_audit_rows(): void
    {
        $listing  = $this->listing(['email' => 'same@example.cm']);
        $claimant = $this->approvedClaimant($listing);

        DB::table('facility_update_audits')->where('facility_id', $listing->id)->delete();

        $this->actingAs($claimant)->post(route('portals.listing.update'), [
            'email' => 'same@example.cm',
        ]);

        $this->assertSame(0, DB::table('facility_update_audits')
            ->where('facility_id', $listing->id)
            ->where('source', 'facility_self_service')
            ->count());
    }

    // ─── 6. 'N/A' is never presented as a phone number ───────────────────────

    public function test_the_na_placeholder_is_treated_as_empty(): void
    {
        $listing  = $this->listing();
        $claimant = $this->approvedClaimant($listing);

        $this->assertNull($listing->dialablePhone());
        $this->assertNull(CareFacility::realValue('N/A'));
        $this->assertNull(CareFacility::realValue('n/a'));
        $this->assertNull(CareFacility::realValue('  '));
        $this->assertSame('+237 1', CareFacility::realValue(' +237 1 '));

        $this->actingAs($claimant)
            ->get(route('portals.listing.edit'))
            ->assertOk()
            ->assertDontSee('value="N/A"', false)
            ->assertSee(__('caremap_claim.hint_phone_placeholder'));
    }

    // ─── 7. Services and hours ───────────────────────────────────────────────

    public function test_a_claimant_maintains_services_and_hours_on_their_listing(): void
    {
        $listing  = $this->listing();
        $claimant = $this->approvedClaimant($listing);

        $this->actingAs($claimant)->post(route('portals.listing.services.store'), [
            'service_name'           => 'Hémodialyse',
            'service_category'       => 'diagnostic',
            'specialty'              => 'Néphrologie',
            'availability_status'    => 'available',
            'appointment_required'   => '1',
            'telemedicine_available' => '1',
        ])->assertRedirect(route('portals.listing.edit'));

        $service = CareFacilityService::where('facility_id', $listing->id)->firstOrFail();
        $this->assertSame('Hémodialyse', $service->service_name);
        $this->assertSame('Néphrologie', $service->specialty);
        $this->assertTrue($service->appointment_required);
        $this->assertTrue($service->telemedicine_available);

        $this->actingAs($claimant)->post(route('portals.listing.hours.update'), [
            'hours' => [
                1 => ['opens_at' => '08:00', 'closes_at' => '17:00'],
                2 => ['opens_at' => '08:00', 'closes_at' => '17:00'],
                0 => ['is_closed' => '1'],
                6 => ['is_24_hours' => '1'],
            ],
        ])->assertRedirect(route('portals.listing.edit'));

        $this->assertSame(4, DB::table('care_facility_hours')->where('facility_id', $listing->id)->count());
        $this->assertDatabaseHas('care_facility_hours', [
            'facility_id' => $listing->id,
            'day_of_week' => 6,
            'is_24_hours' => true,
        ]);

        // Removing a service the claimant owns works, and is audited.
        $this->actingAs($claimant)
            ->delete(route('portals.listing.services.destroy', $service->id))
            ->assertRedirect(route('portals.listing.edit'));

        $this->assertDatabaseMissing('care_facility_services', ['id' => $service->id]);
        $this->assertTrue(
            DB::table('facility_update_audits')
                ->where('facility_id', $listing->id)
                ->where('field_changed', 'service:' . $service->id)
                ->exists()
        );
    }

    // ─── 8. The admin review desk ────────────────────────────────────────────

    public function test_an_admin_can_approve_and_reject_claims_from_the_review_desk(): void
    {
        $admin = $this->platformAdmin();

        $approveListing = $this->listing();
        $rejectListing  = $this->listing();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $claimA = $this->claims->submitDirectoryClaim($approveListing->id, $userA->id, ['claimant_name' => 'A']);
        $claimB = $this->claims->submitDirectoryClaim($rejectListing->id, $userB->id, ['claimant_name' => 'B']);

        $this->actingAs($admin)->get(route('admin.care-map.review'))->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.care-map.review.claims.approve', $claimA->id))
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.care-map.review.claims.reject', $claimB->id), ['review_notes' => 'No proof of authority'])
            ->assertRedirect();

        $this->assertSame(FacilityClaimStatus::Approved, $claimA->fresh()->claim_status);
        $this->assertSame(FacilityClaimStatus::Rejected, $claimB->fresh()->claim_status);
        $this->assertSame('No proof of authority', $claimB->fresh()->review_notes);

        $this->assertSame($approveListing->id, $this->claims->approvedListingFor($userA->id)?->id);
        $this->assertNull($this->claims->approvedListingFor($userB->id));

        // Neither decision made anything verified.
        $this->assertSame('unverified', $approveListing->fresh()->verification_status);
        $this->assertSame('unverified', $rejectListing->fresh()->verification_status);
    }

    public function test_an_admin_can_accept_an_import_review(): void
    {
        $admin  = $this->platformAdmin();
        $review = $this->importReview(['candidate_name' => 'Centre de Santé de Mvog-Ada']);

        $before = CareFacility::count();

        $this->actingAs($admin)
            ->post(route('admin.care-map.review.imports.accept', $review->id))
            ->assertRedirect();

        $review->refresh();
        $this->assertSame(FacilityImportReviewStatus::Imported, $review->status);
        $this->assertSame($admin->id, $review->reviewed_by);
        $this->assertNotNull($review->reviewed_at);

        $this->assertSame($before + 1, CareFacility::count());

        $created = CareFacility::where('source_ref', $review->source_ref)->firstOrFail();
        $this->assertSame('Centre de Santé de Mvog-Ada', $created->facility_name);
        // An import is not a verification either.
        $this->assertSame('unverified', $created->verification_status);
        $this->assertSame('openstreetmap', $created->source_system);
    }

    public function test_an_admin_can_reject_an_import_review_and_nothing_is_created(): void
    {
        $admin  = $this->platformAdmin();
        $review = $this->importReview();

        $before = CareFacility::count();

        $this->actingAs($admin)
            ->post(route('admin.care-map.review.imports.reject', $review->id), ['review_notes' => 'Not a health facility'])
            ->assertRedirect();

        $review->refresh();
        $this->assertSame(FacilityImportReviewStatus::Rejected, $review->status);
        $this->assertSame('Not a health facility', $review->review_notes);
        $this->assertSame($before, CareFacility::count());
    }

    public function test_an_import_review_cannot_be_decided_twice(): void
    {
        $admin  = $this->platformAdmin();
        $review = $this->importReview();

        app(FacilityImportReviewService::class)->reject($review, $admin->id);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('IMPORT_REVIEW_ALREADY_DECIDED');
        app(FacilityImportReviewService::class)->accept($review->fresh(), $admin->id);
    }

    public function test_an_unnamed_candidate_cannot_be_added_to_the_directory(): void
    {
        $admin  = $this->platformAdmin();
        $review = $this->importReview(['candidate_name' => null, 'payload' => ['amenity' => 'clinic']]);

        $before = CareFacility::count();

        $this->actingAs($admin)
            ->post(route('admin.care-map.review.imports.accept', $review->id))
            ->assertSessionHas('error', __('caremap_claim.error_import_unnamed'));

        $this->assertSame($before, CareFacility::count());
        $this->assertSame(FacilityImportReviewStatus::Pending, $review->fresh()->status);
    }

    public function test_both_review_tabs_render_with_real_rows_in_them(): void
    {
        $admin   = $this->platformAdmin();
        $listing = $this->listing(['facility_name' => 'Polyclinique Bonanjo']);
        $user    = User::factory()->create();

        $this->claims->submitDirectoryClaim($listing->id, $user->id, [
            'claimant_name'  => 'Paul Etoga',
            'claimant_role'  => 'owner',
            'claimant_email' => 'paul@example.cm',
            'claimant_phone' => '+237 600 111 222',
            'claim_reason'   => str_repeat('Registration number CM-2019-4471. ', 12),
        ]);

        $matched = $this->listing(['facility_name' => 'Polyclinique Bonanjo Annexe']);
        $this->importReview([
            'reason'                => 'uncertain_match',
            'matched_facility_id'   => $matched->id,
            'matched_facility_name' => $matched->facility_name,
            'match_score'           => 0.812,
            'match_distance_m'      => 140,
        ]);
        $this->importReview(['reason' => 'unnamed_element', 'candidate_name' => null]);

        $this->actingAs($admin)->get(route('admin.care-map.review', ['tab' => 'claims']))
            ->assertOk()
            ->assertSee('Polyclinique Bonanjo')
            ->assertSee('Paul Etoga')
            ->assertSee(__('caremap_claim.approve_warning'));

        $this->actingAs($admin)->get(route('admin.care-map.review', ['tab' => 'imports']))
            ->assertOk()
            ->assertSee('Pharmacie du Carrefour')
            ->assertSee(__('caremap_claim.reason_uncertain_match'))
            ->assertSee(__('caremap_claim.no_name_warning'));
    }

    public function test_a_non_platform_user_cannot_reach_the_review_desk(): void
    {
        $this->platformAdmin();   // seed roles
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->get(route('admin.care-map.review'))->assertForbidden();
    }

    private function importReview(array $overrides = []): FacilityImportReview
    {
        return FacilityImportReview::create(array_merge([
            'source_system'      => 'openstreetmap',
            'source_ref'         => 'node/' . random_int(100000, 999999),
            'source_attribution' => '© OpenStreetMap contributors',
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
}
