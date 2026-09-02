<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Brute-force limits on the reset link itself.
 *
 * POST /forgot-password already carried 'throttle:password-reset' (covered by
 * AuthCodeRateLimitTest). The two /reset-password/{token} verbs carried nothing
 * at all, and both are oracles:
 *
 *   - the POST sets a password from a token in the URL, so a successful guess
 *     is a full account takeover with no credential needed;
 *   - the GET tells a caller whether a token is live by what it renders — a
 *     form for a live one, a "link expired" card for a dead one — so an
 *     unthrottled GET finds the live token for free and the POST is spent once.
 *
 * The counters cannot reuse 'password-reset' (3/minute by IP): that rate is
 * right for an endpoint that sends mail and wrong for one a genuine user hits
 * three or four times in a row — land, submit, fail the eight-character rule,
 * submit again — from behind a shared office NAT.
 */
class PasswordResetRateLimitTest extends TestCase
{
    use RefreshDatabase;

    /** A distinct, syntactically plausible token per attempt — this is the attack. */
    private function guess(int $i): string
    {
        return str_pad((string) $i, 64, 'a', STR_PAD_LEFT);
    }

    public function test_walking_reset_tokens_over_the_get_is_capped(): void
    {
        $statuses = [];
        for ($i = 0; $i < 16; $i++) {
            $statuses[] = $this->get('/reset-password/' . $this->guess($i) . '?email=probe@example.test')
                ->getStatusCode();
        }

        $this->assertContains(
            429,
            $statuses,
            'the GET distinguishes a live reset token from a dead one, so an unlimited GET is a free oracle: '
                . implode(',', $statuses)
        );
    }

    public function test_walking_reset_tokens_over_the_post_is_capped(): void
    {
        $statuses = [];
        for ($i = 0; $i < 16; $i++) {
            $statuses[] = $this->post('/reset-password/' . $this->guess($i), [
                'email'                 => 'probe@example.test',
                'password'              => 'attacker-chosen-password-3',
                'password_confirmation' => 'attacker-chosen-password-3',
            ])->getStatusCode();
        }

        $this->assertContains(
            429,
            $statuses,
            'a successful guess on this endpoint takes over an account outright: ' . implode(',', $statuses)
        );
    }

    /** Hammering one leaked link is capped too, not just the spread-out walk. */
    public function test_hammering_a_single_reset_link_is_capped(): void
    {
        $token = $this->guess(0);

        $statuses = [];
        for ($i = 0; $i < 10; $i++) {
            $statuses[] = $this->get('/reset-password/' . $token . '?email=probe@example.test')
                ->getStatusCode();
        }

        $this->assertContains(429, $statuses, 'one link, hammered: ' . implode(',', $statuses));
    }

    /**
     * The limit must not cost a real user their account. One person costs a
     * handful of requests — land on the page, submit, mistype the confirmation,
     * submit again — and that has to keep working.
     */
    public function test_a_genuine_user_can_still_land_and_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email'    => 'real.user@example.test',
            'password' => Hash::make('old-correct-horse-8'),
            'status'   => 'active',
        ]);

        $this->post('/forgot-password', ['email' => $user->email])->assertRedirect();

        $raw = null;
        Notification::assertSentTo(
            $user,
            PasswordResetLinkNotification::class,
            function (object $notification) use (&$raw): bool {
                $raw = $notification->token;

                return true;
            }
        );

        $url = route('password.reset', ['token' => $raw, 'email' => $user->email]);

        $this->get($url)->assertOk();

        // A mistyped confirmation, then the real submission — the limit has to
        // survive an ordinary human correcting themselves.
        $this->from($url)->post('/reset-password/' . $raw, [
            'email'                 => $user->email,
            'password'              => 'new-correct-horse-9',
            'password_confirmation' => 'typo',
        ])->assertSessionHasErrors('password');

        $this->get($url)->assertOk();

        $this->from($url)->post('/reset-password/' . $raw, [
            'email'                 => $user->email,
            'password'              => 'new-correct-horse-9',
            'password_confirmation' => 'new-correct-horse-9',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-correct-horse-9', $user->fresh()->password));
    }
}
