<?php

namespace App\Modules\Notifications\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Push Notification Service — Firebase Cloud Messaging (FCM) HTTP v1
 *
 * Supports Android (FCM) and iOS (APNs via FCM bridge).
 * Uses OAuth2 service account credentials (FCM HTTP v1 API).
 *
 * Docs: https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages/send
 *
 * Required env:
 *   FCM_PROJECT_ID            — Firebase project ID (e.g. opescare-prod)
 *   FCM_SERVICE_ACCOUNT_JSON  — Full JSON of the service account key file (base64-encoded or raw JSON string)
 *
 * The service account must have the "Firebase Cloud Messaging API" role.
 */
class PushNotificationService
{
    private string $projectId;
    private string $fcmEndpoint;
    private ?array $serviceAccount = null;

    public function __construct()
    {
        $this->projectId   = config('services.fcm.project_id', '');
        $this->fcmEndpoint = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
    }

    /**
     * Send a push notification to a single device token.
     *
     * @param  string  $deviceToken   FCM registration token
     * @param  string  $title         Notification title
     * @param  string  $body          Notification body
     * @param  array   $data          Custom key-value data payload (optional)
     * @param  array   $options       Additional FCM options (android, apns, webpush)
     * @return bool
     */
    public function sendToDevice(
        string $deviceToken,
        string $title,
        string $body,
        array  $data = [],
        array  $options = []
    ): bool {
        $message = array_merge([
            'token'        => $deviceToken,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'android' => [
                'priority'     => 'high',
                'notification' => [
                    'sound'        => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound'             => 'default',
                        'content-available' => 1,
                    ],
                ],
            ],
        ], $options);

        if (!empty($data)) {
            $message['data'] = array_map('strval', $data); // FCM data must be string values
        }

        return $this->send(['message' => $message]);
    }

    /**
     * Send a push notification to a topic (multicast).
     *
     * @param  string  $topic   Topic name (e.g. 'facility_abc123_alerts')
     * @param  string  $title
     * @param  string  $body
     * @param  array   $data
     * @return bool
     */
    public function sendToTopic(
        string $topic,
        string $title,
        string $body,
        array  $data = []
    ): bool {
        $message = [
            'topic'        => $topic,
            'notification' => ['title' => $title, 'body' => $body],
            'android'      => ['priority' => 'high'],
        ];

        if (!empty($data)) {
            $message['data'] = array_map('strval', $data);
        }

        return $this->send(['message' => $message]);
    }

    /**
     * Send a clinical alert push (high priority, with clinical context in data).
     *
     * @param  string  $deviceToken
     * @param  string  $alertType    e.g. 'critical_lab', 'allergy', 'drug_interaction'
     * @param  string  $message
     * @param  string  $patientId
     * @param  string  $alertId
     * @return bool
     */
    public function sendClinicalAlert(
        string $deviceToken,
        string $alertType,
        string $message,
        string $patientId,
        string $alertId
    ): bool {
        return $this->sendToDevice(
            deviceToken: $deviceToken,
            title: '⚠️ Clinical Alert',
            body: $message,
            data: [
                'type'       => 'clinical_alert',
                'alert_type' => $alertType,
                'patient_id' => $patientId,
                'alert_id'   => $alertId,
                'screen'     => 'clinical_alerts',
            ]
        );
    }

    /**
     * Send an appointment reminder push.
     */
    public function sendAppointmentReminder(
        string $deviceToken,
        string $patientName,
        string $dateTime,
        string $appointmentId
    ): bool {
        return $this->sendToDevice(
            deviceToken: $deviceToken,
            title: 'Upcoming Appointment',
            body: "Hi {$patientName}, you have an appointment on {$dateTime}.",
            data: [
                'type'           => 'appointment_reminder',
                'appointment_id' => $appointmentId,
                'screen'         => 'appointments',
            ]
        );
    }

    /**
     * Check if the service is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->projectId) && $this->getServiceAccount() !== null;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function send(array $payload): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('PushNotificationService: FCM not configured — push not sent');
            return false;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error('PushNotificationService: could not obtain FCM access token');
            return false;
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(15)
                ->post($this->fcmEndpoint, $payload);

            if ($response->successful()) {
                Log::debug('FCM push sent', ['message_id' => $response->json('name')]);
                return true;
            }

            Log::error('FCM API error', [
                'status' => $response->status(),
                'error'  => $response->json('error'),
            ]);
            return false;

        } catch (\Throwable $e) {
            Log::error('FCM send exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtain a short-lived OAuth2 Bearer token using the service account.
     * Token is cached for 55 minutes (FCM tokens expire at 60 min).
     */
    private function getAccessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 3300, function () {
            $sa = $this->getServiceAccount();
            if (!$sa) return null;

            $now  = time();
            $jwt  = $this->buildJwt($sa, $now);

            try {
                $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion'  => $jwt,
                ]);

                return $response->ok() ? $response->json('access_token') : null;

            } catch (\Throwable) {
                return null;
            }
        });
    }

    private function buildJwt(array $sa, int $now): string
    {
        $header  = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode([
            'iss'   => $sa['client_email'],
            'sub'   => $sa['client_email'],
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ]));

        $signingInput = "{$header}.{$payload}";
        $privateKey   = openssl_pkey_get_private($sa['private_key']);

        if (!$privateKey) {
            throw new \RuntimeException('FCM: invalid private key in service account JSON');
        }

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $sig = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return "{$signingInput}.{$sig}";
    }

    private function getServiceAccount(): ?array
    {
        if ($this->serviceAccount !== null) {
            return $this->serviceAccount ?: null;
        }

        $raw = config('services.fcm.service_account_json', '');
        if (empty($raw)) {
            $this->serviceAccount = [];
            return null;
        }

        // Support base64-encoded JSON
        if (!str_starts_with(trim($raw), '{')) {
            $raw = base64_decode($raw);
        }

        $parsed = json_decode($raw, true);
        if (!$parsed || empty($parsed['private_key'])) {
            Log::error('PushNotificationService: invalid FCM service account JSON');
            $this->serviceAccount = [];
            return null;
        }

        $this->serviceAccount = $parsed;
        return $parsed;
    }
}
