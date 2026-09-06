<?php

namespace Tests\Feature\Mobile;

use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\Patient;
use App\Models\ReferenceRange;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use App\Support\ResultFlagResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * One value, one verdict.
 *
 * A serum potassium of 6.9 mmol/L that the bench left unflagged used to reach
 * the PATIENT as "critical" (their screen compares against published ranges)
 * and the ORDERING CLINICIAN as "info — the laboratory recorded no flags"
 * (their inbox read the flag column and refused to derive anything). The
 * person who could act on it was the one not told.
 *
 * Both screens now classify through App\Support\ResultFlagResolver, so the
 * same row means the same thing everywhere — and the derivation is recorded
 * as a derivation, never passed off as the laboratory's own verdict.
 */
class CriticalValueAgreementTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private Patient $patient;
    private User $doctor;
    private string $bearer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create(['name' => 'Limbe District Hospital']);
        $this->doctor   = $this->professional('doctor');
        $this->bearer   = app(ProviderSessionService::class)->mint($this->doctor, $this->facility->id, 'test');

        $this->patient = Patient::factory()->create([
            'health_id'     => 'CM-HID-TEST-CRIT-0001',
            'sex'           => 'female',
            'date_of_birth' => now()->subYears(41)->toDateString(),
            'is_demo'       => (bool) config('demo.enabled'),
        ]);

        // The published panic limit for potassium. This is the only source that
        // carries critical bounds, so it is the only thing that can raise an
        // unflagged value to critical.
        ReferenceRange::create([
            'test_code'     => 'K',
            'test_name'     => 'Serum Potassium',
            'loinc_code'    => '2823-3',
            'age_group'     => 'all',
            'sex'           => 'all',
            'unit'          => 'mmol/L',
            'normal_low'    => 3.5,
            'normal_high'   => 5.1,
            'critical_low'  => 2.5,
            'critical_high' => 6.0,
            'source'        => 'test',
            'is_active'     => true,
        ]);
    }

    public function test_an_unflagged_panic_value_reaches_the_ordering_clinician_as_critical(): void
    {
        $this->resultedOrder(value: '6.9', flag: null);

        $row = $this->inboxRow();

        $this->assertSame('critical', $row['severity'], 'the clinician must see what the patient sees');
        $this->assertTrue($row['is_critical']);
    }

    public function test_the_inbox_count_of_criticals_includes_it(): void
    {
        $this->resultedOrder(value: '6.9', flag: null);

        $counts = $this->withHeaders(['Authorization' => "Bearer {$this->bearer}"])
            ->getJson('/api/mobile/doctor/results-inbox/counts')
            ->assertOk()
            ->json('data');

        $this->assertGreaterThanOrEqual(1, $counts['critical_unacknowledged'] ?? $counts['critical'] ?? 0);
    }

    public function test_a_value_inside_the_published_range_is_not_raised(): void
    {
        $this->resultedOrder(value: '4.2', flag: null);

        $this->assertNotSame('critical', $this->inboxRow()['severity']);
    }

    /**
     * Silence is not a clean bill of health. A value with nothing to compare
     * it against must not be reported as normal.
     */
    public function test_an_unflagged_value_with_no_published_range_stays_unflagged(): void
    {
        ReferenceRange::query()->delete();
        $this->resultedOrder(value: '6.9', flag: null);

        $row = $this->inboxRow();
        $this->assertSame('info', $row['severity']);
        $this->assertFalse($row['is_critical']);
    }

    /** The laboratory's own flag always wins — it saw the sample. */
    public function test_the_laboratorys_own_flag_outranks_the_published_range(): void
    {
        $this->resultedOrder(value: '4.2', flag: 'CRITICAL');

        $this->assertSame('critical', $this->inboxRow()['severity']);
    }

    public function test_the_resolver_records_where_the_flag_came_from(): void
    {
        $range = ReferenceRange::first();

        $derived = ResultFlagResolver::resolve(null, '6.9', null, $range);
        $this->assertSame('critical', $derived['flag']);
        $this->assertSame('published', $derived['source'], 'a derived flag is never the laboratory\'s own verdict');

        $bench = ResultFlagResolver::resolve('CRITICAL', '4.2', null, $range);
        $this->assertSame('critical', $bench['flag']);
        $this->assertSame('lab', $bench['source']);
    }

    // ── fixtures ─────────────────────────────────────────────────────────

    private function resultedOrder(string $value, ?string $flag): LabOrder
    {
        $order = LabOrder::create([
            'id'          => (string) Str::uuid(),
            'patient_id'  => $this->patient->id,
            'facility_id' => $this->facility->id,
            'ordered_by'  => $this->doctor->id,
            'test_name'   => 'Serum Potassium',
            'test_code'   => 'K',
            'urgency'     => 'routine',
            'status'      => 'resulted',
            'ordered_at'  => now()->subHour(),
            'resulted_at' => now(),
        ]);

        LabResult::withoutEvents(fn () => LabResult::create([
            'id'              => (string) Str::uuid(),
            'lab_order_id'    => $order->id,
            'patient_id'      => $this->patient->id,
            'parameter_name'  => 'Serum Potassium',
            'value'           => $value,
            'unit'            => 'mmol/L',
            'flag'            => $flag,
            'loinc_code'      => '2823-3',
            'resulted_at'     => now(),
        ]));

        return $order;
    }

    private function inboxRow(): array
    {
        $rows = $this->withHeaders(['Authorization' => "Bearer {$this->bearer}"])
            ->getJson('/api/mobile/doctor/results-inbox?filter=all')
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($rows, 'the ordering clinician must see their own result');

        return $rows[0];
    }

    private function professional(string $role): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save();

        return User::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);
    }
}
