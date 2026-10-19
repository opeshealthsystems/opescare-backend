<?php

namespace Tests\Feature\Security;

use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Modules\Notifications\Services\SmsNotificationService;
use App\Services\JwtService;
use App\Services\Payments\Providers\DemoPaymentProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/** 2026-10-19 audit: JWT claims, log hygiene, demo HMAC, self-service invoices, dead consent middleware, repo hygiene. */
class AuthAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    // ── JWT: exp and aud are required, alg is pinned ──────────────────────

    private function signed(array $payload, string $alg = 'RS256'): string
    {
        $path = (string) config('jwt.private_key_path');
        if (! is_file($path)) {
            $this->markTestSkipped('No JWT keypair on this host (php artisan opescare:jwt-keys).');
        }

        $b64 = fn (string $d) => rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
        $input = $b64(json_encode(['alg' => $alg, 'typ' => 'JWT'])).'.'.$b64(json_encode($payload));
        openssl_sign($input, $sig, openssl_pkey_get_private(file_get_contents($path)), OPENSSL_ALGO_SHA256);

        return $input.'.'.$b64($sig);
    }

    private function rejects(string $token, string $why): void
    {
        try {
            app(JwtService::class)->verify($token);
            $this->fail("JWT accepted: {$why}");
        } catch (\RuntimeException) {
            $this->addToAssertionCount(1);
        }
    }

    public function test_jwt_without_exp_is_rejected(): void
    {
        $this->rejects($this->signed(['iss' => 'opescare-connect', 'aud' => 'opescare-api']), 'no exp');
    }

    public function test_jwt_without_aud_is_rejected(): void
    {
        $this->rejects($this->signed(['iss' => 'opescare-connect', 'exp' => time() + 600]), 'no aud');
    }

    public function test_jwt_with_foreign_alg_header_is_rejected(): void
    {
        $this->rejects($this->signed(['iss' => 'opescare-connect', 'aud' => 'opescare-api', 'exp' => time() + 600], 'HS256'), 'alg');
    }

    public function test_jwt_with_all_claims_is_accepted(): void
    {
        $claims = app(JwtService::class)->verify(
            $this->signed(['iss' => 'opescare-connect', 'aud' => 'opescare-api', 'exp' => time() + 600])
        );
        $this->assertSame('opescare-api', $claims['aud']);
    }

    // ── SMS log never holds an OTP or a full number ───────────────────────

    public function test_simulated_sms_log_masks_number_and_code(): void
    {
        config()->set('services.twilio', ['sid' => null, 'token' => null, 'from' => null]);

        $captured = null;
        Log::shouldReceive('channel')->with('sms')->andReturnSelf();
        Log::shouldReceive('info')->andReturnUsing(function ($msg, $ctx) use (&$captured) {
            $captured = $ctx;
        });

        app(SmsNotificationService::class)->send('+237677123456', 'Your OpesCare verification code is: 482913. Valid for 10 minutes.');

        $dump = json_encode($captured);
        $this->assertStringNotContainsString('482913', $dump);
        $this->assertStringNotContainsString('677123456', $dump);
        $this->assertSame('***456', $captured['to']);
    }

    // ── Demo HMAC has no hardcoded fallback ───────────────────────────────

    public function test_demo_callback_token_requires_app_key(): void
    {
        config()->set('app.key', '');
        $this->expectException(\RuntimeException::class);
        DemoPaymentProvider::callbackToken('INV-1');
    }

    public function test_no_hardcoded_demo_hmac_key_in_source(): void
    {
        $src = file_get_contents(app_path('Services/Payments/Providers/DemoPaymentProvider.php'));
        $this->assertStringNotContainsString("'opescare-demo'", $src);
    }

    // ── Self-service invoices only where money is simulated ──────────────

    public function test_self_service_invoice_refused_where_money_could_be_real(): void
    {
        $p = Patient::forceCreate([
            'health_id' => 'CM-HID-INV-'.strtoupper(Str::random(6)), 'first_name' => 'A', 'last_name' => 'B',
            'date_of_birth' => '1990-01-01', 'sex' => 'male', 'phone_number' => '0700'.random_int(100000, 999999), 'is_demo' => false,
        ]);
        $raw = 'pat_'.Str::random(40);
        PatientAccessToken::forceCreate([
            'patient_id' => $p->id, 'token_hash' => Hash::make($raw), 'token_prefix' => substr($raw, 0, 12),
            'token_lookup_hash' => hash('sha256', $raw), 'expires_at' => now()->addDay(),
        ]);

        // The payment barriers read config('app.env') (what APP_ENV sets on a
        // real host), not the container's env string, so both are set here.
        $this->app['env'] = 'production';
        config()->set('app.env', 'production');

        $this->withToken($raw)->postJson('https://localhost/api/mobile/payments/invoices', [
            'amount_minor' => 999999, 'description' => 'anything I like',
        ])->assertStatus(403)->assertJsonPath('code', 'self_service_invoice_unavailable');

        $this->assertDatabaseCount('mobile_invoices', 0);
    }

    public function test_self_service_invoice_refused_where_live_credentials_exist(): void
    {
        $p = Patient::forceCreate([
            'health_id' => 'CM-HID-INV-'.strtoupper(Str::random(6)), 'first_name' => 'A', 'last_name' => 'B',
            'date_of_birth' => '1990-01-01', 'sex' => 'male', 'phone_number' => '0700'.random_int(100000, 999999), 'is_demo' => false,
        ]);
        $raw = 'pat_'.Str::random(40);
        PatientAccessToken::forceCreate([
            'patient_id' => $p->id, 'token_hash' => Hash::make($raw), 'token_prefix' => substr($raw, 0, 12),
            'token_lookup_hash' => hash('sha256', $raw), 'expires_at' => now()->addDay(),
        ]);

        // Even on a testing host: real merchant credentials mean money could
        // be real, so the simulator refuses and the endpoint must too.
        config()->set('services.mtn_momo.api_key', 'live-looking-key');

        $this->withToken($raw)->postJson('https://localhost/api/mobile/payments/invoices', [
            'amount_minor' => 999999, 'description' => 'anything I like',
        ])->assertStatus(403)->assertJsonPath('code', 'self_service_invoice_unavailable');

        $this->assertDatabaseCount('mobile_invoices', 0);
    }

    // ── Dead fail-open consent middleware is gone ────────────────────────

    public function test_unused_consent_grant_middleware_is_removed(): void
    {
        $this->assertFalse(class_exists(\App\Http\Middleware\RequireConsentGrant::class));
        $this->assertArrayNotHasKey('consent.grant', app('router')->getMiddleware());
    }

    // ── Repo hygiene ──────────────────────────────────────────────────────

    public function test_test_run_dump_is_not_tracked(): void
    {
        $out = @shell_exec('git -C '.escapeshellarg(base_path()).' ls-files storage/full_test_run.txt 2>&1');
        if ($out === null || str_contains((string) $out, 'not a git repository')) {
            $this->markTestSkipped('git not available');
        }
        $this->assertSame('', trim((string) $out));
        $this->assertStringContainsString('/storage/full_test_run.txt', file_get_contents(base_path('.gitignore')));
    }
}
