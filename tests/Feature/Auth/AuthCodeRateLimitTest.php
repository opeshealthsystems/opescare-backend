<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Brute-force limits on the endpoints that accept a short numeric secret.
 *
 * /mfa/challenge verifies a 6-digit TOTP and, on success, logs the user in. It
 * had no limit of any kind, so anyone holding a stolen password could sit on
 * it and walk the whole 000000-999999 space against a code that stays valid
 * for a minute or two. The password-reset endpoint was equally open, which
 * makes it both an email cannon and a way to probe which addresses exist.
 */
class AuthCodeRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function userAwaitingMfa(): User
    {
        return User::factory()->create([
            'status'                  => 'active',
            'password'                => Hash::make('correct-horse-8'),
            'two_factor_secret'       => 'JBSWY3DPEHPK3PXP',
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function test_the_mfa_challenge_stops_accepting_guesses(): void
    {
        $user = $this->userAwaitingMfa();

        $statuses = [];
        for ($i = 0; $i < 7; $i++) {
            $statuses[] = $this->withSession(['mfa.user_id' => $user->id])
                ->post('/mfa/challenge', ['code' => str_pad((string) $i, 6, '0', STR_PAD_LEFT)])
                ->getStatusCode();
        }

        $this->assertContains(
            429,
            $statuses,
            'unlimited guesses against a 6-digit code is an MFA bypass: ' . implode(',', $statuses)
        );
    }

    /** The limit must follow the account, not just the source address. */
    public function test_the_mfa_limit_is_keyed_to_the_account_under_challenge(): void
    {
        $user = $this->userAwaitingMfa();

        for ($i = 0; $i < 6; $i++) {
            $this->withSession(['mfa.user_id' => $user->id])
                ->post('/mfa/challenge', ['code' => '000000']);
        }

        // Same account, a fresh session — still refused.
        $this->withSession(['mfa.user_id' => $user->id])
            ->post('/mfa/challenge', ['code' => '123456'])
            ->assertStatus(429);
    }

    public function test_password_reset_requests_are_capped(): void
    {
        $statuses = [];
        for ($i = 0; $i < 5; $i++) {
            $statuses[] = $this->post('/forgot-password', ['email' => "probe{$i}@example.test"])
                ->getStatusCode();
        }

        $this->assertContains(
            429,
            $statuses,
            'an open reset endpoint is an email cannon and an enumeration oracle: ' . implode(',', $statuses)
        );
    }

    public function test_the_contact_form_cannot_be_used_as_a_write_amplifier(): void
    {
        $statuses = [];
        for ($i = 0; $i < 7; $i++) {
            $statuses[] = $this->post('/contact', [
                'name'    => "Spammer {$i}",
                'email'   => "spam{$i}@example.test",
                'message' => 'flood',
            ])->getStatusCode();
        }

        $this->assertContains(429, $statuses, 'contact writes a row per request: ' . implode(',', $statuses));
    }
}
