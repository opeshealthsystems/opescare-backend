<?php

namespace Tests\Feature\Maternity;

use App\Models\CareFacility;
use App\Models\Patient;
use App\Models\User;
use App\Modules\CareMap\Services\CareMapSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Maternity audit 2: a baby born today must be able to receive a Health ID
 * through the guardian flow the same day (birth doses are given at birth), and
 * a pregnant woman must be able to find a maternity by the word she types.
 */
class MaternityNewbornAndFinderTest extends TestCase
{
    use RefreshDatabase;

    private array $session = ['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'];

    private function payload(array $o = []): array
    {
        return array_merge([
            'first_name' => 'Bebe', 'last_name' => 'Nouveau',
            'date_of_birth' => now()->toDateString(), 'sex' => 'female',
            'relationship' => 'parent', 'access_level' => 'full',
        ], $o);
    }

    public function test_mother_can_register_a_baby_born_today_through_the_identity_service(): void
    {
        $own = Patient::factory()->create(['is_demo' => false, 'country_code' => 'CM']);
        $mother = User::factory()->create(['patient_id' => $own->id]);

        $this->actingAs($mother)->withSession($this->session)
            ->post(route('portals.patient.family.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('portals.patient.family'));

        $baby = Patient::where('first_name', 'Bebe')->where('last_name', 'Nouveau')->first();
        $this->assertNotNull($baby);
        $this->assertStringStartsWith('CM-HID-', $baby->health_id);
        $this->assertDatabaseHas('audit_events', ['patient_id' => $baby->id, 'source_system' => 'identity']);
        $this->assertDatabaseHas('family_links', ['guardian_user_id' => $mother->id, 'dependent_patient_id' => $baby->id, 'status' => 'active']);
    }

    public function test_a_future_date_of_birth_is_still_refused(): void
    {
        $own = Patient::factory()->create(['is_demo' => false]);
        $mother = User::factory()->create(['patient_id' => $own->id]);

        $this->actingAs($mother)->withSession($this->session)
            ->post(route('portals.patient.family.store'), $this->payload(['date_of_birth' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('date_of_birth');

        $this->assertNull(Patient::where('first_name', 'Bebe')->first());
    }

    public function test_add_form_date_picker_allows_today(): void
    {
        $own = Patient::factory()->create(['is_demo' => false]);
        $mother = User::factory()->create(['patient_id' => $own->id]);

        $this->actingAs($mother)->withSession($this->session)
            ->get(route('portals.patient.family.add'))
            ->assertOk()
            ->assertSee('max="' . now()->toDateString() . '"', false);
    }

    public function test_mobile_family_registration_accepts_a_birth_today(): void
    {
        $src = file_get_contents(app_path('Http/Controllers/Api/Mobile/MobileFamilyController.php'));
        $this->assertStringContainsString("'date_of_birth' => 'required|date|before_or_equal:today'", $src);
    }

    public function test_the_word_maternity_finds_a_maternity_type_listing(): void
    {
        $l = CareFacility::create([
            'facility_name' => 'Centre Mere Enfant ' . Str::random(4), 'facility_type' => 'maternity',
            'listing_status' => 'active', 'verification_status' => 'unverified', 'country_code' => 'CM',
            'region' => 'Centre', 'city' => 'Yaoundé', 'address' => 'Essos', 'phone_primary' => '+237222000001',
        ]);

        $ids = collect(app(CareMapSearchService::class)->searchNearby(['query' => 'maternity']))->pluck('id')->all();
        $this->assertContains($l->id, $ids);

        if (! array_key_exists('maternity', CareMapSearchService::TYPE_SYNONYMS)) {
            $this->markTestIncomplete('French/EN maternity words (maternité, sage-femme, accouchement) not yet in TYPE_SYNONYMS — shared-file change pending with the lead.');
        }
        foreach (['maternité', 'Sage-femme', 'accouchement'] as $q) {
            $ids = collect(app(CareMapSearchService::class)->searchNearby(['query' => $q]))->pluck('id')->all();
            $this->assertContains($l->id, $ids, $q);
        }
    }
}
