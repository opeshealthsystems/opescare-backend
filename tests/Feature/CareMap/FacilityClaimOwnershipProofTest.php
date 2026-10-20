<?php

namespace Tests\Feature\CareMap;

use App\Enums\FacilityClaimStatus;
use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\FacilityClaim;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use App\Modules\CareMap\Services\ClaimSmsGateway;
use App\Modules\CareMap\Services\FacilityClaimProofService;
use App\Modules\CareMap\Services\FacilityClaimService;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use App\Notifications\FacilityClaimUpdateNotification;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Ownership proof for facility claims (owner decision 2026-10-20; readiness
 * OB-1, OB-2, OB-8, PH-2): SMS code to the LISTING's phone + a private
 * licence document + admin review, and approval that creates and links a
 * tenant with the claimant as its (inactive until MFA) manager.
 */
class FacilityClaimOwnershipProofTest extends TestCase
{
    use RefreshDatabase;

    private const LISTING_PHONE  = '+237 222 111 333';
    private const CLAIMANT_PHONE = '+237 699 888 777';

    /** @var list<array{to:string,body:string}> */
    public static array $sent = [];

    private FacilityClaimService $claims;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);

        Storage::fake(FacilityClaimProofService::disk());
        Notification::fake();

        self::$sent = [];
        $this->app->instance(ClaimSmsGateway::class, new class extends ClaimSmsGateway {
            public function __construct() {}
            public function isLive(): bool { return true; }
            public function deliver(string $phone, string $body): bool
            {
                FacilityClaimOwnershipProofTest::$sent[] = ['to' => $phone, 'body' => $body];

                return true;
            }
        });

        $this->claims = app(FacilityClaimService::class);
    }

    private function listing(array $overrides = []): CareFacility
    {
        return CareFacility::create(array_merge([
            'facility_name'       => 'Pharmacie du Test ' . Str::random(6),
            'facility_type'       => 'pharmacy',
            'country_code'        => 'CM',
            'region'              => 'Centre',
            'city'                => 'Yaoundé',
            'address'             => 'Yaoundé, Cameroon',
            'phone_primary'       => self::LISTING_PHONE,
            'listing_status'      => 'active',
            'verification_status' => 'unverified',
            'license_status'      => 'active',
            'integration_status'  => 'none',
        ], $overrides));
    }

    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('licence.pdf', "%PDF-1.4\n% test licence\n");
    }

    private function admin(): User
    {
        $user = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $user->role_id = Role::where('name', 'super_admin')->value('id');
        $user->save();

        return $user;
    }

    private function claimant(): User
    {
        return User::factory()->create(['primary_facility_id' => null]);
    }

    /** Files a claim over HTTP, exactly as the form does. */
    private function file(CareFacility $listing, User $user, ?UploadedFile $doc = null): \Illuminate\Testing\TestResponse
    {
        $payload = [
            'claimant_name'  => 'Ada Ngu',
            'claimant_role'  => 'owner',
            'claimant_email' => 'ada@example.cm',
            'claimant_phone' => self::CLAIMANT_PHONE,
        ];
        if ($doc !== null) {
            $payload['document'] = $doc;
        }

        return $this->actingAs($user)->post(route('public.care-map.claim.store', $listing->id), $payload);
    }

    private function sentCode(): string
    {
        $toListing = array_values(array_filter(self::$sent, fn ($s) => $s['to'] === self::LISTING_PHONE));
        $last      = end($toListing);
        $this->assertSame(1, preg_match('/\b(\d{6})\b/', $last['body'], $m), 'no code in: ' . $last['body']);

        return $m[1];
    }

    private function reviewableClaim(CareFacility $listing, User $user): FacilityClaim
    {
        $this->file($listing, $user, $this->pdf());
        $claim = FacilityClaim::where('care_facility_id', $listing->id)->where('claimant_user_id', $user->id)->firstOrFail();
        app(FacilityClaimProofService::class)->verifyCode($claim, $this->sentCode());

        return $claim->fresh();
    }

    // ── SMS code ─────────────────────────────────────────────────────────

    public function test_the_code_goes_to_the_listing_phone_never_the_claimant_phone(): void
    {
        $listing = $this->listing();
        $user    = $this->claimant();

        $this->file($listing, $user, $this->pdf())->assertRedirect(route('portals.listing.claims'));

        $claim = FacilityClaim::where('care_facility_id', $listing->id)->firstOrFail();
        $this->assertSame(FacilityClaimStatus::PendingVerification, $claim->claim_status);
        $this->assertCount(1, array_filter(self::$sent, fn ($s) => $s["to"] === self::LISTING_PHONE));
        $this->assertSame(self::LISTING_PHONE, self::$sent[0]['to']);
        $this->assertNotSame(self::CLAIMANT_PHONE, self::$sent[0]['to']);

        // Stored hashed only; never echoed to the claimant.
        $code = $this->sentCode();
        $this->assertNotNull($claim->verification_code_hash);
        $this->assertStringNotContainsString($code, (string) $claim->verification_code_hash);
        $this->actingAs($user)->get(route('portals.listing.claims'))->assertOk()->assertDontSee($code);
        $this->assertArrayNotHasKey('verification_code_hash', $claim->toArray());
    }

    public function test_the_right_code_moves_the_claim_to_review(): void
    {
        $listing = $this->listing();
        $user    = $this->claimant();
        $this->file($listing, $user, $this->pdf());
        $claim = FacilityClaim::where('care_facility_id', $listing->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('portals.listing.claims.verify', $claim->id), ['code' => $this->sentCode()])
            ->assertSessionHas('success', __('caremap_claim.flash_code_verified'));

        $claim->refresh();
        $this->assertSame(FacilityClaimStatus::PendingReview, $claim->claim_status);
        $this->assertTrue($claim->isPhoneVerified());
    }

    public function test_a_wrong_code_is_refused(): void
    {
        $listing = $this->listing();
        $user    = $this->claimant();
        $this->file($listing, $user, $this->pdf());
        $claim = FacilityClaim::where('care_facility_id', $listing->id)->firstOrFail();
        $wrong = $this->sentCode() === '111111' ? '222222' : '111111';

        $this->actingAs($user)
            ->post(route('portals.listing.claims.verify', $claim->id), ['code' => $wrong])
            ->assertSessionHas('error', __('caremap_claim.error_code_invalid'));

        $this->assertSame(FacilityClaimStatus::PendingVerification, $claim->fresh()->claim_status);
        $this->assertSame(1, $claim->fresh()->verification_attempts);
    }

    public function test_an_expired_code_is_refused(): void
    {
        $listing = $this->listing();
        $user    = $this->claimant();
        $this->file($listing, $user, $this->pdf());
        $claim = FacilityClaim::where('care_facility_id', $listing->id)->firstOrFail();
        $code  = $this->sentCode();

        $this->travel(FacilityClaimProofService::CODE_TTL_MINUTES + 1)->minutes();

        $this->actingAs($user)
            ->post(route('portals.listing.claims.verify', $claim->id), ['code' => $code])
            ->assertSessionHas('error', __('caremap_claim.error_code_expired'));

        $this->assertFalse($claim->fresh()->isPhoneVerified());
    }

    public function test_the_attempt_limit_burns_the_code(): void
    {
        $listing = $this->listing();
        $user    = $this->claimant();
        $this->file($listing, $user, $this->pdf());
        $claim = FacilityClaim::where('care_facility_id', $listing->id)->firstOrFail();
        $code  = $this->sentCode();
        $wrong = $code === '111111' ? '222222' : '111111';

        for ($i = 0; $i < FacilityClaimProofService::MAX_ATTEMPTS; $i++) {
            $this->actingAs($user)->post(route('portals.listing.claims.verify', $claim->id), ['code' => $wrong]);
        }

        // Even the right code is now useless.
        $this->actingAs($user)->post(route('portals.listing.claims.verify', $claim->id), ['code' => $code]);
        $this->assertSame(FacilityClaimStatus::PendingVerification, $claim->fresh()->claim_status);
        $this->assertNull($claim->fresh()->verification_code_hash);
    }

    public function test_resend_has_a_cooldown_and_a_cap(): void
    {
        $listing = $this->listing();
        $user    = $this->claimant();
        $this->file($listing, $user, $this->pdf());
        $claim = FacilityClaim::where('care_facility_id', $listing->id)->firstOrFail();

        $this->actingAs($user)->post(route('portals.listing.claims.resend', $claim->id))
            ->assertSessionHas('error', __('caremap_claim.error_code_too_soon'));

        for ($i = 1; $i < FacilityClaimProofService::MAX_SENDS; $i++) {
            $this->travel(2)->minutes();
            $this->actingAs($user)->post(route('portals.listing.claims.resend', $claim->id))
                ->assertSessionHas('success');
        }

        $this->travel(2)->minutes();
        $this->actingAs($user)->post(route('portals.listing.claims.resend', $claim->id))
            ->assertSessionHas('error', __('caremap_claim.error_code_send_limit'));
        $this->assertCount(FacilityClaimProofService::MAX_SENDS, array_filter(self::$sent, fn ($s) => $s["to"] === self::LISTING_PHONE));
    }

    public function test_another_user_cannot_verify_someone_elses_claim(): void
    {
        $listing = $this->listing();
        $this->file($listing, $this->claimant(), $this->pdf());
        $claim = FacilityClaim::where('care_facility_id', $listing->id)->firstOrFail();

        $this->actingAs($this->claimant())
            ->post(route('portals.listing.claims.verify', $claim->id), ['code' => $this->sentCode()])
            ->assertNotFound();
    }

    public function test_an_undeliverable_code_sends_the_claim_to_review_flagged(): void
    {
        $this->app->instance(ClaimSmsGateway::class, new class extends ClaimSmsGateway {
            public function __construct() {}
            public function isLive(): bool { return false; }
            public function deliver(string $phone, string $body): bool { return false; }
        });

        $listing = $this->listing();
        $this->file($listing, $this->claimant(), $this->pdf());
        $claim = FacilityClaim::where('care_facility_id', $listing->id)->firstOrFail();

        $this->assertSame(FacilityClaimStatus::PendingReview, $claim->claim_status);
        $this->assertSame(FacilityClaimProofService::SMS_NOT_DELIVERED, $claim->sms_status);
        $this->assertFalse($claim->isPhoneVerified());
        $this->assertNull($claim->verification_code_hash);
    }

    public function test_a_listing_without_a_phone_goes_to_review_flagged(): void
    {
        $listing = $this->listing(['phone_primary' => CareFacility::PHONE_PLACEHOLDER]);
        $this->file($listing, $this->claimant(), $this->pdf());
        $claim = FacilityClaim::where('care_facility_id', $listing->id)->firstOrFail();

        $this->assertSame(FacilityClaimStatus::PendingReview, $claim->claim_status);
        $this->assertSame(FacilityClaimProofService::SMS_NO_LISTING_PHONE, $claim->sms_status);
        $this->assertCount(0, array_filter(self::$sent, fn ($s) => $s["to"] === self::LISTING_PHONE));
    }

    // ── Document ─────────────────────────────────────────────────────────

    public function test_a_document_is_required(): void
    {
        $listing = $this->listing();
        $this->file($listing, $this->claimant())->assertSessionHasErrors('document');
        $this->assertSame(0, FacilityClaim::count());
    }

    public function test_a_file_that_is_not_really_a_pdf_or_image_is_refused(): void
    {
        $listing = $this->listing();
        $fake    = UploadedFile::fake()->createWithContent('licence.pdf', "<?php echo 'x';");

        $this->file($listing, $this->claimant(), $fake);
        $this->assertSame(0, FacilityClaim::count());
    }

    public function test_the_document_is_private_and_served_only_signed_to_admins(): void
    {
        $listing = $this->listing();
        $user    = $this->claimant();
        $this->file($listing, $user, $this->pdf());
        $claim = FacilityClaim::where('care_facility_id', $listing->id)->firstOrFail();

        Storage::disk(FacilityClaimProofService::disk())->assertExists($claim->document_path);
        $this->assertStringStartsWith(FacilityClaimProofService::DOCUMENT_DIR . '/', $claim->document_path);
        $this->assertSame('application/pdf', $claim->document_mime);
        $this->assertArrayNotHasKey('document_path', $claim->toArray());

        $signed = URL::temporarySignedRoute('admin.care-map.review.claims.document', now()->addMinutes(5), ['claim' => $claim->id]);

        // Unsigned: refused even for an admin.
        $this->actingAs($this->admin())->get(route('admin.care-map.review.claims.document', $claim->id))->assertForbidden();
        // Signed but not an admin: refused.
        $this->actingAs($user)->get($signed)->assertStatus(403);
        // Signed + admin: served.
        $this->actingAs($this->admin())->get($signed)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    // ── Approval guards ──────────────────────────────────────────────────

    private function assertApprovalRefused(FacilityClaim $claim, string $code): void
    {
        try {
            $this->claims->approveClaim($claim->id, $this->admin()->id);
            $this->fail("expected {$code}");
        } catch (\Exception $e) {
            $this->assertSame($code, $e->getMessage());
        }
    }

    public function test_a_claim_still_awaiting_its_code_cannot_be_approved(): void
    {
        $listing = $this->listing();
        $this->file($listing, $this->claimant(), $this->pdf());
        $claim = FacilityClaim::firstOrFail();

        $this->assertApprovalRefused($claim, 'CLAIM_NOT_REVIEWABLE');
        $this->assertNull($listing->fresh()->facility_id);
    }

    public function test_rejected_revoked_and_approved_claims_cannot_be_approved(): void
    {
        foreach ([FacilityClaimStatus::Rejected, FacilityClaimStatus::Revoked, FacilityClaimStatus::Approved, FacilityClaimStatus::Submitted] as $status) {
            $listing = $this->listing();
            $claim   = $this->reviewableClaim($listing, $this->claimant());
            $claim->forceFill(['claim_status' => $status->value])->save();

            $this->assertApprovalRefused($claim, 'CLAIM_NOT_REVIEWABLE');
            $this->assertNull($listing->fresh()->facility_id, $status->value);
        }
    }

    public function test_a_claim_without_a_document_cannot_be_approved(): void
    {
        $claim = $this->reviewableClaim($this->listing(), $this->claimant());
        $claim->forceFill(['document_path' => null])->save();

        $this->assertApprovalRefused($claim, 'CLAIM_HAS_NO_DOCUMENT');
    }

    public function test_a_listing_already_linked_to_a_tenant_cannot_be_approved(): void
    {
        $tenant  = Facility::create(['name' => 'Incumbent', 'type' => 'pharmacy', 'status' => 'active']);
        $listing = $this->listing(['facility_id' => $tenant->id]);
        $claim   = $this->reviewableClaim($listing, $this->claimant());

        $this->assertApprovalRefused($claim, 'LISTING_ALREADY_LINKED');
        $this->assertSame((string) $tenant->id, (string) $listing->fresh()->facility_id);
    }

    public function test_two_pending_claims_on_one_listing_only_one_is_approved(): void
    {
        $listing = $this->listing();
        $a = $this->reviewableClaim($listing, $this->claimant());
        $this->travel(2)->minutes();
        $b = $this->reviewableClaim($listing, $this->claimant());

        $this->claims->approveClaim($a->id, $this->admin()->id);
        $this->assertApprovalRefused($b, 'LISTING_ALREADY_CLAIMED');

        $this->assertSame(FacilityClaimStatus::PendingReview, $b->fresh()->claim_status);
        $this->assertSame(1, Facility::where('name', $listing->facility_name)->count());
    }

    public function test_a_second_claim_on_an_approved_listing_is_blocked_at_submit(): void
    {
        $listing = $this->listing();
        $claim   = $this->reviewableClaim($listing, $this->claimant());
        $this->claims->approveClaim($claim->id, $this->admin()->id);

        $this->file($listing, $this->claimant(), $this->pdf())
            ->assertSessionHas('error', __('caremap_claim.error_already_claimed'));
        $this->assertSame(1, FacilityClaim::where('care_facility_id', $listing->id)->count());
    }

    public function test_the_desk_refuses_approval_with_a_message_not_a_500(): void
    {
        $listing = $this->listing();
        $this->file($listing, $this->claimant(), $this->pdf());
        $claim = FacilityClaim::firstOrFail();

        $this->actingAs($this->admin())
            ->post(route('admin.care-map.review.claims.approve', $claim->id))
            ->assertRedirect()
            ->assertSessionHas('error', __('caremap_claim.error_not_reviewable'));
    }

    // ── Approval effects ─────────────────────────────────────────────────

    public function test_approval_creates_and_links_a_tenant_with_the_claimant_as_manager(): void
    {
        $listing = $this->listing();
        $user    = $this->claimant();
        $claim   = $this->reviewableClaim($listing, $user);
        $admin   = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.care-map.review.claims.approve', $claim->id))
            ->assertSessionHas('claim_activation_url');

        $claim->refresh();
        $listing->refresh();

        $this->assertSame(FacilityClaimStatus::Approved, $claim->claim_status);
        $this->assertNotNull($listing->facility_id);
        $this->assertSame((string) $listing->facility_id, (string) $claim->created_facility_id);
        $this->assertSame((string) $user->id, (string) $listing->claimed_by_user_id);

        $tenant = Facility::findOrFail($listing->facility_id);
        $this->assertSame('pharmacy', $tenant->type);

        $assignment = FacilityRoleAssignment::where('user_id', $user->id)->where('facility_id', $tenant->id)->firstOrFail();
        $this->assertSame('pharmacy_manager', Role::find($assignment->role_id)->name);
        $this->assertFalse($assignment->is_active, 'inactive until the claimant activates with MFA');

        // PH-2: the pharmacy's stock is now reachable — syncFinder's
        // listingFor() gate returns the listing instead of null.
        $this->assertSame($listing->id, app(PharmacyStockReportService::class)->listingFor($tenant->id)?->id);
    }

    public function test_approval_picks_the_manager_role_by_type(): void
    {
        foreach (['laboratory' => 'lab_manager', 'hospital' => 'facility_admin'] as $type => $roleName) {
            $listing = $this->listing(['facility_type' => $type]);
            $user    = $this->claimant();
            $claim   = $this->reviewableClaim($listing, $user);
            $this->claims->approveClaim($claim->id, $this->admin()->id);

            $roleId = FacilityRoleAssignment::where('user_id', $user->id)->value('role_id');
            $this->assertSame($roleName, Role::find($roleId)->name, $type);
        }
    }

    public function test_activation_requires_mfa_then_switches_the_role_on_once(): void
    {
        $listing = $this->listing();
        $user    = $this->claimant();
        $claim   = $this->reviewableClaim($listing, $user);
        $claim   = $this->claims->approveClaim($claim->id, $this->admin()->id);
        $url     = $this->claims->activationUrlFor($claim);

        // Without MFA: sent to enrol, nothing activated.
        $this->actingAs($user)->get($url)->assertRedirect(route('mfa.challenge'));
        $this->assertNull($claim->fresh()->activated_at);

        // Someone else's account cannot use the link.
        $this->actingAs($this->claimant())->get($url)->assertNotFound();

        $user->forceFill(['two_factor_secret' => 'SECRET', 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($user->fresh())->get($url)->assertSessionHas('success', __('caremap_claim.flash_activated'));
        $this->actingAs($user->fresh())->get($url)->assertSessionHas('success'); // idempotent

        $this->assertNotNull($claim->fresh()->activated_at);
        $this->assertTrue((bool) FacilityRoleAssignment::where('user_id', $user->id)->value('is_active'));
        $this->assertSame((string) $listing->fresh()->facility_id, (string) $user->fresh()->primary_facility_id);

        // An unsigned link is refused.
        $this->actingAs($user)->get(route('portals.listing.claims.activate', $claim->id))->assertForbidden();
    }

    // ── Notifications ────────────────────────────────────────────────────

    public function test_the_claimant_is_notified_on_filing_and_on_each_decision(): void
    {
        $approvedUser = $this->claimant();
        $claim = $this->reviewableClaim($this->listing(), $approvedUser);

        Notification::assertSentTo($approvedUser, FacilityClaimUpdateNotification::class,
            fn ($n, $channels) => $n->event === 'filed' && in_array('database', $channels, true) && in_array('mail', $channels, true));

        $this->claims->approveClaim($claim->id, $this->admin()->id);
        Notification::assertSentTo($approvedUser, FacilityClaimUpdateNotification::class,
            fn ($n) => $n->event === 'approved' && $n->activationUrl !== null);

        $rejectedUser = $this->claimant();
        $other = $this->reviewableClaim($this->listing(), $rejectedUser);
        $this->claims->rejectClaim($other->id, $this->admin()->id, 'No licence match');
        Notification::assertSentTo($rejectedUser, FacilityClaimUpdateNotification::class,
            fn ($n) => $n->event === 'rejected' && $n->notes === 'No licence match');

        // The decision SMS goes to the claimant's own phone (provider live in this test).
        $this->assertContains(self::CLAIMANT_PHONE, array_column(self::$sent, 'to'));
    }

    public function test_the_notification_renders_in_both_languages(): void
    {
        $user = $this->claimant();
        foreach (['en', 'fr'] as $locale) {
            $n = (new FacilityClaimUpdateNotification('approved', (string) Str::uuid(), 'Pharmacie X', 'https://example.test/a'))->locale($locale);
            $data = $n->toDatabase($user);
            $this->assertStringNotContainsString('caremap_claim.', $data['title']);
            $this->assertStringContainsString('Pharmacie X', $data['message']);
        }
    }
}
