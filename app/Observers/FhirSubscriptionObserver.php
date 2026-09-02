<?php

namespace App\Observers;

use App\Jobs\SendFhirSubscriptionNotificationJob;
use App\Models\FhirSubscription;
use App\Modules\Fhir\Services\FhirService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * FhirSubscriptionObserver
 *
 * Watches clinical domain models for changes and dispatches FHIR Subscription
 * notifications to active REST-hook subscribers whose criteria match.
 *
 * Resource-type to criteria mapping:
 *   Patient          → criteria contains 'Patient'
 *   Encounter/Visit  → criteria contains 'Encounter'
 *   DiagnosticReport → criteria contains 'DiagnosticReport'
 *   MedicationRequest→ criteria contains 'MedicationRequest'
 *   Immunization     → criteria contains 'Immunization'
 *   AllergyIntolerance→criteria contains 'AllergyIntolerance'
 *   Condition        → criteria contains 'Condition'
 *
 * Delivery is async (queued) so it never blocks the originating request.
 */
class FhirSubscriptionObserver
{
    /**
     * Map of Eloquent model class → FHIR resource type.
     * This observer is registered for each of these models individually.
     */
    private const RESOURCE_TYPE_MAP = [
        \App\Models\Patient::class           => 'Patient',
        \App\Models\Visit::class             => 'Encounter',
        \App\Models\LabOrder::class          => 'DiagnosticReport',
        \App\Models\Prescription::class      => 'MedicationRequest',
        \App\Models\ImmunizationRecord::class=> 'Immunization',
        \App\Models\AllergyRecord::class     => 'AllergyIntolerance',
        \App\Models\Diagnosis::class         => 'Condition',
    ];

    public function __construct(private readonly FhirService $fhirService) {}

    /**
     * Handle model created / updated events.
     * Both create and update trigger subscription delivery (FHIR "change" event).
     */
    public function created(Model $model): void
    {
        $this->dispatch($model);
    }

    public function updated(Model $model): void
    {
        $this->dispatch($model);
    }

    // ──────────────────────────────────────────────────────────────────────────

    private function dispatch(Model $model): void
    {
        $resourceType = self::RESOURCE_TYPE_MAP[get_class($model)] ?? null;

        if (! $resourceType) {
            return; // Not a mapped FHIR resource
        }

        try {
            $payload = $this->buildPayload($resourceType, $model);
        } catch (\Throwable $e) {
            // Mapper failure must not roll back the original transaction
            Log::warning('FhirSubscriptionObserver: could not build payload', [
                'resource_type' => $resourceType,
                'model_id'      => $model->getKey(),
                'error'         => $e->getMessage(),
            ]);
            return;
        }

        // Find active subscriptions matching this resource type
        $subscriptions = FhirSubscription::where('status', 'active')
            ->where('channel_type', 'rest-hook')
            ->where(function ($q) use ($resourceType) {
                // Criteria format: "ResourceType" or "ResourceType?param=value"
                $q->where('criteria', $resourceType)
                  ->orWhere('criteria', 'like', $resourceType . '?%');
            })
            ->get();

        foreach ($subscriptions as $subscription) {
            SendFhirSubscriptionNotificationJob::dispatch(
                $subscription->id,
                $resourceType,
                $model->getKey(),
                $payload,
            )->onQueue('fhir-notifications');
        }
    }

    /**
     * Build the FHIR resource payload for delivery.
     * Each resource type uses its existing mapper via FhirService.
     */
    private function buildPayload(string $resourceType, Model $model): array
    {
        return match ($resourceType) {
            'Patient'              => $this->fhirService->patient($model),
            'Encounter'            => $this->fhirService->encounter($model),
            'DiagnosticReport'     => $this->fhirService->diagnosticReport($model),
            'MedicationRequest'    => $this->fhirService->medicationRequest($model),
            'Immunization'         => $this->fhirService->immunization($model),
            'AllergyIntolerance'   => $this->fhirService->allergyIntolerance($model),
            'Condition'            => $this->fhirService->condition($model),
            default                => [],
        };
    }
}
