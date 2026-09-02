<?php

namespace App\Modules\OpesCareLite\Services;

use App\Models\Appointment;
use App\Models\BillingAccount;
use App\Models\ClinicalNote;
use App\Models\Diagnosis;
use App\Models\DrugFormulary;
use App\Models\Invoice;
use App\Models\LabResult;
use App\Models\LiteConfig;
use App\Models\LiteConflict;
use App\Models\LiteDevice;
use App\Models\LiteModuleEntitlement;
use App\Models\LiteOfflineEvent;
use App\Models\LiteSyncJob;
use App\Models\Patient;
use App\Models\PharmacyStockAvailability;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\TriageVitalSign;
use App\Models\Visit;
use App\Services\Identity\HealthIdGeneratorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OpesCareLiteService
{
    /**
     * Default modules granted to every new Lite device.
     */
    private const DEFAULT_MODULES = [
        'health_id_lookup',
        'patient_registration',
        'queue_checkin',
        'vitals',
        'consultation_note',
        'basic_prescription',
        'basic_billing',
        'document_qr',
    ];

    /**
     * Actions always blocked in offline-limited mode.
     */
    private const BLOCKED_OFFLINE_ACTIONS = [
        'full_emr_access',
        'insurance_claim_submission',
        'public_health_submission',
        'final_document_issuance',
        'broad_patient_search',
        'non_emergency_consent_expansion',
    ];

    public function __construct(
        private readonly HealthIdGeneratorService $healthIdGenerator,
    ) {}

    // ──────────────────────────────────────────────────────────────────────────
    // Device lifecycle
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Register a new Lite device for a facility.
     * Returns the device with its ONE-TIME device_secret included (store securely on device).
     */
    public function registerDevice(
        string $facilityId,
        string $deviceName,
        string $deviceFingerprint,
        string $authorizedBy,
        string $platform = 'web',
        array  $extraModules = [],
        bool   $offlineAllowed = false,
    ): LiteDevice {
        // Generate a 64-char HMAC secret — returned ONCE, never retrievable again
        $secret = Str::random(64);

        $device = LiteDevice::create([
            'facility_id'        => $facilityId,
            'device_name'        => $deviceName,
            'device_fingerprint' => $deviceFingerprint,
            'environment'        => app()->environment(),
            'status'             => 'pending',
            'platform'           => $platform,
            'authorized_by'      => $authorizedBy,
            'allowed_modes'      => $offlineAllowed
                ? ['online', 'low-bandwidth', 'offline-limited']
                : ['online', 'low-bandwidth'],
            'device_secret'      => $secret,
        ]);

        // Create default config (XAF = CFA Franc, Cameroon)
        $allowedModules = array_unique(array_merge(self::DEFAULT_MODULES, $extraModules));

        LiteConfig::create([
            'lite_device_id'          => $device->id,
            'allowed_modules'         => $allowedModules,
            'language'                => 'en',
            'offline_allowed'         => $offlineAllowed,
            'low_bandwidth_mode'      => false,
            'sync_interval_seconds'   => 300,
            'blocked_offline_actions' => self::BLOCKED_OFFLINE_ACTIONS,
            'currency_code'           => 'XAF',
            'config_updated_at'       => now(),
        ]);

        // Grant module entitlements
        $this->grantModules($device->id, $allowedModules);

        return $device->fresh(['config', 'entitlements']);
    }

    /**
     * Activate a pending device.
     */
    public function activateDevice(LiteDevice $device): LiteDevice
    {
        $device->update([
            'status'       => 'active',
            'activated_at' => now(),
        ]);

        return $device->fresh();
    }

    /**
     * Suspend a device (keeps data, blocks access).
     */
    public function suspendDevice(LiteDevice $device, string $reason = ''): void
    {
        $device->update(['status' => 'suspended']);
    }

    /**
     * Revoke a device permanently.
     */
    public function revokeDevice(LiteDevice $device, string $reason): void
    {
        $device->update([
            'status'        => 'revoked',
            'revoked_at'    => now(),
            'revoke_reason' => $reason,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Config
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Get device config payload for the Lite client.
     */
    public function getConfig(LiteDevice $device): array
    {
        $cfg = $device->config;

        return [
            'device_id'               => $device->id,
            'device_name'             => $device->device_name,
            'facility_id'             => $device->facility_id,
            'status'                  => $device->status,
            'allowed_modes'           => $device->allowed_modes ?? ['online'],
            'allowed_modules'         => $cfg?->allowed_modules ?? self::DEFAULT_MODULES,
            'language'                => $cfg?->language ?? 'en',
            'offline_allowed'         => $cfg?->offline_allowed ?? false,
            'low_bandwidth_mode'      => $cfg?->low_bandwidth_mode ?? false,
            'sync_interval_seconds'   => $cfg?->sync_interval_seconds ?? 300,
            'blocked_offline_actions' => $cfg?->blocked_offline_actions ?? self::BLOCKED_OFFLINE_ACTIONS,
            'currency_code'           => $cfg?->currency_code ?? 'XAF',
            'config_updated_at'       => $cfg?->config_updated_at?->toIso8601String(),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sync — push
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Accept and queue offline events pushed from a Lite device.
     * Returns summary of what was accepted / rejected / conflicted.
     */
    public function pushOfflineEvents(LiteDevice $device, array $events): array
    {
        $job = LiteSyncJob::create([
            'lite_device_id' => $device->id,
            'direction'      => 'push',
            'status'         => 'running',
            'started_at'     => now(),
        ]);

        $device->touchSeen();

        $accepted  = 0;
        $rejected  = 0;
        $conflicts = 0;
        $errors    = [];

        foreach ($events as $event) {
            $clientId   = $event['client_id'] ?? Str::uuid()->toString();
            $eventType  = $event['event_type'] ?? 'unknown';
            $payload    = $event['payload'] ?? [];
            $capturedAt = $event['captured_at'] ?? now()->toIso8601String();

            // Block illegal offline actions
            $cfg     = $device->config;
            $blocked = $cfg?->blocked_offline_actions ?? self::BLOCKED_OFFLINE_ACTIONS;
            if (in_array($eventType, $blocked, true)) {
                $rejected++;
                $errors[] = "Blocked offline action: {$eventType}";
                continue;
            }

            // Idempotency — skip duplicates
            $exists = LiteOfflineEvent::where('lite_device_id', $device->id)
                ->where('client_id', $clientId)
                ->first();
            if ($exists) {
                $accepted++;
                continue;
            }

            $offlineEvent = LiteOfflineEvent::create([
                'lite_device_id' => $device->id,
                'facility_id'    => $device->facility_id,
                'event_type'     => $eventType,
                'client_id'      => $clientId,
                'payload'        => $payload,
                'status'         => 'queued',
                'captured_at'    => $capturedAt,
                'received_at'    => now(),
            ]);

            // Process immediately for simple event types
            $result = $this->applyOfflineEvent($offlineEvent);

            if ($result === 'conflict') {
                $conflicts++;
            } elseif ($result === 'applied') {
                $accepted++;
            } else {
                $rejected++;
            }
        }

        $job->update([
            'status'            => 'completed',
            'events_sent'       => count($events),
            'events_applied'    => $accepted,
            'events_rejected'   => $rejected,
            'conflicts_created' => $conflicts,
            'completed_at'      => now(),
        ]);

        return [
            'sync_job_id' => $job->id,
            'accepted'    => $accepted,
            'rejected'    => $rejected,
            'conflicts'   => $conflicts,
            'errors'      => $errors,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sync — pull (delta)
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Build a pull-sync payload for the device: pending updates since last sync.
     *
     * Returns lab results, appointment status changes, and formulary updates
     * for the device's facility since the given timestamp.
     */
    public function pullSync(LiteDevice $device, ?string $since = null): array
    {
        $device->touchSeen();

        $job = LiteSyncJob::create([
            'lite_device_id' => $device->id,
            'direction'      => 'pull',
            'status'         => 'running',
            'started_at'     => now(),
        ]);

        // Parse $since or default to 1 hour ago to avoid returning entire history
        $sinceDate = $since
            ? Carbon::parse($since)
            : now()->subHour();

        // ── Open conflicts ────────────────────────────────────────────────────
        $openConflicts = LiteConflict::where('lite_device_id', $device->id)
            ->where('status', 'open')
            ->with('offlineEvent')
            ->get()
            ->map(fn ($c) => [
                'conflict_id'    => $c->id,
                'event_type'     => $c->offlineEvent?->event_type,
                'conflict_type'  => $c->conflict_type,
                'server_version' => $c->server_version,
                'device_version' => $c->device_version,
                'captured_at'    => $c->offlineEvent?->captured_at?->toIso8601String(),
            ])
            ->all();

        // ── Lab results for patients in this facility ─────────────────────────
        // lab_results has no facility_id — join through lab_orders which does.
        $newLabResults = LabResult::join('lab_orders', 'lab_results.lab_order_id', '=', 'lab_orders.id')
            ->where('lab_orders.facility_id', $device->facility_id)
            ->where('lab_results.resulted_at', '>', $sinceDate)
            ->select([
                'lab_results.id',
                'lab_results.patient_id',
                'lab_results.parameter_name',
                'lab_results.value',
                'lab_results.unit',
                'lab_results.flag',
                'lab_results.resulted_at',
            ])
            ->orderBy('lab_results.resulted_at')
            ->limit(200)
            ->get()
            ->map(fn ($r) => [
                'type'           => 'lab_result',
                'id'             => $r->id,
                'patient_id'     => $r->patient_id,
                'parameter_name' => $r->parameter_name,
                'value'          => $r->value,
                'unit'           => $r->unit,
                'flag'           => $r->flag,
                'resulted_at'    => $r->resulted_at
                    ? Carbon::parse($r->resulted_at)->toIso8601String()
                    : null,
            ])
            ->all();

        // ── Appointment status changes ────────────────────────────────────────
        $updatedAppointments = Appointment::where('facility_id', $device->facility_id)
            ->where('updated_at', '>', $sinceDate)
            ->select(['id', 'patient_id', 'status', 'scheduled_at', 'updated_at'])
            ->orderBy('updated_at')
            ->limit(200)
            ->get()
            ->map(fn ($a) => [
                'type'         => 'appointment',
                'id'           => $a->id,
                'patient_id'   => $a->patient_id,
                'status'       => $a->status,
                'scheduled_at' => $a->scheduled_at?->toIso8601String(),
                'updated_at'   => $a->updated_at?->toIso8601String(),
            ])
            ->all();

        // ── Formulary delta (available drugs at this facility) ────────────────
        $formularyUpdates = DrugFormulary::where('facility_id', $device->facility_id)
            ->where('updated_at', '>', $sinceDate)
            ->select(['id', 'generic_name', 'brand_names', 'strength', 'form', 'is_available', 'updated_at'])
            ->orderBy('updated_at')
            ->limit(500)
            ->get()
            ->map(fn ($d) => [
                'type'         => 'formulary',
                'id'           => $d->id,
                'generic_name' => $d->generic_name,
                'brand_names'  => $d->brand_names ?? [],
                'strength'     => $d->strength,
                'form'         => $d->form,
                'is_available' => $d->is_available,
                'updated_at'   => $d->updated_at?->toIso8601String(),
            ])
            ->all();

        $updates     = array_merge($newLabResults, $updatedAppointments, $formularyUpdates);
        $updateCount = count($updates);

        $job->update([
            'status'       => 'completed',
            'events_sent'  => $updateCount,
            'completed_at' => now(),
        ]);

        return [
            'sync_job_id'    => $job->id,
            'server_time'    => now()->toIso8601String(),
            'since'          => $sinceDate->toIso8601String(),
            'open_conflicts' => $openConflicts,
            'config_stale'   => $this->isConfigStale($device, $since),
            'updates'        => $updates,
            'update_count'   => $updateCount,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Conflict resolution
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Resolve a conflict.
     */
    public function resolveConflict(
        LiteConflict $conflict,
        string       $resolvedBy,
        string       $resolution,
        string       $note = ''
    ): LiteConflict {
        $conflict->update([
            'status'          => $resolution === 'dismiss' ? 'dismissed' : 'resolved',
            'resolved_by'     => $resolvedBy,
            'resolution_note' => $note,
            'resolved_at'     => now(),
        ]);

        return $conflict->fresh();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Admin stats
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Summary stats for admin dashboard.
     */
    public function getAdminStats(string $facilityId): array
    {
        $devices = LiteDevice::where('facility_id', $facilityId)->get();

        return [
            'total_devices'   => $devices->count(),
            'active_devices'  => $devices->where('status', 'active')->count(),
            'pending_devices' => $devices->where('status', 'pending')->count(),
            'revoked_devices' => $devices->whereIn('status', ['revoked', 'suspended', 'lost'])->count(),
            'open_conflicts'  => LiteConflict::whereIn('lite_device_id', $devices->pluck('id'))
                ->where('status', 'open')->count(),
            'pending_events'  => LiteOfflineEvent::whereIn('lite_device_id', $devices->pluck('id'))
                ->where('status', 'queued')->count(),
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private — offline event application
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Apply a single offline event (maps to domain writes).
     * Returns: 'applied'|'conflict'|'rejected'
     */
    private function applyOfflineEvent(LiteOfflineEvent $event): string
    {
        $event->update(['status' => 'processing']);

        try {
            match ($event->event_type) {
                'vitals'               => $this->applyVitalsEvent($event),
                'consultation_note'    => $this->applyConsultationEvent($event),
                'basic_prescription'   => $this->applyPrescriptionEvent($event),
                'basic_billing'        => $this->applyBillingEvent($event),
                'patient_registration' => $this->applyPatientRegistrationEvent($event),
                'stock_update'         => $this->applyStockUpdateEvent($event),
                default                => null,
            };

            $event->update(['status' => 'applied', 'applied_at' => now()]);
            return 'applied';

        } catch (\Throwable $e) {
            Log::warning('Lite offline event could not be applied', [
                'event_id'   => $event->id,
                'event_type' => $event->event_type,
                'error'      => $e->getMessage(),
            ]);

            $event->update(['status' => 'conflict']);

            LiteConflict::create([
                'lite_device_id'        => $event->lite_device_id,
                'lite_offline_event_id' => $event->id,
                'conflict_type'         => 'data_mismatch',
                'device_version'        => $event->payload,
                'server_version'        => null,
                'status'                => 'open',
            ]);

            return 'conflict';
        }
    }

    /**
     * Vitals event — write to triage_vital_signs.
     *
     * triage_record_id and recorded_by have no FK constraints in the migration,
     * so we use the offline event ID and device ID as sentinel references.
     * This preserves all clinical data while avoiding coupling to the full
     * triage record lifecycle.
     */
    private function applyVitalsEvent(LiteOfflineEvent $event): void
    {
        $payload = $event->payload;

        if (empty($payload['patient_id'])) {
            throw new \InvalidArgumentException('patient_id required for vitals event');
        }

        if (! Patient::where('id', $payload['patient_id'])->exists()) {
            throw new \InvalidArgumentException('Patient not found for vitals event');
        }

        TriageVitalSign::create([
            'triage_record_id'    => $event->id,               // offline event ID as sentinel
            'visit_id'            => $payload['visit_id'] ?? null,
            'patient_id'          => $payload['patient_id'],
            'facility_id'         => $event->facility_id,
            'temperature'         => $payload['temperature'] ?? null,
            'pulse_rate'          => $payload['pulse_rate'] ?? null,
            'respiratory_rate'    => $payload['respiratory_rate'] ?? null,
            'systolic_bp'         => $payload['systolic_bp'] ?? null,
            'diastolic_bp'        => $payload['diastolic_bp'] ?? null,
            'oxygen_saturation'   => $payload['oxygen_saturation'] ?? null,
            'weight_kg'           => $payload['weight_kg'] ?? null,
            'height_cm'           => $payload['height_cm'] ?? null,
            'gcs_score'           => $payload['gcs_score'] ?? null,
            'pain_score'          => $payload['pain_score'] ?? null,
            'consciousness_level' => $payload['consciousness_level'] ?? null,
            'recorded_by'         => $payload['recorded_by'] ?? $event->lite_device_id,
            'recorded_at'         => $payload['recorded_at'] ?? $event->captured_at,
        ]);
    }

    /**
     * Consultation note event — creates a Visit and a signed ClinicalNote.
     *
     * Uses the system provider ID for the provider_id FK on clinical_notes,
     * since Lite devices do not map to individual user accounts.
     */
    private function applyConsultationEvent(LiteOfflineEvent $event): void
    {
        $payload = $event->payload;

        if (empty($payload['patient_id'])) {
            throw new \InvalidArgumentException('patient_id required for consultation event');
        }

        if (! Patient::where('id', $payload['patient_id'])->exists()) {
            throw new \InvalidArgumentException('Patient not found for consultation event');
        }

        $systemProviderId = $this->ensureSystemProvider();

        $visit = Visit::create([
            'patient_id'  => $payload['patient_id'],
            'facility_id' => $event->facility_id,
            'provider_id' => null,       // Lite device — no direct user mapping
            'visit_type'  => $payload['visit_type'] ?? 'outpatient',
            'status'      => 'completed',
            'started_at'  => $payload['visited_at'] ?? $event->captured_at,
        ]);

        ClinicalNote::create([
            'visit_id'                   => $visit->id,
            'provider_id'                => $systemProviderId,
            'history_of_present_illness' => $payload['complaint'] ?? $payload['history'] ?? 'Offline consultation (Lite)',
            'examination_findings'       => $payload['examination'] ?? 'Recorded offline via OpesCare Lite',
            'treatment_plan'             => $payload['plan'] ?? 'See prescription',
            'status'                     => 'signed',
            'signed_at'                  => now(),
        ]);
    }

    /**
     * Prescription event — creates a Prescription with one or more items.
     */
    private function applyPrescriptionEvent(LiteOfflineEvent $event): void
    {
        $payload = $event->payload;

        if (empty($payload['patient_id']) || empty($payload['items'])) {
            throw new \InvalidArgumentException('patient_id and items required for prescription event');
        }

        if (! Patient::where('id', $payload['patient_id'])->exists()) {
            throw new \InvalidArgumentException('Patient not found for prescription event');
        }

        $prescription = Prescription::create([
            'patient_id'    => $payload['patient_id'],
            'facility_id'   => $event->facility_id,
            'visit_id'      => $payload['visit_id'] ?? null,
            'prescribed_by' => null,   // Lite device — no user mapping; prescribed_by is nullable
            'status'        => 'active',
            'notes'         => $payload['notes'] ?? 'Issued offline via OpesCare Lite',
            'prescribed_at' => $payload['prescribed_at'] ?? $event->captured_at,
        ]);

        foreach ($payload['items'] as $item) {
            if (empty($item['drug_name'])) {
                continue;
            }

            PrescriptionItem::create([
                'prescription_id' => $prescription->id,
                'drug_name'       => $item['drug_name'],
                'drug_code'       => $item['drug_code'] ?? null,
                'dose'            => $item['dose'] ?? null,
                'frequency'       => $item['frequency'] ?? null,
                'route'           => $item['route'] ?? null,
                'duration_days'   => $item['duration_days'] ?? null,
                'quantity'        => $item['quantity'] ?? null,
                'status'          => 'pending',
            ]);
        }
    }

    /**
     * Billing event — creates a BillingAccount (if not exists) and an Invoice.
     */
    private function applyBillingEvent(LiteOfflineEvent $event): void
    {
        $payload = $event->payload;

        if (empty($payload['patient_id']) || !isset($payload['amount'])) {
            throw new \InvalidArgumentException('patient_id and amount required for billing event');
        }

        if (! Patient::where('id', $payload['patient_id'])->exists()) {
            throw new \InvalidArgumentException('Patient not found for billing event');
        }

        // Ensure a billing account exists for this patient × facility
        $account = BillingAccount::firstOrCreate(
            [
                'patient_id'  => $payload['patient_id'],
                'facility_id' => $event->facility_id,
            ],
            [
                'status'                     => 'active',
                'outstanding_balance_amount' => 0,
            ]
        );

        $amount = (float) $payload['amount'];

        Invoice::create([
            'billing_account_id'             => $account->id,
            'patient_id'                     => $payload['patient_id'],
            'facility_id'                    => $event->facility_id,
            'visit_id'                       => $payload['visit_id'] ?? null,
            'invoice_number'                 => 'LITE-' . strtoupper(Str::random(8)),
            'status'                         => 'issued',
            'subtotal_amount'                => $amount,
            'discount_amount'                => 0,
            'insurance_covered_amount'       => 0,
            'patient_responsibility_amount'  => $amount,
            'paid_amount'                    => 0,
            'refunded_amount'                => 0,
            'balance_amount'                 => $amount,
            'issued_at'                      => $payload['billed_at'] ?? $event->captured_at,
        ]);

        // Update outstanding balance
        $account->increment('outstanding_balance_amount', $amount);
    }

    /**
     * Patient registration event — generates a Health ID and creates a Patient record.
     *
     * This is the first-point-of-care registration for previously unregistered
     * patients captured on an offline Lite device.
     */
    private function applyPatientRegistrationEvent(LiteOfflineEvent $event): void
    {
        $payload = $event->payload;

        if (empty($payload['first_name']) || empty($payload['last_name'])) {
            throw new \InvalidArgumentException('first_name and last_name required for patient registration');
        }

        // Idempotency: if a patient was already registered from this offline event
        // (e.g. re-processed after a crash), skip silently.
        $existingHealthId = $payload['health_id'] ?? null;
        if ($existingHealthId && Patient::where('health_id', $existingHealthId)->exists()) {
            return;
        }

        // Generate unique Health ID (CM-HID-XXXX-XXXX-XXXX)
        $healthId = $this->healthIdGenerator->generate('CM');

        Patient::create([
            'health_id'              => $healthId,
            'first_name'             => $payload['first_name'],
            'last_name'              => $payload['last_name'],
            'middle_name'            => $payload['middle_name'] ?? null,
            'date_of_birth'          => $payload['date_of_birth'] ?? null,
            'is_dob_estimated'       => (bool) ($payload['is_dob_estimated'] ?? false),
            'sex'                    => $payload['sex'] ?? null,
            'blood_group'            => $payload['blood_group'] ?? null,
            'phone_number'           => $payload['phone_number'] ?? null,
            'country_code'           => $payload['country_code'] ?? 'CM',
            'address'                => $payload['address'] ?? null,
            'identity_status'        => 'registered_lite',
            'verification_status'    => 'unverified',
            'verified_by_facility_id'=> $event->facility_id,
        ]);
    }

    /**
     * Stock update event — adjusts availability status of a pharmacy item.
     *
     * Quantity delta from the device is used to derive an availability status.
     * Full inventory management (exact quantities) requires an online sync.
     */
    private function applyStockUpdateEvent(LiteOfflineEvent $event): void
    {
        $payload = $event->payload;

        if (empty($payload['item_id']) || !isset($payload['quantity_delta'])) {
            throw new \InvalidArgumentException('item_id and quantity_delta required for stock update');
        }

        $stock = PharmacyStockAvailability::where('facility_id', $event->facility_id)
            ->where(function ($q) use ($payload) {
                $q->where('id', $payload['item_id'])
                  ->orWhere('local_medicine_code', $payload['item_id']);
            })
            ->first();

        if (! $stock) {
            // Item not in facility formulary — log but don't fail (create conflict would be wrong)
            Log::info('Lite stock update: item not found in facility', [
                'facility_id' => $event->facility_id,
                'item_id'     => $payload['item_id'],
            ]);
            return;
        }

        $delta = (int) $payload['quantity_delta'];

        // Derive availability status from the delta direction
        // Full quantity tracking requires an online connection
        $availabilityStatus = match (true) {
            $delta < 0 && abs($delta) >= 10 => 'low',
            $delta < 0                       => 'low',
            $delta > 0                       => 'available',
            default                          => $stock->availability_status,
        };

        $stock->update([
            'availability_status' => $availabilityStatus,
            'last_updated_at'     => $payload['updated_at'] ?? $event->captured_at,
            'source_system'       => 'opescare_lite',
            'freshness_status'    => 'stale', // full quantity sync required
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Ensure the system provider user exists and return its ID.
     * Used as provider_id for Lite-sourced clinical records (no direct user mapping).
     */
    private function ensureSystemProvider(): string
    {
        $id = config('opescare.system_provider_id', '00000000-0000-0000-0000-000000000001');

        DB::table('users')->insertOrIgnore([
            'id'         => $id,
            'name'       => 'System Provider (Lite)',
            'email'      => $id . '@system.opescare.local',
            'password'   => bcrypt(Str::random(64)),
            'status'     => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * Grant module entitlements to a device.
     */
    private function grantModules(string $deviceId, array $modules): void
    {
        foreach ($modules as $moduleKey) {
            LiteModuleEntitlement::updateOrCreate(
                ['lite_device_id' => $deviceId, 'module_key' => $moduleKey],
                ['is_enabled' => true, 'granted_at' => now(), 'revoked_at' => null],
            );
        }
    }

    /**
     * Check if device config is stale relative to a since timestamp.
     */
    private function isConfigStale(LiteDevice $device, ?string $since): bool
    {
        if (! $since) {
            return true;
        }
        $configUpdated = $device->config?->config_updated_at;
        if (! $configUpdated) {
            return false;
        }
        return $configUpdated->isAfter($since);
    }
}
