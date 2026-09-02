<?php

namespace Tests\Feature\Mobile;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\WithMobileAuth;

class MobileSupportTest extends TestCase
{
    use RefreshDatabase, WithMobileAuth;

    private Patient $patient;
    private Patient $otherPatient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = Patient::create([
            'health_id'     => 'OC-TST-8200-0001-01',
            'first_name'    => 'Amara',
            'last_name'     => 'Support',
            'sex'           => 'female',
            'date_of_birth' => '1990-02-02',
            'is_demo'       => false,
        ]);

        $this->otherPatient = Patient::create([
            'health_id'     => 'OC-TST-8200-0002-01',
            'first_name'    => 'Bruno',
            'last_name'     => 'Other',
            'sex'           => 'male',
            'date_of_birth' => '1988-06-06',
            'is_demo'       => false,
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->getJson('/api/mobile/support/tickets')->assertStatus(401);
    }

    public function test_contact_returns_configured_channels(): void
    {
        $response = $this->mobileGetJson($this->patient, '/api/mobile/support/contact');

        $response->assertOk()->assertJsonStructure(['data' => ['email', 'phone', 'categories']]);
    }

    public function test_patient_can_create_and_list_own_ticket(): void
    {
        $create = $this->mobilePostJson($this->patient, '/api/mobile/support/tickets', [
            'category'    => 'technical_issue',
            'subject'     => 'App crashes on Health ID tab',
            'description' => 'The app closes every time I open the Health ID QR screen.',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.category', 'technical_issue')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.subject', 'App crashes on Health ID tab');

        $ticketId = $create->json('data.id');

        $list = $this->mobileGetJson($this->patient, '/api/mobile/support/tickets');
        $list->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ticketId);
    }

    public function test_patient_cannot_read_another_patients_ticket(): void
    {
        $create = $this->mobilePostJson($this->patient, '/api/mobile/support/tickets', [
            'category'    => 'billing_question',
            'subject'     => 'Invoice question',
            'description' => 'I was charged twice for the same consultation.',
        ]);
        $ticketId = $create->json('data.id');

        $this->mobileGetJson($this->otherPatient, "/api/mobile/support/tickets/{$ticketId}")
            ->assertStatus(404);
    }

    public function test_patient_can_message_own_ticket(): void
    {
        $create = $this->mobilePostJson($this->patient, '/api/mobile/support/tickets', [
            'category'    => 'appointment_issue',
            'subject'     => 'Reminder not received',
            'description' => 'I did not get a reminder for my appointment yesterday.',
        ]);
        $ticketId = $create->json('data.id');

        $message = $this->mobilePostJson(
            $this->patient,
            "/api/mobile/support/tickets/{$ticketId}/messages",
            ['body' => 'Any update on this?'],
        );

        $message->assertCreated()->assertJsonPath('data.is_mine', true);

        $show = $this->mobileGetJson($this->patient, "/api/mobile/support/tickets/{$ticketId}");
        $show->assertOk()->assertJsonCount(1, 'data.messages');
    }

    public function test_invalid_category_is_rejected(): void
    {
        $this->mobilePostJson($this->patient, '/api/mobile/support/tickets', [
            'category'    => 'not_a_real_category',
            'subject'     => 'Subject',
            'description' => 'Description',
        ])->assertStatus(422);
    }
}
