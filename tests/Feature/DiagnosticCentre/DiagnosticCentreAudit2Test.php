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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * docs/audits/DIAGNOSTIC_CENTRE_DASHBOARD_AUDIT_2.md fixes:
 *
 *  D-3  ONE LIST, TWO FINDERS: the catalogue says which public finder (lab
 *       test or imaging) each LOINC row reaches, and how many of each are
 *       published, decided by the dictionary only.
 *  D-4  TWO ROLES, ONE CATALOGUE: facility_admin and lab_manager both edit it;
 *       an edit form opened before the other role saved is refused instead of
 *       silently overwriting; forms without the marker still save.
 */
class DiagnosticCentreAudit2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    private function staff(string $roleName, Facility $facility): User
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id, 'primary_facility_id' => $facility->id, 'status' => 'active',
        ]);
        FacilityRoleAssignment::create([
            'user_id' => $user->id, 'facility_id' => $facility->id, 'role_id' => $role->id,
            'is_active' => true, 'assigned_at' => now(),
        ]);

        return $user;
    }

    private function as(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function code(string $system, string $code, string $display, ?string $reason): void
    {
        ClinicalCodeDictionary::create([
            'id' => (string) Str::uuid(), 'system' => $system, 'code' => $code, 'display' => $display,
            'kind' => 'term', 'common_reason' => $reason, 'is_active' => true, 'source' => 'test-fixture',
        ]);
    }

    private function item(Facility $f, string $system, string $code, ?int $price = 5000): FacilityCatalogueItem
    {
        return FacilityCatalogueItem::create([
            'facility_id' => $f->id, 'system' => $system, 'code' => $code,
            'price_xaf' => $price, 'is_active' => true,
        ]);
    }

    /** A diagnostic centre with one lab test, one imaging exam and one ICD row. */
    private function centre(): array
    {
        $tenant  = Facility::factory()->create(['type' => 'diagnostic_center']);
        $listing = CareFacility::create([
            'facility_id' => $tenant->id, 'facility_name' => 'Centre de Diagnostic Test',
            'facility_type' => 'diagnostic_center', 'listing_status' => 'active', 'country_code' => 'CM',
            'region' => 'Littoral', 'city' => 'Douala', 'address' => 'Akwa',
            'latitude' => 4.06, 'longitude' => 9.74, 'phone_primary' => '+237600000088',
        ]);

        $this->code('LOINC', '2345-7', 'Glucose [Mass/volume] in Serum or Plasma', 'Diabetes screening');
        $this->code('LOINC', '24558-9', 'US Abdomen', 'Imaging: Ultrasound');
        $this->code('ICD-10', 'E11', 'Type 2 diabetes mellitus', null);

        $lab     = $this->item($tenant, 'LOINC', '2345-7', 2000);
        $imaging = $this->item($tenant, 'LOINC', '24558-9', 15000);
        $this->item($tenant, 'ICD-10', 'E11', null);

        foreach ([['2345-7', 'Glucose', 2000], ['24558-9', 'Echographie abdominale', 15000]] as [$c, $n, $p]) {
            LabTestAvailability::create([
                'facility_id' => $listing->id, 'loinc_code' => $c, 'test_name' => $n,
                'price_xaf' => $p, 'price' => $p, 'currency' => 'XAF',
                'availability_status' => 'available', 'freshness_status' => 'fresh',
                'source_system' => 'portal', 'last_updated_at' => now(), 'last_published_at' => now(),
            ]);
        }

        return [$tenant, $listing, $lab, $imaging];
    }

    public function test_service_mix_splits_lab_and_imaging_from_the_dictionary_only(): void
    {
        [$tenant] = $this->centre();

        $mix = app(ServiceMix::class)->forFacility($tenant->id);

        $this->assertSame(
            ['lab_catalogue' => 1, 'imaging_catalogue' => 1, 'lab_published' => 1, 'imaging_published' => 1],
            $mix
        );

        $this->assertNull(ServiceMix::finderFor('ICD-10', null));
        $this->assertSame(ServiceMix::LAB, ServiceMix::finderFor('LOINC', null));
        $this->assertSame(ServiceMix::IMAGING, ServiceMix::finderFor('LOINC',
            ClinicalCodeDictionary::where('code', '24558-9')->first()));
    }

    public function test_both_roles_see_which_finder_each_row_reaches(): void
    {
        [$tenant] = $this->centre();

        foreach (['facility_admin', 'lab_manager'] as $role) {
            $user = $this->staff($role, $tenant);

            $this->as($user, $tenant)->get(route('portals.facility.catalogue'))
                ->assertOk()
                ->assertSee('data-testid="service-mix"', false)
                ->assertSee('data-finder="imaging"', false)
                ->assertSee('data-finder="lab"', false)
                ->assertSee(__('catalogue.mix_imaging', ['catalogue' => 1, 'published' => 1]));
        }
    }

    public function test_a_lab_without_imaging_exams_gets_no_mix_banner(): void
    {
        $lab = Facility::factory()->create(['type' => 'laboratory']);
        $this->code('LOINC', '2345-7', 'Glucose', 'Diabetes screening');
        $this->item($lab, 'LOINC', '2345-7');

        $this->as($this->staff('lab_manager', $lab), $lab)->get(route('portals.facility.catalogue'))
            ->assertOk()
            ->assertDontSee('data-testid="service-mix"', false)
            ->assertSee('data-finder="lab"', false);
    }

    public function test_a_stale_edit_from_the_other_role_does_not_overwrite_a_save(): void
    {
        [$tenant, , , $imaging] = $this->centre();
        $admin   = $this->staff('facility_admin', $tenant);
        $manager = $this->staff('lab_manager', $tenant);

        // Both forms were opened five minutes ago.
        $imaging->timestamps = false;
        $imaging->forceFill(['updated_at' => now()->subMinutes(5)])->save();
        $imaging->timestamps = true;
        $seen = (string) $imaging->fresh()->updated_at->getTimestamp();

        // The admin saves a new price first.
        $this->as($admin, $tenant)->post(route('portals.facility.catalogue.update', $imaging->id), [
            'price_xaf' => 18000, 'local_name' => 'Echo abdo', 'seen_updated_at' => $seen,
        ])->assertSessionHas('success');
        $this->assertSame(18000, $imaging->fresh()->price_xaf);

        // The manager's form predates that save: refused, nothing cleared.
        $this->as($manager, $tenant)->post(route('portals.facility.catalogue.update', $imaging->id), [
            'price_xaf' => 12000, 'seen_updated_at' => $seen,
        ])->assertSessionHas('error', __('catalogue.flash_stale_edit'));
        $fresh = $imaging->fresh();
        $this->assertSame(18000, $fresh->price_xaf);
        $this->assertSame('Echo abdo', $fresh->local_name);

        // With the current marker the manager's save goes through.
        $this->as($manager, $tenant)->post(route('portals.facility.catalogue.update', $imaging->id), [
            'price_xaf' => 12000, 'local_name' => 'Echo abdo',
            'seen_updated_at' => (string) $fresh->updated_at->getTimestamp(),
        ])->assertSessionHas('success');
        $this->assertSame(12000, $imaging->fresh()->price_xaf);

        // A client that never sends the marker keeps the old behaviour.
        $this->as($manager, $tenant)->post(route('portals.facility.catalogue.update', $imaging->id), [
            'price_xaf' => 11000,
        ])->assertSessionHas('success');
        $this->assertSame(11000, $imaging->fresh()->price_xaf);
    }

    public function test_another_centre_cannot_edit_this_catalogue(): void
    {
        [, , $lab] = $this->centre();
        $other = Facility::factory()->create(['type' => 'diagnostic_center']);

        $this->as($this->staff('lab_manager', $other), $other)
            ->post(route('portals.facility.catalogue.update', $lab->id), ['price_xaf' => 1])
            ->assertNotFound();
        $this->assertSame(2000, $lab->fresh()->price_xaf);
    }
}
