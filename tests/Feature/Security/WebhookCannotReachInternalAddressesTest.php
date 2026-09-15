<?php

namespace Tests\Feature\Security;

use App\Jobs\SendWebhookJob;
use App\Models\WebhookDeliveryLog;
use App\Models\WebhookSubscription;
use App\Support\PublicUrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Security scan 2026-09-15, item 5. Partner webhook URLs were POSTed from the
 * server with no destination check, so a URL could target loopback, the
 * private network or cloud metadata, and redirects were followed.
 */
class WebhookCannotReachInternalAddressesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_guard_refuses_internal_non_https_credentialed_and_unresolvable_urls(): void
    {
        foreach ([
            'https://127.0.0.1/hook', 'https://10.0.0.5:8443/hook', 'https://169.254.169.254/latest/meta-data/',
            'https://100.64.0.1/hook', 'https://[::1]/hook', 'https://[fd00::1]/hook', 'https://localhost/hook',
            'http://partner.example.com/hook', 'https://user:pass@partner.example.com/hook', 'https://nowhere.invalid/hook',
        ] as $url) {
            $this->assertFalse(PublicUrlGuard::isAllowed($url), "$url must be refused");
        }

        $this->assertTrue(PublicUrlGuard::isAllowed('https://partner.example.com/hooks'));
        $this->assertTrue(PublicUrlGuard::isAllowed('https://93.184.215.14/hooks'));
    }

    public function test_a_delivery_to_an_internal_address_is_never_sent_and_is_not_retried(): void
    {
        Http::fake();

        $subscription = WebhookSubscription::create([
            'client_id'         => 'client_' . Str::random(8),
            'facility_id'       => null,
            'callback_url'      => 'https://169.254.169.254/latest/meta-data/',
            'webhook_secret'    => 'whsec_test',
            'subscribed_events' => ['prescription.issued'],
            'status'            => 'active',
        ]);

        $eventId = (string) Str::uuid();
        (new SendWebhookJob($subscription->id, [
            'event_id'   => $eventId,
            'event_type' => 'prescription.issued',
            'data'       => [],
        ]))->handle();

        Http::assertNothingSent();
        $log = WebhookDeliveryLog::where('event_id', $eventId)->firstOrFail();
        $this->assertSame('failed', $log->status);
        $this->assertSame('blocked_destination', $log->response_body);
    }
}
