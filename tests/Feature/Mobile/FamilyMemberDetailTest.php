<?php

namespace Tests\Feature\Mobile;

use App\Models\Appointment;
use App\Models\Facility;
use App\Models\FamilyLink;
use App\Models\ImmunizationRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * GET /api/mobile/family/members/{linkId} — a dependent's card and record
 * summary for the guardian who holds the ACTIVE link, and nobody else — plus
 * the card fields GET /api/mobile/family now carries per member.
 */
class FamilyMemberDetailTest extends FamilyTestCase
{
    public function test_detail_returns_the_dependents_card_and_record_summary(): void
    {
        $g     = $this->makeGuardian();
        $child = $this->makeChild();
        $link  = $this->link($g['user'], $child);
        $site  = Facility::factory()->create();

        ImmunizationRecord::create([
            'patient_id'      => $child->id,
            'facility_id'     => $site->id,
            'vaccine_code'    => 'BCG',
            'vaccine_name'    => 'BCG',
            'dose_number'     => 1,
            'administered_at' => '2017-03-13 09:30:00',
            'status'          => 'completed',
        ]);
        ImmunizationRecord::create([
            'patient_id'      => $child->id,
            'facility_id'     => $site->id,
            'vaccine_code'    => 'OPV',
            'vaccine_name'    => 'Oral polio vaccine',
            'dose_number'     => 0,
            'administered_at' => '2017-03-13 09:35:00',
            'status'          => 'entered_in_error',
        ]);

        Appointment::factory()->create(['patient_id' => $child->id, 'scheduled_at' => now()->addDays(4), 'status' => 'scheduled', 'appointment_type' => 'consultation']);
        Appointment::factory()->cancelled()->create(['patient_id' => $child->id, 'scheduled_at' => now()->addDays(2)]);
        Appointment::factory()->create(['patient_id' => $child->id, 'scheduled_at' => now()->subDays(30), 'status' => 'completed']);
        // The guardian's own appointment must not bleed into the child's view.
        Appointment::factory()->create(['patient_id' => $g['patient']->id, 'scheduled_at' => now()->addDays(1)]);

        $response = $this->as($g['headers'])
            ->getJson("/api/mobile/family/members/{$link->id}")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'link'    => ['id', 'relationship', 'access_level', 'status', 'created_at', 'age_transition_expires_at'],
                    'patient' => ['id', 'health_id', 'display_name', 'first_name', 'last_name', 'dob', 'sex', 'blood_type', 'status', 'photo_url', 'qr_payload'],
                    'immunizations' => [['id', 'vaccine_name', 'vaccine_code', 'dose_number', 'administered_at', 'status']],
                    'upcoming_appointments' => [['id', 'starts_at', 'facility_name', 'provider_name', 'status', 'appointment_type']],
                    'records_summary' => ['allergies', 'diagnoses', 'active_prescriptions', 'lab_results', 'immunizations', 'last_visit_at'],
                ],
            ]);

        $response
            ->assertJsonPath('data.link.id', $link->id)
            ->assertJsonPath('data.link.relationship', 'parent')
            ->assertJsonPath('data.link.access_level', 'full')
            ->assertJsonPath('data.link.status', 'active')
            ->assertJsonPath('data.link.age_transition_expires_at', null)
            ->assertJsonPath('data.patient.id', $child->id)
            ->assertJsonPath('data.patient.health_id', $child->health_id)
            ->assertJsonPath('data.patient.display_name', 'Demo Boy')
            ->assertJsonPath('data.patient.first_name', 'Demo')
            ->assertJsonPath('data.patient.last_name', 'Boy')
            ->assertJsonPath('data.patient.dob', '2017-03-12')
            ->assertJsonPath('data.patient.sex', 'male')
            ->assertJsonPath('data.patient.blood_type', 'B+')
            ->assertJsonPath('data.patient.status', 'verified')
            ->assertJsonPath('data.patient.photo_url', null)
            ->assertJsonCount(1, 'data.immunizations')
            ->assertJsonPath('data.immunizations.0.vaccine_code', 'BCG')
            ->assertJsonPath('data.immunizations.0.administered_at', '2017-03-13')
            ->assertJsonCount(1, 'data.upcoming_appointments')
            ->assertJsonPath('data.upcoming_appointments.0.appointment_type', 'consultation')
            ->assertJsonPath('data.upcoming_appointments.0.status', 'scheduled')
            ->assertJsonPath('data.records_summary.immunizations', 1)
            ->assertJsonPath('data.records_summary.allergies', 0)
            ->assertJsonPath('data.records_summary.diagnoses', 0)
            ->assertJsonPath('data.records_summary.active_prescriptions', 0)
            ->assertJsonPath('data.records_summary.lab_results', 0)
            ->assertJsonPath('data.records_summary.last_visit_at', null);

        $this->assertNotNull($response->json('data.patient.qr_payload'), 'A full-access guardian may present the card.');
        $this->assertNotNull($response->json('data.upcoming_appointments.0.starts_at'));
        $this->assertNotNull($response->json('data.upcoming_appointments.0.facility_name'));
    }

    public function test_patient_block_uses_the_same_names_and_values_as_the_health_id_card_endpoint(): void
    {
        $g     = $this->makeGuardian();
        $child = $this->makeChild();
        $link  = $this->link($g['user'], $child);

        $card   = $this->as($this->headersFor($child))->getJson('/api/mobile/health-id-card')->assertOk()->json();
        $detail = $this->as($g['headers'])->getJson("/api/mobile/family/members/{$link->id}")->assertOk()->json('data.patient');

        foreach (['health_id', 'display_name', 'sex', 'dob', 'blood_type', 'photo_url', 'status'] as $field) {
            $this->assertArrayHasKey($field, $detail);
            $this->assertSame($card[$field], $detail[$field], "Field {$field} must match /mobile/health-id-card.");
        }

        // Both readers serve the same card QR: a verification URL for this child.
        $this->assertSame($card['qr_payload'], $detail['qr_payload']);
        $this->assertMatchesRegularExpression('~/verify/qr/qrx_[A-Za-z0-9]+_[A-Za-z0-9]+$~', $detail['qr_payload']);
        $token = \App\Http\Controllers\Api\Mobile\MobileProviderScanController::extractToken($detail['qr_payload']);
        $this->assertSame($child->id, app(\App\Services\Identity\QrTokenService::class)->verifyToken($token)?->patient_id);
    }

    public function test_a_read_only_link_hides_the_qr_payload(): void
    {
        $g     = $this->makeGuardian();
        $child = $this->makeChild();
        $link  = $this->link($g['user'], $child, ['access_level' => FamilyLink::ACCESS_READ_ONLY]);

        $this->as($g['headers'])
            ->getJson("/api/mobile/family/members/{$link->id}")
            ->assertOk()
            ->assertJsonPath('data.link.access_level', 'read_only')
            ->assertJsonPath('data.patient.qr_payload', null)
            ->assertJsonPath('data.patient.health_id', $child->health_id);
    }

    public function test_only_the_guardian_who_holds_the_link_can_open_it(): void
    {
        $g     = $this->makeGuardian();
        $child = $this->makeChild();
        $link  = $this->link($g['user'], $child);

        $stranger = $this->makeGuardian(['first_name' => 'Paul']);
        $this->as($stranger['headers'])
            ->getJson("/api/mobile/family/members/{$link->id}")
            ->assertNotFound()
            ->assertJsonPath('error_code', 'member_not_found');

        // The dependent is not the guardian either.
        $this->as($this->headersFor($child))
            ->getJson("/api/mobile/family/members/{$link->id}")
            ->assertNotFound();

        $this->flushHeaders()->getJson("/api/mobile/family/members/{$link->id}")->assertUnauthorized();

        $this->as($g['headers'])
            ->getJson('/api/mobile/family/members/' . Str::uuid())
            ->assertNotFound();
    }

    public function test_a_link_that_is_not_active_is_not_found(): void
    {
        $g = $this->makeGuardian();

        foreach ([FamilyLink::STATUS_REVOKED, FamilyLink::STATUS_PENDING_INVITE, FamilyLink::STATUS_EXPIRED] as $status) {
            $link = $this->link($g['user'], $this->makeChild(), ['status' => $status]);

            $this->as($g['headers'])
                ->getJson("/api/mobile/family/members/{$link->id}")
                ->assertNotFound();
        }
    }

    public function test_an_age_expired_link_is_forbidden_until_the_dependent_re_consents(): void
    {
        $g    = $this->makeGuardian();
        $teen = $this->makeChild(['date_of_birth' => now()->subYears(18)->subDays(40)->toDateString()]);
        $link = $this->link($g['user'], $teen, ['age_transition_expires_at' => now()->subDay()]);

        $this->as($g['headers'])
            ->getJson("/api/mobile/family/members/{$link->id}")
            ->assertForbidden()
            ->assertJsonPath('error_code', 'dependent_forbidden');
    }

    public function test_every_detail_read_lands_in_the_dependents_access_log_with_the_guardian_as_actor(): void
    {
        $g     = $this->makeGuardian();
        $child = $this->makeChild();
        $link  = $this->link($g['user'], $child);

        $this->as($g['headers'])->getJson("/api/mobile/family/members/{$link->id}")->assertOk();

        $this->assertDatabaseHas('access_logs', [
            'patient_id'    => $child->id,
            'actor_id'      => $g['user']->id,
            'actor_type'    => 'guardian',
            'purpose'       => 'guardian_access',
            'data_category' => 'summary',
            'resource_type' => 'FamilyLink',
            'resource_id'   => $link->id,
            'access_type'   => 'read',
        ]);
    }

    public function test_member_list_carries_the_card_fields_and_a_canonical_access_level(): void
    {
        $g    = $this->makeGuardian();
        $boy  = $this->makeChild();
        $girl = $this->makeChild(['first_name' => 'Demo', 'last_name' => 'Girl', 'sex' => 'female', 'date_of_birth' => '2013-11-05', 'blood_group' => 'A-']);

        $full = $this->link($g['user'], $boy);

        // A row written by the first app release, with the vocabulary the web
        // never knew — inserted raw so the model's mutator cannot clean it up.
        $legacyId = (string) Str::uuid();
        DB::table('family_links')->insert([
            'id'                   => $legacyId,
            'guardian_user_id'     => $g['user']->id,
            'dependent_patient_id' => $girl->id,
            'relationship'         => 'parent',
            'access_level'         => 'view_only',
            'status'               => 'active',
            'created_by'           => 'guardian_invited',
            'notification_prefs'   => '{}',
            'created_at'           => now()->subDay(),
            'updated_at'           => now()->subDay(),
        ]);

        $items = collect($this->as($g['headers'])->getJson('/api/mobile/family')->assertOk()->json('data'))->keyBy('id');

        $this->assertCount(2, $items);

        $boyRow = $items[$full->id];
        $this->assertSame('full', $boyRow['access_level']);
        $this->assertFalse($boyRow['is_read_only']);
        $this->assertSame('active', $boyRow['status']);
        $this->assertSame('parent', $boyRow['relationship']);
        $this->assertArrayHasKey('age_transition_expires_at', $boyRow);
        $this->assertNull($boyRow['age_transition_expires_at']);
        $this->assertSame('male', $boyRow['patient']['sex']);
        $this->assertSame('2017-03-12', $boyRow['patient']['dob']);
        $this->assertSame('B+', $boyRow['patient']['blood_type']);
        $this->assertNull($boyRow['patient']['photo_url']);
        $this->assertSame('verified', $boyRow['patient']['status']);
        $this->assertSame('verified', $boyRow['patient']['identity_status']);
        $this->assertSame($boy->health_id, $boyRow['patient']['health_id']);

        $girlRow = $items[$legacyId];
        $this->assertSame('read_only', $girlRow['access_level'], 'view_only must read as read_only.');
        $this->assertTrue($girlRow['is_read_only']);
        $this->assertSame('female', $girlRow['patient']['sex']);
        $this->assertSame('2013-11-05', $girlRow['patient']['dob']);
    }
}
