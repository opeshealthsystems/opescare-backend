<?php

namespace Tests\Feature\Mobile;

use App\Models\AllergyRecord;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\User;
use App\Services\Patient\HealthPassportBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The Health Passport: one patient's own record, as thirty pages.
 *
 * The rule these tests exist to hold is that the passport NEVER INVENTS. A page
 * with nothing behind it comes back with no sections, so the app can say
 * "nothing has been recorded on this page yet" — a true statement. A passport
 * that filled empty pages with plausible prose would be worse than no passport
 * at all, because a clinician reading "no known allergies" has to be able to
 * trust that somebody actually checked.
 */
class HealthPassportTest extends FamilyTestCase
{
    private Patient $patient;

    private array $headers;

    /** allergy_records records WHO noted it, so the fixture needs a clinician. */
    private User $recorder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = $this->makePatient([
            'first_name'    => 'Amina',
            'last_name'     => 'Nkeng',
            'sex'           => 'female',
            'date_of_birth' => '1991-04-18',
        ]);
        $this->headers  = $this->headersFor($this->patient);
        $this->recorder = User::factory()->create(['is_demo' => $this->demoFlag()]);
    }

    /** The signed-in patient reading their own passport. */
    private function asPatient(): static
    {
        return $this->as($this->headers);
    }

    public function test_the_index_lists_the_canonical_thirty_pages_in_order(): void
    {
        $data = $this->asPatient()->getJson('/api/mobile/health-passport')->assertOk()->json('data');

        $this->assertSame(30, $data['total_pages']);
        $this->assertCount(30, $data['pages']);
        $this->assertSame('cover', $data['pages'][0]['id']);
        $this->assertSame('back-cover', $data['pages'][29]['id']);
        $this->assertSame('vitals', $data['pages'][24]['id'], 'page 25 is Vital Signs in the canonical sequence');
        $this->assertSame('care-plans', $data['pages'][28]['id'], 'page 29 is Care Plans');
        $this->assertSame(range(1, 30), array_column($data['pages'], 'number'));

        // Titles are translated, never raw keys.
        foreach ($data['pages'] as $page) {
            $this->assertStringNotContainsString('passport.', $page['title'], "page {$page['id']} shows a raw key");
            $this->assertNotSame('', trim($page['title']));
        }
    }

    public function test_a_page_with_nothing_recorded_returns_no_sections_rather_than_filler(): void
    {
        $page = $this->asPatient()->getJson('/api/mobile/health-passport/pages/allergies-alerts')
            ->assertOk()->json('data');

        $this->assertSame('allergies-alerts', $page['id']);
        $this->assertSame([], $page['sections'], 'an empty page must be empty, not filled in');
    }

    public function test_a_page_shows_the_patients_own_records(): void
    {
        AllergyRecord::create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $this->recorder->id,
            'substance'   => 'Penicillin',
            'severity'   => 'severe',
            'status'     => 'active',
        ]);

        $page = $this->asPatient()->getJson('/api/mobile/health-passport/pages/allergies-alerts')
            ->assertOk()->json('data');

        $this->assertNotEmpty($page['sections']);
        $item = $page['sections'][0]['items'][0];
        $this->assertSame('Penicillin', $item['label']);
        $this->assertSame('danger', $item['tone'], 'a severe allergy is shown as a danger');
    }

    public function test_immunisations_come_from_the_record_with_their_dates(): void
    {
        ImmunizationRecord::create([
            'id'              => (string) Str::uuid(),
            'patient_id'      => $this->patient->id,
            'facility_id'     => \App\Models\Facility::factory()->create()->id,
            'vaccine_name'    => 'Measles-Rubella',
            'vaccine_code'    => 'MR',
            'administered_at' => now()->subYears(2),
            'status'          => 'completed',
        ]);

        $page = $this->asPatient()->getJson('/api/mobile/health-passport/pages/immunization')
            ->assertOk()->json('data');

        $this->assertSame('Measles-Rubella', $page['sections'][0]['items'][0]['label']);
    }

    /**
     * The six pages the platform has no source for keep their place in the
     * sequence and stay honestly empty. Dropping them would misnumber the
     * passport; filling them would be a lie.
     */
    public function test_pages_with_no_source_keep_their_place_and_stay_empty(): void
    {
        foreach (['procedures', 'cardiology', 'maternal-child', 'family-genetic', 'preventive-screening', 'devices-transfusion'] as $id) {
            $page = $this->asPatient()->getJson("/api/mobile/health-passport/pages/{$id}")->assertOk()->json('data');
            $this->assertSame([], $page['sections'], "{$id} must not invent content");
            $this->assertNotSame('', trim($page['title']));
        }
    }

    public function test_identity_names_the_patient_and_their_health_id(): void
    {
        $page = $this->asPatient()->getJson('/api/mobile/health-passport/pages/patient-identity')
            ->assertOk()->json('data');

        $values = collect($page['sections'])->flatMap(fn ($s) => array_column($s['items'], 'value'))->all();
        $this->assertContains($this->patient->health_id, $values);
    }

    public function test_an_unknown_page_is_refused_without_saying_anything_about_the_record(): void
    {
        $this->asPatient()->getJson('/api/mobile/health-passport/pages/not-a-page')->assertStatus(404);
    }

    public function test_a_signed_out_caller_gets_nothing(): void
    {
        $this->getJson('/api/mobile/health-passport')->assertStatus(401);
    }

    /**
     * A guardian with an active full link reads the DEPENDENT'S passport
     * through the same act-as header every other record route uses.
     */
    public function test_a_guardian_reads_their_dependents_passport_through_the_act_as_header(): void
    {
        $guardian = $this->makeGuardian();
        $child    = $this->makeChild();
        $this->link($guardian['user'], $child);

        AllergyRecord::create([
            'patient_id'  => $child->id,
            'provider_id' => $this->recorder->id,
            'substance'   => 'Sulfa drugs',
            'severity'   => 'moderate',
            'status'     => 'active',
        ]);

        $page = $this->as($this->actingFor($guardian['headers'], $child))
            ->getJson('/api/mobile/health-passport/pages/allergies-alerts')
            ->assertOk()->json('data');

        $this->assertSame('Sulfa drugs', $page['sections'][0]['items'][0]['label']);

        // And the guardian's OWN passport is a different record.
        $own = $this->as($guardian['headers'])
            ->getJson('/api/mobile/health-passport/pages/allergies-alerts')
            ->assertOk()->json('data');
        $this->assertSame([], $own['sections']);
    }

    public function test_the_builder_holds_the_canonical_sequence(): void
    {
        $ids = array_column(HealthPassportBuilder::PAGES, 'id');

        $this->assertCount(30, $ids);
        $this->assertSame(30, HealthPassportBuilder::TOTAL_PAGES);
        $this->assertSame(count($ids), count(array_unique($ids)), 'page ids are unique');
    }

    /** One patient's passport never reaches another patient's records. */
    public function test_the_passport_is_scoped_to_its_own_patient(): void
    {
        $stranger = $this->makePatient();
        AllergyRecord::create([
            'patient_id'  => $stranger->id,
            'provider_id' => $this->recorder->id,
            'substance'   => 'Latex',
            'severity'   => 'severe',
            'status'     => 'active',
        ]);

        $page = $this->asPatient()->getJson('/api/mobile/health-passport/pages/allergies-alerts')
            ->assertOk()->json('data');

        $this->assertStringNotContainsString('Latex', json_encode($page));
    }
}
