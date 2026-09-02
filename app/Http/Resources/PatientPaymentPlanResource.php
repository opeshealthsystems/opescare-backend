<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\PatientPaymentPlan. Add fields here deliberately;
 * never expose the model directly.
 */
class PatientPaymentPlanResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'patient_id'         => $this->patient_id,
            'invoice_id'         => $this->invoice_id,
            'facility_id'        => $this->facility_id,
            'total_amount'       => $this->total_amount,
            'down_payment'       => $this->down_payment,
            'installment_amount' => $this->installment_amount,
            'installment_count'  => $this->installment_count,
            'paid_count'         => $this->paid_count,
            'frequency'          => $this->frequency,
            'status'             => $this->status,
            'next_due_date'      => $this->next_due_date?->toISOString(),
            'started_at'         => $this->started_at?->toISOString(),
            'completed_at'       => $this->completed_at?->toISOString(),
            'notes'              => $this->notes,
            'created_at'         => $this->created_at?->toISOString(),
            'updated_at'         => $this->updated_at?->toISOString(),
        ];
    }
}
