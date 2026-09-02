<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The web password-recovery flow must actually recover a password.
 *
 * Before this suite existed the whole flow was decorative:
 * submitForgotPassword() ignored its input, issued no token and sent no mail,
 * yet flashed "instructions have been sent"; submitResetPassword() ignored its
 * input, changed nothing, and redirected to /login with "your password has been
 * securely updated"; and GET /reset-password/{token} rendered a working form
 * for any string at all. A user who forgot their password was told twice that
 * it had worked and stayed locked out for good.
 *
 * These tests pin the guarantees, not the implementation: a link is really
 * issued and stored hashed, it dies once it is used or once it expires, a dead
 * link never renders a form and never reports success, the response cannot be
 * used to tell a registered address from an unregistered one, and — the whole
 * point — the password changes and the new one signs in.
 */
class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    private const OLD_PASSWORD = 'old-correct-horse-8';
    private const NEW_PASSWORD = 'new-correct-horse-9';

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function account(string $email = 'ada.nkemcha@example.test'): User
    {
        return User::factory()->create([
            'email'    => $email,
            'password' => Hash::make(self::OLD_PASSWORD),
            'status'   => 'active',
        ]);
    }

    /**
     * Drives the real first leg of the flow and returns the raw token that was
     * mailed out. Deliberately end-to-end: it posts the form the user posts and
     * reads the token off the notification the user receives, so it cannot pass
     * against an endpoint that only pretends to send one.
     */
    private function requestLink(User $user): string
    {
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

        $this->assertIsString($raw, 'no reset token was carried by the notification');

        return $raw;
    }

    private function resetUrl(string $email, string $token): string
    {
        return route('password.reset', ['token' => $token, 'email' => $email]);
    }

    /*
     * ── 1. No account enumeration ────────────────────────────────────────────
     */

    public function test_the_response_is_identical_for_a_registered_and_an_unregistered_address(): void
    {
        $user = $this->account();

        $registered = $this->post('/forgot-password', ['email' => $user->email]);
        $unknown    = $this->post('/forgot-password', ['email' => 'nobody-at-all@example.test']);

        $this->assertSame(
            $registered->getStatusCode(),
            $unknown->getStatusCode(),
            'the status code distinguishes a registered address from an unregistered one'
        );

        $this->assertSame(
            $registered->headers->get('Location'),
            $unknown->headers->get('Location'),
            'the redirect target distinguishes a registered address from an unregistered one'
        );

        $this->assertSame(
            $registered->getSession()->get('success'),
            $unknown->getSession()->get('success'),
            'the flash message distinguishes a registered address from an unregistered one'
        );

        $unknown->assertSessionHasNoErrors();
    }

    /**
     * A dead queue or mail transport must not change the SHAPE of the answer.
     * If the send is allowed to throw, /forgot-password answers 500 for a
     * registered address and 302 for an unknown one — a cleaner oracle than any
     * message body.
     */
    public function test_a_failing_mail_layer_does_not_become_an_enumeration_oracle(): void
    {
        $user = $this->account();

        Notification::shouldReceive('send')
            ->andThrow(new \RuntimeException('queue is down'));

        $registered = $this->post('/forgot-password', ['email' => $user->email]);
        $unknown    = $this->post('/forgot-password', ['email' => 'nobody-at-all@example.test']);

        $this->assertSame(302, $registered->getStatusCode(), 'a mail failure leaked through as a 500');
        $this->assertSame($registered->getStatusCode(), $unknown->getStatusCode());
        $this->assertSame(
            $registered->headers->get('Location'),
            $unknown->headers->get('Location')
        );
    }

    /*
     * ── 2. A link is really issued, and stored hashed ────────────────────────
     */

    public function test_a_registered_address_gets_a_real_single_use_token_stored_hashed(): void
    {
        $user = $this->account();

        $raw = $this->requestLink($user);

        $stored = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');

        $this->assertNotNull($stored, 'no reset token was persisted, so the emailed link could never be honoured');
        $this->assertNotSame($raw, $stored, 'the reset token is stored in plain text — a database read is a takeover');
        $this->assertTrue(
            Hash::check($raw, $stored),
            'the stored value is neither the token nor a hash of it'
        );
        $this->assertGreaterThanOrEqual(32, strlen($raw), 'the token is too short to resist guessing');
    }

    public function test_an_unregistered_address_creates_no_token_and_sends_no_mail(): void
    {
        $this->post('/forgot-password', ['email' => 'nobody-at-all@example.test'])->assertRedirect();

        $this->assertDatabaseCount('password_reset_tokens', 0);
        Notification::assertNothingSent();
    }

    /**
     * Registration stores users.email exactly as it was typed, and PostgreSQL
     * matches it case-sensitively, so recovery must not fold the case: an
     * address login can reach has to be an address recovery can reach.
     */
    public function test_recovery_reaches_an_account_whose_stored_address_has_capitals(): void
    {
        $user = $this->account('Ada.Nkemcha@Example.test');

        $raw = $this->requestLink($user);

        $this->get($this->resetUrl($user->email, $raw))->assertOk();

        $this->post('/reset-password/' . $raw, [
            'email'                 => $user->email,
            'password'              => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->fresh()->password));
    }

    /*
     * ── 3. The password actually changes ─────────────────────────────────────
     */

    public function test_the_password_really_changes_and_the_new_one_signs_in(): void
    {
        $user = $this->account();
        $raw  = $this->requestLink($user);

        $this->from($this->resetUrl($user->email, $raw))
            ->post('/reset-password/' . $raw, [
                'email'                 => $user->email,
                'password'              => self::NEW_PASSWORD,
                'password_confirmation' => self::NEW_PASSWORD,
            ])
            ->assertRedirect(route('login'));

        $user->refresh();

        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->password), 'the new password was not stored');
        $this->assertFalse(Hash::check(self::OLD_PASSWORD, $user->password), 'the old password still works');

        $this->post('/login', [
            'email'    => $user->email,
            'password' => self::NEW_PASSWORD,
        ]);

        $this->assertAuthenticatedAs($user->fresh());
    }

    /*
     * ── 4. Single use ────────────────────────────────────────────────────────
     */

    public function test_the_token_cannot_be_used_twice(): void
    {
        $user = $this->account();
        $raw  = $this->requestLink($user);

        $this->post('/reset-password/' . $raw, [
            'email'                 => $user->email,
            'password'              => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        $replay = $this->post('/reset-password/' . $raw, [
            'email'                 => $user->email,
            'password'              => 'attacker-chosen-password-3',
            'password_confirmation' => 'attacker-chosen-password-3',
        ]);

        $this->assertNotSame(
            302,
            $replay->getStatusCode(),
            'a spent reset link was accepted a second time'
        );
        $replay->assertSessionMissing('success');

        $user->refresh();
        $this->assertTrue(
            Hash::check(self::NEW_PASSWORD, $user->password),
            'a replayed reset link overwrote the password a second time'
        );

        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    /*
     * ── 5. Expiry ────────────────────────────────────────────────────────────
     */

    public function test_an_expired_token_is_refused(): void
    {
        $user = $this->account();
        $raw  = $this->requestLink($user);

        $ttl = (int) config('auth.passwords.users.expire', 60);

        $this->travel($ttl + 5)->minutes();

        $post = $this->post('/reset-password/' . $raw, [
            'email'                 => $user->email,
            'password'              => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ]);

        $post->assertSessionMissing('success');

        $user->refresh();
        $this->assertTrue(
            Hash::check(self::OLD_PASSWORD, $user->password),
            'an expired reset link still changed the password'
        );
    }

    public function test_an_expired_token_does_not_render_the_form(): void
    {
        $user = $this->account();
        $raw  = $this->requestLink($user);

        $ttl = (int) config('auth.passwords.users.expire', 60);

        $this->travel($ttl + 5)->minutes();

        $response = $this->get($this->resetUrl($user->email, $raw));

        $this->assertNotSame(200, $response->getStatusCode(), 'an expired link rendered a working reset form');
        $response->assertDontSee('name="password"', false);
    }

    /*
     * ── 6. A dead token must fail visibly ────────────────────────────────────
     */

    public function test_the_reset_form_is_not_rendered_for_a_token_that_was_never_issued(): void
    {
        $user = $this->account();

        $response = $this->get($this->resetUrl($user->email, str_repeat('f', 64)));

        $this->assertNotSame(
            200,
            $response->getStatusCode(),
            'any string at all rendered a working reset form — the token is decorative'
        );
        $response->assertDontSee('name="password"', false);
    }

    public function test_a_forged_token_cannot_change_a_password(): void
    {
        $user = $this->account();

        $response = $this->post('/reset-password/' . str_repeat('f', 64), [
            'email'                 => $user->email,
            'password'              => 'attacker-chosen-password-3',
            'password_confirmation' => 'attacker-chosen-password-3',
        ]);

        $response->assertSessionMissing('success');

        $user->refresh();
        $this->assertTrue(
            Hash::check(self::OLD_PASSWORD, $user->password),
            'a forged token changed the password'
        );
    }

    /**
     * A token issued for one account must not work on another. The email is
     * carried in the link, so nothing stops a caller from swapping it.
     */
    public function test_a_token_issued_for_one_account_does_not_reset_another(): void
    {
        $mallory = $this->account('mallory@example.test');
        $victim  = $this->account('victim@example.test');

        $raw = $this->requestLink($mallory);

        $this->post('/reset-password/' . $raw, [
            'email'                 => $victim->email,
            'password'              => 'attacker-chosen-password-3',
            'password_confirmation' => 'attacker-chosen-password-3',
        ]);

        $victim->refresh();
        $this->assertTrue(
            Hash::check(self::OLD_PASSWORD, $victim->password),
            "one account's reset token reset a different account"
        );
    }

    /*
     * ── 7. Credential change hygiene ─────────────────────────────────────────
     */

    public function test_a_successful_reset_signs_out_every_other_session(): void
    {
        $user = $this->account();

        DB::table('sessions')->insert([
            'id'            => 'stolen-session-id',
            'user_id'       => $user->id,
            'ip_address'    => '203.0.113.9',
            'user_agent'    => 'attacker',
            'payload'       => base64_encode(serialize([])),
            'last_activity' => time(),
        ]);

        $rememberBefore = $user->fresh()->remember_token;

        $raw = $this->requestLink($user);

        $this->post('/reset-password/' . $raw, [
            'email'                 => $user->email,
            'password'              => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('sessions', ['id' => 'stolen-session-id']);

        $this->assertNotSame(
            $rememberBefore,
            $user->fresh()->remember_token,
            'the remember-me token survived the reset, so a stolen cookie still signs in'
        );
    }

    public function test_a_successful_reset_is_audited(): void
    {
        $user = $this->account();
        $raw  = $this->requestLink($user);

        $this->post('/reset-password/' . $raw, [
            'email'                 => $user->email,
            'password'              => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('audit_events', [
            'action_type' => 'password_reset_completed',
            'actor_id'    => $user->id,
        ]);
    }

    /*
     * ── 8. Validation still applies ──────────────────────────────────────────
     */

    public function test_a_short_password_is_refused_and_the_old_one_survives(): void
    {
        $user = $this->account();
        $raw  = $this->requestLink($user);

        $this->from($this->resetUrl($user->email, $raw))
            ->post('/reset-password/' . $raw, [
                'email'                 => $user->email,
                'password'              => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $user->password));
    }

    public function test_a_mismatched_confirmation_is_refused(): void
    {
        $user = $this->account();
        $raw  = $this->requestLink($user);

        $this->from($this->resetUrl($user->email, $raw))
            ->post('/reset-password/' . $raw, [
                'email'                 => $user->email,
                'password'              => self::NEW_PASSWORD,
                'password_confirmation' => 'something-else-entirely',
            ])
            ->assertSessionHasErrors('password');

        $user->refresh();
        $this->assertTrue(Hash::check(self::OLD_PASSWORD, $user->password));
    }
}
