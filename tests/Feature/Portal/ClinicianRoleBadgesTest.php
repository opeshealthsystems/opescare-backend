<?php

namespace Tests\Feature\Portal;

use App\Models\Role;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Which dashboard profile — and therefore which sidebar badge — each doctor
 * role gets.
 *
 * `resident_doctor` began life as a bucket for "resident, visiting, or
 * multi-facility doctor", which meant a consultant covering three hospitals and
 * a visiting specialist were both badged "Resident Doctor": a TRAINEE label on
 * two kinds of senior clinician, and one that contradicted the sidebar's own
 * brand-sub and footer, which render `roles.description` ("Multi-Facility
 * Doctor", "Visiting Doctor"). multi_doctor was repointed at `doctor` first;
 * visiting_doctor followed. This test is here so there is no third time.
 *
 * It also pins the invariant that made repointing safe rather than clever:
 * `layouts/portal.blade.php` renders the sidebar with `@includeIf`, so a
 * dashboard profile whose partial does not exist renders an EMPTY sidebar —
 * silently, with no error anywhere. Every profile a role can hold must have a
 * partial.
 */
class ClinicianRoleBadgesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function doctorRoles(): array
    {
        return [
            'general doctor'       => ['doctor', 'doctor'],
            // Working at several facilities says nothing about seniority.
            'multi-facility'       => ['multi_doctor', 'doctor'],
            // Neither does being invited in for sessions.
            'visiting'             => ['visiting_doctor', 'doctor'],
            // Specialisation does, and has its own profile.
            'specialist'           => ['specialist', 'specialist_doctor'],
            'consultant'           => ['consultant', 'specialist_doctor'],
            // The one role that really is in training.
            'resident'             => ['resident', 'resident_doctor'],
        ];
    }

    #[DataProvider('doctorRoles')]
    public function test_doctor_roles_map_to_the_profile_that_describes_them(
        string $role,
        string $expectedProfile,
    ): void {
        $this->assertSame(
            $expectedProfile,
            Role::where('name', $role)->sole()->dashboard_profile_key,
            "Role '{$role}' no longer renders the '{$expectedProfile}' dashboard."
        );
    }

    public function test_only_the_resident_role_is_badged_resident_doctor(): void
    {
        $this->assertSame(
            ['resident'],
            Role::where('dashboard_profile_key', 'resident_doctor')
                ->orderBy('name')
                ->pluck('name')
                ->all(),
            'A role that is not a doctor in training is being badged "Resident Doctor" again.'
        );
    }

    public function test_every_dashboard_profile_a_role_holds_has_a_sidebar_partial(): void
    {
        $missing = Role::query()
            ->whereNotNull('dashboard_profile_key')
            ->distinct()
            ->pluck('dashboard_profile_key')
            ->reject(fn (string $key) => view()->exists("partials.sidebars.{$key}"))
            ->values()
            ->all();

        $this->assertSame(
            [],
            $missing,
            'These dashboard profiles render an EMPTY sidebar (@includeIf finds no partial): '
                . implode(', ', $missing)
        );
    }
}
