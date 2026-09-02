<?php
namespace App\Services\Billing;

use App\Models\PatientPaymentPlan;
use App\Models\PaymentPlanInstallment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class PaymentPlanService
{
    private const MISSED_INSTALLMENTS_BEFORE_DEFAULT = 2;

    public function createPlan(array $data): PatientPaymentPlan
    {
        $expected = round(
            (float) ($data['down_payment'] ?? 0) + ((float) $data['installment_amount'] * (int) $data['installment_count']),
            2,
        );
        $actual = round((float) $data['total_amount'], 2);

        if (abs($expected - $actual) > 0.01) {
            throw new InvalidArgumentException(
                "total_amount ({$actual}) must equal down_payment + (installment_amount × installment_count) = {$expected}."
            );
        }

        return DB::transaction(function () use ($data): PatientPaymentPlan {
            $plan = PatientPaymentPlan::create([
                'patient_id'         => $data['patient_id'],
                'invoice_id'         => $data['invoice_id'],
                'facility_id'        => $data['facility_id'],
                'total_amount'       => $data['total_amount'],
                'down_payment'       => $data['down_payment'] ?? 0,
                'installment_amount' => $data['installment_amount'],
                'installment_count'  => $data['installment_count'],
                'paid_count'         => 0,
                'frequency'          => $data['frequency'],
                'status'             => 'active',
                'next_due_date'      => $data['next_due_date'],
                'started_at'         => now(),
                'notes'              => $data['notes'] ?? null,
            ]);

            $this->generateInstallments($plan);

            return $plan->load('installments');
        });
    }

    public function generateInstallments(PatientPaymentPlan $plan): void
    {
        $dueDate      = Carbon::parse($plan->next_due_date);
        $installments = [];

        for ($i = 0; $i < $plan->installment_count; $i++) {
            $installments[] = [
                'id'              => (string) Str::uuid(),
                'payment_plan_id' => $plan->id,
                'due_date'        => $dueDate->format('Y-m-d'),
                'amount'          => $plan->installment_amount,
                'paid_amount'     => 0,
                'status'          => 'pending',
                'created_at'      => now(),
                'updated_at'      => now(),
            ];

            $dueDate = match ($plan->frequency) {
                'weekly'   => $dueDate->copy()->addWeek(),
                'biweekly' => $dueDate->copy()->addWeeks(2),
                'monthly'  => $dueDate->copy()->addMonth(),
            };
        }

        PaymentPlanInstallment::insert($installments);
    }

    public function recordInstallmentPayment(string $installmentId, float $amount, string $reference): PaymentPlanInstallment
    {
        return DB::transaction(function () use ($installmentId, $amount, $reference): PaymentPlanInstallment {
            $installment = PaymentPlanInstallment::lockForUpdate()->findOrFail($installmentId);
            $plan        = PatientPaymentPlan::lockForUpdate()->findOrFail($installment->payment_plan_id);

            if ($plan->status !== 'active') {
                throw new RuntimeException("Payment plan is not active (status: {$plan->status}).");
            }

            if ($installment->status === 'paid') {
                throw new RuntimeException("Installment {$installmentId} is already fully paid.");
            }

            $newPaid = round((float) $installment->paid_amount + $amount, 2);

            $installment->update([
                'paid_amount'       => $newPaid,
                'status'            => $newPaid >= (float) $installment->amount ? 'paid' : 'partial',
                'paid_at'           => now(),
                'payment_reference' => $reference,
            ]);

            if ($installment->fresh()->status === 'paid') {
                $plan->increment('paid_count');
                $plan->refresh();

                if ($plan->paid_count >= $plan->installment_count) {
                    $plan->update(['status' => 'completed', 'completed_at' => now()]);
                } else {
                    $nextDue = PaymentPlanInstallment::where('payment_plan_id', $plan->id)
                        ->whereIn('status', ['pending', 'partial', 'missed'])
                        ->orderBy('due_date')
                        ->value('due_date');

                    if ($nextDue) {
                        $plan->update(['next_due_date' => $nextDue]);
                    }
                }
            }

            return $installment->fresh();
        });
    }

    public function checkForDefaults(): int
    {
        $missedCount = PaymentPlanInstallment::whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', Carbon::today())
            ->whereHas('paymentPlan', fn ($q) => $q->where('status', 'active'))
            ->update(['status' => 'missed']);

        $planIds = PaymentPlanInstallment::where('status', 'missed')
            ->select('payment_plan_id')
            ->groupBy('payment_plan_id')
            ->havingRaw('COUNT(*) >= ?', [self::MISSED_INSTALLMENTS_BEFORE_DEFAULT])
            ->pluck('payment_plan_id');

        if ($planIds->isNotEmpty()) {
            PatientPaymentPlan::whereIn('id', $planIds)
                ->where('status', 'active')
                ->update(['status' => 'defaulted']);
        }

        return $missedCount;
    }

    public function getPlanSummary(string $planId): array
    {
        $plan         = PatientPaymentPlan::with('installments')->findOrFail($planId);
        $installments = $plan->installments;

        $totalPaid      = (float) $installments->sum('paid_amount') + (float) $plan->down_payment;
        $totalRemaining = max(0, round((float) $plan->total_amount - $totalPaid, 2));
        $isOverdue      = $installments->contains(
            fn ($i) => in_array($i->status, ['missed', 'partial']) && $i->due_date->isPast()
        );

        return [
            'plan'            => $plan,
            'installments'    => $installments,
            'total_paid'      => round($totalPaid, 2),
            'total_remaining' => $totalRemaining,
            'is_overdue'      => $isOverdue,
        ];
    }
}
