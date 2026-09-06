<?php

namespace Tests\Feature\Mobile;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoFrontDeskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * DemoFrontDeskSeeder fills today's book at the demo receptionist's facility,
 * touches demo rows only, and does the same thing every time it runs.
 */
class FrontDeskSeederTest extends TestCase
{
    use RefreshDatabase;

    private Facility $hospital;

    /** @var array<string, User> */
    private array $staff = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->hospital = Facility::factory()->create(['name' => 'Hôpital Central de Yaoundé', 'is_demo' => true]);

        foreach (['receptionist', 'doctor', 'nurse', 'specialist', 'patient'] as $role) {
            Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        }

        foreach ([
            DemoFrontDeskSeeder::RECEPTIONIST_EMAIL => 'receptionist',
            'demo.doctor@opescare.test'             => 'doctor',
            'demo.nurse@opescare.test'              => 'nurse',
            'demo.specialist@opescare.test'         => 'specialist',
        ] as $email => $role) {
            $this->staff[$email] = User::factory()->create([
                'email'   => $email,
                'is_demo' => true,
                'role_id' => Role::where('name', $role)->value('id'),
                // Posted elsewhere on purpose: the seeder must post them at the desk's facility.
                'primary_facility_id' => null,
            ]);
        }

        FacilityRoleAssignment::create([
            'user_id'     => $this->staff[DemoFrontDeskSeeder::RECEPTIONIST_EMAIL]->id,
            'facility_id' => $this->hospital->id,
            'role_id'     => Role::where('name', 'receptionist')->value('id'),
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        foreach (['demo.reviewer', 'demo.patient', 'demo.boy', 'demo.girl', 'demo.grandmother'] as $handle) {
            Patient::factory()->create(['email' => "{$handle}@opescare.test", 'is_demo' => true]);
        }
    }

    private function runSeeder(): void
    {
        $this->seed(DemoFrontDeskSeeder::class);
    }

    public function test_it_fills_todays_book_at_the_receptionists_facility(): void
    {
        $this->runSeeder();

        $rows = DB::table('appointments')->orderBy('scheduled_at')->get();
        $this->assertCount(5, $rows);

        $today = now(config('app.timezone'))->toDateString();
        foreach ($rows as $row) {
            $this->assertSame($this->hospital->id, $row->facility_id);
            $this->assertSame($today, \Illuminate\Support\Carbon::parse($row->scheduled_at)->timezone(config('app.timezone'))->toDateString());
            $this->assertSame('staff', $row->booked_by_type);
            $this->assertSame($this->staff[DemoFrontDeskSeeder::RECEPTIONIST_EMAIL]->id, $row->booked_by_id);
        }

        $this->assertEqualsCanonicalizing(['no_show', 'confirmed', 'checked_in', 'scheduled', 'scheduled'], $rows->pluck('status')->all());
        $this->assertSame(1, $rows->whereNull('provider_id')->count(), 'one appointment is waiting to be routed');

        // Every named clinician is now posted at the facility and holds a row.
        foreach (['demo.doctor@opescare.test', 'demo.nurse@opescare.test', 'demo.specialist@opescare.test'] as $email) {
            $this->assertDatabaseHas('facility_role_assignments', [
                'user_id' => $this->staff[$email]->id, 'facility_id' => $this->hospital->id, 'is_active' => true,
            ]);
            $this->assertTrue($rows->contains('provider_id', $this->staff[$email]->id), "{$email} has an appointment");
        }

        // The checked-in row has its visit open.
        $checkedIn = $rows->firstWhere('status', 'checked_in');
        $this->assertNotNull($checkedIn->visit_id);
        $this->assertNotNull($checkedIn->checked_in_at);
        $this->assertDatabaseHas('visits', ['id' => $checkedIn->visit_id, 'status' => 'open', 'is_demo' => true, 'patient_id' => $checkedIn->patient_id]);

        $this->assertNotNull($rows->firstWhere('status', 'no_show')->no_show_at);

        // Ledger rows: one "booked" per appointment plus one step for each non-scheduled row.
        $this->assertSame(5 + 3, DB::table('appointment_status_histories')->count());
    }

    public function test_running_it_again_changes_nothing_but_the_dates(): void
    {
        $this->runSeeder();
        $ids = DB::table('appointments')->orderBy('id')->pluck('id')->all();

        $this->travel(3)->days();
        $this->runSeeder();

        $this->assertSame($ids, DB::table('appointments')->orderBy('id')->pluck('id')->all());
        $this->assertSame(5, DB::table('appointments')->count());
        $this->assertSame(8, DB::table('appointment_status_histories')->count());
        $this->assertSame(1, DB::table('visits')->count());
        $this->assertSame(3, FacilityRoleAssignment::whereIn('user_id', collect($this->staff)->except(DemoFrontDeskSeeder::RECEPTIONIST_EMAIL)->pluck('id'))->count());

        // Moved onto the new "today".
        $today = now(config('app.timezone'))->toDateString();
        foreach (DB::table('appointments')->get() as $row) {
            $this->assertSame($today, \Illuminate\Support\Carbon::parse($row->scheduled_at)->timezone(config('app.timezone'))->toDateString());
        }
    }

    public function test_it_skips_a_patient_the_estate_does_not_have_and_refuses_a_real_one(): void
    {
        Patient::withoutGlobalScopes()->where('email', 'demo.grandmother@opescare.test')->delete();
        Patient::withoutGlobalScopes()->where('email', 'demo.girl@opescare.test')->update(['is_demo' => false]);

        $this->runSeeder();

        $this->assertSame(3, DB::table('appointments')->count());
        $real = Patient::withoutGlobalScopes()->where('email', 'demo.girl@opescare.test')->first();
        $this->assertDatabaseMissing('appointments', ['patient_id' => $real->id]);
    }

    public function test_it_seeds_nothing_without_a_demo_receptionist(): void
    {
        User::withoutGlobalScopes()->where('email', DemoFrontDeskSeeder::RECEPTIONIST_EMAIL)->update(['is_demo' => false]);

        $this->runSeeder();

        $this->assertSame(0, DB::table('appointments')->count());
        $this->assertSame(0, FacilityRoleAssignment::where('facility_id', $this->hospital->id)->where('user_id', $this->staff['demo.doctor@opescare.test']->id)->count());
    }
}
