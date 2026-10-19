<?php

namespace Tests\Feature\Perf;

use App\Models\Appointment;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\PatientAccessToken;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * N+1 guard for the hot patient endpoints: query count must not grow with the
 * number of records (measured 2026-10-19: flat, 5-12 queries each).
 */
class PatientEndpointsQueryCountTest extends TestCase
{
    use RefreshDatabase;

    private function seedRows(Patient $p, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $rx = Prescription::factory()->create(['patient_id' => $p->id, 'status' => 'active']);
            PrescriptionItem::factory()->create(['prescription_id' => $rx->id]);
            Appointment::factory()->create(['patient_id' => $p->id, 'scheduled_at' => now()->addDays($i + 1)]);
            LabResult::factory()->create(['patient_id' => $p->id]);
        }
    }

    private function measure(string $token, string $uri): array
    {
        $n = 0;
        DB::listen(function () use (&$n) { $n++; });
        $r = $this->withHeader('Authorization', 'Bearer ' . $token)->getJson($uri);
        return [$r->status(), $n];
    }

    public function test_query_count_does_not_grow_with_records(): void
    {
        $out = [];
        foreach ([2, 8] as $n) {
            $p = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
            $token = 'pat_' . Str::random(40);
            PatientAccessToken::create(['patient_id' => $p->id, 'token_hash' => Hash::make($token), 'token_prefix' => substr($token, 0, 12), 'expires_at' => now()->addDays(30)]);
            $this->seedRows($p, $n);
            foreach (['/api/mobile/me', '/api/mobile/timeline', '/api/mobile/clinical', '/api/mobile/prescriptions', '/api/mobile/appointments', '/api/mobile/documents', '/api/mobile/notifications', '/api/mobile/messages/threads', '/api/mobile/referrals', '/api/mobile/care-plans', '/api/mobile/family', '/api/mobile/payments/invoices', '/api/mobile/access-logs', '/api/mobile/records/imported', '/api/mobile/labs', '/api/mobile/facilities', '/api/mobile/pharmacy/reservations', '/api/mobile/health-passport'] as $u) {
                $this->app['events']->forget(\Illuminate\Database\Events\QueryExecuted::class);
                $out[$u][$n] = $this->measure($token, $u);
            }
        }
        foreach ($out as $u => $v) {
            $this->assertSame(200, $v[8][0], $u);
            $this->assertLessThanOrEqual($v[2][1] + 1, $v[8][1], "$u grows with record count (N+1)");
            $this->assertLessThanOrEqual(25, $v[8][1], "$u issues too many queries");
        }
    }
}
