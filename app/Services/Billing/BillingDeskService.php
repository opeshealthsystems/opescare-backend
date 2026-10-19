<?php

namespace App\Services\Billing;

use App\Models\BillingAccount;
use App\Models\CashierSession;
use App\Models\FacilityCatalogueItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Refund;
use App\Services\Payments\Domain\Money;
use App\Services\Payments\Domain\PaymentMethod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The billing / cashier desk — every write to invoices, invoice_items,
 * payments, receipts, refunds and cashier_sessions from the web portal.
 *
 * ── THE ONE RULE ─────────────────────────────────────────────────────────
 * Care is never gated on payment. This class knows nothing about a visit's
 * queue, a triage priority, a prescription or a result, and it must stay that
 * way: tests/Feature/Billing/BillingIsolationGuardTest.php refuses any billing
 * file that can spell a clinical or queue table, and any clinical file that
 * can spell this one. An unpaid invoice is a fact the cashier chases. It is
 * not a condition anything else consults.
 *
 * ── Money ────────────────────────────────────────────────────────────────
 * Whole francs, PHP ints, end to end. A unit price is the facility's own
 * published `price_xaf` or a figure the cashier typed and is named on; there
 * is no default, no lookup elsewhere and no rounding. Xaf::int() reads the
 * decimal columns back as the integers they hold.
 *
 * ── Append-only ──────────────────────────────────────────────────────────
 * An invoice is voided (status + reason + who), never deleted. A payment is
 * never edited: money going back is a `refunds` row, asked for by one person
 * and approved by another. A cashier session's variance is written down as
 * counted minus expected and nothing here ever corrects it.
 *
 * ── Invoice lifecycle ────────────────────────────────────────────────────
 *   draft → issued → part_paid → paid
 *                 ↘ void   (only while nothing has been paid against it)
 */
final class BillingDeskService
{
    /**
     * The od-pill modifier for an invoice status. One mapping for every page
     * that lists invoices (billing desk, facility admin invoices), so a
     * status reads the same colour everywhere.
     */
    public static function invoiceStatusPill(?string $status): string
    {
        return match ((string) $status) {
            'paid'      => 'ready',
            'part_paid' => 'verifying',
            'issued'    => 'pending',
            'void'      => 'low',
            default     => 'neutral',
        };
    }

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_ISSUED    = 'issued';
    public const STATUS_PART_PAID = 'part_paid';
    public const STATUS_PAID      = 'paid';
    public const STATUS_VOID      = 'void';

    public const STATUSES = [
        self::STATUS_DRAFT, self::STATUS_ISSUED, self::STATUS_PART_PAID, self::STATUS_PAID, self::STATUS_VOID,
    ];

    /** The one non-mobile-money way a counter takes money. */
    public const METHOD_CASH = 'cash';

    /** @return list<string> every method a payment row may carry from this desk */
    public static function methods(): array
    {
        return [self::METHOD_CASH, ...PaymentMethod::values()];
    }

    // ── Invoices ─────────────────────────────────────────────────────────

    /**
     * Raise an invoice from explicit lines.
     *
     * Each line is either a catalogue item of THIS facility (its price is the
     * price — a catalogue row with no published price cannot be billed by
     * pointing at it) or a free line with a description and a unit price the
     * cashier typed. Nothing is inferred.
     *
     * @param  list<array{catalogue_item_id?: string|null, description?: string|null, unit_price_xaf?: int|string|null, quantity: int|string}>  $lines
     */
    public function createInvoice(string $facilityId, Patient $patient, array $lines, ?string $createdBy, bool $issue = true): Invoice
    {
        if ($lines === []) {
            throw new BillingRefusal('no_lines');
        }

        $resolved = $this->resolveLines($facilityId, $lines);
        $subtotal = array_sum(array_column($resolved, 'line_total'));

        if ($subtotal <= 0) {
            throw new BillingRefusal('zero_total');
        }

        if ($subtotal > Money::MAX_MINOR) {
            throw new BillingRefusal('too_large', ['max' => Xaf::format(Money::MAX_MINOR)]);
        }

        return DB::transaction(function () use ($facilityId, $patient, $resolved, $subtotal, $createdBy, $issue) {
            $account = BillingAccount::firstOrCreate(
                ['patient_id' => $patient->id, 'facility_id' => $facilityId],
                ['status' => 'active', 'outstanding_balance_amount' => 0],
            );

            $invoice = (new Invoice)->forceFill([
                'billing_account_id'            => $account->id,
                'patient_id'                    => $patient->id,
                'facility_id'                   => $facilityId,
                'created_by'                    => $createdBy,
                'invoice_number'                => $this->mintNumber('INV'),
                'status'                        => $issue ? self::STATUS_ISSUED : self::STATUS_DRAFT,
                'subtotal_amount'               => $subtotal,
                'discount_amount'               => 0,
                'insurance_covered_amount'      => 0,
                'patient_responsibility_amount' => $subtotal,
                'paid_amount'                   => 0,
                'refunded_amount'               => 0,
                'balance_amount'                => $subtotal,
                'issued_at'                     => $issue ? Carbon::now() : null,
            ]);
            $invoice->save();

            foreach ($resolved as $line) {
                InvoiceItem::create([
                    'invoice_id'        => $invoice->id,
                    'service_code'      => $line['service_code'],
                    'description'       => $line['description'],
                    'quantity'          => $line['quantity'],
                    'unit_price'        => $line['unit_price'],
                    'discount_amount'   => 0,
                    'line_total_amount' => $line['line_total'],
                ]);
            }

            return $invoice;
        });
    }

