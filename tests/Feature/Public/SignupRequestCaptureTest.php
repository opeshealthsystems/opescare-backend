<?php

namespace Tests\Feature\Public;

use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The three public request forms that are not account sign-ups.
 *
 * Guardian and developer both told the visitor "your request has been
 * submitted" and then did nothing at all — no validation, no persistence, no
 * notification. The organisation form validated and queued an email, but wrote
 * nothing down, so the reference code it handed the applicant referred to a
 * record that did not exist, and a mail failure lost the application silently.
 *
 * Each now lands in the leads pipeline, which already has an admin screen.
 * These tests pin that a submission survives, and that no password typed into
 * the guardian form is ever stored.
 */
class SignupRequestCaptureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function guardianUser(): User
    {
        return User::factory()->create([
            'status'  => 'active',
            'role_id' => Role::firstOrCreate(['name' => 'guardian'], ['display_name' => 'Guardian'])->id,
        ]);
    }

    public function test_a_guardian_signs_up_with_only_an_email_and_a_password(): void
    {
        Role::firstOrCreate(['name' => 'guardian'], ['display_name' => 'Guardian']);

        $this->post('/signup/guardian', [
            'email'                 => 'marie.fotso@example.test',
            'password'              => 'correct-horse-8',
            'password_confirmation' => 'correct-horse-8',
        ])->assertRedirect(route('portals.guardian.complete-profile'));

        $user = User::where('email', 'marie.fotso@example.test')->first();

        $this->assertNotNull($user, 'the account should exist');
        $this->assertSame('active', $user->status, "status 'pending' would lock the account out of login");
        $this->assertNull($user->profile_completed_at, 'nothing is submitted for review at sign-up');
        $this->assertSame(0, Lead::count());
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_caregiver_request_is_recorded_after_login(): void
    {
        $user = $this->guardianUser();

        $this->actingAs($user)->withSession(['mfa.verified' => true])
            ->post('/portals/guardian/complete-profile', [
                'first_name'       => 'Marie',
                'last_name'        => 'Fotso',
                'phone'            => '+237670000010',
                'dep_name'         => 'Jean Fotso',
                'dep_relationship' => 'child',
                'dep_dob'          => '2016-09-11',
                'dep_sex'          => 'male',
                'access_reason'    => "I manage my son's care.",
            ])->assertRedirect(route('portals.guardian.pending'));

        $lead = Lead::where('source', 'guardian_signup')->first();

        $this->assertNotNull($lead, 'the caregiver request must be recorded, not discarded');
        $this->assertSame('Marie Fotso', $lead->name);
        $this->assertSame($user->email, $lead->email);
        $this->assertStringContainsString('Jean Fotso', (string) $lead->message);
        $this->assertNotNull($user->fresh()->profile_completed_at);
    }

    /** Nothing is granted here — verification stays with a human. */
    public function test_completing_the_caregiver_request_grants_no_access(): void
    {
        $user = $this->guardianUser();

        $this->actingAs($user)->withSession(['mfa.verified' => true])
            ->post('/portals/guardian/complete-profile', [
                'first_name' => 'Marie', 'last_name' => 'Fotso', 'phone' => '+237670000010',
                'dep_name'   => 'Jean Fotso', 'dep_relationship' => 'child',
            ]);

        $this->assertSame(0, DB::table('guardian_relationships')->count(),
            'a relationship row is written by the reviewer, never by the request');
        $this->assertNull($user->fresh()->patient_id);
    }

    public function test_a_guardian_is_held_at_the_pending_screen_until_verified(): void
    {
        $user = $this->guardianUser();

        // Before submitting: pinned to the completion step.
        $this->actingAs($user)->withSession(['mfa.verified' => true])
            ->get('/portals/patient')
            ->assertRedirect(route('portals.guardian.complete-profile'));

        $user->forceFill(['profile_completed_at' => now()])->save();

        // After submitting: pinned to the pending screen, not an empty portal.
        $this->actingAs($user->fresh())->withSession(['mfa.verified' => true])
            ->get('/portals/patient')
            ->assertRedirect(route('portals.guardian.pending'));
    }

    /** A password typed into sign-up must never reach the review queue. */
    public function test_the_review_queue_never_holds_a_password(): void
    {
        Role::firstOrCreate(['name' => 'guardian'], ['display_name' => 'Guardian']);

        $this->post('/signup/guardian', [
            'email'                 => 'marie2@example.test',
            'password'              => 'super-secret-value',
            'password_confirmation' => 'super-secret-value',
        ]);

        $user = User::where('email', 'marie2@example.test')->firstOrFail();

        $this->actingAs($user)->withSession(['mfa.verified' => true])
            ->post('/portals/guardian/complete-profile', [
                'first_name' => 'Marie', 'last_name' => 'Fotso', 'phone' => '+237670000011',
                'dep_name'   => 'Jean Fotso', 'dep_relationship' => 'child',
            ]);

        foreach (Lead::all() as $lead) {
            $blob = strtolower(json_encode($lead->toArray()));
            $this->assertStringNotContainsString('super-secret-value', $blob);
            $this->assertStringNotContainsString('password', $blob);
        }
    }

    public function test_a_developer_request_is_recorded(): void
    {
        $this->post('/signup/developer', [
            'name'                => 'Ada Byron',
            'email'               => 'ada@vendor.test',
            'phone'               => '+237670000012',
            'organization'        => 'Vendor Systems Sarl',
            'role'                => 'CTO',
            'country'             => 'Cameroon',
            'system_type'         => 'EMR',
            'integration_purpose' => 'Push encounters into the Health ID.',
            'data_flow'           => 'bidirectional',
        ])->assertRedirect(route('register.developer'));

        $lead = Lead::where('source', 'developer_signup')->first();

        $this->assertNotNull($lead, 'the developer request must be recorded, not discarded');
        $this->assertSame('Ada Byron', $lead->name);
        $this->assertSame('developer', $lead->organization_type);
        $this->assertSame('Vendor Systems Sarl', $lead->organization_name);
    }

    public function test_an_organisation_application_survives_a_mail_failure(): void
    {
        $payload = [
            'org_type'       => 'facility',
            'legal_name'     => 'Clinique du Centre Sarl',
            'reg_number'     => 'RC/DLA/2019/B/1234',
            'license_number' => 'MINSANTE-2021-778',
            'address'        => '12 Rue Manga Bell, Douala',
            'main_phone'     => '+237670000013',
            'main_email'     => 'contact@clinique.test',
            'contact_name'   => 'Paul Mbarga',
            'contact_role'   => 'Director',
            'contact_email'  => 'paul@clinique.test',
            'contact_phone'  => '+237670000014',
        ];

        $response = $this->post('/signup/organization', $payload);
        $response->assertOk();

        $lead = Lead::where('source', 'organization_application')->first();

        $this->assertNotNull($lead, 'the application must exist independently of the email');
        $this->assertSame('Clinique du Centre Sarl', $lead->organization_name);

        // The reference code shown to the applicant has to be findable later.
        $ref = $response->viewData('ref_code');
        $this->assertNotEmpty($ref);
        $this->assertStringContainsString($ref, (string) $lead->message);
    }

    /**
     * The contact form only queued an email, to an address on a domain that
     * accepts no inbound mail — so every message was discarded while the page
     * thanked the sender for it.
     */
    public function test_a_contact_enquiry_is_recorded(): void
    {
        $this->post('/contact', [
            'name'    => 'Paul Mbarga',
            'email'   => 'paul@clinique.test',
            'subject' => 'Integration question',
            'message' => 'Does the Connect API expose Coverage?',
            'organisation' => 'Clinique du Centre',
        ])->assertRedirect();

        $lead = Lead::where('source', 'contact')->first();

        $this->assertNotNull($lead, 'the enquiry must be recorded, not only emailed');
        $this->assertSame('Paul Mbarga', $lead->name);
        $this->assertSame('paul@clinique.test', $lead->email);
        $this->assertStringContainsString('Coverage', (string) $lead->message);
        $this->assertSame('Clinique du Centre', $lead->organization_name);
    }

    public function test_request_forms_reject_incomplete_submissions(): void
    {
        $this->post('/signup/guardian', ['email' => 'not-an-email'])
            ->assertSessionHasErrors(['email', 'password']);

        $this->post('/signup/developer', ['name' => 'Ada'])
            ->assertSessionHasErrors(['email', 'organization']);

        $this->assertSame(0, Lead::count());
    }
}
