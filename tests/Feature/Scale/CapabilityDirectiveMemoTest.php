<?php

namespace Tests\Feature\Scale;

use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `@capability` inside a row loop must not cost queries per row: the role is
 * resolved once per request per (user, global role, facility). Switching the
 * active facility mid-request must still resolve the role held THERE.
 */
class CapabilityDirectiveMemoTest extends TestCase
{
    use RefreshDatabase;

    private const LOOP = "@foreach(\$rows as \$r)@capability('prescribe') Y @else N @endcapability @endforeach";

    private function postedUser(string $roleAtFacility, string $globalRole): array
    {
        $global   = Role::firstOrCreate(['name' => $globalRole], ['display_name' => $globalRole]);
        $local    = Role::firstOrCreate(['name' => $roleAtFacility], ['display_name' => $roleAtFacility]);
        $facility = Facility::factory()->create(['type' => 'hospital']);

        $user = User::factory()->create([
            'role_id'             => $global->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $local->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function queriesToRender(int $rows): array
    {
        app('request')->attributes->remove('_staff_capabilities_roles');
        DB::flushQueryLog();
        DB::enableQueryLog();
        $html = Blade::render(self::LOOP, ['rows' => range(1, $rows)]);
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$count, $html];
    }

    public function test_capability_in_a_loop_costs_constant_queries(): void
    {
        [$user, $facility] = $this->postedUser('doctor', 'nurse');
        Auth::login($user);
        session(['active_facility_id' => $facility->id]);

        [$few, $html3]   = $this->queriesToRender(3);
        [$many, $html30] = $this->queriesToRender(30);

        $this->assertSame($few, $many, "@capability cost grows with rows: {$few} for 3, {$many} for 30");
        $this->assertLessThanOrEqual(2, $many);
        // Resolved at the facility (doctor), not the global role (nurse).
        $this->assertSame(30, substr_count($html30, 'Y'));
        $this->assertStringNotContainsString('N', $html3);
    }

    public function test_switching_facility_mid_request_resolves_the_role_there(): void
    {
        [$user, $hospitalA] = $this->postedUser('doctor', 'nurse');
        $hospitalB = Facility::factory()->create(['type' => 'hospital']);
        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $hospitalB->id,
            'role_id'     => Role::where('name', 'nurse')->value('id'),
            'is_active'   => true,
            'assigned_at' => now(),
        ]);
        Auth::login($user);

        session(['active_facility_id' => $hospitalA->id]);
        $atA = Blade::render("@capability('prescribe') Y @else N @endcapability");
        session(['active_facility_id' => $hospitalB->id]);
        $atB = Blade::render("@capability('prescribe') Y @else N @endcapability");
        session(['active_facility_id' => $hospitalA->id]);
        $backAtA = Blade::render("@capability('prescribe') Y @else N @endcapability");

        $this->assertSame('Y', trim($atA));
        $this->assertSame('N', trim($atB));
        $this->assertSame('Y', trim($backAtA));
    }
}
