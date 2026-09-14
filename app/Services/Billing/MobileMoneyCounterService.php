<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\MobileInvoice;
use App\Models\MobilePaymentCharge;
use App\Models\Payment;
use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\Msisdn;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Payments\Domain\PaymentStatus;
use App\Services\Payments\Exceptions\PaymentException;
use App\Services\Payments\InvoiceService;
use App\Services\Payments\PaymentChargeService;
use InvalidArgumentException;

/**
 * MTN MoMo / Orange Money at the counter — the platform's ONE mobile money
 * integration (App\Services\Payments), reused; not a second one.
 *
 * PaymentChargeService charges a `mobile_invoices` row and settles it under
 * the Postgres guard trigger. So the counter does exactly what the patient
 * app does: it issues a mobile invoice for the outstanding balance with
 * `subject_type = 'facility_invoice'` pointing back at the facility invoice,
 * pushes the prompt to the patient's handset, and later — when the provider
 * says paid — records ONE `payments` row + receipt against the facility
 * invoice. The `payment_reference` is derived from the settled charge, so a
 * second recording of the same charge collides on the unique index rather
 * than paying the invoice twice.
 *
 * Nothing here is synchronous with the handset: the cashier requests, the
 * patient confirms on their phone, the cashier refreshes.
 */
final class MobileMoneyCounterService
{
    public const SUBJECT_TYPE = 'facility_invoice';

    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly PaymentChargeService $charges,
        private readonly BillingDeskService $desk,
    ) {
    }

    /** Push a prompt for the invoice's current balance to the given number. */
    public function request(Invoice $invoice, PaymentMethod $method, string $phone, string $actorId): MobilePaymentCharge
    {
        if (! in_array($invoice->status, [BillingDeskService::STATUS_ISSUED, BillingDeskService::STATUS_PART_PAID], true)) {
            throw new BillingRefusal('not_payable', ['status' => $invoice->status]);
        }

        $balance = Xaf::int($invoice->balance_amount);

        if ($balance <= 0) {
            throw new BillingRefusal('not_payable', ['status' => $invoice->status]);
        }

        try {
            $payer = Msisdn::parse($phone);
        } catch (InvalidArgumentException) {
            throw new BillingRefusal('bad_phone');
        }

        // Reuse an open (pending, unexpired) mobile invoice for this facility
        // invoice if the same balance is still owed; otherwise issue one.
        $mobile = MobileInvoice::where('subject_type', self::SUBJECT_TYPE)
            ->where('subject_id', $invoice->id)
            ->where('status', PaymentStatus::Pending->value)
            ->where('amount_minor', $balance)
            ->where('expires_at', '>', now())
            ->first();

        if ($mobile === null) {
            $mobile = $this->invoices->issueFor(
                patientId: $invoice->patient_id,
                amount: Money::ofMinor($balance, 'XAF'),
                description: __('billing_desk.momo.description', ['number' => $invoice->invoice_number]),
                subjectType: self::SUBJECT_TYPE,
                subjectId: $invoice->id,
                facilityId: $invoice->facility_id,
                isDemo: (bool) \App\Models\Patient::withoutGlobalScopes()->whereKey($invoice->patient_id)->value('is_demo'),
            );
        }

        try {
            return $this->charges->start(
                invoice: $mobile,
                method: $method,
                payer: $payer,
                idempotencyKey: 'desk:'.$actorId.':'.$mobile->id.':'.now()->format('YmdHis'),
                requestFingerprint: hash('sha256', $mobile->id.'|'.$method->value.'|'.$payer->hash()),
                expectedAmountMinor: $balance,
            );
        } catch (PaymentException $e) {
            throw new BillingRefusal('momo_failed', ['reason' => __($e->translationKey, $e->replace)]);
        }
    }

    /**
     * Ask the provider about every pending charge on this invoice, then record
     * a `payments` row for every charge that settled and is not yet recorded.
     *
     * @return array{synced: int, recorded: int}
     */
    public function sync(Invoice $invoice): array
    {
        $mobiles = MobileInvoice::where('subject_type', self::SUBJECT_TYPE)
            ->where('subject_id', $invoice->id)
            ->with('charges')
            ->get();

        $synced = 0;
        $recorded = 0;

        foreach ($mobiles as $mobile) {
            foreach ($mobile->charges as $charge) {
                if ($charge->status === PaymentStatus::Pending) {
                    try {
                        $charge = $this->charges->syncFromProvider($charge);
                        $synced++;
                    } catch (PaymentException) {
                        continue;
                    }
                }

                if ($charge->status !== PaymentStatus::Paid) {
                    continue;
                }

                $reference = 'MM-'.$charge->id;

                if (Payment::where('payment_reference', $reference)->exists()) {
                    continue;
                }

                $method = $charge->method instanceof PaymentMethod ? $charge->method : PaymentMethod::from((string) $charge->method);

                $this->desk->recordSettledMobilePayment(
                    $invoice->fresh(),
                    (int) $charge->amount_minor,
                    $method,
                    $reference,
                    [
                        'gateway_transaction_id' => $charge->provider_reference,
                        'gateway_status'         => 'SUCCESSFUL',
                        'payer_phone'            => $charge->payer_msisdn_masked ?? null,
                    ],
                );
                $recorded++;
            }
        }

        return ['synced' => $synced, 'recorded' => $recorded];
    }

    /** @return \Illuminate\Support\Collection<int, MobilePaymentCharge> */
    public function chargesFor(Invoice $invoice)
    {
        return MobilePaymentCharge::whereIn(
            'invoice_id',
            MobileInvoice::where('subject_type', self::SUBJECT_TYPE)->where('subject_id', $invoice->id)->select('id')
        )->orderByDesc('created_at')->get();
    }
}
