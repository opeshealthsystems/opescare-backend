<?php

namespace Tests\Feature\DiagnosticCentre;

use App\Models\CareFacility;
use App\Models\ClinicalCodeDictionary;
use App\Models\Facility;
use App\Models\FacilityCatalogueItem;
use App\Models\FacilityRoleAssignment;
use App\Models\LabTestAvailability;
use App\Models\Role;
use App\Models\User;
use App\Services\DiagnosticCentre\ServiceMix;
use App\Services\Imaging\ImagingExamSearch;
use App\Services\Lab\LabCatalogueProjector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * ICHI imaging procedures reach the imaging finder (audit I-3), and only it:
 *
 *   1. An ACTIVE ICHI diagnostic-imaging procedure (action `BA`) added to the
 *      catalogue is published to the imaging finder with its price — and is
 *      NOT offered by the lab-test finder.
 *   2. An ICHI procedure that is NOT imaging (a biopsy) is published nowhere.
 *   3. An UNLINKED tenant publishes nothing.
 *   4. The catalogue page badges each row: lab finder / imaging finder /
 *      not on a finder.
 *   5. The service mix counts the ICHI exam on the imaging side.
 */
class ImagingCataloguePublishTest extends TestCase
{
    use RefreshDatabase;

    private const ICHI_XRAY   = 'JBF.BA.BA';   // Radiography of lung — diagnostic imaging
    private const ICHI_BIOPSY = 'AAN.AD.AA';   // Biopsy of peripheral nerve — not imaging

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function manager(): array
    {
        $role     = Role::where('name', 'lab_manager')->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'diagnostic_center']);

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

    private function actingAsManager(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function listing(Facility $facility): CareFacility
    {
        return CareFacility::create([
            'facility_id'    => $facility->id,
            'facility_name'  => 'Centre de Diagnostic du Littoral',
            'facility_type'  => 'diagnostic_center',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Littoral',
            'city'           => 'Douala',
            'address'        => 'Akwa',
            'latitude'       => 4.0511,
            'longitude'      => 9.7679,
            'phone_primary'  => '+237600000099',
        ]);
    }

    private function code(string $system, string $code, string $display, ?string $reason = null): void
    {
        ClinicalCodeDictionary::create([
            'id'            => (string) Str::uuid(),
            'system'        => $system,
            'code'          => $code,
            'display'       => $display,
            'kind'          => 'term',
            'common_reason' => $reason,
            'is_active'     => true,
            'source'        => 'test-fixture',
        ]);
    }

    private function dictionary(): void
    {
        $this->code('ICHI', self::ICHI_XRAY, 'Radiography of lung');
        $this->code('ICHI', self::ICHI_BIOPSY, 'Biopsy of peripheral nerve');
        $this->code('LOINC', '718-7', 'Hemoglobin [Mass/volume] in Blood', 'LOINC Universal Lab Orders');
    }

    private function labSearch(string $term): array
    {
        return $this->getJson('/api/v1/care-map/labs/test-search?test_name=' . urlencode($term))
            ->assertOk()
            ->json('data');
    }

    // ── 1. ICHI imaging → imaging finder, not the lab finder ────────────────

    public function test_an_ichi_imaging_procedure_publishes_to_the_imaging_finder_and_not_the_lab_finder(): void
    {
        [$manager, $facility] = $this->manager();
        $listing = $this->listing($facility);
        $this->dictionary();

        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue', [
                'system'     => 'ICHI',
                'code'       => self::ICHI_XRAY,
                'local_name' => 'Radiographie pulmonaire',
                'price_xaf'  => 8000,
            ])
            ->assertRedirect();

        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue', ['system' => 'LOINC', 'code' => '718-7', 'local_name' => 'Hémoglobine', 'price_xaf' => 2500])
            ->assertRedirect();

        $this->assertDatabaseHas('lab_test_availability', [
            'facility_id'         => $listing->id,
            'loinc_code'          => self::ICHI_XRAY,
            'test_name'           => 'Radiographie pulmonaire',
            'price_xaf'           => 8000,
            'availability_status' => LabTestAvailability::STATUS_AVAILABLE,
            'source_system'       => LabTestAvailability::SOURCE_PORTAL,
        ]);

        // Imaging finder: found by local name, by code and by the dictionary title.
        $search = app(ImagingExamSearch::class);
        foreach (['Radiographie', self::ICHI_XRAY, 'Radiography of lung'] as $term) {
            $hits = $search->search($term);
            $this->assertCount(1, $hits, "term {$term}");
            $this->assertSame(8000, (int) $hits->first()->matched_test->price_xaf);
        }
        $this->assertCount(0, $search->search('Hémoglobine'));

        // Lab finder: the haemoglobin test only — never the X-ray.
        $this->assertCount(1, $this->labSearch('Hémoglobine'));
        $this->assertCount(0, $this->labSearch('Radiographie'));
        $this->assertCount(0, $this->labSearch(self::ICHI_XRAY));

        // Retiring the exam marks the row unavailable; the finder drops it, the row stays.
        $item = FacilityCatalogueItem::where('facility_id', $facility->id)->where('code', self::ICHI_XRAY)->firstOrFail();
        $this->actingAsManager($manager, $facility)->post('/facility/catalogue/' . $item->id . '/retire')->assertRedirect();

        $this->assertDatabaseHas('lab_test_availability', [
            'loinc_code'          => self::ICHI_XRAY,
            'availability_status' => LabTestAvailability::STATUS_UNAVAILABLE,
        ]);
        $this->assertCount(0, $search->search('Radiographie'));
    }

    // ── 2. A non-imaging ICHI procedure reaches no finder ───────────────────

    public function test_a_non_imaging_ichi_procedure_is_published_nowhere(): void
    {
        [$manager, $facility] = $this->manager();
        $this->listing($facility);
        $this->dictionary();

        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue', ['system' => 'ICHI', 'code' => self::ICHI_BIOPSY, 'price_xaf' => 15000])
            ->assertRedirect();

        $this->assertDatabaseMissing('lab_test_availability', ['loinc_code' => self::ICHI_BIOPSY]);
        $this->assertCount(0, app(ImagingExamSearch::class)->search('Biopsy'));
        $this->assertFalse(ImagingExamSearch::isImagingProcedureCode(self::ICHI_BIOPSY));
        $this->assertTrue(ImagingExamSearch::isImagingProcedureCode(self::ICHI_XRAY));
        $this->assertNull(ServiceMix::finderFor('ICHI', null, self::ICHI_BIOPSY));
        $this->assertSame(ServiceMix::IMAGING, ServiceMix::finderFor('ICHI', null, self::ICHI_XRAY));
    }

    // ── 3. Unlinked tenant publishes nothing ────────────────────────────────

    public function test_an_unlinked_facility_publishes_nothing(): void
    {
        [$manager, $facility] = $this->manager();
        $this->dictionary();

        $this->actingAsManager($manager, $facility)
            ->post('/facility/catalogue', ['system' => 'ICHI', 'code' => self::ICHI_XRAY, 'price_xaf' => 8000])
            ->assertRedirect();

        $this->assertSame(1, FacilityCatalogueItem::where('facility_id', $facility->id)->count());
        $this->assertSame(0, LabTestAvailability::count());
        $this->assertSame(0, app(LabCatalogueProjector::class)->syncListing($facility));
        $this->assertCount(0, app(ImagingExamSearch::class)->search('Radiography'));
    }

    // ── 4 + 5. Badges and the service mix ───────────────────────────────────

    public function test_the_catalogue_badges_each_row_with_its_finder_and_the_mix_counts_imaging(): void
    {
        [$manager, $facility] = $this->manager();
        $this->listing($facility);
        $this->dictionary();

        foreach ([
            ['system' => 'ICHI', 'code' => self::ICHI_XRAY, 'price_xaf' => 8000],
            ['system' => 'ICHI', 'code' => self::ICHI_BIOPSY, 'price_xaf' => 15000],
            ['system' => 'LOINC', 'code' => '718-7', 'price_xaf' => 2500],
        ] as $row) {
            $this->actingAsManager($manager, $facility)->post('/facility/catalogue', $row)->assertRedirect();
        }

        $this->actingAsManager($manager, $facility)->get('/facility/catalogue')->assertOk()
            ->assertSee('data-finder="imaging"', false)
            ->assertSee('data-finder="lab"', false)
            ->assertSee('data-finder="none"', false)
            ->assertSee(__('diagnostic_desk.badge_not_published'));

        $mix = app(ServiceMix::class)->forFacility($facility->id);
        $this->assertSame(1, $mix['lab_catalogue']);
        $this->assertSame(1, $mix['imaging_catalogue']);
        $this->assertSame(1, $mix['lab_published']);
        $this->assertSame(1, $mix['imaging_published']);

        // The centre dashboard reads the same split.
        $this->actingAsManager($manager, $facility)->get('/facility/diagnostic')->assertOk()
            ->assertViewHas('publication', fn ($p) => $p['linked'] && $p['lab_published'] === 1 && $p['imaging_published'] === 1
                && $p['imaging_last_published_at'] !== null);
    }
}
