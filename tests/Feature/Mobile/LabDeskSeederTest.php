<?php

namespace Tests\Feature\Mobile;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabTestRequest;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoLabDeskSeeder;
use Database\Seeders\Support\DemoCareTeam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * DemoLabDeskSeeder fills the lab tech's worklist once, at the facility their
 * session starts at, and a re-run writes nothing twice.
 */
class LabDeskSeederTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;

    private User $labTech;

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create(['name' => 'Hôpital Test', 'type' => 'hospital']);

        foreach (['labtech', 'doctor'] as $role) {
            DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
                'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)])
                ->forceFill(['dashboard_profile_key' => $role])->save();
        }

        $this->labTech = User::factory()->create([
            'name'                => 'Lab Tech Boubacar Keïta',
            'email'               => DemoCareTeam::LAB_TECH['email'],
            'is_demo'             => true,
            'role_id'             => Role::where('name', 'labtech')->value('id'),
            'primary_facility_id' => $this->facility->id,
        ]);

        User::factory()->create([
            'name'                => 'Dr. Amara Diallo',
            'email'               => DemoCareTeam::DOCTOR['email'],
            'is_demo'             => true,
            'role_id'             => Role::where('name', 'doctor')->value('id'),
            'primary_facility_id' => $this->facility->id,
        ]);

        foreach ([
            [DemoLabDeskSeeder::REVIEWER_EMAIL, 'Demo', 'Reviewer', 'female', '1988-02-10'],
            [DemoLabDeskSeeder::GIRL_EMAIL, 'Demo', 'Girl', 'female', '2013-11-05'],
            [DemoLabDeskSeeder::PATIENT_EMAIL, 'Demo Patient', 'One', 'female', '1992-04-14'],
        ] as [$email, $first, $last, $sex, $dob]) {
            Patient::factory()->create([
                'email'         => $email,
                'first_name'    => $first,
                'last_name'     => $last,
                'sex'           => $sex,
                'date_of_birth' => $dob,
                'is_demo'       => true,
            ]);
        }

        DemoCareTeam::flush();
    }

    public function test_it_seeds_the_worklist_once_at_the_lab_techs_facility(): void
    {
        (new DemoLabDeskSeeder())->run();

        $orders = LabOrder::where('demo_seed_key', 'like', DemoLabDeskSeeder::KEY . ':%')->get();
        $this->assertCount(3, $orders);
        $this->assertTrue($orders->every(fn (LabOrder $o) => $o->facility_id === $this->facility->id && $o->is_demo));

        $statuses = $orders->pluck('status')->sort()->values()->all();
        $this->assertSame(['collected', 'in_progress', 'ordered'], $statuses);
        $this->assertSame('urgent', $orders->firstWhere('status', 'collected')->urgency);
        $this->assertNotNull($orders->firstWhere('status', 'collected')->collected_at);
        $this->assertNull($orders->firstWhere('status', 'ordered')->collected_at);

        $inProgress = $orders->firstWhere('status', 'in_progress');
        $girl       = Patient::withoutGlobalScopes()->where('email', DemoLabDeskSeeder::GIRL_EMAIL)->first();
        $this->assertSame($girl->id, $inProgress->patient_id);
        $this->assertSame(2, LabResult::where('lab_order_id', $inProgress->id)->count());
        $this->assertSame('L', LabResult::where('lab_order_id', $inProgress->id)->where('parameter_name', 'Haemoglobin')->value('flag'));

        $requests = LabTestRequest::where('demo_seed_key', 'like', DemoLabDeskSeeder::KEY . ':%')->get();
        $this->assertCount(1, $requests);
        $this->assertSame('submitted', $requests[0]->status);
        $this->assertSame($this->facility->id, $requests[0]->facility_id);
        $this->assertTrue($requests[0]->is_demo);

        // Ordered by the demo doctor, so a release lands in their inbox.
        $doctorId = User::withoutGlobalScopes()->where('email', DemoCareTeam::DOCTOR['email'])->value('id');
        $this->assertTrue($orders->every(fn (LabOrder $o) => $o->ordered_by === $doctorId));

        // The lab tech is posted where the orders live.
        $this->assertTrue(
            FacilityRoleAssignment::where('user_id', $this->labTech->id)->where('facility_id', $this->facility->id)->active()->exists()
        );
    }

    public function test_a_rerun_writes_nothing_twice_and_leaves_worked_rows_alone(): void
    {
        (new DemoLabDeskSeeder())->run();

        // The reviewer has since collected the FBC sample.
        $fbc = LabOrder::where('demo_seed_key', DemoLabDeskSeeder::KEY . ':reviewer:fbc')->first();
        $fbc->forceFill(['status' => 'collected', 'collected_at' => now()])->save();

        (new DemoLabDeskSeeder())->run();
        (new DemoLabDeskSeeder())->run();

        $this->assertSame(3, LabOrder::where('demo_seed_key', 'like', DemoLabDeskSeeder::KEY . ':%')->count());
        $this->assertSame(2, LabResult::where('demo_seed_key', 'like', DemoLabDeskSeeder::KEY . ':%')->count());
        $this->assertSame(1, LabTestRequest::where('demo_seed_key', 'like', DemoLabDeskSeeder::KEY . ':%')->count());
        $this->assertSame(1, FacilityRoleAssignment::where('user_id', $this->labTech->id)->where('facility_id', $this->facility->id)->count());

        $this->assertSame('collected', $fbc->fresh()->status);
    }

    public function test_it_seeds_nothing_when_the_lab_tech_is_not_on_the_estate(): void
    {
        DB::table('users')->where('id', $this->labTech->id)->delete();
        DemoCareTeam::flush();

        (new DemoLabDeskSeeder())->run();

        $this->assertSame(0, LabOrder::count());
        $this->assertSame(0, LabTestRequest::count());
    }
}