    /** draft → issued. Anything else is refused. */
    public function issue(Invoice $invoice): Invoice
    {
        if ($invoice->status !== self::STATUS_DRAFT) {
            throw new BillingRefusal('not_draft');
        }

        $invoice->forceFill(['status' => self::STATUS_ISSUED, 'issued_at' => Carbon::now()])->save();

        return $invoice;
    }

    /**
     * Void. The row stays, with who and why. An invoice with money against it
     * cannot be voided — the money has to go back first, through a refund
     * that someone approved, or the void would make a payment disappear.
     */
    public function void(Invoice $invoice, string $reason, ?string $actorId): Invoice
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new BillingRefusal('reason_required');
        }

        return DB::transaction(function () use ($invoice, $reason, $actorId) {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === self::STATUS_VOID) {
                throw new BillingRefusal('already_void');
            }

            if (Xaf::int($locked->paid_amount) - Xaf::int($locked->refunded_amount) > 0) {
                throw new BillingRefusal('void_with_payments');
            }

            $locked->forceFill([
                'status'         => self::STATUS_VOID,
                'balance_amount' => 0,
                'voided_at'      => Carbon::now(),
                'voided_by'      => $actorId,
                'void_reason'    => $reason,
            ])->save();

            return $locked;
        });
    }

    // ── Payments ─────────────────────────────────────────────────────────

    /**
     * Cash taken at the counter. Attributed to the cashier's OPEN session at
     * this facility — a cash payment nobody's till can account for is refused,
     * not silently orphaned. Overpayment is refused with the exact balance.
     */
    public function recordCashPayment(Invoice $invoice, int $amount, string $cashierId, CashierSession $session): Payment
    {
        if ($session->status !== 'open' || $session->cashier_id !== $cashierId || $session->facility_id !== $invoice->facility_id) {
            throw new BillingRefusal('session_required');
        }

        return $this->recordPayment($invoice, $amount, self::METHOD_CASH, [
            'cashier_id'         => $cashierId,
            'cashier_session_id' => $session->id,
            'gateway'            => self::METHOD_CASH,
        ]);
    }

    /**
     * A mobile money charge that the provider has confirmed. Only
     * MobileMoneyCounterService calls this, once per settled charge — the
     * `payment_reference` it passes is derived from the charge, and the
     * unique index on that column makes a second recording collide.
     */
    public function recordSettledMobilePayment(Invoice $invoice, int $amount, PaymentMethod $method, string $reference, array $extra = []): Payment
    {
        return $this->recordPayment($invoice, $amount, $method->value, $extra + [
            'payment_reference' => $reference,
            'gateway'           => $method->value,
        ]);
    }

    /** @param array<string, mixed> $attrs */
    private function recordPayment(Invoice $invoice, int $amount, string $method, array $attrs): Payment
    {
        if ($amount <= 0) {
            throw new BillingRefusal('amount_positive');
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $attrs) {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [self::STATUS_ISSUED, self::STATUS_PART_PAID], true)) {
                throw new BillingRefusal('not_payable', ['status' => $locked->status]);
            }

            $balance = Xaf::int($locked->balance_amount);

            if ($amount > $balance) {
                throw new BillingRefusal('overpayment', [
                    'amount'  => Xaf::format($amount),
                    'balance' => Xaf::format($balance),
                ]);
            }

            $payment = Payment::create($attrs + [
                'invoice_id'        => $locked->id,
                'patient_id'        => $locked->patient_id,
                'facility_id'       => $locked->facility_id,
                'payment_reference' => $this->mintNumber('PAY'),
                'method'            => $method,
                'status'            => 'successful',
                'amount'            => $amount,
                'currency'          => 'XAF',
                'refunded_amount'   => 0,
                'device_type'       => 'web',
                'confirmed_at'      => Carbon::now(),
            ]);

            Receipt::create([
                'payment_id'     => $payment->id,
                'invoice_id'     => $locked->id,
                'receipt_number' => $this->mintNumber('RCP'),
                'amount'         => $amount,
                'issued_at'      => Carbon::now(),
            ]);

            $paid = Xaf::int($locked->paid_amount) + $amount;
            $this->applyBalance($locked, $paid, Xaf::int($locked->refunded_amount));

            if ($method === self::METHOD_CASH && isset($attrs['cashier_session_id'])) {
                CashierSession::whereKey($attrs['cashier_session_id'])->increment('cash_total_amount', $amount);
            }

            return $payment;
        });
    }

    /**
     * Recompute the invoice's balance and status from what has been paid and
     * what has gone back. The ONLY place status moves between issued,
     * part_paid and paid.
     */
    private function applyBalance(Invoice $locked, int $paid, int $refunded): void
    {
        $due     = Xaf::int($locked->patient_responsibility_amount);
        $net     = $paid - $refunded;
        $balance = max(0, $due - $net);

        $status = match (true) {
            $balance === 0 => self::STATUS_PAID,
            $net > 0       => self::STATUS_PART_PAID,
            default        => self::STATUS_ISSUED,
        };

        $locked->forceFill([
            'paid_amount'     => $paid,
            'refunded_amount' => $refunded,
            'balance_amount'  => $balance,
            'status'          => $status,
            'paid_at'         => $status === self::STATUS_PAID ? ($locked->paid_at ?? Carbon::now()) : null,
        ])->save();
    }

    // ── Refunds ──────────────────────────────────────────────────────────

    /**
     * Ask for money back. Needs a reason; cannot exceed what the payment still
     * holds once earlier refunds (approved or still pending) are taken off.
     */
    public function requestRefund(Payment $payment, int $amount, string $reason, string $requestedBy): Refund
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new BillingRefusal('reason_required');
        }

        if ($amount <= 0) {
            throw new BillingRefusal('amount_positive');
        }

        return DB::transaction(function () use ($payment, $amount, $reason, $requestedBy) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $committed = Xaf::int($locked->refunded_amount)
                + Xaf::int(Refund::where('payment_id', $locked->id)->where('status', 'pending')->sum('amount'));
            $refundable = Xaf::int($locked->amount) - $committed;

            if ($amount > $refundable) {
                throw new BillingRefusal('refund_exceeds', [
                    'amount'     => Xaf::format($amount),
                    'refundable' => Xaf::format(max(0, $refundable)),
                ]);
            }

            $refund = (new Refund)->forceFill([
                'payment_id'    => $locked->id,
                'invoice_id'    => $locked->invoice_id,
                'patient_id'    => $locked->patient_id,
                'facility_id'   => $locked->facility_id,
                'amount'        => $amount,
                'currency'      => 'XAF',
                'reason'        => $reason,
                'status'        => 'pending',
                'refund_method' => $locked->method,
                'requested_by'  => $requestedBy,
            ]);
            $refund->save();

            return $refund;
        });
    }

    /**
     * The second signature. Approving records the refund against the payment
     * and the invoice in the same transaction — "approved but not applied"
     * is a state nobody at a counter can reason about. The approver may not
     * be the requester.
     */
    public function approveRefund(Refund $refund, string $approverId): Refund
    {
        return DB::transaction(function () use ($refund, $approverId) {
            $locked = Refund::whereKey($refund->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                throw new BillingRefusal('refund_not_pending');
            }

            if ($locked->requested_by !== null && $locked->requested_by === $approverId) {
                throw new BillingRefusal('refund_self_approval');
            }

            $payment = Payment::whereKey($locked->payment_id)->lockForUpdate()->firstOrFail();
            $invoice = Invoice::whereKey($payment->invoice_id)->lockForUpdate()->firstOrFail();
            $amount  = Xaf::int($locked->amount);

            $payment->forceFill(['refunded_amount' => Xaf::int($payment->refunded_amount) + $amount])->save();
            $this->applyBalance($invoice, Xaf::int($invoice->paid_amount), Xaf::int($invoice->refunded_amount) + $amount);

            $locked->forceFill([
                'status'           => 'processed',
                'approved_by'      => $approverId,
                'approved_at'      => Carbon::now(),
                'processed_by'     => $approverId,
                'processed_at'     => Carbon::now(),
                'reference_number' => $this->mintNumber('RFD'),
            ])->save();

            return $locked;
        });
    }

    public function rejectRefund(Refund $refund, string $reason, string $approverId): Refund
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new BillingRefusal('reason_required');
        }

        if ($refund->status !== 'pending') {
            throw new BillingRefusal('refund_not_pending');
        }

        $refund->forceFill([
            'status'      => 'rejected',
            'notes'       => $reason,
            'approved_by' => $approverId,
        ])->save();

        return $refund;
    }

    // ── Cashier sessions ─────────────────────────────────────────────────

    public function openSession(string $facilityId, string $cashierId, int $float): CashierSession
    {
        if ($float < 0) {
            throw new BillingRefusal('amount_positive');
        }

        if ($this->openSessionFor($facilityId, $cashierId) !== null) {
            throw new BillingRefusal('session_already_open');
        }

        $session = (new CashierSession)->forceFill([
            'facility_id'          => $facilityId,
            'cashier_id'           => $cashierId,
            'status'               => 'open',
            'cash_total_amount'    => 0,
            'opening_float_amount' => $float,
            'opened_at'            => Carbon::now(),
        ]);
        $session->save();

        return $session;
    }

    public function openSessionFor(string $facilityId, string $cashierId): ?CashierSession
    {
        return CashierSession::where('facility_id', $facilityId)
            ->where('cashier_id', $cashierId)
            ->where('status', 'open')
            ->orderByDesc('opened_at')
            ->first();
    }

    /**
     * Close with a count. Expected = float + cash taken in the session.
     * Variance = counted − expected, written down as it is. If the till is
     * short, the till is short; the desk records it and moves nothing.
     */
    public function closeSession(CashierSession $session, int $counted, ?string $note): CashierSession
    {
        if ($counted < 0) {
            throw new BillingRefusal('amount_positive');
        }

        return DB::transaction(function () use ($session, $counted, $note) {
            $locked = CashierSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'open') {
                throw new BillingRefusal('session_not_open');
            }

            $cash = Xaf::int(
                Payment::where('cashier_session_id', $locked->id)
                    ->where('method', self::METHOD_CASH)
                    ->where('status', 'successful')
                    ->sum('amount')
            );

            $expected = Xaf::int($locked->opening_float_amount) + $cash;

            $locked->forceFill([
                'status'               => 'closed',
                'cash_total_amount'    => $cash,
                'expected_cash_amount' => $expected,
                'counted_cash_amount'  => $counted,
                'variance_amount'      => $counted - $expected,
                'closing_note'         => $note !== null && trim($note) !== '' ? trim($note) : null,
                'closed_at'            => Carbon::now(),
            ])->save();

            return $locked;
        });
    }

    // ── Internals ────────────────────────────────────────────────────────

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return list<array{service_code: ?string, description: string, quantity: int, unit_price: int, line_total: int}>
     */
    private function resolveLines(string $facilityId, array $lines): array
    {
        $out = [];

        foreach ($lines as $i => $line) {
            $quantity = (int) ($line['quantity'] ?? 0);

            if ($quantity < 1 || $quantity > 1000) {
                throw new BillingRefusal('bad_quantity', ['line' => $i + 1]);
            }

            $itemId = $line['catalogue_item_id'] ?? null;

            if ($itemId !== null && $itemId !== '') {
                $item = FacilityCatalogueItem::where('facility_id', $facilityId)
                    ->where('is_active', true)
                    ->whereKey($itemId)
                    ->first();

                if ($item === null) {
                    throw new BillingRefusal('catalogue_item_unknown', ['line' => $i + 1]);
                }

                if ($item->price_xaf === null) {
                    throw new BillingRefusal('catalogue_item_unpriced', ['line' => $i + 1]);
                }

                $unit        = (int) $item->price_xaf;
                $description = $item->local_name ?: ($item->system.' '.$item->code);
                $code        = $item->system.':'.$item->code;
            } else {
                $description = trim((string) ($line['description'] ?? ''));
                $raw         = $line['unit_price_xaf'] ?? null;

                if ($description === '') {
                    throw new BillingRefusal('description_required', ['line' => $i + 1]);
                }

                if ($raw === null || $raw === '' || ! preg_match('/^\d{1,9}$/', (string) $raw)) {
                    throw new BillingRefusal('price_required', ['line' => $i + 1]);
                }

                $unit = (int) $raw;
                $code = null;
            }

            if ($unit <= 0) {
                throw new BillingRefusal('price_required', ['line' => $i + 1]);
            }

            $out[] = [
                'service_code' => $code,
                'description'  => Str::limit($description, 250, ''),
                'quantity'     => $quantity,
                'unit_price'   => $unit,
                'line_total'   => $unit * $quantity,
            ];
        }

        return $out;
    }

    private function mintNumber(string $prefix): string
    {
        return $prefix.'-'.Carbon::now()->format('Ymd').'-'.strtoupper(Str::random(6));
    }
}
