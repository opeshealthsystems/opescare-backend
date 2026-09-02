<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * Wire contract for an App\Models\PaymentPlanInstallment. Add fields here
 * deliberately; never expose the model directly.
 */
class PaymentPlanInstallmentResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'payment_plan_id'   => $this->payment_plan_id,
            'due_date'          => $this->due_date?->toISOString(),
            'amount'            => $this->amount,
            'paid_amount'       => $this->paid_amount,
            'status'            => $this->status,
            'paid_at'           => $this->paid_at?->toISOString(),
            'payment_reference' => $this->payment_reference,
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}
