<?php
namespace Tests\Feature\Portal;

use App\Models\Facility;
use App\Models\FamilyLink;
use App\Models\ImmunizationRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The record pages a parent looks for first (immunisations, timeline,
 * documents, allergies, conditions, care plans, access logs, the printable
 * card) had controller methods and no routes. They live in routes/web_family.php
 * under guardian.context, so they resolve AND follow the switch — and every
 * one of them is linked from the dashboard.
 */
class FamilyRecordRoutesTest extends TestCase
{
    use RefreshDatabase;

    private const RECORD_ROUTES = [
        'portals.patient.immunizations',
        'portals.patient.timeline',
        'portals.patient.documents',
        'portals.patient.allergies',
        'portals.patient.clinical',
        'portals.patient.care-plans',
        'portals.patient.access-logs',
    ];

    private array $session = ['active_facility_id' => '7e57fac1-0000-4000-8000-000000000001'];

    public function test_every_record_route_resolves_for_the_patient(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user    = User::factory()->create(['patient_id' => $patient->id]);

        foreach (self::RECORD_ROUTES as $name) {
            $this->actingAs($user)->withSession($this->session)
                ->get(route($name))
                ->assertOk();
        }
    }

    public function test_record_routes_require_authentication(): void
    {
        foreach (self::RECORD_ROUTES as $name) {
            $this->get(route($name))->assertRedirect();
        }
        $this->get(route('portals.patient.health-id-card'))->assertRedirect();
    }

    public function test_dashboard_links_to_every_record_page_and_the_printable_card(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false]);
        $user    = User::factory()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($user)->withSession($this->session)
            ->get(route('portals.patient'))
            ->assertOk();

        foreach (self::RECORD_ROUTES as $name) {
            $response->assertSee(route($name));
        }
        $response->assertSee(route('portals.patient.health-id-card'));
        $response->assertSee(route('portals.guardian.dashboard'));
    }

    public function test_printable_card_is_a_pdf_for_the_patient(): void
    {
        $patient = Patient::factory()->create(['is_demo' => false, 'health_id' => 'CM-HID-CARD-TEST-0001']);
        $user    = User::factory()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($user)->withSession($this->session)
            ->get(route('portals.patient.health-id-card'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('OpesCare-HealthID-CMHIDCARDTEST0001.pdf', (string) $response->headers->get('content-disposition'));
        $this->assertDatabaseHas('health_id_qr_tokens', ['patient_id' => $patient->id, 'token_type' => 'card_qr']);
        $this->assertDatabaseHas('audit_events', ['patient_id' => $patient->id, 'action_type' => 'patient_health_id_card_downloaded']);
    }

    public function test_immunizations_page_shows_the_dependents_shots_while_switched(): void
    {
        $facility = Facility::forceCreate([
            'id'      => '99a00000-0000-0000-0000-00000000f001',
            'name'    => 'Family Test Hospital',
            'type'    => 'hospital',
            'is_demo' => false,
        ]);

        $own       = Patient::factory()->create(['is_demo' => false]);
        $guardian  = User::factory()->create(['patient_id' => $own->id]);
        $dependent = Patient::factory()->create(['is_demo' => false, 'first_name' => 'Little', 'last_name' => 'One']);
        FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'access_level'         => 'read_only',
            'status'               => 'active',
        ]);

        ImmunizationRecord::create([
            'patient_id'      => $own->id,
            'facility_id'     => $facility->id,
            'vaccine_code'    => 'MMR',
            'vaccine_name'    => 'MMR Own Guardian Shot',
            'administered_at' => now()->subYears(20),
        ]);
        ImmunizationRecord::create([
            'patient_id'      => $dependent->id,
            'facility_id'     => $facility->id,
            'vaccine_code'    => 'BCG',
            'vaccine_name'    => 'BCG Dependent Shot',
            'administered_at' => now()->subYears(2),
            'lot_number'      => 'LOT-DEP-1',
        ]);

        $response = $this->actingAs($guardian)
            ->withSession(array_merge($this->session, ['guardian_viewing_patient_id' => $dependent->id]))
            ->get(route('portals.patient.immunizations'));

        $response->assertOk();
        $response->assertViewHas('patient', fn ($p) => $p->id === $dependent->id);
        $response->assertSee('BCG Dependent Shot');
        $response->assertSee('LOT-DEP-1');
        $response->assertDontSee('MMR Own Guardian Shot');
        $response->assertSee('You are viewing Little One');
        $response->assertSee('Record of Little One');

        // A read-only guardian still READS every record page — only writes are refused.
        foreach (self::RECORD_ROUTES as $name) {
            $this->actingAs($guardian)
                ->withSession(array_merge($this->session, ['guardian_viewing_patient_id' => $dependent->id]))
                ->get(route($name))
                ->assertOk()
                ->assertViewHas('patient', fn ($p) => $p->id === $dependent->id);
        }

        // The audit trail names the dependent, not the guardian.
        $this->assertDatabaseHas('audit_events', ['patient_id' => $dependent->id, 'action_type' => 'patient_immunizations_view', 'actor_id' => $guardian->id]);
    }

    public function test_record_pages_refuse_a_stale_switch(): void
    {
        $own       = Patient::factory()->create(['is_demo' => false]);
        $guardian  = User::factory()->create(['patient_id' => $own->id]);
        $dependent = Patient::factory()->create(['is_demo' => false]);
        FamilyLink::factory()->create([
            'guardian_user_id'     => $guardian->id,
            'dependent_patient_id' => $dependent->id,
            'status'               => 'revoked',
        ]);

        $this->actingAs($guardian)
            ->withSession(array_merge($this->session, ['guardian_viewing_patient_id' => $dependent->id]))
            ->get(route('portals.patient.timeline'))
            ->assertRedirect(route('portals.patient'))
            ->assertSessionHas('error')
            ->assertSessionMissing('guardian_viewing_patient_id');
    }
}
