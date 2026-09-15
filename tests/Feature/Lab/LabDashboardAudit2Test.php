<?php

namespace Tests\Feature\Lab;

use App\Models\CareFacility;
use App\Models\CriticalValueAlert;
use App\Models\Facility;
use App\Models\FacilityRoleAssignment;
use App\Models\LabOrder;
use App\Models\LabTestAvailability;
use App\Models\Patient;
use App\Models\ReferenceRange;
use App\Models\Role;
use App\Models\User;
use App\Services\Lab\LabManagerDeskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * docs/audits/LABORATORY_DASHBOARD_AUDIT_2.md fixes:
 *
 *   1. A LAB WITH NO WORK says so — no wall of zeros that reads like a quiet day.
 *   2. THE MANAGER SEES WHAT THE PUBLIC FINDER SHOWS — published count and
 *      last-published read from the projection; an unlinked lab is told why
 *      nothing is published.
 *   3. A CRITICAL VALUE CAN BE CLOSED — the lab records the call-back to a named
 *      person, once; another facility's alert does not exist; a collection-only
 *      role is refused.
 *   4. THE PUBLIC LAB-TEST FINDER PAGE shows what laboratories published, with
 *      whole-franc prices, and never a seeded row.
 */
class LabDashboardAudit2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function staff(string $roleName = 'lab_manager', ?Facility $facility = null): array
    {
        $role     = Role::where('name', $roleName)->firstOrFail();
        $facility ??= Facility::factory()->create(['type' => 'laboratory']);

        $user = User::factory()->create([
            'role_id'             => $role->id,
            'primary_facility_id' => $facility->id,
            'status'              => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id'     => $user->id,
            'facility_id' => $facility->id,
            'role_id'     => $role->id,
            'is_active'   => true,
            'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function as(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function listing(?Facility $facility, string $name = 'Laboratoire du Centre'): CareFacility
    {
        return CareFacility::create([
            'facility_id'    => $facility?->id,
            'facility_name'  => $name,
            'facility_type'  => 'laboratory',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Littoral',
            'city'           => 'Douala',
            'address'        => 'Rue Joffre, Akwa',
            'latitude'       => 4.0511,
            'longitude'      => 9.7679,
            'phone_primary'  => '+237600000077',
        ]);
    }

    private function published(CareFacility $listing, string $code, string $name, array $overrides = []): LabTestAvailability
    {
        return LabTestAvailability::create(array_merge([
            'facility_id'         => $listing->id,
            'loinc_code'          => $code,
            'test_name'           => $name,
            'price_xaf'           => 2500,
            'price'               => 2500,
            'currency'            => 'XAF',
            'turnaround_time'     => '24h',
            'availability_status' => 'available',
            'freshness_status'    => 'fresh',
            'source_system'       => 'portal',
            'last_updated_at'     => now(),
            'last_published_at'   => now()->subHour(),
        ], $overrides));
    }

    /** A critical potassium raised through the bench at $facility. */
    private function criticalAlert(Facility $facility): CriticalValueAlert
    {
        ReferenceRange::firstOrCreate(['test_code' => 'K', 'age_group' => 'all', 'sex' => 'all'], [
            'test_name' => 'Potassium', 'loinc_code' => null, 'unit' => 'mmol/L',
            'normal_low' => 3.5, 'normal_high' => 5.1, 'critical_low' => 2.5, 'critical_high' => 6.5,
            'is_active' => true,
        ]);

        [$tech] = $this->staff('labtech', $facility);

        $order = LabOrder::factory()->create([
            'facility_id'  => $facility->id,
            'patient_id'   => Patient::factory()->create(['sex' => 'male', 'date_of_birth' => '1980-05-05'])->id,
            'test_name'    => 'Potassium',
            'test_code'    => 'K',
            'status'       => 'collected',
            'collected_at' => now(),
            'resulted_at'  => null,
        ]);

        $this->as($tech, $facility)
            ->post('/facility/lab/orders/' . $order->id . '/results', ['value' => '7.4', 'unit' => 'mmol/L'])
            ->assertRedirect();

        return CriticalValueAlert::query()
            ->whereIn('lab_result_id', $order->results()->pluck('id'))
            ->firstOrFail();
    }

    // ── 1 + 2. First run and publication ────────────────────────────────────

    public function test_a_lab_with_no_work_and_no_listing_is_told_so_rather_than_shown_a_quiet_day(): void
    {
        [$manager, $facility] = $this->staff();

        $this->as($manager, $facility)->get('/facility/lab/dashboard')
            ->assertOk()
            ->assertViewHas('hasAnyWork', false)
            ->assertViewHas('publication', fn ($p) => $p['linked'] === false && $p['published'] === 0 && $p['last_published_at'] === null)
            ->assertSee(__('lab_manager.first_run_title'))
            ->assertSee(__('lab_manager.pub_unlinked'))
            ->assertSee(route('public.network.lab-test-finder'), false);
    }

    public function test_the_manager_sees_the_published_count_and_last_publication_from_the_projection(): void
    {
        [$manager, $facility] = $this->staff();
        $listing = $this->listing($facility);

        $this->published($listing, '718-7', 'Hémoglobine', ['last_published_at' => now()->subDays(2)]);
        $latest = $this->published($listing, '2345-7', 'Glycémie', ['last_published_at' => now()->subMinutes(5)]);
        $this->published($listing, '2160-0', 'Créatinine', ['availability_status' => 'unavailable']);
        // A seeded row is not this lab's publication.
        $this->published($listing, '777-3', 'Plaquettes', ['source_system' => 'demo_seed']);

        LabOrder::factory()->create(['facility_id' => $facility->id, 'status' => 'ordered']);

        $this->as($manager, $facility)->get('/facility/lab/dashboard')
            ->assertOk()
            ->assertViewHas('hasAnyWork', true)
            ->assertViewHas('publication', function ($p) use ($latest) {
                return $p['linked'] === true
                    && $p['published'] === 2
                    && $p['listing_name'] === 'Laboratoire du Centre'
                    && $p['last_published_at']->format('Y-m-d H:i') === $latest->last_published_at->format('Y-m-d H:i');
            })
            ->assertDontSee(__('lab_manager.first_run_title'))
            ->assertDontSee(__('lab_manager.pub_unlinked'));
    }

    // ── 3. Critical call-back ───────────────────────────────────────────────

    public function test_the_lab_records_a_critical_call_back_once_and_the_alert_leaves_the_board(): void
    {
        [$manager, $facility] = $this->staff();
        $alert = $this->criticalAlert($facility);

        $this->as($manager, $facility)->get('/facility/lab/dashboard')
            ->assertViewHas('critical_open', 1)
            ->assertSee(route('portals.lab_manager.critical_callback', $alert->id), false);

        // A name is required.
        $this->as($manager, $facility)
            ->post('/facility/lab/alerts/' . $alert->id . '/callback', ['informed' => ''])
            ->assertSessionHasErrors('informed');
        $this->assertFalse($alert->fresh()->acknowledged);

        $this->as($manager, $facility)
            ->post('/facility/lab/alerts/' . $alert->id . '/callback', ['informed' => 'Dr Mbarga, prescriber', 'note' => 'Read back 7.4'])
            ->assertRedirect(route('portals.lab_manager.dashboard'))
            ->assertSessionHas('success');

        $closed = $alert->fresh();
        $this->assertTrue($closed->acknowledged);
        $this->assertSame((string) $manager->id, (string) $closed->acknowledged_by);
        $this->assertStringStartsWith(LabManagerDeskService::CALLBACK_TAG . ' Dr Mbarga, prescriber', $closed->acknowledgement_note);
        // The clinical statement is untouched.
        $this->assertSame($alert->result_value, $closed->result_value);

        // Never re-stamped.
        $stamp = $closed->acknowledged_at;
        $this->as($manager, $facility)
            ->post('/facility/lab/alerts/' . $alert->id . '/callback', ['informed' => 'Someone else'])
            ->assertSessionHas('error');
        $this->assertEquals($stamp, $alert->fresh()->acknowledged_at);
        $this->assertStringContainsString('Dr Mbarga', $alert->fresh()->acknowledgement_note);

        $this->as($manager, $facility)->get('/facility/lab/dashboard')
            ->assertViewHas('critical_open', 0);
    }

    public function test_another_laboratorys_alert_does_not_exist_and_a_collection_role_is_refused(): void
    {
        [$manager, $facility] = $this->staff();
        [, $theirLab]         = $this->staff();
        $theirs = $this->criticalAlert($theirLab);

        $this->as($manager, $facility)
            ->post('/facility/lab/alerts/' . $theirs->id . '/callback', ['informed' => 'Dr X'])
            ->assertNotFound();

        $this->as($manager, $facility)
            ->post('/facility/lab/alerts/not-a-uuid/callback', ['informed' => 'Dr X'])
            ->assertNotFound();

        [$collector] = $this->staff('sample_collection', $theirLab);
        $this->as($collector, $theirLab)
            ->post('/facility/lab/alerts/' . $theirs->id . '/callback', ['informed' => 'Dr X'])
            ->assertForbidden();

        $this->assertFalse($theirs->fresh()->acknowledged);
    }

    // ── 4. Public lab-test finder page ──────────────────────────────────────

    public function test_the_public_lab_test_finder_shows_published_tests_with_whole_franc_prices_only(): void
    {
        $this->get('/network/lab-test-finder')
            ->assertOk()
            ->assertViewHas('results', null)
            ->assertSee(__('lab_manager.finder.search_label'));

        $listing = $this->listing(Facility::factory()->create(['type' => 'laboratory']), 'Laboratoire Akwa');
        $this->published($listing, '718-7', 'Hémoglobine', ['price_xaf' => 3500, 'price' => 3500]);

        $seeded = $this->listing(null, 'Labo Seed');
        $this->published($seeded, '718-7', 'Hémoglobine seed', ['source_system' => 'demo_seed']);

        $this->get('/network/lab-test-finder?q=' . urlencode('Hémoglobine'))
            ->assertOk()
            ->assertViewHas('results', fn ($r) => $r->count() === 1)
            ->assertSee('Laboratoire Akwa')
            ->assertSee('3 500 FCFA')
            ->assertDontSee('Labo Seed');

        $this->get('/network/lab-test-finder?q=zzzz-nothing')
            ->assertOk()
            ->assertSee(__('lab_manager.finder.empty_title'));

        // Garbage coordinates are discarded, never passed to the distance formula.
        $this->get('/network/lab-test-finder?q=718-7&lat=abc&lng=999')
            ->assertOk()
            ->assertViewHas('lat', null)
            ->assertViewHas('results', fn ($r) => $r->count() === 1);
    }

    public function test_the_public_finder_page_in_french_is_translated(): void
    {
        $this->withSession(['locale' => 'fr'])->get('/network/lab-test-finder')->assertOk();

        app()->setLocale('fr');
        $this->assertNotSame(__('lab_manager.finder.title', [], 'en'), __('lab_manager.finder.title', [], 'fr'));
    }
}
