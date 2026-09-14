<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\CashierSession;
use App\Models\FacilityCatalogueItem;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\Refund;
use App\Services\Billing\BillingDeskService;
use App\Services\Billing\BillingRefusal;
use App\Services\Billing\MobileMoneyCounterService;
use App\Services\Billing\Xaf;
use App\Services\Payments\Domain\PaymentMethod;
use App\Services\Portal\PortalContextService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The billing / cashier desk — the first web surface over `invoices`,
 * `invoice_items`, `payments`, `receipts`, `refunds` and `cashier_sessions`,
 * whose `billing` capability was granted to seven roles and gated nothing.
 *
 * Only a surface: every write goes through BillingDeskService (the rules) or
 * MobileMoneyCounterService (the one mobile money integration, reused).
 *
 * ── SCOPE ──────────────────────────────────────────────────────────────────
 * The facility is read from PortalContextService — the web tier's convention —
 * never from the request. Every row is looked up through `facility_id =` the
 * active facility, so another facility's invoice is not refused, it does not
 * exist (404). The role gate is `capability:billing` on the routes (403
 * `role_forbidden`); approving a refund needs `capability:refund_approve`.
 *
 * ── WHAT THIS DESK CANNOT DO ───────────────────────────────────────────────
 * Nothing here reads or writes a visit, a queue ticket, a triage record, a
 * prescription or a result. The patient is found by exact Health ID for the
 * purpose of naming the invoice, and that is the whole of the desk's contact
 * with the record. An unpaid balance changes nothing outside these tables.
 */
