<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Patient;
use App\Models\User;
use App\Modules\Support\Services\SupportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportHelpdeskPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_patient_support_ticket_redacts_patient_identifiers_and_audits_creation()
    {
        [$facility, $patient, $agent] = $this->supportActors();

        $ticket = app(SupportService::class)->createTicket([
            'requester_type' => 'patient',
            'requester_id' => $patient->id,
            'facility_id' => $facility->id,
            'category' => 'account_access',
            'priority' => 'normal',
            'subject' => 'Cannot access my profile',
            'description' => 'My Health ID is OPC-123456 and my phone is +2348012345678. Please help.',
        ], $agent->id);

        $this->assertEquals('open', $ticket->status);
        $this->assertStringNotContainsString('OPC-123456', $ticket->description_redacted);
        $this->assertStringNotContainsString('+2348012345678', $ticket->description_redacted);
        $this->assertStringContainsString('[REDACTED_HEALTH_ID]', $ticket->description_redacted);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'support_ticket',
            'resource_id' => $ticket->id,
            'action_type' => 'create',
            'actor_id' => $agent->id,
        ]);
    }

    public function test_support_api_accepts_facility_and_developer_tickets_with_sla()
    {
        [$facility, $patient, $agent] = $this->supportActors();

        $clientHeaders = [
            'X-Client-ID'     => 'test_client_id',
            'X-Client-Secret' => 'test_client_secret',
        ];

        $facilityTicket = $this->withHeaders($clientHeaders)->postJson('/api/v1/support/tickets', [
            'requester_type' => 'facility',
            'requester_id' => $facility->id,
            'facility_id' => $facility->id,
            'category' => 'billing',
            'priority' => 'urgent',
            'subject' => 'Cashier issue',
            'description' => 'Cashier cannot close the shift.',
            'actor_id' => $agent->id,
        ]);

        $facilityTicket->assertCreated()
            ->assertJsonPath('data.requester_type', 'facility')
            ->assertJsonPath('data.priority', 'urgent')
            ->assertJsonPath('data.status', 'open');

        $this->assertNotNull($facilityTicket->json('data.sla_due_at'));

        $developerTicket = $this->withHeaders($clientHeaders)->postJson('/api/v1/support/tickets', [
            'requester_type' => 'developer',
            'requester_id' => $agent->id,
            'category' => 'api',
            'priority' => 'normal',
            'subject' => 'Webhook retry question',
            'description' => 'Need help with idempotency keys.',
            'actor_id' => $agent->id,
        ]);

        $developerTicket->assertCreated()
            ->assertJsonPath('data.requester_type', 'developer')
            ->assertJsonPath('data.facility_id', null);
    }

    public function test_ticket_assignment_escalation_resolution_and_incident_conversion_are_audited()
    {
        [$facility, $patient, $agent] = $this->supportActors();
        $service = app(SupportService::class);
        $ticket = $service->createTicket([
            'requester_type' => 'patient',
            'requester_id' => $patient->id,
            'facility_id' => $facility->id,
            'category' => 'privacy',
            'priority' => 'high',
            'subject' => 'Possible privacy incident',
            'description' => 'A message may have exposed my phone 08012345678.',
        ], $agent->id);

        $assigned = $service->assignTicket($ticket, $agent->id, $agent->id);
        $this->assertEquals('assigned', $assigned->status);

        $escalated = $service->escalateTicket($assigned, 'security', $agent->id, 'Potential privacy issue');
        $incident = $service->createIncidentFromTicket($escalated, $agent->id, 'medium');
        $resolved = $service->resolveTicket($escalated->fresh(), $agent->id, 'Contained and answered');

        $this->assertEquals('escalated', $escalated->status);
        $this->assertEquals('resolved', $resolved->status);
        $this->assertEquals($incident->id, $resolved->incident_id);
        $this->assertDatabaseHas('ticket_assignments', [
            'support_ticket_id' => $ticket->id,
            'assigned_to' => $agent->id,
        ]);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'support_ticket',
            'resource_id' => $ticket->id,
            'action_type' => 'escalate',
        ]);
        $this->assertDatabaseHas('audit_events', [
            'resource_type' => 'support_ticket',
            'resource_id' => $ticket->id,
            'action_type' => 'resolve',
        ]);
    }

    public function test_ticket_messages_are_redacted_and_knowledge_base_views_are_counted()
    {
        [$facility, $patient, $agent] = $this->supportActors();
        $service = app(SupportService::class);
        $ticket = $service->createTicket([
            'requester_type' => 'patient',
            'requester_id' => $patient->id,
            'facility_id' => $facility->id,
            'category' => 'account_access',
            'priority' => 'normal',
            'subject' => 'Password help',
            'description' => 'I need a password reset.',
        ], $agent->id);

        $message = $service->addMessage($ticket, [
            'sender_type' => 'agent',
            'sender_id' => $agent->id,
            'body' => 'Do not paste NIN 12345678901 into support chat.',
        ], $agent->id);
        $article = $service->publishKnowledgeBaseArticle([
            'title' => 'How to reset your password',
            'audience' => 'patient',
            'body' => 'Use the password reset screen.',
        ], $agent->id);
        $viewed = $service->recordArticleView($article, $patient->id);

        $this->assertStringNotContainsString('12345678901', $message->body_redacted);
        $this->assertStringContainsString('[REDACTED_NATIONAL_ID]', $message->body_redacted);
        $this->assertEquals(1, $viewed->view_count);
    }

    private function supportActors(): array
    {
        $facility = Facility::create([
            'name' => 'Support Clinic',
            'type' => 'clinic',
            'status' => 'active',
            'license_number' => 'LIC-SUPPORT',
        ]);
        $patient = Patient::create([
            'health_id' => 'OPC-123456',
            'country_code' => 'NG',
            'first_name' => 'Ada',
            'last_name' => 'Okoro',
            'date_of_birth' => '1990-01-01',
            'sex' => 'female',
            'phone_number' => '+2348012345678',
            'identity_status' => 'verified',
            'verification_status' => 'verified',
        ]);
        $agent = User::create([
            'name' => 'Support Agent',
            'email' => 'support-agent@test.com',
            'password' => 'password',
            'primary_facility_id' => $facility->id,
        ]);

        return [$facility, $patient, $agent];
    }
}
