<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /verify/otp must never report a verification it did not perform.
 *
 * The screen was theatre. submitVerifyOtp() compared the six digits against two
 * hardcoded literals — '000000' answered "incorrect", '111111' answered
 * "expired" — and every other value on earth fell through to a redirect
 * carrying flash.authentication_complete, sending the caller to a portal
 * landing page. resendOtp() sent no code and announced that one had been sent.
 *
 * It is not wired to any flow: nothing in the application redirects to
 * otp.verify, there is no session key naming a subject the way /mfa/challenge
 * carries mfa.user_id, and the OTP tables that exist (patient_otp_codes,
 * provider_otp_codes) belong to the mobile API's phone login. There is no
 * unambiguous correct behaviour to wire, so the endpoint fails closed.
 *
 * These tests pin the honesty, not the implementation: whatever /verify/otp
 * eventually becomes, it must not say "verified" without verifying.
 */
class VerifyOtpFailsClosedTest extends TestCase
{
    use RefreshDatabase;

    private function digits(string $code): array
    {
        return str_split($code);
    }

    public function test_a_guessed_code_is_never_accepted(): void
    {
        $response = $this->post('/verify/otp', ['otp' => $this->digits('123456')]);

        $response->assertRedirect(route('otp.verify'));
        $response->assertSessionHas('error');
        $response->assertSessionMissing('success');
    }

    /**
     * The old code only rejected '000000' and '111111'. Every other code in the
     * space was a pass, so the check has to be shown to hold across the space.
     */
    public function test_no_six_digit_code_reaches_a_portal(): void
    {
        foreach (['123456', '999999', '424242'] as $code) {
            $response = $this->post('/verify/otp', ['otp' => $this->digits($code)]);

            $response->assertRedirect(route('otp.verify'));
            $this->assertNull(
                $response->getSession()->get('success'),
                "code {$code} produced a success flash"
            );
        }
    }

    /** An empty submission must fail the same way — no code, no verification. */
    public function test_an_empty_submission_is_refused(): void
    {
        $response = $this->post('/verify/otp', []);

        $response->assertRedirect(route('otp.verify'));
        $response->assertSessionHas('error');
        $response->assertSessionMissing('success');
    }

    /** Being signed in must not turn the stub into a working second factor. */
    public function test_a_signed_in_user_is_not_handed_their_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post('/verify/otp', ['otp' => $this->digits('123456')]);

        $response->assertRedirect(route('otp.verify'));
        $response->assertSessionMissing('success');
    }

    public function test_resend_does_not_claim_to_have_sent_a_code(): void
    {
        $response = $this->post('/verify/otp/resend');

        $response->assertRedirect(route('otp.verify'));
        $response->assertSessionMissing('success');
    }

    /**
     * The page's resend button used fetch() and announced a new code on any 2xx.
     * A JSON caller therefore has to receive a non-2xx, or the browser lies for
     * the server.
     */
    public function test_a_json_resend_is_refused_with_a_service_unavailable(): void
    {
        $this->postJson('/verify/otp/resend')
            ->assertStatus(503)
            ->assertJsonPath('error', 'otp_channel_unavailable');
    }

    /** The page itself must not present a code form it cannot honour. */
    public function test_the_page_states_that_verification_is_unavailable(): void
    {
        $response = $this->get('/verify/otp');

        $response->assertOk();
        $response->assertSee(__('auth.otp_unavailable.title'), false);
        $response->assertDontSee('name="otp[]"', false);
    }

    /** The same refusal has to exist in French — this is a bilingual product. */
    public function test_the_refusal_is_bilingual(): void
    {
        $this->app->setLocale('fr');

        $fr = __('auth.otp_unavailable.error');

        $this->assertNotSame('auth.otp_unavailable.error', $fr, 'the French refusal string is missing');
        $this->assertNotSame(
            trans('auth.otp_unavailable.error', [], 'en'),
            $fr,
            'the French refusal is the English string verbatim'
        );
    }
}
