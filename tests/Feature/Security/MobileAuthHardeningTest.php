<?php

namespace Tests\Feature\Security;

use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\PatientOtpCode;
use App\Models\Role;
use App\Models\User;
use App\Modules\Auth\Services\TwoFactorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Mobile patient auth (2026-10-19 audit):
 *  - email login applies the web login's status + MFA gates;
 *  - every minted token carries the full-entropy lookup hash;
 *  - refresh is hash-looked-up, single-use, with a capped expiry grace;
 *  - a first-login PIN takes effect only after the OTP round-trip.
 */
class MobileAuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function patient(array $overrides = []): Patient
    {
        return Patient::forceCreate(array_merge([
            'health_id'     => 'CM-HID-SEC-'.strtoupper(Str::random(6)),
            'first_name'    => 'Ama',
            'last_name'     => 'Test',
            'date_of_birth' => '1990-01-01',
            'sex'           => 'female',
            'phone_number'  => '0700'.random_int(100000, 999999),
            'email'         => 'patient-'.Str::random(6).'@example.test',
            'is_demo'       => false,
        ], $overrides));
    }

    private function user(Patient $patient, string $role, string $status = 'active'): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $user = User::factory()->create([
            'email'    => $patient->email,
            'password' => Hash::make('secret-pass'),
            'status'   => $status,
        ]);
        $user->role_id = $roleModel->id;
        $user->patient_id = $patient->id;
        $user->save();

        return $user;
    }

    private function login(string $email, array $extra = [])
    {
        return $this->postJson('/api/mobile/auth/login-email', array_merge([
            'email'    => $email,
            'password' => 'secret-pass',
        ], $extra));
    }

    // ── Email login gates ─────────────────────────────────────────────────

    public function test_suspended_account_gets_no_token(): void
    {
        $p = $this->patient();
        $this->user($p, 'patient', 'suspended');

        $this->login($p->email)->assertStatus(403)->assertJsonPath('code', 'account_suspended');
        $this->assertSame(0, PatientAccessToken::count());
    }

    public function test_pending_non_patient_account_gets_no_token(): void
    {
        $p = $this->patient();
        $this->user($p, 'doctor', 'pending');

        $this->login($p->email)->assertStatus(403)->assertJsonPath('code', 'account_pending');
    }

    public function test_pending_patient_from_app_registration_can_still_sign_in(): void
    {
        $p = $this->patient();
        $this->user($p, 'patient', 'pending');

        $this->login($p->email)->assertOk()->assertJsonPath('status', 'authenticated');
    }

    public function test_mfa_enrolled_account_needs_a_valid_code(): void
    {
        $p = $this->patient();
        $user = $this->user($p, 'patient');
        $twoFactor = app(TwoFactorService::class);
        $secret = $twoFactor->generateSecret();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now()])->save();

        $this->login($p->email)->assertStatus(403)->assertJsonPath('code', 'mfa_required');
        $this->login($p->email, ['mfa_code' => '000000'])->assertStatus(401)->assertJsonPath('code', 'mfa_code_invalid');
        $this->assertSame(0, PatientAccessToken::count());

        $this->login($p->email, ['mfa_code' => $twoFactor->codeAt($secret)])->assertOk();
    }

    public function test_mfa_required_role_without_enrolment_is_refused(): void
    {
        config()->set('mfa.required_roles', ['doctor']);
        $p = $this->patient();
        $this->user($p, 'doctor');

        $this->login($p->email)->assertStatus(403)->assertJsonPath('code', 'mfa_setup_required');
    }

    public function test_minted_token_carries_lookup_hash(): void
    {
        $p = $this->patient();
        $this->user($p, 'patient');

        $raw = $this->login($p->email)->assertOk()->json('access_token');

        $this->assertDatabaseHas('patient_access_tokens', ['token_lookup_hash' => hash('sha256', $raw)]);
    }

    // ── Refresh ───────────────────────────────────────────────────────────

    private function token(Patient $p, Carbon $expiresAt): string
    {
        $raw = 'pat_'.Str::random(40);
        PatientAccessToken::forceCreate([
            'patient_id'        => $p->id,
            'token_hash'        => Hash::make($raw),
            'token_prefix'      => substr($raw, 0, 12),
            'token_lookup_hash' => hash('sha256', $raw),
            'expires_at'        => $expiresAt,
        ]);

        return $raw;
    }

    public function test_refresh_is_single_use(): void
    {
        $p = $this->patient();
        $old = $this->token($p, now()->addDay());

        $new = $this->withToken($old)->postJson('/api/mobile/auth/refresh')->assertOk()->json('access_token');
        $this->assertNotSame($old, $new);

        // Replaying the consumed token fails; the new one works.
        $this->withToken($old)->postJson('/api/mobile/auth/refresh')->assertStatus(401);
        $this->assertDatabaseMissing('patient_access_tokens', ['token_lookup_hash' => hash('sha256', $old)]);
        $this->assertDatabaseHas('patient_access_tokens', ['token_lookup_hash' => hash('sha256', $new)]);
    }

    public function test_refresh_grace_is_capped(): void
    {
        $p = $this->patient();

        $recent = $this->token($p, now()->subHours(2));
        $this->withToken($recent)->postJson('/api/mobile/auth/refresh')->assertOk();

        $stale = $this->token($p, now()->subDays(3));
        $this->withToken($stale)->postJson('/api/mobile/auth/refresh')->assertStatus(401);
    }

    public function test_refresh_does_not_match_on_prefix_alone(): void
    {
        $p = $this->patient();
        $real = $this->token($p, now()->addDay());
        $forged = substr($real, 0, 12).Str::random(32);

        $this->withToken($forged)->postJson('/api/mobile/auth/refresh')->assertStatus(401);
    }

    // ── PIN bootstrap ─────────────────────────────────────────────────────

    public function test_pin_is_set_only_after_otp_round_trip(): void
    {
        $p = $this->patient(['pin_hash' => null, 'phone_number' => '0711222333', 'date_of_birth' => '1985-03-20']);

        $this->postJson('/api/mobile/auth/login', [
            'phone_number'  => '0711222333',
            'pin'           => '4321',
            'date_of_birth' => '1985-03-20',
        ])->assertOk();

        $this->assertNull($p->fresh()->pin_hash);

        // Replace the SMS'd code with a known one to complete the round-trip.
        PatientOtpCode::query()->update(['used_at' => now()]);
        PatientOtpCode::create([
            'phone_number' => '0711222333',
            'code_hash'    => Hash::make('654321'),
            'expires_at'   => now()->addMinutes(10),
        ]);

        $this->postJson('/api/mobile/auth/otp/verify', ['phone_number' => '0711222333', 'otp' => '654321'])->assertOk();

        $this->assertTrue(Hash::check('4321', $p->fresh()->pin_hash));
    }

    public function test_wrong_otp_attempts_are_capped(): void
    {
        $this->patient(['phone_number' => '0711444555']);
        PatientOtpCode::create([
            'phone_number' => '0711444555',
            'code_hash'    => Hash::make('111111'),
            'expires_at'   => now()->addMinutes(10),
        ]);

        // The route throttle is 5/min per IP; spread attempts over IPs so the
        // per-patient budget is what is being tested.
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.$i"])
                ->postJson('/api/mobile/auth/otp/verify', ['phone_number' => '0711444555', 'otp' => '00000'.$i])
                ->assertStatus(401);
        }

        // Even the right code is now refused.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.1.1'])
            ->postJson('/api/mobile/auth/otp/verify', ['phone_number' => '0711444555', 'otp' => '111111'])
            ->assertStatus(429);
    }
}
