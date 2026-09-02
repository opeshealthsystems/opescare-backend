<?php

namespace App\Services\Pharmacy;

use App\Models\Prescription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pharmacy Routing Service
 *
 * Routes electronic prescriptions to dispensing pharmacies.
 * Supports: direct HTTP webhook delivery (for integrated pharmacy systems)
 * and manual pharmacy assignment (for non-integrated pharmacies).
 */
class PharmacyRoutingService
{
    /**
     * Assign a pharmacy to a prescription and optionally route it electronically.
     */
    public function assignPharmacy(
        string $prescriptionId,
        array  $pharmacyDetails,
        bool   $sendElectronically = false
    ): Prescription {
        $prescription = Prescription::findOrFail($prescriptionId);

        $prescription->update([
            'dispensing_pharmacy_name'    => $pharmacyDetails['name'],
            'dispensing_pharmacy_address' => $pharmacyDetails['address'] ?? null,
            'dispensing_pharmacy_phone'   => $pharmacyDetails['phone'] ?? null,
            'dispensing_pharmacy_fax'     => $pharmacyDetails['fax'] ?? null,
            'pharmacy_routing_status'     => 'pending',
        ]);

        if ($sendElectronically) {
            $this->sendElectronically($prescription);
        }

        return $prescription->fresh();
    }

    /**
     * Send a prescription to a pharmacy via webhook.
     * The pharmacy must have a registered webhook URL in PHARMACY_ROUTING_WEBHOOK_URL
     * or a per-pharmacy webhook configured in the future.
     */
    public function sendElectronically(Prescription $prescription): bool
    {
        $webhookUrl = config('services.pharmacy.routing_webhook_url');

        if (!$webhookUrl) {
            Log::warning('PharmacyRoutingService: No webhook URL configured. Cannot route electronically.', [
                'prescription_id' => $prescription->id,
            ]);
            return false;
        }

        $payload = $this->buildPayload($prescription);

        try {
            $response = Http::timeout(10)
                ->withHeader('X-OpesCare-Signature', $this->sign($payload))
                ->post($webhookUrl, $payload);

            if ($response->successful()) {
                $prescription->update([
                    'pharmacy_routing_status'  => 'sent',
                    'pharmacy_routing_sent_at' => now(),
                ]);
                Log::info('Prescription routed to pharmacy', ['prescription_id' => $prescription->id]);
                return true;
            }

            Log::error('Pharmacy routing HTTP error', [
                'prescription_id' => $prescription->id,
                'status'          => $response->status(),
                'body'            => $response->body(),
            ]);
            $prescription->update(['pharmacy_routing_status' => 'rejected']);
            return false;

        } catch (\Throwable $e) {
            Log::error('Pharmacy routing exception', [
                'prescription_id' => $prescription->id,
                'error'           => $e->getMessage(),
            ]);
            $prescription->update(['pharmacy_routing_status' => 'rejected']);
            return false;
        }
    }

    /**
     * Mark prescription as confirmed by pharmacy.
     */
    public function confirmReceipt(string $prescriptionId): Prescription
    {
        $prescription = Prescription::findOrFail($prescriptionId);
        $prescription->update([
            'pharmacy_routing_status' => 'confirmed',
            'pharmacy_confirmed_at'   => now(),
        ]);
        return $prescription->fresh();
    }

    /**
     * Mark prescription as dispensed.
     */
    public function markDispensed(string $prescriptionId): Prescription
    {
        $prescription = Prescription::findOrFail($prescriptionId);
        $prescription->update([
            'pharmacy_routing_status' => 'dispensed',
            'dispensed_at'            => now(),
        ]);
        return $prescription->fresh();
    }

    private function buildPayload(Prescription $prescription): array
    {
        return [
            'prescription_id' => $prescription->id,
            'patient_id'      => $prescription->patient_id,
            'facility_id'     => $prescription->facility_id,
            'prescribed_at'   => $prescription->prescribed_at?->toIso8601String(),
            'expires_at'      => $prescription->expires_at?->toIso8601String(),
            'items'           => $prescription->items()->get()->map(fn($item) => [
                'drug_name'  => $item->drug_name,
                'drug_code'  => $item->drug_code,
                'dose'       => $item->dose,
                'frequency'  => $item->frequency,
                'route'      => $item->route,
                'quantity'   => $item->quantity,
                'duration'   => $item->duration,
                'notes'      => $item->notes,
            ])->all(),
        ];
    }

    private function sign(array $payload): string
    {
        return hash_hmac('sha256', json_encode($payload), config('app.key'));
    }
}
