<?php

namespace App\Modules\Notifications\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Business API Notification Service
 *
 * Uses Meta WhatsApp Business Cloud API (Graph API v18+).
 * Docs: https://developers.facebook.com/docs/whatsapp/cloud-api/messages
 *
 * Required env:
 *   WHATSAPP_PHONE_NUMBER_ID — the numeric Phone Number ID from Meta Business dashboard
 *   WHATSAPP_ACCESS_TOKEN    — permanent system user access token
 *   WHATSAPP_API_VERSION     — e.g. v18.0 (default)
 *
 * In production: rotate WHATSAPP_ACCESS_TOKEN every 60 days via RotateSecretsCommand.
 */
class WhatsAppNotificationService
{
    private string $phoneNumberId;
    private string $accessToken;
    private string $apiVersion;
    private string $apiBase;

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
        $this->accessToken   = config('services.whatsapp.access_token', '');
        $this->apiVersion    = config('services.whatsapp.api_version', 'v18.0');
        $this->apiBase       = "https://graph.facebook.com/{$this->apiVersion}";
    }

    /**
     * Send a plain text message to a WhatsApp number.
     *
     * @param  string  $to   Phone number in international format: 237XXXXXXXXX
     * @param  string  $text Plain text body (max 4096 chars)
     * @return bool
     */
    public function sendText(string $to, string $text): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $this->normalizePhone($to),
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => substr($text, 0, 4096),
            ],
        ];

        return $this->send($payload);
    }

    /**
     * Send a template message (e.g. appointment reminder).
     *
     * @param  string  $to           International phone number
     * @param  string  $templateName Approved template name in Meta Business Manager
     * @param  string  $languageCode e.g. 'en_US', 'fr'
     * @param  array   $components   Template variable components (header, body, button)
     * @return bool
     */
    public function sendTemplate(
        string $to,
        string $templateName,
        string $languageCode = 'en_US',
        array  $components = []
    ): bool {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $this->normalizePhone($to),
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $languageCode],
                'components' => $components,
            ],
        ];

        return $this->send($payload);
    }

    /**
     * Send an appointment reminder via WhatsApp template.
     *
     * Assumes a pre-approved template named 'appointment_reminder' with:
     *   Body params: {{1}} = patient name, {{2}} = appointment date/time, {{3}} = facility name
     *
     * @param  string  $to
     * @param  string  $patientName
     * @param  string  $appointmentDateTime  e.g. "Monday, 2 June at 10:00"
     * @param  string  $facilityName
     * @return bool
     */
    public function sendAppointmentReminder(
        string $to,
        string $patientName,
        string $appointmentDateTime,
        string $facilityName
    ): bool {
        return $this->sendTemplate(
            to: $to,
            templateName: 'appointment_reminder',
            languageCode: 'en_US',
            components: [
                [
                    'type'       => 'body',
                    'parameters' => [
                        ['type' => 'text', 'text' => $patientName],
                        ['type' => 'text', 'text' => $appointmentDateTime],
                        ['type' => 'text', 'text' => $facilityName],
                    ],
                ],
            ]
        );
    }

    /**
     * Send a lab result notification (text message — no PHI in the body).
     * Only notifies that results are ready; patient must log in to view.
     *
     * @param  string  $to
     * @param  string  $patientName
     * @return bool
     */
    public function sendLabResultNotification(string $to, string $patientName): bool
    {
        $text = "Hello {$patientName}, your lab results are now available on OpesCare. "
              . "Please log in to your account or visit the facility to review your results. "
              . "Reply STOP to unsubscribe.";

        return $this->sendText($to, $text);
    }

    /**
     * Send a prescription ready notification.
     *
     * @param  string  $to
     * @param  string  $patientName
     * @param  string  $pharmacyName
     * @return bool
     */
    public function sendPrescriptionReady(string $to, string $patientName, string $pharmacyName): bool
    {
        $text = "Hello {$patientName}, your prescription is ready for collection at {$pharmacyName}. "
              . "Please bring your ID when collecting.";

        return $this->sendText($to, $text);
    }

    /**
     * Mark a message as read by its message ID.
     */
    public function markAsRead(string $messageId): bool
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'status'            => 'read',
            'message_id'        => $messageId,
        ];
        return $this->send($payload);
    }

    /**
     * Check if the service is configured (not just stubs).
     */
    public function isConfigured(): bool
    {
        return !empty($this->phoneNumberId) && !empty($this->accessToken);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function send(array $payload): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('WhatsAppNotificationService: not configured — message not sent', [
                'to'   => $payload['to'] ?? null,
                'type' => $payload['type'] ?? null,
            ]);
            return false;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->timeout(10)
                ->post("{$this->apiBase}/{$this->phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                Log::debug('WhatsApp message sent', [
                    'to'         => $payload['to'] ?? null,
                    'message_id' => $response->json('messages.0.id'),
                ]);
                return true;
            }

            Log::error('WhatsApp API error', [
                'status' => $response->status(),
                'error'  => $response->json('error'),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('WhatsApp send exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function normalizePhone(string $phone): string
    {
        // Strip non-digits
        $phone = preg_replace('/\D/', '', $phone);

        // Cameroon: add country code if missing
        if (strlen($phone) === 9) {
            $phone = '237' . $phone;
        }

        // Remove leading + if present in raw
        return ltrim($phone, '+');
    }
}
