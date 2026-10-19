<?php

namespace Tests\Feature\Mobile;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Role;
use App\Models\User;
use App\Services\Portal\ProviderSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * GET /api/mobile/doctor/lab-catalogue — the orderable-test list behind the
 * clinician's "Order a lab test" sheet. Clinicians read it, one entry per
 * code, searchable from two characters; the pharmacy does not.
 */
class DoctorLabCatalogueTest extends TestCase
{
    use RefreshDatabase;

    private Facility $facility;
    private array $bearer = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->facility = Facility::factory()->create(['name' => 'Limbe District Hospital']);

        foreach (['doctor', 'pharmacist'] as $role) {
            $user = $this->professional($role);
            $this->bearer[$role] = app(ProviderSessionService::class)->mint($user, $this->facility->id, 'test');
        }

        $listing = CareFacility::create([
            'facility_id'    => $this->facility->id,
            'facility_name'  => 'Laboratoire Central (listing)',
            'facility_type'  => 'laboratory',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Centre',
            'city'           => 'Yaoundé',
            'address'        => 'Rue 1.234, Yaoundé',
            'phone_primary'  => '+237 6 90 00 00 11',
            'latitude'       => 3.8667,
            'longitude'      => 11.5167,
        ]);
        $second = CareFacility::create([
            'facility_id'    => $this->facility->id,
            'facility_name'  => 'Laboratoire du Nord (listing)',
            'facility_type'  => 'laboratory',
            'listing_status' => 'active',
            'country_code'   => 'CM',
            'region'         => 'Nord',
            'city'           => 'Garoua',
            'address'        => 'Quartier Plateau, Garoua',
            'phone_primary'  => '+237 6 90 00 00 12',
            'latitude'       => 9.3,
            'longitude'      => 13.4,
        ]);

        // The same test published by two laboratories collapses to one entry;
        // a withdrawn test never appears.
        foreach ([
            [$listing->id, 'Full Blood Count', 'FBC', '58410-2', 'Whole blood', 'available'],
            [$second->id,  'Full Blood Count', 'FBC', '58410-2', 'Whole blood', 'available'],
            [$listing->id, 'Serum Potassium',  'K',   '2823-3',  'Serum',       'available'],
            [$listing->id, 'Lipid Panel',      'LIPID', null,    'Serum',       'withdrawn'],
        ] as [$facilityId, $name, $code, $loinc, $specimen, $status]) {
            DB::table('lab_test_availability')->insert([
                'id'                  => (string) Str::uuid(),
                'facility_id'         => $facilityId,
                'test_name'           => $name,
                'local_test_code'     => $code,
                'loinc_code'          => $loinc,
                'specimen_type'       => $specimen,
                'availability_status' => $status,
                'source_system'       => 'portal',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
    }

    public function test_a_clinician_reads_one_entry_per_published_test(): void
    {
        $res = $this->catalogue('doctor')->assertOk();

        $codes = collect($res->json('data'))->pluck('code')->all();
        sort($codes);

        $this->assertSame(['FBC', 'K'], $codes, 'one entry per code; withdrawn tests are not offered');
        $this->assertSame('Full Blood Count', collect($res->json('data'))->firstWhere('code', 'FBC')['name']);
        $this->assertSame('Whole blood', collect($res->json('data'))->firstWhere('code', 'FBC')['specimen']);
    }

    public function test_rows_no_real_source_published_are_never_offered(): void
    {
        // A seeded row and an unclaimed (NULL) row: neither is on the
        // LabTestAvailability::LIVE_SOURCE_SYSTEMS allow-list.
        foreach (['seed' => 'Seeded Malaria RDT', null => 'Unclaimed HbA1c'] as $source => $name) {
            DB::table('lab_test_availability')->insert([
                'id'                  => (string) Str::uuid(),
                'facility_id'         => CareFacility::first()->id,
                'test_name'           => $name,
                'local_test_code'     => strtoupper(Str::random(5)),
                'specimen_type'       => 'Blood',
                'availability_status' => 'available',
                'source_system'       => $source === '' ? null : $source,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        $names = collect($this->catalogue('doctor')->assertOk()->json('data'))->pluck('name')->all();
        $this->assertNotContains('Seeded Malaria RDT', $names);
        $this->assertNotContains('Unclaimed HbA1c', $names);

        // The patient-side catalogue reads the same allow-list.
        $patient = \App\Models\Patient::factory()->create(['is_demo' => (bool) config('demo.enabled')]);
        $raw = 'pat_'.Str::random(40);
        \App\Models\PatientAccessToken::forceCreate([
            'patient_id' => $patient->id, 'token_hash' => \Illuminate\Support\Facades\Hash::make($raw),
            'token_prefix' => substr($raw, 0, 12), 'token_lookup_hash' => hash('sha256', $raw), 'expires_at' => now()->addDay(),
        ]);
        $res = $this->withToken($raw)->getJson('/api/mobile/labs/catalogue')->assertOk();
        $patientNames = collect($res->json('data'))->pluck('name')->all();
        $this->assertContains('Full Blood Count', $patientNames);
        $this->assertNotContains('Seeded Malaria RDT', $patientNames);
        $this->assertNotContains('Unclaimed HbA1c', $patientNames);
    }

    public function test_search_matches_name_code_or_loinc_from_two_characters(): void
    {
        $this->assertSame(['K'], collect($this->catalogue('doctor', ['q' => 'potas'])->assertOk()->json('data'))->pluck('code')->all());
        $this->assertSame(['FBC'], collect($this->catalogue('doctor', ['q' => '58410'])->assertOk()->json('data'))->pluck('code')->all());
        $this->assertSame([], collect($this->catalogue('doctor', ['q' => 'zzzz'])->assertOk()->json('data'))->all());
        // One character is not a search: the whole catalogue comes back.
        $this->assertCount(2, $this->catalogue('doctor', ['q' => 'k'])->assertOk()->json('data'));
    }

    public function test_the_pharmacy_is_refused_by_role(): void
    {
        $this->catalogue('pharmacist')->assertStatus(403)->assertJsonPath('error_code', 'role_forbidden');
    }

    public function test_anonymous_is_refused(): void
    {
        $this->getJson('/api/mobile/doctor/lab-catalogue')->assertStatus(401);
    }

    private function catalogue(string $role, array $query = []): TestResponse
    {
        return $this->withHeaders(['Authorization' => "Bearer {$this->bearer[$role]}"])
            ->getJson('/api/mobile/doctor/lab-catalogue' . ($query ? '?' . http_build_query($query) : ''));
    }

    /** Same recipe as ProviderRoleMatrixTest. */
    private function professional(string $role): User
    {
        DB::table('dashboard_profiles')->updateOrInsert(['key' => $role], [
            'name' => ucfirst($role), 'portal_prefix' => 'staff', 'landing_route' => 'staff.dashboard',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $model = Role::firstOrCreate(['name' => $role], ['description' => ucfirst($role)]);
        $model->forceFill(['dashboard_profile_key' => $role])->save();

        return User::factory()->create([
            'is_demo' => (bool) config('demo.enabled'),
            'role_id' => $model->id,
        ]);
    }
}
