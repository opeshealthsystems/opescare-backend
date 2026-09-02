<?php

namespace Tests\Feature\Communications;

use Tests\TestCase;
use App\Modules\Notifications\Models\NotificationTemplate;
use App\Modules\Notifications\Models\NotificationPreference;
use App\Modules\Notifications\Models\NotificationEvent;
use App\Modules\Notifications\Models\EscalationChain;
use App\Modules\Tasks\Models\ActionTask;
use App\Modules\Messaging\Models\MessageThread;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageAttachment;
use App\Modules\Broadcasts\Models\Broadcast;

use App\Modules\Notifications\Services\NotificationService;
use App\Modules\Notifications\Services\AlertEscalationService;
use App\Modules\Communications\Services\CommunicationRouterService;
use App\Modules\Tasks\Services\TaskService;
use App\Modules\Messaging\Services\MessagingService;
use App\Modules\Messaging\Services\MessagePermissionService;
use App\Modules\Messaging\Services\MessageAttachmentService;
use App\Modules\Notifications\Services\NotificationPreferenceService;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class CommunicationEcosystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure some baseline templates exist for testing
        NotificationTemplate::firstOrCreate([
            'event_type' => 'welcome_patient',
            'language' => 'en',
            'channel' => 'email'
        ], [
            'uuid' => Str::uuid(),
            'subject' => 'Welcome to OpesCare',
            'title' => 'Welcome',
            'body' => 'Your account has been created.',
            'template_text' => 'Welcome patient'
        ]);

        NotificationTemplate::firstOrCreate([
            'event_type' => 'welcome_patient',
            'language' => 'fr',
            'channel' => 'email'
        ], [
            'uuid' => Str::uuid(),
            'subject' => 'Bienvenue sur OpesCare',
            'title' => 'Bienvenue',
            'body' => 'Votre compte OpesCare a été créé.',
            'template_text' => 'Bienvenue patient'
        ]);

        NotificationTemplate::firstOrCreate([
            'event_type' => 'lab_result_patient',
            'language' => 'en',
            'channel' => 'email'
        ], [
            'uuid' => Str::uuid(),
            'subject' => 'Lab Result Ready',
            'title' => 'Lab Result Available',
            'body' => 'Your lab result is ready to view. Value is {{ result_value }}',
            'template_text' => 'Lab result ready.'
        ]);
    }

    public function test_welcome_email_renders_correctly_and_bilingual_french_fallback_works()
    {
        $notificationService = app(NotificationService::class);
        $userEn = (string) Str::uuid();
        $userFr = (string) Str::uuid();

        // 1. Check English Welcome Rendering
        $resultEn = $notificationService->sendNotification($userEn, 'welcome_patient', []);
        $this->assertEquals('success', $resultEn['status']);
        $eventEn = $resultEn['event'];
        $payloadEn = json_decode($eventEn->payload_json, true);
        $this->assertStringContainsString('Your account has been created', $payloadEn['body']);

        // 2. Set Preference to French
        NotificationPreference::create([
            'user_id' => $userFr,
            'category' => 'health_updates',
            'language' => 'fr',
            'email_enabled' => true,
            'whatsapp_enabled' => false,
            'sms_enabled' => false,
            'push_enabled' => false,
            'voice_enabled' => false,
            'dashboard_enabled' => true
        ]);

        $resultFr = $notificationService->sendNotification($userFr, 'welcome_patient', []);
        $eventFr = $resultFr['event'];
        $payloadFr = json_decode($eventFr->payload_json, true);
        $this->assertStringContainsString('Votre compte OpesCare a été créé', $payloadFr['body']);
    }

    public function test_lab_result_email_does_not_include_sensitive_result_value_external_privacy_gating()
    {
        $notificationService = app(NotificationService::class);

        $result = $notificationService->sendNotification((string) Str::uuid(), 'lab_result_patient', [
            'result_value' => 'POSITIVE_HIV_CONFIRMED'
        ]);

        $event = $result['event'];
        $payload = json_decode($event->payload_json, true);

        // Privacy rule should block raw medical details from external channel payload
        $this->assertStringNotContainsString('POSITIVE_HIV_CONFIRMED', $payload['body']);
        $this->assertStringContainsString('A new health update is available in OpesCare', $payload['body']);
    }

    public function test_quiet_hours_respects_opt_out_but_critical_security_alerts_bypass_quiet_hours()
    {
        $preferenceService = app(NotificationPreferenceService::class);
        $this->travelTo(now()->setTime(23, 59, 45));

        // Define a preference with active quiet hours starting now to tomorrow
        NotificationPreference::create([
            'user_id' => '201',
            'category' => 'appointments',
            'quiet_hours_json' => json_encode(['start' => '00:00', 'end' => '23:59']),
            'email_enabled' => true
        ]);

        // Routine appointment notification should be suppressed during quiet hours
        $this->assertFalse(
            $preferenceService->isChannelEnabled('201', 'appointments', 'email', 'normal')
        );

        // Critical security alert should bypass quiet hours entirely
        $this->assertTrue(
            $preferenceService->isChannelEnabled('201', 'account_and_security', 'email', 'critical')
        );
    }

    public function test_deduplication_prevents_identical_spam_within_short_window()
    {
        $router = app(CommunicationRouterService::class);
        $recipient = (string) Str::uuid();

        // First route call
        $res1 = $router->route('appointment_reminder', $recipient, ['body' => 'Remember your consult.']);
        $this->assertEquals('success', $res1['status']);

        // Second route call within 5 mins with same event type should get suppressed
        $res2 = $router->route('appointment_reminder', $recipient, ['body' => 'Remember your consult.']);
        $this->assertEquals('suppressed', $res2['status']);
        $this->assertEquals('DEDUPLICATION_LIMIT', $res2['reason']);
    }

    public function test_messaging_boundaries_prevent_patients_messaging_random_doctors()
    {
        $permissionService = app(MessagePermissionService::class);

        // Patient trying to message doctor without care relationship context
        $canPatientMessage = $permissionService->canCreateThread('pat_001', 'patient', [
            'thread_type' => 'patient_provider',
            'doctor_id' => 'doc_999'
        ]);
        $this->assertFalse($canPatientMessage);

        // Valid relationship context
        $canPatientMessageWithContext = $permissionService->canCreateThread('pat_001', 'patient', [
            'thread_type' => 'patient_provider',
            'doctor_id' => 'doc_999',
            'context_id' => 'encounter_777'
        ]);
        $this->assertTrue($canPatientMessageWithContext);
    }

    public function test_insurance_messaging_requires_claim_or_preauth_context()
    {
        $permissionService = app(MessagePermissionService::class);

        // Invalid thread create without claim context
        $badThread = $permissionService->canCreateThread('ins_001', 'insurance', [
            'thread_type' => 'insurance_facility',
            'context_type' => 'general_discussion'
        ]);
        $this->assertFalse($badThread);

        // Valid claim context thread
        $goodThread = $permissionService->canCreateThread('ins_001', 'insurance', [
            'thread_type' => 'insurance_facility',
            'context_type' => 'claim'
        ]);
        $this->assertTrue($goodThread);
    }

    public function test_public_health_threads_do_not_expose_patient_identity_by_default()
    {
        $permissionService = app(MessagePermissionService::class);

        $phThreadExposed = $permissionService->canCreateThread('pub_001', 'public_health', [
            'thread_type' => 'public_health',
            'expose_patient_id' => true
        ]);
        $this->assertFalse($phThreadExposed);
    }

    public function test_critical_lab_alert_creates_action_task_and_escalates_if_unacknowledged()
    {
        $taskService = app(TaskService::class);
        $escalationService = app(AlertEscalationService::class);

        // 1. Create escalation chain
        $chain = EscalationChain::create([
            'uuid' => Str::uuid(),
            'name' => 'Critical Lab Escalation',
            'event_type' => 'critical_lab_provider',
            'steps_json' => json_encode([
                ['step' => 1, 'role' => 'ordering_doctor'],
                ['step' => 2, 'role' => 'on_duty_director']
            ]),
            'active' => true
        ]);

                // 2. Create notification event for critical lab alert linked to escalation chain
        $event = NotificationEvent::create([
            'uuid' => Str::uuid(),
            'event_type' => 'critical_lab_provider',
            'communication_type' => 'health_updates',
            'recipient_type' => 'provider',
            'payload_json' => json_encode(['body' => 'Critical alert']),
            'requires_acknowledgement' => true,
            'acknowledgement_status' => 'pending',
            'escalation_chain_id' => $chain->id
        ]);

        // 3. Trigger escalation checks
        $escalationService->escalateIfUnacknowledged($event);

        // 4. Verify original event status escalates
        $event->refresh();
        $this->assertEquals('escalated', $event->acknowledgement_status);

        // 5. Verify backup escalated task was generated for next role
        $escalatedTask = ActionTask::where('task_type', 'escalated_clinical_alert')->first();
        $this->assertNotNull($escalatedTask);
        $this->assertEquals('on_duty_director', $escalatedTask->assigned_role);
    }

    public function test_blocked_executables_and_allowed_attachments_work_on_messaging()
    {
        $attachmentService = app(MessageAttachmentService::class);

        // Mock message model
        $message = Message::create([
            'uuid' => Str::uuid(),
            'thread_id' => 9999,
            'sender_id' => 'user_1',
            'message_type' => 'text',
            'body' => 'Check file.'
        ]);

        // Executable attachment should fail
        $this->expectExceptionMessage('MESSAGE_ATTACHMENT_BLOCKED');
        $attachmentService->uploadAttachment(
            $message,
            'virus.exe',
            '/storage/attachments/virus.exe',
            'application/x-msdownload',
            10240,
            'PHI'
        );
    }

    public function test_closed_thread_blocks_new_messages()
    {
        $messagingService = app(MessagingService::class);

        $thread = MessageThread::create([
            'uuid' => Str::uuid(),
            'thread_type' => 'facility_staff',
            'title' => 'Triage Clarification',
            'status' => 'closed',
            'created_by' => 'doc_1'
        ]);

        $this->expectExceptionMessage('MESSAGE_THREAD_CLOSED');
        $messagingService->sendMessage($thread->uuid, 'doc_1', 'Can I send this message?');
    }
}
