<?php

namespace App\Modules\Insurance\Services;

use App\Models\ClaimDecision;
use App\Models\ClaimItem;
use App\Models\InsuranceClaim;
use Illuminate\Support\Facades\DB;

class ClaimService
{
    /**
     * Create a new claim draft.
     */
    public function createClaim(array $data): InsuranceClaim
    {
        return DB::transaction(function () use ($data) {
            $claim = InsuranceClaim::create([
                'patient_insurance_policy_id' => $data['policy_id'],
                'invoice_id' => $data['invoice_id'] ?? null,
                'preauthorization_request_id' => $data['preauthorization_request_id'] ?? null,
                'facility_id' => $data['facility_id'],
                'claim_number' => InsuranceClaim::generateClaimNumber(),
                'status' => 'draft',
                'claimed_amount' => $data['claimed_amount'] ?? 0,
                'submitted_by' => $data['actor_id'],
                'submission_notes' => $data['notes'] ?? null,
            ]);

            // Attach line items if provided
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $qty = (int) ($item['quantity'] ?? 1);
                    $unitPrice = (float) ($item['unit_price'] ?? 0);
                    ClaimItem::create([
                        'insurance_claim_id' => $claim->id,
                        'description' => $item['description'],
                        'service_code' => $item['service_code'] ?? null,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'total_price' => round($qty * $unitPrice, 2),
                        'status' => 'pending',
                    ]);
                }

                // Recalculate claimed_amount from items
                $total = $claim->items()->sum(DB::raw('quantity * unit_price'));
                $claim->update(['claimed_amount' => $total]);
            }

            return $claim->load('items');
        });
    }

    /**
     * List claims visible to a user, with optional filters (minimum-necessary fields only).
     */
    public function listForUser($user, array $filters = [])
    {
        $query = InsuranceClaim::query()
            ->select([
                'id', 'claim_number', 'status', 'facility_id',
                'patient_insurance_policy_id', 'invoice_id', 'preauthorization_request_id',
                'claimed_amount', 'approved_amount', 'paid_amount',
                'submitted_at', 'decided_at', 'created_at', 'updated_at',
            ])
            ->orderByDesc('created_at');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['facility_id'])) {
            $query->where('facility_id', $filters['facility_id']);
        }

        if (!empty($filters['policy_id'])) {
            $query->where('patient_insurance_policy_id', $filters['policy_id']);
        }

        $perPage = min(100, max(1, (int) ($filters['per_page'] ?? 25)));

        return $query->paginate($perPage);
    }

    /**
     * Minimum-necessary view of a single claim for insurance users.
     * Returns claim, line items, and decisions only — no unrelated clinical data.
     */
    public function getMinimumNecessaryView(string $claimId, $user): array
    {
        $claim = InsuranceClaim::with(['items', 'decisions', 'payments'])->findOrFail($claimId);

        return [
            'id'                          => $claim->id,
            'claim_number'                => $claim->claim_number,
            'status'                      => $claim->status,
            'facility_id'                 => $claim->facility_id,
            'patient_insurance_policy_id' => $claim->patient_insurance_policy_id,
            'invoice_id'                  => $claim->invoice_id,
            'preauthorization_request_id' => $claim->preauthorization_request_id,
            'claimed_amount'              => $claim->claimed_amount,
            'approved_amount'             => $claim->approved_amount,
            'paid_amount'                 => $claim->paid_amount,
            'submitted_at'                => $claim->submitted_at,
            'decided_at'                  => $claim->decided_at,
            'submission_notes'            => $claim->submission_notes,
            'items'                       => $claim->items->map(fn ($item) => [
                'id'           => $item->id,
                'description'  => $item->description,
                'service_code' => $item->service_code,
                'quantity'     => $item->quantity,
                'unit_price'   => $item->unit_price,
                'total_price'  => $item->total_price,
                'status'       => $item->status,
            ])->all(),
            'decisions'                   => $claim->decisions->map(fn ($d) => [
                'id'              => $d->id,
                'decision'        => $d->decision,
                'approved_amount' => $d->approved_amount,
                'reason'          => $d->reason,
                'decided_at'      => $d->decided_at,
            ])->all(),
            'payments'                    => $claim->payments->map(fn ($p) => [
                'id'        => $p->id,
                'amount'    => $p->amount,
                'paid_at'   => $p->paid_at,
            ])->all(),
            'created_at'                  => $claim->created_at,
            'updated_at'                  => $claim->updated_at,
        ];
    }

    /**
     * Submit a draft claim to the payer.
     */
    public function submit(string $claimId, string $actorId): InsuranceClaim
    {
        $claim = InsuranceClaim::findOrFail($claimId);

        if (!$claim->canBeSubmitted()) {
            throw new \Exception('CLAIM_NOT_SUBMITTABLE');
        }

        $claim->update([
            'status' => 'submitted',
            'submitted_by' => $actorId,
            'submitted_at' => now(),
        ]);

        return $claim->fresh();
    }

    /**
     * Record a payer decision on a claim.
     */
    public function decide(string $claimId, string $actorId, array $data): ClaimDecision
    {
        return DB::transaction(function () use ($claimId, $actorId, $data) {
            $claim = InsuranceClaim::findOrFail($claimId);

            if (!$claim->canReceiveDecision()) {
                throw new \Exception('CLAIM_NOT_REVIEWABLE');
            }

            $decision = ClaimDecision::create([
                'insurance_claim_id' => $claim->id,
                'decided_by' => $actorId,
                'decision' => $data['decision'],
                'approved_amount' => $data['approved_amount'] ?? null,
                'reason' => $data['reason'],
                'missing_information' => $data['missing_information'] ?? null,
                'decided_at' => now(),
            ]);

            $newStatus = match ($data['decision']) {
                'approved' => 'approved',
                'partially_approved' => 'partially_approved',
                'rejected' => 'rejected',
                'more_information_required' => 'more_information_required',
                'disputed' => 'disputed',
                default => 'under_review',
            };

            $updates = [
                'status' => $newStatus,
                'decided_at' => now(),
            ];

            if (isset($data['approved_amount'])) {
                $updates['approved_amount'] = $data['approved_amount'];
            }

            $claim->update($updates);

            return $decision;
        });
    }

    /**
     * Cancel a claim.
     */
    public function cancel(string $claimId, string $actorId): InsuranceClaim
    {
        $claim = InsuranceClaim::findOrFail($claimId);

        if (!in_array($claim->status, ['draft', 'submitted', 'under_review', 'more_information_required'])) {
            throw new \Exception('CLAIM_NOT_CANCELLABLE');
        }

        $claim->update(['status' => 'cancelled']);

        return $claim->fresh();
    }
}
