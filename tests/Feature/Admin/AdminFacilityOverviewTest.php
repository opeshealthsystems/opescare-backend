<?php

namespace Tests\Feature\Admin;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The platform admin's facility detail page shows real rows only: a section
 * with nothing behind it is hidden or says so, never a made-up number.
 */
class AdminFacilityOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withSession(['mfa.verified' => true]);
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::where('name', 'super_admin')->firstOrFail()->id]);
    }

    private function staffMember(string $roleName, Facility $facility, bool $mfa = false): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active',
            'two_factor_confirmed_at' => $mfa ? now() : null,
        ]);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return $user;
    }

    private function listing(Facility $facility, array $attrs = []): string
    {
        $id = (string) Str::uuid();
        DB::table('care_facilities')->insert(array_merge([
            'id' => $id, 'facility_id' => $facility->id, 'facility_name' => $facility->name,
            'facility_type' => 'clinic', 'license_status' => 'valid', 'verification_status' => 'verified',
            'listing_status' => 'active', 'country_code' => 'CM', 'city' => 'Douala', 'address' => 'Rue Test 1',
            'region' => 'Littoral', 'phone_primary' => '+237600000001', 'integration_status' => 'none',
            'latitude' => 4.0911, 'longitude' => 9.7437,
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));

        return $id;
    }

    private function patient(Facility $facility): Patient
    {
        return Patient::factory()->create([
            'facility_id' => $facility->id,
            'is_demo' => (bool) config('demo.enabled'),
            'date_of_birth' => '1985-04-02',
        ]);
    }

    public function test_bare_facility_hides_every_section_without_data(): void
    {
        $facility = Facility::factory()->create(['name' => 'Clinique Vide', 'type' => 'clinic', 'status' => 'active']);

        $html = $this->actingAs($this->admin())->get(route('admin.facilities.show', $facility->id))
            ->assertOk()
            ->assertSee('Clinique Vide')
            ->assertSee(__('admin_facility.no_listing_location'))
            ->assertSee(__('admin_facility.activity_empty'))
            ->assertSee(route('admin.facilities.update', $facility->id), false)
            ->assertSee('suspend-modal', false)
            ->assertSee('delete-modal', false)
            ->getContent();

        foreach (['data-section="staff"', 'data-section="listing"', 'data-section="billing"', 'data-action="public-listing"',
                  'data-pill="claimed"', 'data-pill="mfa"', 'data-pill="plan"', 'href="#staff"', 'href="#billing"', 'href="#listing"'] as $absent) {
            $this->assertStringNotContainsString($absent, $html, "{$absent} must be hidden without data");
        }
        $this->assertStringContainsString('data-readiness-score="0"', $html);
    }

    public function test_populated_facility_shows_real_counts(): void
    {
        $facility = Facility::factory()->create(['name' => 'Clinique Pleine', 'type' => 'clinic', 'status' => 'active']);
        $listingId = $this->listing($facility, ['claimed_by_user_id' => null]);

        $this->staffMember('facility_admin', $facility, mfa: true);
        $this->staffMember('facility_admin', $facility, mfa: false);
        $doctor = $this->staffMember('doctor', $facility);

        $p1 = $this->patient($facility);
        $p2 = $this->patient($facility);
        $this->patient($facility); // registered, never seen
        foreach ([[$p1, 'completed'], [$p1, 'completed'], [$p2, 'no_show']] as [$p, $status]) {
            Appointment::create([
                'patient_id' => $p->id, 'facility_id' => $facility->id, 'provider_id' => $doctor->id,
                'appointment_type' => 'consultation', 'status' => $status, 'scheduled_at' => now()->subDays(3),
            ]);
        }

        Invoice::factory()->create(['facility_id' => $facility->id, 'patient_id' => $p1->id, 'status' => 'issued',
            'subtotal_amount' => 15000, 'patient_responsibility_amount' => 15000, 'balance_amount' => 15000]);
        Invoice::factory()->create(['facility_id' => $facility->id, 'patient_id' => $p2->id, 'status' => 'paid',
            'balance_amount' => 0, 'paid_amount' => 5000]);

        $html = $this->actingAs($this->admin())->get(route('admin.facilities.show', $facility->id))
            ->assertOk()
            ->assertSee('data-section="staff"', false)
            ->assertSee('data-section="listing"', false)
            ->assertSee('data-section="billing"', false)
            ->assertSee(route('public.care-map.profile', $listingId), false)
            ->assertSee('data-live-stock="0"', false)
            ->getContent();

        $kpi = fn (string $k) => preg_match('/data-kpi="' . $k . '".*?od-kpi__value">([^<]+)</s', $html, $m) ? trim($m[1]) : null;
        $this->assertSame('3', $kpi('staff'));
        $this->assertSame('2', $kpi('patients'));
        $this->assertSame('3', $kpi('appointments'));
        $this->assertSame('1', $kpi('invoices'));
        $this->assertStringContainsString(__('admin_facility.kpi_no_show', ['pct' => 33]), $html);
        $this->assertStringContainsString(__('admin_facility.pill_mfa', ['done' => 1, 'total' => 2]), $html);
        $this->assertStringContainsString('15', $html);
    }

    public function test_non_admin_is_refused(): void
    {
        $facility = Facility::factory()->create();
        $fa = $this->staffMember('facility_admin', $facility, mfa: true);

        $this->actingAs($fa)->withSession(['active_facility_id' => $facility->id])
            ->get(route('admin.facilities.show', $facility->id))
            ->assertForbidden();
    }

    public function test_query_count_is_bounded_and_flat(): void
    {
        $facility = Facility::factory()->create(['type' => 'clinic', 'status' => 'active']);
        $this->listing($facility);
        $admin = $this->admin();
        $url = route('admin.facilities.show', $facility->id);
        $this->actingAs($admin)->get($url)->assertOk();

        $count = function () use ($admin, $url) {
            $n = 0;
            $this->app['events']->forget(QueryExecuted::class);
            DB::listen(function () use (&$n) { $n++; });
            $this->actingAs($admin)->get($url)->assertOk();
            $this->app['events']->forget(QueryExecuted::class);

            return $n;
        };

        $few = $count();
        foreach (['doctor', 'nurse', 'facility_admin', 'doctor'] as $r) {
            $this->staffMember($r, $facility);
        }
        $many = $count();

        $this->assertLessThanOrEqual($few + 1, $many, "{$few} vs {$many} queries: N+1");
        $this->assertLessThan(40, $many);
    }
}
