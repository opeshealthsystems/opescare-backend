<?php

namespace Tests\Feature\Imports;

use App\Models\Facility;
use App\Models\FacilityReadinessScore;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use App\Modules\FacilityReadiness\Services\FacilityReadinessScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Group D import — facility go-live readiness (docs/PLATFORM_IMPORTS.md).
 * The score is built from the Finder's real go-live checks, one step per check.
 */
class FacilityReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function postStaff(string $roleName, Facility $facility): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);
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
            'listing_status' => 'active', 'country_code' => 'CM', 'city' => 'Douala', 'address' => 'Akwa',
            'phone_primary' => '+237600000000', 'integration_status' => 'none',
            'created_at' => now(), 'updated_at' => now(),
        ], $attrs));

        return $id;
    }

    public function test_the_score_rises_as_each_go_live_check_is_satisfied(): void
    {
        $scoring = app(FacilityReadinessScoringService::class);
        $facility = Facility::factory()->create();
        $admin = $this->postStaff('facility_admin', $facility);

        $this->assertSame(0, $scoring->evaluate($facility->id)['score']);

        $listingId = $this->listing($facility);
        $this->assertSame(17, $scoring->evaluate($facility->id)['score']);

        DB::table('care_facilities')->where('id', $listingId)->update(['latitude' => 4.05, 'longitude' => 9.7]);
        $this->assertSame(33, $scoring->evaluate($facility->id)['score']);

        DB::table('care_facility_hours')->insert([
            'id' => (string) Str::uuid(), 'facility_id' => $listingId, 'day_of_week' => 1,
            'opens_at' => '08:00', 'closes_at' => '17:00', 'is_closed' => false, 'is_24_hours' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertSame(50, $scoring->evaluate($facility->id)['score']);

        DB::table('facility_catalogue_items')->insert([
            'id' => (string) Str::uuid(), 'facility_id' => $facility->id, 'system' => 'loinc', 'code' => '718-7',
            'is_active' => true, 'added_by' => $admin->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertSame(67, $scoring->evaluate($facility->id)['score']);

        // The admin alone is not a team.
        $this->assertFalse($scoring->evaluate($facility->id)['checks']['staff_member']);
        $this->postStaff('doctor', $facility);
        $this->assertSame(83, $scoring->evaluate($facility->id)['score']);

        DB::table('care_facilities')->where('id', $listingId)->update(['logo_path' => 'facility-photos/x.jpg']);
        $result = $scoring->evaluate($facility->id);
        $this->assertSame(100, $result['score']);
        $this->assertSame([], $result['missing']);

        $snapshot = $scoring->calculateAndStore($facility->id);
        $this->assertSame(100.0, $snapshot->overall_score);
        $this->assertFalse($snapshot->is_ready, 'a score never unlocks go-live by itself');
    }

    public function test_platform_admin_sees_the_readiness_page_and_can_store_a_snapshot(): void
    {
        $facility = Facility::factory()->create(['name' => 'Clinique Bonamoussadi']);
        $admin = User::factory()->create(['role_id' => Role::where('name', 'super_admin')->firstOrFail()->id]);

        $this->actingAs($admin)->get("/admin/facilities/{$facility->id}/readiness")
            ->assertOk()->assertSee('Clinique Bonamoussadi')->assertSee('data-readiness-score="0"', false);

        $this->actingAs($admin)->post("/admin/facilities/{$facility->id}/readiness/score")->assertRedirect();
        $this->assertSame(1, FacilityReadinessScore::where('facility_id', $facility->id)->count());

        $this->actingAs($admin)->get('/admin/facilities/not-a-uuid/readiness')->assertNotFound();
    }

    public function test_facility_admin_sees_their_own_checklist_and_not_another_facilitys(): void
    {
        $mine = Facility::factory()->create(['type' => 'hospital']);
        $other = Facility::factory()->create(['type' => 'hospital']);
        $admin = $this->postStaff('facility_admin', $mine);

        // The other facility is fully listed with a photo; mine has nothing.
        $this->listing($other, ['latitude' => 4.0, 'longitude' => 9.0, 'logo_path' => 'facility-photos/o.jpg']);

        $html = $this->actingAs($admin)->withSession(['active_facility_id' => $mine->id])
            ->get(route('portals.facility.admin.overview'))
            ->assertOk()
            ->assertSee('data-facility="' . $mine->id . '"', false)
            ->assertDontSee('data-facility="' . $other->id . '"', false)
            ->getContent();

        $this->assertStringContainsString('data-readiness-score="0"', $html);
        $this->assertStringContainsString('data-check="photo_set" data-ok="0"', $html);

        // And the platform readiness page is not theirs to open.
        $this->actingAs($admin)->withSession(['active_facility_id' => $mine->id])
            ->get("/admin/facilities/{$other->id}/readiness")->assertForbidden();
    }
}
