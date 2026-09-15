<?php

namespace Tests\Feature\Imports;

use App\Models\Appointment;
use App\Models\AuditEvent;
use App\Models\Dhis2DataElementMapping;
use App\Models\Dhis2OrgUnitMapping;
use App\Models\Dhis2PushRecord;
use App\Models\Facility;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PublicHealthReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/** DHIS2 aggregate reporting (group E): draft -> review -> approve -> push, counts only. */
class Dhis2ReportingTest extends TestCase
{
    use ImportsGroupBHelpers;
    use RefreshDatabase;

    private const ORG_UNIT = 'OuFacility1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function configure(): void
    {
        config(['dhis2.base_url' => 'https://dhis.test', 'dhis2.username' => 'reporter', 'dhis2.password' => 'secret-pass', 'dhis2.dataset_id' => 'DsMonthly01']);
    }

    private function fakeDhis2(): void
    {
        Http::fake([
            'dhis.test/api/dataValueSets' => Http::response([
                'httpStatus' => 'OK', 'status' => 'OK',
                'response' => ['status' => 'SUCCESS', 'importCount' => ['imported' => 3, 'updated' => 0, 'ignored' => 0, 'deleted' => 0], 'conflicts' => []],
            ]),
            'dhis.test/api/system/info' => Http::response(['version' => '2.40.3', 'systemName' => 'DHIS2 MINSANTE']),
        ]);
    }

