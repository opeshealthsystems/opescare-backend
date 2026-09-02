<?php

namespace Tests\Feature;

use App\Models\AuditEvent;
use App\Models\CareFacility;
use App\Models\DocumentTemplate;
use App\Models\Facility;
use App\Models\LabTestAvailability;
use App\Models\OfficialDocument;
use App\Models\Patient;
use App\Models\PharmacyStockAvailability;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Partners\Models\Partner;
use App\Modules\Search\Services\GlobalSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_returns_public_operational_results_without_patient_leak()
    {
        [$patient] = $this->seedSearchRecords();

        $results = app(GlobalSearchService::class)->search('Amina', [
            'actor_id' => (string) Str::uuid(),
            'include_sensitive' => false,
        ]);

        $this->assertFalse(collect($results['results'])->contains(fn ($row) => $row['type'] === 'patient'));
        $this->assertTrue(collect($results['results'])->contains(fn ($row) => $row['type'] === 'facility'));
        $this->assertDatabaseMissing('audit_events', [
            'resource_type' => 'global_search',
            'patient_id' => $patient->id,
        ]);
    }

    public function test_sensitive_patient_search_requires_explicit_scope_and_is_audited()
    {
        [$patient] = $this->seedSearchRecords();
        $actorId = (string) Str::uuid();

        $results = app(GlobalSearchService::class)->search('OC-GS-001', [
            'actor_id' => $actorId,
            'include_sensitive' => true,
            'purpose' => 'care_coordination',
        ]);

        $patientResult = collect($results['results'])->firstWhere('type', 'patient');
        $this->assertNotNull($patientResult);
        $this->assertSame('OC-GS-001', $patientResult['metadata']['health_id']);
        $this->assertSame('Amina S.', $patientResult['title']);
        $this->assertDatabaseHas('audit_events', [
            'actor_id' => $actorId,
            'resource_type' => 'global_search',
            'patient_id' => $patient->id,
            'action_type' => 'search_patient',
        ]);
    }

    public function test_global_search_finds_document_medicine_lab_partner_and_authorized_messages()
    {
        $this->seedSearchRecords();

        $results = app(GlobalSearchService::class)->search('malaria', [
            'actor_id' => 'doctor_1',
            'include_sensitive' => false,
            'authorized_message_user_id' => 'doctor_1',
        ]);

        $types = collect($results['results'])->pluck('type')->all();
        $this->assertContains('document', $types);
        $this->assertContains('medicine', $types);
        $this->assertContains('lab_test', $types);
        $this->assertContains('partner', $types);
        $this->assertContains('message', $types);
    }

    public function test_global_search_api_returns_grouped_results()
    {
        $this->seedSearchRecords();

        $response = $this->withHeaders([
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ])->getJson('/api/v1/admin/global-search?q=malaria&actor_id=doctor_1&authorized_message_user_id=doctor_1');

        $response->assertOk()
            ->assertJsonPath('query', 'malaria')
            ->assertJsonStructure([
                'query',
                'results' => [
                    '*' => ['type', 'title', 'subtitle', 'metadata'],
                ],
                'counts',
            ]);
    }

    private function seedSearchRecords(): array
    {
        $role = \App\Models\Role::firstOrCreate(
            ['name' => 'platform_admin'],
            ['description' => 'Platform Administrator']
        );

        $admin = new \App\Models\User();
        $admin->id = '00000000-0000-0000-0000-000000000001';
        $admin->name = 'Master Admin';
        $admin->email = 'master-admin@test.com';
        $admin->password = 'password';
        $admin->role_id = $role->id;
        $admin->save();

        $patient = Patient::create([
            'health_id' => 'OC-GS-001',
            'first_name' => 'Amina',
            'last_name' => 'Search',
            'sex' => 'female',
            'date_of_birth' => '1994-02-10',
        ]);

        Facility::create([
            'name' => 'Amina Memorial Clinic',
            'type' => 'clinic',
            'status' => 'active',
            'license_number' => 'LIC-GS',
        ]);

        $careFacility = CareFacility::create([
            'facility_name' => 'Global Care Pharmacy',
            'facility_type' => 'pharmacy',
            'license_status' => 'active',
            'verification_status' => 'license_verified',
            'listing_status' => 'active',
            'city' => 'Douala',
            'address' => 'Care Street',
            'phone_primary' => '+237000000',
        ]);

        PharmacyStockAvailability::create([
            'facility_id' => $careFacility->id,
            'medicine_name' => 'Malaria Rapid Relief',
            'generic_name' => 'Artemether Lumefantrine',
            'availability_status' => 'reported_available',
        ]);

        LabTestAvailability::create([
            'facility_id' => $careFacility->id,
            'test_name' => 'Malaria RDT',
            'loinc_code' => 'RDT-MAL',
            'availability_status' => 'available',
        ]);

        $template = DocumentTemplate::create([
            'template_code' => 'GS_DOC',
            'document_type' => 'LAB',
            'language' => 'en',
            'status' => 'published',
            'version' => '1.0',
            'html_template' => '<div>Document</div>',
        ]);
        OfficialDocument::create([
            'document_type' => 'LAB',
            'document_number' => 'LAB-CM-2026-GLOB-A',
            'verification_code' => 'VFY-CM-LAB-2026-MALARIA-A',
            'patient_id' => $patient->id,
            'health_id' => $patient->health_id,
            'template_id' => $template->id,
            'template_version' => '1.0',
            'status' => 'issued',
            'version' => '1.0',
            'title' => 'Malaria Lab Result',
            'payload_json' => ['summary' => 'Malaria result available'],
            'payload_hash' => hash('sha256', 'malaria'),
            'issued_at' => now(),
        ]);

        Partner::create([
            'uuid' => Str::uuid(),
            'partner_type' => 'laboratory',
            'legal_name' => 'Malaria Diagnostics Partner',
            'trade_name' => 'Malaria Diagnostics',
            'status' => 'active',
        ]);

        $thread = MessageThread::create([
            'uuid' => Str::uuid(),
            'thread_type' => 'facility_staff',
            'title' => 'Malaria follow-up',
            'status' => 'open',
            'created_by' => 'doctor_1',
        ]);
        Message::create([
            'uuid' => Str::uuid(),
            'thread_id' => $thread->id,
            'sender_id' => 'doctor_1',
            'message_type' => 'text',
            'body' => 'Please follow up on the malaria result.',
        ]);

        return [$patient];
    }
}
