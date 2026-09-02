<?php

namespace Tests\Feature\Mobile;

use App\Mail\PasswordResetCodeMail;
use App\Models\Patient;
use App\Models\PasswordResetCode;
use App\Models\PatientAccessToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MobilePasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::forceCreate([
            'health_id'     => 'CM-HID-RESET-0001',
            'first_name'    => 'Jane',
            'last_name'     => 'Patient',
            'date_of_birth' => '1992-06-15',
            'sex'           => 'female',
            'phone_number'  => '0700000002',
            'email'         => 'jane.reset@example.test',
            'is_demo'       => false,
        ]);

        $this->user = User::factory()->create([
            'email'      => 'jane.reset@example.test',
            'password'   => Hash::make('OldPassword123'),
            'patient_id' => $this->patient->id,
        ]);
    }

    public function test_forgot_password_sends_a_reset_code_for_a_known_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/mobile/auth/forgot-password', [
            'email' => 'jane.reset@example.test',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => __('api.password_reset_code_sent')]);

        $this->assertDatabaseHas('password_reset_codes', [
            'email' => 'jane.reset@example.test',
        ]);

        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) {
            return $mail->recipientEmail === 'jane.reset@example.test';
        });
    }

    public function test_forgot_password_for_unknown_email_returns_generic_success_without_sending_mail(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/mobile/auth/forgot-password', [
            'email' => 'nobody@example.test',
        ]);

        // Same 200 + generic message as the known-email case — no enumeration.
        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => __('api.password_reset_code_sent')]);

        $this->assertDatabaseMissing('password_reset_codes', [
            'email' => 'nobody@example.test',
        ]);

        Mail::assertNothingSent();
    }

    public function test_reset_password_with_valid_code_updates_password_and_revokes_tokens(): void
    {
        $code = '123456';
        PasswordResetCode::create([
            'email'      => 'jane.reset@example.test',
            'code_hash'  => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $rawToken = 'pat_' . str_repeat('b', 40);
        PatientAccessToken::create([
            'patient_id'   => $this->patient->id,
            'token_hash'   => Hash::make($rawToken),
            'token_prefix' => substr($rawToken, 0, 12),
            'expires_at'   => Carbon::now()->addDays(30),
        ]);

        $response = $this->postJson('/api/mobile/auth/reset-password', [
            'email'                 => 'jane.reset@example.test',
            'code'                  => $code,
            'password'              => 'NewPassword456',
            'password_confirmation' => 'NewPassword456',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => __('api.password_reset_success')]);

        $this->user->refresh();
        $this->assertTrue(Hash::check('NewPassword456', $this->user->password));

        // Existing mobile sessions are revoked on password reset.
        $this->assertDatabaseMissing('patient_access_tokens', [
            'patient_id' => $this->patient->id,
        ]);

        // A brand-new login with the new password should now work via login-email.
        $login = $this->postJson('/api/mobile/auth/login-email', [
            'email'    => 'jane.reset@example.test',
            'password' => 'NewPassword456',
        ]);
        $login->assertStatus(200)->assertJsonFragment(['status' => 'authenticated']);
    }

    public function test_reset_password_cannot_be_reused(): void
    {
        $code = '654321';
        PasswordResetCode::create([
            'email'      => 'jane.reset@example.test',
            'code_hash'  => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $this->postJson('/api/mobile/auth/reset-password', [
            'email'                 => 'jane.reset@example.test',
            'code'                  => $code,
            'password'              => 'FirstNewPass1',
            'password_confirmation' => 'FirstNewPass1',
        ])->assertStatus(200);

        // Second attempt with the same code must fail — it's already used.
        $this->postJson('/api/mobile/auth/reset-password', [
            'email'                 => 'jane.reset@example.test',
            'code'                  => $code,
            'password'              => 'SecondNewPass2',
            'password_confirmation' => 'SecondNewPass2',
        ])->assertStatus(401);
    }

    public function test_reset_password_with_wrong_code_returns_401(): void
    {
        PasswordResetCode::create([
            'email'      => 'jane.reset@example.test',
            'code_hash'  => Hash::make('111111'),
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $this->postJson('/api/mobile/auth/reset-password', [
            'email'                 => 'jane.reset@example.test',
            'code'                  => '999999',
            'password'              => 'AnyPassword12',
            'password_confirmation' => 'AnyPassword12',
        ])->assertStatus(401);
    }

    public function test_reset_password_with_expired_code_returns_401(): void
    {
        PasswordResetCode::create([
            'email'      => 'jane.reset@example.test',
            'code_hash'  => Hash::make('222222'),
            'expires_at' => Carbon::now()->subMinutes(1),
        ]);

        $this->postJson('/api/mobile/auth/reset-password', [
            'email'                 => 'jane.reset@example.test',
            'code'                  => '222222',
            'password'              => 'AnyPassword12',
            'password_confirmation' => 'AnyPassword12',
        ])->assertStatus(401);
    }

    public function test_reset_password_requires_matching_confirmation(): void
    {
        PasswordResetCode::create([
            'email'      => 'jane.reset@example.test',
            'code_hash'  => Hash::make('333333'),
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $this->postJson('/api/mobile/auth/reset-password', [
            'email'                 => 'jane.reset@example.test',
            'code'                  => '333333',
            'password'              => 'AnyPassword12',
            'password_confirmation' => 'DifferentPassword',
        ])->assertStatus(422);
    }
}
