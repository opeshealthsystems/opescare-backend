<?php

namespace App\Modules\Connect\Services;

use App\Models\ConsentGrant;
use App\Models\LabOrder;
use App\Models\Patient;
use App\Models\Prescription;
use App\Services\WebhookService;
use Illuminate\Support\Facades\Log;

/**
 * The ONE place a domain write turns into a partner webhook.
 *
 * Three event types exist — exactly the ones the developer portal lets a
 * partner subscribe to (DeveloperExtController::EVENT_TYPES, docs/webhooks):
 *
 *   consent.granted        ConsentService::approveConsent
 *   lab_result.released    LabDeskService::release
 *   prescription.issued    PrescriptionService::issue
 *
 * Every event is facility-scoped: WebhookService::dispatch persists a
 * webhook_events row and queues one SendWebhookJob (queue "webhooks", HMAC-SHA256
 * signed with the subscription's secret, 3 attempts, logged to
 * webhook_delivery_logs) per active subscription whose facility_id is the
 * facility the write happened at. A facility-less (sandbox) app therefore never
 * receives a patient-data event — see docs/DEVELOPER_DASHBOARD_AUDIT.md D-5.
 *
 * Nothing here may fail the domain write. Each publisher runs inside rescue():
 * a broken webhook table, a serialisation error or a queue outage is logged and
 * swallowed; the consent, the released panel or the prescription stands.
 *
 * Payloads carry identifiers the partner already holds or is entitled to (the
 * Health ID of a patient who consented to that facility, order/prescription
 * ids, codes, timestamps). Never names, never values — the partner fetches the
 * resource over the API it is scoped for.
 */
final class WebhookEventPublisher
{
    public const CONSENT_GRANTED      = 'consent.granted';
    public const LAB_RESULT_RELEASED  = 'lab_result.released';
    public const PRESCRIPTION_ISSUED  = 'prescription.issued';

    public static function consentGranted(ConsentGrant $grant): void
    {
        self::publish(self::CONSENT_GRANTED, (string) $grant->facility_id, function () use ($grant) {
            return [
                'grant_id'    => (string) $grant->id,
                'health_id'   => self::healthId($grant->patient_id),
                'facility_id' => (string) $grant->facility_id,
                'scope'       => array_values((array) $grant->scope),
                'status'      => (string) $grant->status,
                'granted_at'  => optional($grant->created_at)->toIso8601String(),
                'expires_at'  => optional($grant->expires_at)->toIso8601String(),
            ];
        });
    }

    public static function labResultReleased(LabOrder $order): void
    {
        self::publish(self::LAB_RESULT_RELEASED, (string) $order->facility_id, function () use ($order) {
            return [
                'lab_order_id' => (string) $order->id,
                'health_id'    => self::healthId($order->patient_id),
                'facility_id'  => (string) $order->facility_id,
                'test_code'    => $order->test_code,
                'test_name'    => $order->test_name,
                'status'       => (string) $order->status,
                'released_at'  => optional($order->resulted_at)->toIso8601String(),
            ];
        });
    }

    public static function prescriptionIssued(Prescription $prescription): void
    {
        self::publish(self::PRESCRIPTION_ISSUED, (string) $prescription->facility_id, function () use ($prescription) {
            return [
                'prescription_id' => (string) $prescription->id,
                'reference'       => $prescription->reference,
                'health_id'       => self::healthId($prescription->patient_id),
                'facility_id'     => (string) $prescription->facility_id,
                'status'          => (string) $prescription->status,
                'item_count'      => (int) $prescription->items()->count(),
                'prescribed_at'   => optional($prescription->prescribed_at)->toIso8601String(),
                'expires_at'      => optional($prescription->expires_at)->toIso8601String(),
            ];
        });
    }

    // ── internals ──────────────────────────────────────────────────────────

    /**
     * Build the payload and hand it to WebhookService, inside rescue(): the
     * caller is a clinical/consent write that must not observe any failure here.
     */
    private static function publish(string $eventType, string $facilityId, \Closure $resource): void
    {
        if ($facilityId === '') {
            // No facility → no subscriber could legitimately receive it.
            return;
        }

        rescue(
            fn () => WebhookService::dispatch($eventType, $resource(), $facilityId),
            fn (\Throwable $e) => Log::warning('webhook_publish_failed', [
                'event_type'  => $eventType,
                'facility_id' => $facilityId,
                'error'       => $e->getMessage(),
            ]),
            report: false,
        );
    }

    /** Exact resolution by primary key — the demo partition is respected. */
    private static function healthId(?string $patientId): ?string
    {
        if (! $patientId) {
            return null;
        }

        return Patient::query()->whereKey($patientId)->value('health_id');
    }
}
