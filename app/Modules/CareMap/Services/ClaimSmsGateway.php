<?php

namespace App\Modules\CareMap\Services;

use App\Modules\Notifications\Services\SmsNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * The SMS leg of facility claiming, with an honest answer to "did it go?".
 *
 * SmsNotificationService::send() returns nothing and, with no provider
 * configured, writes a redacted line to the sms log and carries on. That is
 * right for reminders, but a claim must know whether the listing's phone
 * really received its code: if it did not, the claim goes to the review desk
 * flagged "SMS not delivered" so an administrator calls the listing instead.
 * The code itself is never shown to the claimant on any path.
 *
 * Tests replace this class in the container to observe what was sent.
 */
class ClaimSmsGateway
{
    public function __construct(private readonly SmsNotificationService $sms)
    {
    }

    /** True only when a real provider is configured and installed. */
    public function isLive(): bool
    {
        return ! empty(config('services.twilio.sid'))
            && ! empty(config('services.twilio.token'))
            && ! empty(config('services.twilio.from'))
            && class_exists(\Twilio\Rest\Client::class);
    }

    /** Returns true when the provider accepted the message. Never throws. */
    public function deliver(string $phone, string $body): bool
    {
        if (! $this->isLive()) {
            // Still logged (redacted) so the attempt is visible in sms.log.
            $this->sms->send($phone, $body);

            return false;
        }

        try {
            $this->sms->send($phone, $body);

            return true;
        } catch (\Throwable $e) {
            Log::warning('facility_claim_sms_failed', [
                'to'    => SmsNotificationService::maskPhone($phone),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