class BillingDeskController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly BillingDeskService $desk,
        private readonly MobileMoneyCounterService $momo,
    ) {
    }

    // ── Invoices ─────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $facilityId = $this->facilityId();

        $status   = (string) $request->query('status', '');
        $healthId = trim((string) $request->query('health_id', ''));
        $patient  = null;
        $notFound = false;

        $query = $this->invoices()->with('items');

        if (in_array($status, BillingDeskService::STATUSES, true)) {
            $query->where('status', $status);
        }

        if ($healthId !== '') {
            $patient = Patient::resolveHealthId($healthId);

            if ($patient === null) {
                $notFound = true;
                $query->whereRaw('1 = 0');
            } else {
                $query->where('patient_id', $patient->id);
            }
        }

        $invoices = $query->orderByDesc('created_at')->paginate(25)->withQueryString();
        $patients = $this->patientsFor($invoices->getCollection()->pluck('patient_id'));

        $counts = $this->invoices()
            ->selectRaw('status, COUNT(*) AS n')
            ->groupBy('status')
            ->pluck('n', 'status')
            ->map(fn ($n) => (int) $n)
            ->all();

        return view('portals.billing.index', [
            'invoices'   => $invoices,
            'patients'   => $patients,
            'status'     => $status,
            'healthId'   => $healthId,
            'patient'    => $patient,
            'notFound'   => $notFound,
            'counts'     => $counts,
            'statuses'   => BillingDeskService::STATUSES,
            'session'    => $this->desk->openSessionFor($facilityId, (string) auth()->id()),
            'facilityId' => $facilityId,
        ]);
    }

    /** Step 1 exact Health ID; step 2 the lines, priced from the catalogue. */
    public function create(Request $request): View
    {
        $facilityId = $this->facilityId();

        $patient  = null;
        $notFound = false;

        if ($healthId = trim((string) $request->input('health_id'))) {
            $patient  = Patient::resolveHealthId($healthId);
            $notFound = $patient === null;
        }

        $catalogue = FacilityCatalogueItem::where('facility_id', $facilityId)
            ->where('is_active', true)
            ->whereNotNull('price_xaf')
            ->orderBy('local_name')
            ->orderBy('code')
            ->get();

        return view('portals.billing.create', [
            'patient'   => $patient,
            'notFound'  => $notFound,
            'catalogue' => $catalogue,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $facilityId = $this->facilityId();

        $validated = $request->validate([
            'patient_id'                  => ['required', 'string'],
            'lines'                       => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.catalogue_item_id'   => ['nullable', 'string', 'max:64'],
            'lines.*.description'         => ['nullable', 'string', 'max:250'],
            'lines.*.unit_price_xaf'      => ['nullable', 'regex:/^\d{1,9}$/'],
            'lines.*.quantity'            => ['required', 'integer', 'min:1', 'max:1000'],
            'issue'                       => ['nullable', 'boolean'],
        ]);

        $patient = Patient::resolveId($validated['patient_id']);

        if ($patient === null) {
            return back()->withInput()->with('error', __('billing_desk.not_found'));
        }

        // Drop wholly blank rows the form may post (an unused extra line).
        $lines = array_values(array_filter($validated['lines'], function (array $line) {
            return ($line['catalogue_item_id'] ?? '') !== ''
                || trim((string) ($line['description'] ?? '')) !== ''
                || ($line['unit_price_xaf'] ?? '') !== '';
        }));

        try {
            $invoice = $this->desk->createInvoice(
                $facilityId,
                $patient,
                $lines,
                (string) auth()->id(),
                $request->boolean('issue', true),
            );
        } catch (BillingRefusal $e) {
            return back()->withInput()->with('error', $e->messageFor());
        }

        return redirect()->route('portals.billing.show', $invoice->id)
            ->with('success', __('billing_desk.created', ['number' => $invoice->invoice_number]));
    }

    public function show(Request $request, string $id): View
    {
        $facilityId = $this->facilityId();
        $invoice    = $this->invoices()->with('items')->findOrFail($id);

        $payments = Payment::where('invoice_id', $invoice->id)->orderBy('created_at')->get();
        $receipts = Receipt::whereIn('payment_id', $payments->pluck('id'))->get()->keyBy('payment_id');
        $refunds  = Refund::where('invoice_id', $invoice->id)->orderBy('created_at')->get();

        return view('portals.billing.show', [
            'invoice'   => $invoice,
            'patient'   => $this->patientsFor(collect([$invoice->patient_id]))->first(),
            'payments'  => $payments,
            'receipts'  => $receipts,
            'refunds'   => $refunds,
            'charges'   => $this->momo->chargesFor($invoice),
            'session'   => $this->desk->openSessionFor($facilityId, (string) auth()->id()),
            'methods'   => PaymentMethod::cases(),
            'canRefund' => \App\Support\StaffCapabilities::canAt(auth()->user(), $facilityId, 'refund_approve'),
        ]);
    }

    public function issue(Request $request, string $id): RedirectResponse
    {
        $invoice = $this->invoices()->findOrFail($id);

        try {
            $this->desk->issue($invoice);
        } catch (BillingRefusal $e) {
            return back()->with('error', $e->messageFor());
        }

        return back()->with('success', __('billing_desk.issued'));
    }

    public function void(Request $request, string $id): RedirectResponse
    {
        $invoice   = $this->invoices()->findOrFail($id);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);

        try {
            $this->desk->void($invoice, $validated['reason'], (string) auth()->id());
        } catch (BillingRefusal $e) {
            return back()->with('error', $e->messageFor());
        }

        return back()->with('success', __('billing_desk.voided'));
    }

    // ── Payments ─────────────────────────────────────────────────────────

    public function pay(Request $request, string $id): RedirectResponse
    {
        $facilityId = $this->facilityId();
        $invoice    = $this->invoices()->findOrFail($id);

        $validated = $request->validate([
            'amount_xaf' => ['required', 'regex:/^\d{1,9}$/'],
            'method'     => ['required', 'in:'.implode(',', BillingDeskService::methods())],
            'phone'      => ['nullable', 'string', 'max:32'],
        ]);

        $amount = (int) $validated['amount_xaf'];

        try {
            if ($validated['method'] === BillingDeskService::METHOD_CASH) {
                $session = $this->desk->openSessionFor($facilityId, (string) auth()->id());

                if ($session === null) {
                    throw new BillingRefusal('session_required');
                }

                $payment = $this->desk->recordCashPayment($invoice, $amount, (string) auth()->id(), $session);
                $receipt = Receipt::where('payment_id', $payment->id)->first();

                return redirect()->route('portals.billing.show', $invoice->id)
                    ->with('success', __('billing_desk.payment_recorded', ['receipt' => $receipt?->receipt_number ?? '']));
            }

            // Mobile money: the amount charged is the balance, always — a push
            // prompt for a figure other than what is owed is how a patient
            // ends up paying twice. The amount field is checked against it.
            if ($amount !== Xaf::int($invoice->balance_amount)) {
                throw new BillingRefusal('momo_amount_is_balance', ['balance' => Xaf::format(Xaf::int($invoice->balance_amount))]);
            }

            $this->momo->request($invoice, PaymentMethod::from($validated['method']), (string) ($validated['phone'] ?? ''), (string) auth()->id());
        } catch (BillingRefusal $e) {
            return back()->withInput()->with('error', $e->messageFor());
        }

        return redirect()->route('portals.billing.show', $invoice->id)
            ->with('success', __('billing_desk.momo.requested'));
    }

    /** Ask the provider about pending prompts and record what settled. */
    public function syncMobile(Request $request, string $id): RedirectResponse
    {
        $invoice = $this->invoices()->findOrFail($id);

        try {
            $result = $this->momo->sync($invoice);
        } catch (BillingRefusal $e) {
            return back()->with('error', $e->messageFor());
        }

        return back()->with('success', __('billing_desk.momo.synced', $result));
    }

    public function receipt(Request $request, string $id): View
    {
        $receipt = Receipt::whereKey($id)
            ->whereIn('invoice_id', $this->invoices()->select('id'))
            ->firstOrFail();

        $invoice = $this->invoices()->with('items')->findOrFail($receipt->invoice_id);
        $payment = Payment::findOrFail($receipt->payment_id);

        return view('portals.billing.receipt', [
            'receipt'  => $receipt,
            'invoice'  => $invoice,
            'payment'  => $payment,
            'patient'  => $this->patientsFor(collect([$invoice->patient_id]))->first(),
            'facility' => \App\Models\Facility::find($this->facilityId()),
            'cashier'  => $payment->cashier,
        ]);
    }

    // ── Refunds ──────────────────────────────────────────────────────────

    public function refunds(Request $request): View
    {
        $facilityId = $this->facilityId();

        $refunds = Refund::where('facility_id', $facilityId)
            ->with(['payment', 'invoice'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('portals.billing.refunds', [
            'refunds'   => $refunds,
            'patients'  => $this->patientsFor($refunds->getCollection()->pluck('patient_id')),
            'canRefund' => \App\Support\StaffCapabilities::canAt(auth()->user(), $facilityId, 'refund_approve'),
        ]);
    }

    public function requestRefund(Request $request, string $paymentId): RedirectResponse
    {
        $payment = Payment::whereKey($paymentId)
            ->where('facility_id', $this->facilityId())
            ->firstOrFail();

        $validated = $request->validate([
            'amount_xaf' => ['required', 'regex:/^\d{1,9}$/'],
            'reason'     => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $this->desk->requestRefund($payment, (int) $validated['amount_xaf'], $validated['reason'], (string) auth()->id());
        } catch (BillingRefusal $e) {
            return back()->withInput()->with('error', $e->messageFor());
        }

        return back()->with('success', __('billing_desk.refund.requested'));
    }

    public function approveRefund(Request $request, string $id): RedirectResponse
    {
        $refund = Refund::whereKey($id)->where('facility_id', $this->facilityId())->firstOrFail();

        try {
            $this->desk->approveRefund($refund, (string) auth()->id());
        } catch (BillingRefusal $e) {
            return back()->with('error', $e->messageFor());
        }

        return back()->with('success', __('billing_desk.refund.approved'));
    }

    public function rejectRefund(Request $request, string $id): RedirectResponse
    {
        $refund    = Refund::whereKey($id)->where('facility_id', $this->facilityId())->firstOrFail();
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:500']]);

        try {
            $this->desk->rejectRefund($refund, $validated['reason'], (string) auth()->id());
        } catch (BillingRefusal $e) {
            return back()->with('error', $e->messageFor());
        }

        return back()->with('success', __('billing_desk.refund.rejected'));
    }

    // ── Cashier sessions ─────────────────────────────────────────────────

    public function sessions(Request $request): View
    {
        $facilityId = $this->facilityId();

        $sessions = CashierSession::where('facility_id', $facilityId)
            ->orderByDesc('opened_at')
            ->paginate(25);

        $cashiers = \App\Models\User::whereIn('id', $sessions->getCollection()->pluck('cashier_id')->unique())
            ->get()
            ->keyBy('id');

        $open = $this->desk->openSessionFor($facilityId, (string) auth()->id());

        $cashSoFar = $open === null ? 0 : Xaf::int(
            Payment::where('cashier_session_id', $open->id)
                ->where('method', BillingDeskService::METHOD_CASH)
                ->where('status', 'successful')
                ->sum('amount')
        );

        return view('portals.billing.sessions', [
            'sessions'  => $sessions,
            'cashiers'  => $cashiers,
            'open'      => $open,
            'cashSoFar' => $cashSoFar,
        ]);
    }

    public function openSession(Request $request): RedirectResponse
    {
        $validated = $request->validate(['float_xaf' => ['required', 'regex:/^\d{1,9}$/']]);

        try {
            $this->desk->openSession($this->facilityId(), (string) auth()->id(), (int) $validated['float_xaf']);
        } catch (BillingRefusal $e) {
            return back()->withInput()->with('error', $e->messageFor());
        }

        return redirect()->route('portals.billing.sessions')->with('success', __('billing_desk.session.opened'));
    }

    public function closeSession(Request $request, string $id): RedirectResponse
    {
        $session = CashierSession::whereKey($id)
            ->where('facility_id', $this->facilityId())
            ->where('cashier_id', (string) auth()->id())
            ->firstOrFail();

        $validated = $request->validate([
            'counted_xaf' => ['required', 'regex:/^\d{1,9}$/'],
            'note'        => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $closed = $this->desk->closeSession($session, (int) $validated['counted_xaf'], $validated['note'] ?? null);
        } catch (BillingRefusal $e) {
            return back()->withInput()->with('error', $e->messageFor());
        }

        return redirect()->route('portals.billing.sessions')
            ->with('success', __('billing_desk.session.closed', ['variance' => Xaf::format(Xaf::int($closed->variance_amount))]));
    }

    // ── Internals ────────────────────────────────────────────────────────

    /** Every invoice query on this desk starts here: the active facility's rows, nothing else. */
    private function invoices(): Builder
    {
        return Invoice::where('facility_id', $this->facilityId());
    }

    /**
     * Name the patients on a page in ONE query. Invoice has no patient
     * relation and this desk does not need one beyond a name and a Health ID.
     *
     * @return \Illuminate\Support\Collection<string, Patient>
     */
    private function patientsFor(\Illuminate\Support\Collection $ids): \Illuminate\Support\Collection
    {
        $ids = $ids->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Patient::whereIn('id', $ids)->get()->keyBy('id');
    }

    private function facilityId(): string
    {
        $facilityId = $this->context->facilityId();

        abort_if($facilityId === null, 403, __('billing_desk.no_facility'));

        return $facilityId;
    }
}
