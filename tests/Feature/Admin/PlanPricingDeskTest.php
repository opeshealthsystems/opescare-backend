<?php

namespace Tests\Feature\Admin;

use App\Models\AdminActionLog;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Modules\Admin\Services\PlatformAdminService;
use App\Services\Payments\Domain\Money;
use Database\Seeders\AccountCategoriesSeeder;
use Database\Seeders\DashboardProfilesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Plans & pricing — the desk that owns what OpesCare charges.
 *
 * Three things are pinned here, because all three are ways a pricing screen
 * goes quietly wrong:
 *
 *   1. A price change lands in the SAME admin_action_logs table the Control
 *      Center audit page reads, carrying who, when, and from what to what. An
 *      unaudited price change is an argument nobody can settle later.
 *   2. A bad amount is REJECTED, not rounded. `1500.75` francs is not a
 *      number of francs; silently storing 1500 (or 150075) is how a catalogue
 *      ends up off by a factor nobody can explain.
 *   3. A plan cannot acquire an interval it is not sold on. A hospital is
 *      billed yearly; typing a figure into its "per day" box must not put
 *      hospitals on a daily rate.
 *
 * Amounts throughout are WHOLE FRANCS — XAF has an ISO 4217 exponent of 0, so
 * 25 000 here is twenty-five thousand francs. See Money.
 */
class PlanPricingDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    private function platformAdmin(): User
    {
        $this->seed(AccountCategoriesSeeder::class);
        $this->seed(DashboardProfilesSeeder::class);
        $this->seed(RolesSeeder::class);

        $user = User::factory()->create(['status' => 'active', 'primary_facility_id' => null]);
        $user->role_id = Role::where('name', 'super_admin')->firstOrFail()->id;
        $user->save();

        return $user;
    }

    /** The patient plan: sold by the day, the month and the year. */
    private function patientPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name'                => 'Patient',
            'slug'                => 'patient',
            'audience'            => 'patient',
            'billing_cycle'       => 'daily',
            'price_minor'         => 50,
            'daily_price_minor'   => 50,
            'monthly_price_minor' => 1_500,
            'annual_price_minor'  => 18_250,
            'currency'            => 'XAF',
            'is_active'           => true,
            'is_public'           => true,
            'sort_order'          => 1,
        ]);
    }

    /** The hospital plan: billed yearly, and only yearly. */
    private function hospitalPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name'                => 'Hospital',
            'slug'                => 'hospital',
            'audience'            => 'facility',
            'billing_cycle'       => 'annual',
            'price_minor'         => 200_000,
            'daily_price_minor'   => null,
            'monthly_price_minor' => null,
            'annual_price_minor'  => 200_000,
            'currency'            => 'XAF',
            'is_active'           => true,
            'is_public'           => true,
            'sort_order'          => 5,
        ]);
    }

    // ─── 1. Wiring ──────────────────────────────────────────────────────────

    public function test_the_desk_has_routes(): void
    {
        $named = collect(Route::getRoutes())->map(fn ($r) => $r->getName())->filter()->all();

        foreach ([
            'portals.admin.plans.index',
            'portals.admin.plans.update',
            'portals.admin.plans.toggle',
        ] as $name) {
            $this->assertContains($name, $named, "route {$name} is not registered");
        }
    }

    public function test_the_desk_is_platform_gated(): void
    {
        $this->platformAdmin();                       // seeds roles
        $outsider = User::factory()->create(['status' => 'active']);
        $plan     = $this->patientPlan();

        $this->actingAs($outsider)->get(route('portals.admin.plans.index'))->assertForbidden();
        $this->actingAs($outsider)
            ->post(route('portals.admin.plans.update', $plan), ['prices' => ['daily' => '999']])
            ->assertForbidden();

        $this->assertSame(50, (int) $plan->fresh()->daily_price_minor);
    }

    public function test_the_page_is_reachable_from_the_admin_navigation(): void
    {
        // A page reachable only by URL is not wired. The admin nav is a shared
        // partial, so this is the one assertion that keeps the link honest.
        $admin = $this->platformAdmin();
        $this->patientPlan();

        $this->actingAs($admin)
            ->get(route('portals.admin.plans.index'))
            ->assertOk()
            ->assertSee(route('portals.admin.plans.index'))
            ->assertSee(__('plan_pricing.nav'));
    }

    public function test_the_listing_shows_prices_status_and_who_last_changed_them(): void
    {
        $admin = $this->platformAdmin();
        $plan  = $this->patientPlan();
        $this->hospitalPlan();

        // Nothing changed yet.
        $this->actingAs($admin)
            ->get(route('portals.admin.plans.index'))
            ->assertOk()
            ->assertSee(__('plan_pricing.last_change_never'))
            ->assertSee(__('plan_pricing.not_sold'));     // the hospital's daily cell

        $this->actingAs($admin)->post(route('portals.admin.plans.update', $plan), [
            'prices' => ['daily' => '60', 'monthly' => '1500', 'annual' => '18250'],
        ]);

        // The patient row now names the administrator who moved the price. The
        // hospital row still reads "never changed here", which is why this
        // asserts on the actor rather than on the absence of that phrase.
        $this->actingAs($admin)
            ->get(route('portals.admin.plans.index'))
            ->assertOk()
            ->assertSee($admin->name);
    }

    // ─── 2. A price change is audited ───────────────────────────────────────

    public function test_a_price_change_is_written_and_audited_with_who_when_and_from_what_to_what(): void
    {
        $admin = $this->platformAdmin();
        $plan  = $this->patientPlan();

        $this->actingAs($admin)
            ->post(route('portals.admin.plans.update', $plan), [
                'prices' => ['daily' => '75', 'monthly' => '2250', 'annual' => '27375'],
            ])
            ->assertRedirect(route('portals.admin.plans.index'))
            ->assertSessionHas('success');

        $plan->refresh();

        // Whole francs. Seventy-five francs a day, not seventy-five hundredths.
        $this->assertSame(75, (int) $plan->daily_price_minor);
        $this->assertSame(2_250, (int) $plan->monthly_price_minor);
        $this->assertSame(27_375, (int) $plan->annual_price_minor);

        // The legacy single-price column follows the default interval, so
        // anything still reading it agrees with what the subscriber is shown.
        $this->assertSame('daily', $plan->defaultInterval());
        $this->assertSame(75, (int) $plan->price_minor);
        $this->assertSame('75 FCFA', str_replace("\u{202F}", ' ', $plan->priceFormatted()));

        $log = AdminActionLog::where('resource_type', PlatformAdminService::PLAN_RESOURCE)
            ->where('resource_id', $plan->id)
            ->orderByDesc('occurred_at')
            ->first();

        $this->assertNotNull($log, 'a price change wrote no audit row');
        $this->assertSame('plan_price_updated', $log->action);

        // WHO — the signed-in administrator's id, not a placeholder string.
        $this->assertSame((string) $admin->id, $log->actor_id);
        $this->assertTrue(\Illuminate\Support\Str::isUuid($log->actor_id));

        // WHEN.
        $this->assertNotNull($log->occurred_at);

        // FROM WHAT TO WHAT.
        $this->assertSame(50, $log->before['daily_price_minor']);
        $this->assertSame(1_500, $log->before['monthly_price_minor']);
        $this->assertSame(75, $log->after['daily_price_minor']);
        $this->assertSame(2_250, $log->after['monthly_price_minor']);
        $this->assertSame('patient', $log->after['slug']);
    }

    public function test_resubmitting_the_same_prices_writes_no_audit_row(): void
    {
        $admin = $this->platformAdmin();
        $plan  = $this->patientPlan();

        $this->actingAs($admin)->post(route('portals.admin.plans.update', $plan), [
            'prices' => ['daily' => '50', 'monthly' => '1500', 'annual' => '18250'],
        ])->assertSessionHas('success');

        $this->assertSame(0, AdminActionLog::where('resource_type', PlatformAdminService::PLAN_RESOURCE)->count());
    }

    public function test_deactivating_a_plan_is_audited_too(): void
    {
        $admin = $this->platformAdmin();
        $plan  = $this->patientPlan();

        $this->actingAs($admin)
            ->post(route('portals.admin.plans.toggle', $plan), ['active' => '0'])
            ->assertRedirect(route('portals.admin.plans.index'));

        $this->assertFalse((bool) $plan->fresh()->is_active);

        $log = AdminActionLog::where('resource_type', PlatformAdminService::PLAN_RESOURCE)->sole();

        $this->assertSame('plan_toggled', $log->action);
        $this->assertTrue($log->before['is_active']);
        $this->assertFalse($log->after['is_active']);
        $this->assertSame((string) $admin->id, $log->actor_id);
    }

    // ─── 3. A bad amount is refused, not rounded ────────────────────────────

    #[DataProvider('badAmounts')]
    public function test_an_amount_that_is_not_whole_francs_is_rejected_with_a_message(string $bad): void
    {
        $admin = $this->platformAdmin();
        $plan  = $this->patientPlan();

        $response = $this->actingAs($admin)
            ->from(route('portals.admin.plans.index'))
            ->post(route('portals.admin.plans.update', $plan), ['prices' => ['daily' => $bad]]);

        $response->assertRedirect(route('portals.admin.plans.index'));
        $response->assertSessionHasErrors();

        // Nothing was rounded, truncated or stored.
        $this->assertSame(50, (int) $plan->fresh()->daily_price_minor);
        $this->assertSame(0, AdminActionLog::where('resource_type', PlatformAdminService::PLAN_RESOURCE)->count());

        // And the message names the plan and the interval, not "prices.daily".
        $message = session('errors')->getBag('default')->first();
        $this->assertStringContainsString('Patient', $message);
        $this->assertStringContainsString(__('plan_pricing.interval_daily'), $message);
    }

    public static function badAmounts(): array
    {
        return [
            'a decimal'            => ['1500.75'],
            'a decimal, comma'     => ['1500,75'],
            'a negative'           => ['-20'],
            'thousands separators' => ['1 500'],
            'scientific notation'  => ['1e3'],
            'not a number at all'  => ['gratuit'],
        ];
    }

    public function test_an_amount_above_the_money_ceiling_is_rejected(): void
    {
        $admin = $this->platformAdmin();
        $plan  = $this->patientPlan();

        $this->actingAs($admin)
            ->from(route('portals.admin.plans.index'))
            ->post(route('portals.admin.plans.update', $plan), [
                'prices' => ['annual' => (string) (Money::MAX_MINOR + 1)],
            ])
            ->assertSessionHasErrors();

        $this->assertSame(18_250, (int) $plan->fresh()->annual_price_minor);

        // The ceiling itself is fine — the rejection is an upper bound, not an
        // off-by-one that quietly forbids the maximum.
        $this->actingAs($admin)
            ->post(route('portals.admin.plans.update', $plan), [
                'prices' => ['annual' => (string) Money::MAX_MINOR],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(Money::MAX_MINOR, (int) $plan->fresh()->annual_price_minor);
    }

    /**
     * Zero is refused — and this test exists to record WHY, because the reason
     * is a contradiction inside the model, not a decision of this screen.
     *
     * SubscriptionPlan::amountFor() states that "a zero price is a real answer
     * — a free plan — and must survive the null check below", then builds it
     * with Money::ofMinor(), which throws on `$minor <= 0`. So a stored 0 does
     * not render as free: every read of that plan throws, this listing
     * included. Until Money learns to hold a zero amount, the pricing screen
     * refuses to write one rather than persisting a row that breaks on the
     * next read.
     */
    public function test_a_zero_price_is_refused_because_the_domain_cannot_hold_one(): void
    {
        $admin = $this->platformAdmin();
        $plan  = $this->patientPlan();

        // The premise: Money really will not build a zero amount.
        $this->expectExceptionIsThrownBy(fn () => Money::ofMinor(0));

        $this->actingAs($admin)
            ->from(route('portals.admin.plans.index'))
            ->post(route('portals.admin.plans.update', $plan), ['prices' => ['daily' => '0']])
            ->assertSessionHasErrors();

        $this->assertSame(50, (int) $plan->fresh()->daily_price_minor);
        $this->assertSame(0, AdminActionLog::where('resource_type', PlatformAdminService::PLAN_RESOURCE)->count());

        // And the page it came from still opens.
        $this->actingAs($admin)->get(route('portals.admin.plans.index'))->assertOk();
    }

    private function expectExceptionIsThrownBy(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('expected the callback to throw, and it did not');
    }

    // ─── 4. The catalogue's shape is not editable from here ─────────────────

    public function test_a_plan_cannot_be_given_a_price_for_an_interval_it_does_not_offer(): void
    {
        $admin    = $this->platformAdmin();
        $hospital = $this->hospitalPlan();

        $this->actingAs($admin)
            ->from(route('portals.admin.plans.index'))
            ->post(route('portals.admin.plans.update', $hospital), [
                'prices' => ['daily' => '50', 'annual' => '200000'],
            ])
            ->assertSessionHasErrors();

        $hospital->refresh();

        $this->assertNull($hospital->daily_price_minor, 'a hospital acquired a daily rate');
        $this->assertSame(['annual'], $hospital->intervalsOffered());
        $this->assertSame(0, AdminActionLog::where('resource_type', PlatformAdminService::PLAN_RESOURCE)->count());
    }

    public function test_the_locked_intervals_are_not_rendered_as_inputs(): void
    {
        $admin = $this->platformAdmin();
        $this->hospitalPlan();

        $html = $this->actingAs($admin)->get(route('portals.admin.plans.index'))->getContent();

        $this->assertStringContainsString('name="prices[annual]"', $html);
        $this->assertStringNotContainsString('name="prices[daily]"', $html);
        $this->assertStringNotContainsString('name="prices[monthly]"', $html);
    }

    public function test_clearing_a_price_stops_selling_that_interval(): void
    {
        $admin = $this->platformAdmin();
        $plan  = $this->patientPlan();

        $this->actingAs($admin)
            ->post(route('portals.admin.plans.update', $plan), [
                'prices' => ['daily' => '', 'monthly' => '1500', 'annual' => '18250'],
            ])
            ->assertSessionHasNoErrors();

        $plan->refresh();

        $this->assertNull($plan->daily_price_minor);
        $this->assertSame(['monthly', 'annual'], $plan->intervalsOffered());

        // billing_cycle still says 'daily', which the plan no longer sells, so
        // the default falls through to the first interval it does — and the
        // legacy price column follows it rather than keeping a stale figure.
        $this->assertSame('monthly', $plan->defaultInterval());
        $this->assertSame(1_500, (int) $plan->price_minor);
    }

    public function test_a_plan_cannot_be_left_with_no_price_at_all(): void
    {
        $admin = $this->platformAdmin();
        $plan  = $this->patientPlan();

        $this->actingAs($admin)
            ->from(route('portals.admin.plans.index'))
            ->post(route('portals.admin.plans.update', $plan), [
                'prices' => ['daily' => '', 'monthly' => '', 'annual' => ''],
            ])
            ->assertSessionHasErrors();

        $plan->refresh();

        $this->assertSame(50, (int) $plan->daily_price_minor);
        $this->assertSame(['daily', 'monthly', 'annual'], $plan->intervalsOffered());
    }
}
