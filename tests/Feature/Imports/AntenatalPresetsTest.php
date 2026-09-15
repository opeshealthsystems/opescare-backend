<?php

namespace Tests\Feature\Imports;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Modules\Maternity\Services\MaternityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Group C import: the platform maternity visit kinds as appointment-type
 * presets on both booking forms. Presets only.
 */
class AntenatalPresetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    public function test_presets_come_from_the_imported_maternity_service_with_labels_in_both_languages(): void
    {
        $this->assertSame(['antenatal', 'postnatal'], MaternityService::appointmentPresets());

        foreach (['en', 'fr'] as $locale) {
            foreach (MaternityService::appointmentPresets() as $preset) {
                $key = 'public.appt_type_' . $preset;
                $this->assertNotSame($key, __($key, [], $locale), "$key missing in $locale");
            }
        }
    }

    public function test_presets_appear_on_the_staff_booking_form(): void
    {
        $role     = Role::where('name', 'receptionist')->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'hospital']);
        $user     = User::factory()->create(['role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active']);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        $this->actingAs($user)->withSession(['active_facility_id' => $facility->id])
            ->get(route('portals.staff.appointments.create'))
            ->assertOk()
            ->assertSee('value="antenatal"', false)
            ->assertSee('value="postnatal"', false)
            ->assertSee(__('public.appt_type_postnatal'));
    }

    public function test_presets_appear_on_the_patient_booking_form_and_are_accepted(): void
    {
        $patient = Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $user = User::factory()->create([
            'status'     => 'active',
            'patient_id' => $patient->id,
            'role_id'    => Role::firstOrCreate(['name' => 'patient'], ['display_name' => 'Patient'])->id,
        ]);

        $this->actingAs($user)->withSession(['mfa.verified' => true])
            ->get(route('portals.patient.appointments.book'))
            ->assertOk()
            ->assertSee('value="antenatal"', false)
            ->assertSee('value="postnatal"', false);

        $facility = Facility::factory()->create(['type' => 'hospital']);

        $this->actingAs($user)->withSession(['mfa.verified' => true])
            ->post(route('portals.patient.appointments.book.store'), [
                'facility_id'      => $facility->id,
                'appointment_type' => 'postnatal',
                'scheduled_at'     => now()->addDays(3)->format('Y-m-d H:i:s'),
            ])
            ->assertSessionDoesntHaveErrors('appointment_type');
    }
}
