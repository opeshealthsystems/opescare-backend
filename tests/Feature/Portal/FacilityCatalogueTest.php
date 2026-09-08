<?php

namespace Tests\Feature\Portal;

use App\Models\ClinicalCodeDictionary;
use App\Models\Facility;
use App\Models\FacilityCatalogueItem;
use App\Models\FacilityRoleAssignment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The facility service catalogue — "what we offer".
 *
 * The interesting tests here are the negative ones. A catalogue row is a
 * public claim about an institution ("we do this test, at this price"), so the
 * two things that must never happen are a facility editing somebody else's
 * claim, and a request naming the facility it wants to write to.
 */
class FacilityCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\AccountCategoriesSeeder::class);
        $this->seed(\Database\Seeders\DashboardProfilesSeeder::class);
        $this->seed(\Database\Seeders\RolesSeeder::class);
    }

    /** A lab manager posted at one facility, exactly as the middleware leaves one. */
    private function manager(string $roleName = 'lab_manager'): array
    {
        $role = Role::where('name', $roleName)->firstOrFail();
        $facility = Facility::factory()->create(['type' => 'hospital']);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'primary_facility_id' => $facility->id,
            'status' => 'active',
        ]);

        FacilityRoleAssignment::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'role_id' => $role->id,
            'is_active' => true,
            'assigned_at' => now(),
        ]);

        return [$user, $facility];
    }

    private function actingAsManager(User $user, Facility $facility): static
    {
        return $this->actingAs($user)->withSession(['active_facility_id' => $facility->id]);
    }

    private function code(string $system = 'LOINC', string $code = '718-7'): ClinicalCodeDictionary
    {
        return ClinicalCodeDictionary::create([
            'id' => (string) Str::uuid(),
            'system' => $system,
            'code' => $code,
            'display' => 'Hemoglobin [Mass/volume] in Blood',
            'kind' => 'term',
            'is_active' => true,
            'source' => 'test-fixture',
        ]);
    }

    private function item(Facility $facility, array $attributes = []): FacilityCatalogueItem
    {
        return FacilityCatalogueItem::create(array_merge([
            'facility_id' => $facility->id,
            'system' => 'LOINC',
            'code' => '718-7',
            'is_active' => true,
        ], $attributes));
    }

    // ── The happy path, only far enough to make the negatives meaningful ──

    public function test_a_manager_sees_only_their_own_facilitys_catalogue(): void
    {
        [$user, $mine] = $this->manager();
        $theirs = Facility::factory()->create(['type' => 'hospital']);

        $this->code();
        $this->item($mine, ['local_name' => 'Taux hemoglobine ici']);
        $this->item($theirs, ['local_name' => 'SOMEBODY ELSES ENTRY']);

        $response = $this->actingAsManager($user, $mine)->get('/facility/catalogue');

        $response->assertOk();
        $response->assertSee('Taux hemoglobine ici');
        $response->assertDontSee('SOMEBODY ELSES ENTRY');
    }

    public function test_adding_a_dictionary_code_writes_it_against_the_session_facility(): void
    {
        [$user, $facility] = $this->manager();
        $this->code();

        $this->actingAsManager($user, $facility)
            ->post('/facility/catalogue', [
                'system' => 'LOINC',
                'code' => '718-7',
                'local_name' => 'Hemoglobine',
                'price_xaf' => 1500,
            ])
            ->assertRedirect();

        $item = FacilityCatalogueItem::firstOrFail();
        $this->assertSame($facility->id, $item->facility_id);
        $this->assertSame($user->id, $item->added_by);
        // XAF is a zero-decimal currency: 1500 francs, an integer, not 1500.00.
        $this->assertSame(1500, $item->price_xaf);
    }

    public function test_a_code_that_is_not_in_the_dictionary_cannot_be_added(): void
    {
        [$user, $facility] = $this->manager();

        $this->actingAsManager($user, $facility)
            ->post('/facility/catalogue', ['system' => 'LOINC', 'code' => '000-0'])
            ->assertRedirect();

        $this->assertDatabaseCount('facility_catalogue_items', 0);
    }

    // ── Removal retires, never deletes ───────────────────────────────────

    public function test_removing_a_service_retires_the_row_and_keeps_it(): void
    {
        [$user, $facility] = $this->manager();
        $this->code();
        $item = $this->item($facility, ['price_xaf' => 2000]);

        $this->actingAsManager($user, $facility)
            ->post("/facility/catalogue/{$item->id}/retire")
            ->assertRedirect();

        $item->refresh();
        $this->assertFalse($item->is_active);
        $this->assertNotNull($item->deactivated_at);
        $this->assertSame($user->id, $item->deactivated_by);
        // The claim and its price survive. A hard delete would have turned
        // "this hospital charged 2000 FCFA" into "it never did".
        $this->assertSame(2000, $item->price_xaf);
    }

    public function test_re_adding_a_retired_service_revives_the_same_row(): void
    {
        [$user, $facility] = $this->manager();
        $this->code();
        $item = $this->item($facility, ['is_active' => false, 'deactivated_at' => now()]);

        $this->actingAsManager($user, $facility)
            ->post('/facility/catalogue', ['system' => 'LOINC', 'code' => '718-7'])
            ->assertRedirect();

        $this->assertDatabaseCount('facility_catalogue_items', 1);
        $this->assertTrue($item->fresh()->is_active);
        $this->assertNull($item->fresh()->deactivated_at);
    }

    // ── Cross-facility access is 404, never 403 ──────────────────────────

    public function test_another_facilitys_item_cannot_be_edited_and_answers_404(): void
    {
        [$user, $mine] = $this->manager();
        $theirs = Facility::factory()->create(['type' => 'hospital']);
        $this->code();
        $item = $this->item($theirs, ['price_xaf' => 5000]);

        // 404, not 403: a 403 would confirm the row is real, and the existence
        // of another hospital's price list is not something to confirm.
        $this->actingAsManager($user, $mine)
            ->post("/facility/catalogue/{$item->id}", ['price_xaf' => 1])
            ->assertNotFound();

        $this->assertSame(5000, $item->fresh()->price_xaf);
    }

    public function test_another_facilitys_item_cannot_be_retired_and_answers_404(): void
    {
        [$user, $mine] = $this->manager();
        $theirs = Facility::factory()->create(['type' => 'hospital']);
        $this->code();
        $item = $this->item($theirs);

        $this->actingAsManager($user, $mine)
            ->post("/facility/catalogue/{$item->id}/retire")
            ->assertNotFound();

        $this->assertTrue($item->fresh()->is_active);
    }

    // ── facility_id is never read from user input ────────────────────────

    public function test_a_facility_id_in_the_request_body_is_ignored(): void
    {
        [$user, $mine] = $this->manager();
        $theirs = Facility::factory()->create(['type' => 'hospital']);
        $this->code();

        $this->actingAsManager($user, $mine)
            ->post('/facility/catalogue', [
                'system' => 'LOINC',
                'code' => '718-7',
                // The whole attack, in one line. Nothing reads it.
                'facility_id' => $theirs->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('facility_catalogue_items', 1);
        $this->assertSame($mine->id, FacilityCatalogueItem::firstOrFail()->facility_id);
    }

    public function test_a_facility_id_in_the_query_string_or_a_header_is_ignored(): void
    {
        [$user, $mine] = $this->manager();
        $theirs = Facility::factory()->create(['type' => 'hospital']);
        $this->code();
        $this->item($mine, ['local_name' => 'MINE']);
        $this->item($theirs, ['local_name' => 'THEIRS']);

        $response = $this->actingAsManager($user, $mine)
            ->withHeaders(['X-Facility-Id' => $theirs->id])
            ->get('/facility/catalogue?facility_id=' . $theirs->id);

        $response->assertOk();
        $response->assertSee('MINE');
        $response->assertDontSee('THEIRS');
    }

    // ── The capability gate ──────────────────────────────────────────────

    public function test_a_role_without_the_capability_is_refused_with_403(): void
    {
        // A ward nurse is a real member of the facility; she simply does not
        // set the institution's published prices. A refusal, not a 404 — the
        // route's existence is no secret to an authenticated professional.
        [$user, $facility] = $this->manager('labtech');

        $this->actingAsManager($user, $facility)
            ->get('/facility/catalogue')
            ->assertForbidden();
    }
}
