<?php

namespace App\Modules\Notifications\Services;

use Illuminate\Support\Facades\Log;

class SmsNotificationService
{
    /**
     * Send an SMS to a phone number.
     *
     * Uses Twilio if TWILIO_SID / TWILIO_TOKEN / TWILIO_FROM are configured.
     * Falls back to writing to the `sms` log channel (storage/logs/sms.log)
     * so local development and CI work without any credentials.
     */
    public function send(string $to, string $body): void
    {
        if ($this->twilioConfigured()) {
            $this->sendViaTwilio($to, $body);
        } else {
            $this->logSms($to, $body);
        }
    }

    private function twilioConfigured(): bool
    {
        return !empty(config('services.twilio.sid'))
            && !empty(config('services.twilio.token'))
            && !empty(config('services.twilio.from'));
    }

    private function sendViaTwilio(string $to, string $body): void
    {
        // Guard: Twilio SDK may not be installed in dev (require twilio/sdk in production)
        if (!class_exists(\Twilio\Rest\Client::class)) {
            $this->logSms($to, $body);
            return;
        }

        $client = new \Twilio\Rest\Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );

        $client->messages->create($to, [
            'from' => config('services.twilio.from'),
            'body' => $body,
        ]);
    }

    /**
     * The simulated-send log never carries a usable secret or a full phone
     * number, in ANY environment: the body has every run of 4+ digits (OTP,
     * PIN, reference codes) replaced, and the number keeps only its last 3
     * digits. A log file is not a secure store; sms.log once held live OTPs.
     */
    private function logSms(string $to, string $body): void
    {
        Log::channel('sms')->info('SMS [simulated]', [
            'to'     => self::maskPhone($to),
            'body'   => self::redactBody($body),
            'length' => mb_strlen($body),
        ]);
    }

    /** "+237 677 123 456" -> "***456". Never returns more than 3 digits. */
    public static function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        return $digits === '' ? '***' : '***'.substr($digits, -3);
    }

    /** Replace every run of 4 or more digits with a fixed placeholder. */
    public static function redactBody(string $body): string
    {
        return preg_replace('/\d{4,}/', '[redacted]', $body) ?? '[redacted]';
    }
}
