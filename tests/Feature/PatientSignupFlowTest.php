<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\Identity\HealthIdGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Sign-up is two steps now: an account (email + password), then identity.
 *
 * The split exists because the old single form asked for nine required fields
 * before anyone could have an account. These tests pin the parts that were
 * actually broken, or are easy to break again:
 *
 *  - a self-registered patient must be able to LOG IN (the old flow wrote
 *    status = 'pending', which submitLogin rejects — so no self-registered
 *    patient could ever sign in);
 *  - no Health ID is minted until identity is supplied;
 *  - the portal stays closed until it is;
 *  - the Health ID country prefix comes from config, never from the request.
 */
class PatientSignupFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'patient'], ['display_name' => 'Patient']);
    }

    private function patientUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'patient_id' => null,
            'status'     => 'active',
            'role_id'    => Role::where('name', 'patient')->value('id'),
        ], $overrides));
    }

    private function identity(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Amina',
            'last_name'  => 'Ngassa',
            'dob'        => '1994-03-12',
            'sex'        => 'female',
            'phone'      => '+237670000000',
            'city'       => 'Douala',
        ], $overrides);
    }

    public function test_signup_needs_only_an_email_and_a_password(): void
    {
        $this->post('/signup/patient', [
            'email'                 => 'amina@example.test',
            'password'              => 'correct-horse-8',
            'password_confirmation' => 'correct-horse-8',
        ])->assertRedirect(route('portals.patient.complete-profile'));

        $user = User::where('email', 'amina@example.test')->first();

        $this->assertNotNull($user, 'the account should exist');
        $this->assertNull($user->patient_id, 'no patient record until identity is given');
        $this->assertSame(0, Patient::count(), 'no Health ID is minted at sign-up');
        $this->assertAuthenticatedAs($user);
    }

    /** The regression that made self-registration useless. */
    public function test_a_self_registered_patient_can_log_in(): void
    {
        $this->post('/signup/patient', [
            'email'                 => 'login@example.test',
            'password'              => 'correct-horse-8',
            'password_confirmation' => 'correct-horse-8',
        ]);

        $user = User::where('email', 'login@example.test')->firstOrFail();

        $this->assertSame('active', $user->status, "status 'pending' would lock the account out of login");

        $this->post('/login', [
            'email'    => 'login@example.test',
            'password' => 'correct-horse-8',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticated();
    }

    public function test_signup_rejects_a_duplicate_email_and_a_short_password(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->post('/signup/patient', [
            'email'                 => 'taken@example.test',
            'password'              => 'correct-horse-8',
            'password_confirmation' => 'correct-horse-8',
        ])->assertSessionHasErrors('email');

        $this->post('/signup/patient', [
            'email'                 => 'fresh@example.test',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertSame(1, User::where('email', 'like', '%@example.test')->count());
    }

    public function test_the_portal_is_closed_until_identity_is_supplied(): void
    {
        $this->actingAs($this->patientUser())
            ->withSession(['mfa.verified' => true])
            ->get('/portals/patient')
            ->assertRedirect(route('portals.patient.complete-profile'));
    }

    public function test_completing_the_profile_mints_the_health_id_and_opens_the_portal(): void
    {
        $user = $this->patientUser();

        $this->actingAs($user)
            ->withSession(['mfa.verified' => true])
            ->post('/portals/patient/complete-profile', $this->identity())
            ->assertRedirect(route('portals.patient'));

        $user->refresh();

        $this->assertNotNull($user->patient_id, 'the account should now be linked to a patient');
        $this->assertSame('Amina Ngassa', $user->name, 'the email local-part placeholder should be replaced');

        $patient = Patient::find($user->patient_id);

        $this->assertMatchesRegularExpression(HealthIdGeneratorService::VALID_PATTERN, $patient->health_id);
        $this->assertSame(
            'provisional',
            is_object($patient->identity_status) ? $patient->identity_status->value : $patient->identity_status
        );
    }

    /** A patient must not be able to choose their own Health ID country prefix. */
    public function test_the_health_id_prefix_comes_from_config_not_from_the_request(): void
    {
        config(['health_id.default_country' => 'CM']);

        $user = $this->patientUser();

        $this->actingAs($user)
            ->withSession(['mfa.verified' => true])
            ->post('/portals/patient/complete-profile', $this->identity(['country' => 'Canada']));

        $patient = Patient::find($user->fresh()->patient_id);

        $this->assertSame('CM', $patient->country_code);
        $this->assertStringStartsWith('CM-HID-', $patient->health_id);
    }

    public function test_completion_is_a_one_time_step(): void
    {
        $user = $this->patientUser();

        $this->actingAs($user)
            ->withSession(['mfa.verified' => true])
            ->post('/portals/patient/complete-profile', $this->identity());

        $firstHealthId = Patient::find($user->fresh()->patient_id)->health_id;

        $this->actingAs($user->fresh())
            ->withSession(['mfa.verified' => true])
            ->post('/portals/patient/complete-profile', $this->identity(['first_name' => 'Someone']))
            ->assertRedirect(route('portals.patient'));

        $this->assertSame(1, Patient::count(), 'a second submission must not mint a second Health ID');
        $this->assertSame($firstHealthId, Patient::first()->health_id);
    }

    public function test_completion_rejects_incomplete_identity(): void
    {
        $this->actingAs($this->patientUser())
            ->withSession(['mfa.verified' => true])
            ->post('/portals/patient/complete-profile', ['first_name' => 'Amina'])
            ->assertSessionHasErrors(['last_name', 'dob', 'sex', 'phone']);

        $this->assertSame(0, Patient::count());
    }

    public function test_the_shortened_form_renders_in_both_languages(): void
    {
        foreach (['en', 'fr'] as $locale) {
            $response = $this->get('/signup/patient?lang=' . $locale);

            $response->assertOk();
            $response->assertSee(__('onboarding.patient.email', [], $locale), false);
            $response->assertSee(__('onboarding.patient.password', [], $locale), false);
            // The nine fields the old form demanded up front are gone.
            $response->assertDontSee('name="dob"', false);
            $response->assertDontSee('name="emergency_phone"', false);

            // The old form's "I agree" checkboxes were never validated
            // server-side. The notice that replaced them must at least be
            // present, in this locale, with both links live.
            $response->assertSee(route('public.terms'), false);
            $response->assertSee(route('public.privacy'), false);
            $response->assertSee(e(__('onboarding.patient.terms_link', [], $locale)), false);
            $response->assertSee(e(__('onboarding.patient.privacy_link', [], $locale)), false);
        }
    }

    public function test_signup_is_rate_limited(): void
    {
        Hash::setRounds(4);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/signup/patient', [
                'email'                 => "burst{$i}@example.test",
                'password'              => 'correct-horse-8',
                'password_confirmation' => 'correct-horse-8',
            ]);
        }

        $this->post('/signup/patient', [
            'email'                 => 'burst-over@example.test',
            'password'              => 'correct-horse-8',
            'password_confirmation' => 'correct-horse-8',
        ])->assertStatus(429);
    }
}
