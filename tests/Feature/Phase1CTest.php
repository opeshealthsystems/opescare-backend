<?php

namespace Tests\Feature;

use App\Modules\Notifications\Services\PushNotificationService;
use App\Modules\Notifications\Services\WhatsAppNotificationService;
use Tests\TestCase;

class Phase1CTest extends TestCase
{
    public function test_whatsapp_service_is_not_configured_when_env_is_empty(): void
    {
        config(['services.whatsapp.phone_number_id' => '']);
        config(['services.whatsapp.access_token'    => '']);

        $service = new WhatsAppNotificationService();
        $this->assertFalse($service->isConfigured());
    }

    public function test_whatsapp_service_is_configured_when_env_set(): void
    {
        config(['services.whatsapp.phone_number_id' => '12345678']);
        config(['services.whatsapp.access_token'    => 'EAAtest...']);

        $service = new WhatsAppNotificationService();
        $this->assertTrue($service->isConfigured());
    }

    public function test_push_service_is_not_configured_when_env_is_empty(): void
    {
        config(['services.fcm.project_id'           => '']);
        config(['services.fcm.service_account_json' => '']);

        $service = new PushNotificationService();
        $this->assertFalse($service->isConfigured());
    }

    public function test_whatsapp_send_returns_false_when_not_configured(): void
    {
        config(['services.whatsapp.phone_number_id' => '']);
        config(['services.whatsapp.access_token'    => '']);

        $service = new WhatsAppNotificationService();
        $result  = $service->sendText('237677000000', 'Test message');
        $this->assertFalse($result);
    }

    public function test_push_send_returns_false_when_not_configured(): void
    {
        config(['services.fcm.project_id'           => '']);
        config(['services.fcm.service_account_json' => '']);

        $service = new PushNotificationService();
        $result  = $service->sendToDevice('fake_token', 'Title', 'Body');
        $this->assertFalse($result);
    }
}