    /** Source rows for one facility in August 2026 plus noise outside it. */
    private function seedActivity(Facility $facility, Facility $other): Patient
    {
        $patient = Patient::factory()->create(['first_name' => 'Ngozi', 'health_id' => 'OC-SEC-777']);
        $in = '2026-08-12 10:00:00';

        Appointment::factory()->count(2)->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'status' => 'completed', 'scheduled_at' => $in]);
        Appointment::factory()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'status' => 'cancelled', 'scheduled_at' => $in]);
        Appointment::factory()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'status' => 'completed', 'scheduled_at' => '2026-07-30 10:00:00']);
        Appointment::factory()->count(5)->create(['facility_id' => $other->id, 'patient_id' => $patient->id, 'status' => 'completed', 'scheduled_at' => $in]);

        LabOrder::factory()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'status' => 'verified', 'test_code' => 'FBC', 'test_name' => 'Full Blood Count', 'resulted_at' => $in]);
        LabOrder::factory()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'status' => 'in_progress', 'test_code' => 'FBC', 'test_name' => 'Full Blood Count', 'resulted_at' => $in]);

        Prescription::factory()->create(['facility_id' => $facility->id, 'patient_id' => $patient->id, 'status' => 'dispensed', 'dispensed_at' => $in]);

        foreach (['BCG', 'BCG', 'OPV'] as $vaccine) {
            DB::table('immunization_records')->insert([
                'id' => (string) Str::uuid(), 'patient_id' => $patient->id, 'facility_id' => $facility->id,
                'vaccine_code' => $vaccine, 'vaccine_system' => 'local', 'vaccine_name' => $vaccine,
                'administered_at' => $in, 'dose_number' => 1, 'status' => 'completed', 'is_historical' => false,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $patient;
    }

    private function mapElements(): void
    {
        foreach (['appointments_completed' => 'DeApptDone1', 'prescriptions_dispensed' => 'DeRxDispen1', 'immunisation_doses.bcg' => 'DeBcgDoses1'] as $code => $uid) {
            Dhis2DataElementMapping::create(['indicator_code' => $code, 'data_element_id' => $uid]);
        }
    }

    private function draftFor(Facility $facility, $admin): PublicHealthReport
    {
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.generate'), ['month' => '2026-08', 'facility_id' => $facility->id])->assertRedirect();

        return PublicHealthReport::where('facility_id', $facility->id)->with('items')->sole();
    }

    public function test_draft_aggregates_only_this_facility_for_the_period_and_holds_no_patient_identifiers(): void
    {
        $facility = Facility::factory()->create(['status' => 'active']);
        $other = Facility::factory()->create(['status' => 'active']);
        $patient = $this->seedActivity($facility, $other);
        Dhis2OrgUnitMapping::create(['facility_id' => $facility->id, 'org_unit_id' => self::ORG_UNIT]);
        $admin = $this->platformAdmin();

        $report = $this->draftFor($facility, $admin);
        $values = $report->items->pluck('value', 'indicator_code')->all();
        ksort($values);

        $this->assertSame([
            'appointments_completed' => 2,
            'immunisation_doses.bcg' => 2,
            'immunisation_doses.opv' => 1,
            'lab_tests_released.fbc' => 1,
            'prescriptions_dispensed' => 1,
        ], $values);
        $this->assertSame('draft', $report->status);
        $this->assertSame(0, PublicHealthReport::where('facility_id', $other->id)->count());

        $dump = json_encode([$report->toArray(), $report->items->toArray()]);
        foreach ([$patient->id, $patient->health_id, 'Ngozi'] as $identifier) {
            $this->assertStringNotContainsString((string) $identifier, $dump);
        }

        $this->actingAsAdmin($admin)->get(route('portals.admin.dhis2.drafts.show', $report->id))
            ->assertOk()->assertSee('appointments_completed')->assertDontSee('OC-SEC-777')->assertDontSee('Ngozi');
        $this->assertTrue(AuditEvent::where('action_type', 'dhis2.draft.generated')->where('actor_id', $admin->id)->exists());
    }

    public function test_unmapped_facility_is_skipped_and_shown_as_not_mapped(): void
    {
        $this->configure();
        $this->fakeDhis2();
        $mapped = Facility::factory()->create(['status' => 'active']);
        $unmapped = Facility::factory()->create(['status' => 'active']);
        Dhis2OrgUnitMapping::create(['facility_id' => $mapped->id, 'org_unit_id' => self::ORG_UNIT]);
        $admin = $this->platformAdmin();

        // "All mapped facilities" never builds a draft for the unmapped one.
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.generate'), ['month' => '2026-08'])->assertRedirect();
        $this->assertSame(1, PublicHealthReport::where('facility_id', $mapped->id)->count());
        $this->assertSame(0, PublicHealthReport::where('facility_id', $unmapped->id)->count());

        // Asked for explicitly, it is drafted, flagged, and cannot be sent.
        $report = $this->draftFor($unmapped, $admin);
        $this->assertTrue($report->qualityChecks()->where('check_code', 'ORG_UNIT_NOT_MAPPED')->exists());
        $this->actingAsAdmin($admin)->get(route('portals.admin.dhis2.drafts.show', $report->id))->assertOk()->assertSee(__('dhis2.not_mapped'));
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.drafts.approve', $report->id))->assertRedirect();
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.drafts.push', $report->id))->assertSessionHas('error', __('dhis2.flash_not_mapped'));

        Http::assertNothingSent();
    }

    public function test_push_is_refused_before_approval(): void
    {
        $this->configure();
        $this->fakeDhis2();
        $facility = Facility::factory()->create(['status' => 'active']);
        $this->seedActivity($facility, Facility::factory()->create());
        Dhis2OrgUnitMapping::create(['facility_id' => $facility->id, 'org_unit_id' => self::ORG_UNIT]);
        $this->mapElements();
        $admin = $this->platformAdmin();

        $report = $this->draftFor($facility, $admin);
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.drafts.push', $report->id))
            ->assertSessionHas('error', __('dhis2.flash_push_not_approved'));

        Http::assertNothingSent();
        $this->assertSame('draft', $report->fresh()->status);
        $this->assertSame(0, Dhis2PushRecord::count());
    }

    public function test_approved_push_sends_the_expected_data_value_set_records_the_summary_and_repush_is_idempotent(): void
    {
        $this->configure();
        $this->fakeDhis2();
        $facility = Facility::factory()->create(['status' => 'active']);
        $patient = $this->seedActivity($facility, Facility::factory()->create());
        Dhis2OrgUnitMapping::create(['facility_id' => $facility->id, 'org_unit_id' => self::ORG_UNIT]);
        $this->mapElements();
        $admin = $this->platformAdmin();

        $report = $this->draftFor($facility, $admin);
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.drafts.approve', $report->id))->assertSessionHas('success');
        $this->assertSame('approved_for_submission', $report->fresh()->status);

        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.drafts.push', $report->id))->assertSessionHas('success');

        Http::assertSentCount(1);
        Http::assertSent(function (HttpRequest $request) use ($patient) {
            if ($request->url() !== 'https://dhis.test/api/dataValueSets') {
                return false;
            }
            $values = collect($request['dataValues'])->pluck('value', 'dataElement')->all();
            ksort($values);

            return $request['dataSet'] === 'DsMonthly01'
                && $request['period'] === '202608'
                && $request['orgUnit'] === self::ORG_UNIT
                && $values === ['DeApptDone1' => '2', 'DeBcgDoses1' => '2', 'DeRxDispen1' => '1']
                && ! str_contains($request->body(), (string) $patient->health_id)
                && ! str_contains($request->body(), (string) $patient->id)
                && $request->hasHeader('Authorization');
        });

        $this->assertSame('submitted', $report->fresh()->status);
        $this->assertSame(3, Dhis2PushRecord::where('status', 'imported')->count());
        $record = Dhis2PushRecord::where('indicator_code', 'appointments_completed')->sole();
        $this->assertSame(3, $record->import_summary['importCount']['imported']);
        $this->assertSame(1, $record->attempts);
        $this->assertTrue(AuditEvent::where('action_type', 'dhis2.push.pushed')->where('resource_id', $report->id)->exists());
        $this->actingAsAdmin($admin)->get(route('portals.admin.dhis2.drafts.show', $report->id))->assertOk()
            ->assertSee(__('dhis2.import_counts', ['imported' => 3, 'updated' => 0, 'ignored' => 0]));

        // Pushing again sends nothing: every value is already imported unchanged.
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.drafts.push', $report->id))->assertSessionHas('success');
        Http::assertSentCount(1);
        $this->assertSame(3, Dhis2PushRecord::count());
        $this->assertSame(1, Dhis2PushRecord::where('indicator_code', 'appointments_completed')->sole()->attempts);

        // And the credentials never reach the audit trail.
        $this->assertStringNotContainsString('secret-pass', AuditEvent::where('source_system', 'finder_dhis2')->get()->toJson());
    }

    public function test_a_failed_push_is_recorded_and_can_be_retried(): void
    {
        $this->configure();
        Http::fakeSequence('dhis.test/api/dataValueSets')
            ->push(['status' => 'ERROR', 'description' => 'Org unit not in data set'], 409)
            ->push(['response' => ['status' => 'SUCCESS', 'importCount' => ['imported' => 1, 'updated' => 0, 'ignored' => 0]]]);
        $facility = Facility::factory()->create(['status' => 'active']);
        $this->seedActivity($facility, Facility::factory()->create());
        Dhis2OrgUnitMapping::create(['facility_id' => $facility->id, 'org_unit_id' => self::ORG_UNIT]);
        Dhis2DataElementMapping::create(['indicator_code' => 'appointments_completed', 'data_element_id' => 'DeApptDone1']);
        $admin = $this->platformAdmin();

        $report = $this->draftFor($facility, $admin);
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.drafts.approve', $report->id));
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.drafts.push', $report->id));
        $this->assertSame('submission_failed', $report->fresh()->status);
        $this->assertSame('failed', Dhis2PushRecord::sole()->status);

        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.drafts.push', $report->id));
        $this->assertSame('submitted', $report->fresh()->status);
        $this->assertSame('imported', Dhis2PushRecord::sole()->status);
        $this->assertSame(2, Dhis2PushRecord::sole()->attempts);
    }

    public function test_without_credentials_push_is_disabled_but_drafts_are_still_generated(): void
    {
        config(['dhis2.base_url' => '', 'dhis2.username' => '', 'dhis2.password' => '', 'dhis2.token' => '']);
        Http::fake();
        $facility = Facility::factory()->create(['status' => 'active']);
        $this->seedActivity($facility, Facility::factory()->create());
        Dhis2OrgUnitMapping::create(['facility_id' => $facility->id, 'org_unit_id' => self::ORG_UNIT]);
        $this->mapElements();
        $admin = $this->platformAdmin();

        $this->actingAsAdmin($admin)->get(route('portals.admin.dhis2'))->assertOk()
            ->assertSee(__('dhis2.not_configured'))->assertDontSee(__('dhis2.test_connection'));

        $report = $this->draftFor($facility, $admin);
        $this->assertNotEmpty($report->items);

        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.drafts.approve', $report->id));
        $this->actingAsAdmin($admin)->get(route('portals.admin.dhis2.drafts.show', $report->id))->assertOk()->assertSee(__('dhis2.push_disabled_config'));
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.drafts.push', $report->id))->assertSessionHas('error', __('dhis2.not_configured'));
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.test'))->assertSessionHas('error', __('dhis2.not_configured'));

        Http::assertNothingSent();
        $this->assertSame('approved_for_submission', $report->fresh()->status);
    }

    public function test_test_connection_calls_system_info_and_mappings_are_saved(): void
    {
        $this->configure();
        $this->fakeDhis2();
        $facility = Facility::factory()->create(['status' => 'active', 'name' => 'Hopital Test Bonamoussadi']);
        $admin = $this->platformAdmin();

        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.test'))->assertSessionHas('dhis2_connection');
        Http::assertSent(fn (HttpRequest $r) => $r->url() === 'https://dhis.test/api/system/info');

        $this->actingAsAdmin($admin)->get(route('portals.admin.dhis2.mappings', ['q' => 'Bonamoussadi']))->assertOk()->assertSee('Hopital Test Bonamoussadi');
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.mappings.org-unit'), ['facility_id' => $facility->id, 'org_unit_id' => 'not a uid'])->assertSessionHasErrors('org_unit_id');
        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.mappings.org-unit'), ['facility_id' => $facility->id, 'org_unit_id' => self::ORG_UNIT])->assertSessionHas('success');
        $this->assertSame(self::ORG_UNIT, Dhis2OrgUnitMapping::where('facility_id', $facility->id)->value('org_unit_id'));
        $this->assertTrue(AuditEvent::where('action_type', 'dhis2.mapping.org_unit_set')->where('facility_id', $facility->id)->exists());

        $this->actingAsAdmin($admin)->post(route('portals.admin.dhis2.mappings.data-element'), ['indicator_code' => 'referrals_sent', 'data_element_id' => 'DeRefSent01'])->assertSessionHas('success');
        $this->actingAsAdmin($admin)->get(route('portals.admin.dhis2.mappings'))->assertOk()->assertSee('DeRefSent01');
    }

    public function test_the_scheduled_command_only_generates_drafts_and_push_takes_approved_reports_only(): void
    {
        $this->configure();
        $this->fakeDhis2();
        $facility = Facility::factory()->create(['status' => 'active']);
        $this->seedActivity($facility, Facility::factory()->create());
        Dhis2OrgUnitMapping::create(['facility_id' => $facility->id, 'org_unit_id' => self::ORG_UNIT]);
        $this->mapElements();

        $code = $this->withoutMockingConsoleOutput()->artisan('opescare:push-dhis2', ['--month' => '2026-08']); $this->assertSame(0, $code);
        $report = PublicHealthReport::where('facility_id', $facility->id)->sole();
        $this->assertSame('draft', $report->status);

        $this->assertSame(0, $this->artisan('opescare:push-dhis2', ['--month' => '2026-08', '--push' => true]), \Illuminate\Support\Facades\Artisan::output());
        Http::assertNothingSent();
        $this->assertSame('draft', $report->fresh()->status);
    }

    public function test_facility_admin_is_refused(): void
    {
        [$facilityAdmin, $facility] = $this->staffAt(null, 'facility_admin');
        $report = null;

        $this->actingAtFacility($facilityAdmin, $facility)->get(route('portals.admin.dhis2'))->assertForbidden();
        $this->actingAtFacility($facilityAdmin, $facility)->get(route('portals.admin.dhis2.mappings'))->assertForbidden();
        $this->actingAtFacility($facilityAdmin, $facility)->post(route('portals.admin.dhis2.generate'), ['month' => '2026-08', 'facility_id' => $facility->id])->assertForbidden();
        $this->assertSame(0, PublicHealthReport::count());
    }
}
