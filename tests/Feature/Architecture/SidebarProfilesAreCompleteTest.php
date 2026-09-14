<?php

namespace Tests\Feature\Architecture;

use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Every role must render a sidebar that exists.
 *
 * `layouts/portal.blade.php` includes the sidebar with `@includeIf`, so a role
 * whose `dashboard_profile_key` has no partial does not 500 — it renders a
 * BLANK sidebar and the user is left on a dashboard with no navigation, and
 * nothing anywhere reports it. Silent is the failure mode this test exists
 * to close.
 *
 * The reverse is checked too: a partial no profile points at is either an
 * orphan that should go, or a profile row that was never seeded. Both are
 * listed by name so the fix is obvious.
 */
class SidebarProfilesAreCompleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    /** @return string[] partial keys on disk, excluding `_`-prefixed shared includes */
    private function partialsOnDisk(): array
    {
        $keys = [];

        foreach (glob(resource_path('views/partials/sidebars/*.blade.php')) as $file) {
            $key = basename($file, '.blade.php');

            if (! str_starts_with($key, '_')) {
                $keys[] = $key;
            }
        }

        sort($keys);

        return $keys;
    }

    public function test_every_role_has_a_sidebar_partial(): void
    {
        $missing = DB::table('roles')
            ->whereNotNull('dashboard_profile_key')
            ->distinct()
            ->pluck('dashboard_profile_key')
            ->reject(fn ($key) => in_array($key, $this->partialsOnDisk(), true))
            ->values()
            ->all();

        $this->assertSame([], $missing,
            'These dashboard_profile_key values have no sidebar partial and would render a blank sidebar: '
            . implode(', ', $missing));
    }

    /*
     * Deliberately NOT asserted: that every seeded dashboard_profiles row has
     * a partial. On first run that flagged sixteen (appointment_coordinator,
     * queue_manager, cashier, billing_officer, finance_manager,
     * department_manager, public_health_officer, public_health_admin,
     * disease_surveillance, support_agent, support_manager,
     * implementation_lead, data_import, partner_admin, partner_review,
     * country_regional_admin) — none of which any role points at. They are
     * unused seed rows, not blank dashboards. The guard that matters is the
     * one above: a ROLE whose key has no partial. Sixteen sidebars nobody can
     * reach would be sixteen more things to keep correct for no one.
     */

    public function test_every_sidebar_partial_belongs_to_a_seeded_profile(): void
    {
        // A partial nobody can ever be routed to is dead weight that still has
        // to be kept correct. `demo_access` is seeded as a profile with no
        // role — that is a deliberate demo affordance, and it passes because
        // the profile row exists.
        $profiles = DB::table('dashboard_profiles')->pluck('key')->all();

        $orphans = array_values(array_filter(
            $this->partialsOnDisk(),
            fn ($key) => ! in_array($key, $profiles, true),
        ));

        $this->assertSame([], $orphans,
            'These sidebar partials are pointed at by no dashboard profile: ' . implode(', ', $orphans));
    }
}
