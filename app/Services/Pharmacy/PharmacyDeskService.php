<?php

namespace App\Services\Pharmacy;

use App\Models\CareFacility;
use App\Models\Facility;
use App\Models\Medicine;
use App\Models\MedicinePharmacyStock;
use App\Models\MedicineReservation;
use App\Models\Patient;
use App\Models\PharmacyRoute;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\StockMovement;
use App\Modules\Pharmacy\Services\PharmacyStockReportService;
use App\Services\Clinical\PrescriptionService;
use App\Support\PatientPush;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The pharmacy desk — what a pharmacist's phone does at the counter.
 *
 * One facility, three jobs:
 *   1. prescriptions routed to (or presented at) this counter, taken through
 *      incoming → preparing → ready → dispensed;
 *   2. patients' medicine reservations, accepted, prepared and either handed
 *      over or sent out for delivery;
 *   3. the public stock listing, kept honest with a ledger entry per change.
 *
 * ── Which facility ──────────────────────────────────────────────────────
 *
 * Every query here is scoped to the Facility the caller passes, and the
 * controllers only ever pass the one the provider token names
 * (`provider_facility_id`, set by auth.provider). Nothing in this class reads
 * a facility from a body, a header or a query string.
 *
 * Prescriptions are keyed on `facilities.id` (the token facility) through
 * `pharmacy_routes`; reservations and stock are keyed on `care_facilities.id`
 * (the public directory listing). listingIds() is the bridge: the directory
 * rows whose `facility_id` is the token facility.
 *
 * ── How a prescription is "routed to this facility" ─────────────────────
 *
 * `prescriptions.pharmacy_route_id` → `pharmacy_routes`. A PharmacyRoute row is
 * read here as a DISPENSING DESTINATION: its `facility_id` is the facility
 * that fills the script. routeFor() keeps exactly one active route per
 * dispensing facility, so "routed to us" is a whereIn on those ids.
 *
 * A script that has not been routed anywhere is the normal walk-in case — the
 * patient carries it from the hospital to whichever pharmacy they choose. It
 * reaches the desk only through the patient's EXACT Health ID (lookup()), and
 * the first counter action claims it (routeToFacility()). A script routed to
 * ANOTHER pharmacy is not at this counter: 404, never 403.
 *
 * ── What stays immutable ────────────────────────────────────────────────
 *
 * The clinical content of a prescription is never touched. Dispensing goes
 * through PrescriptionService::dispense() (items, dispenser, audit, refusal of
 * a second dispense) and the routing status through PharmacyRoutingService;
 * this class only decides WHEN those may be called.
 */
class PharmacyDeskService
{
    // ── prescriptions.pharmacy_routing_status ─────────────────────────────
    public const ROUTING_PENDING   = 'pending';    // routed, not yet seen by the counter
    public const ROUTING_SENT      = 'sent';       // delivered electronically (PharmacyRoutingService)
    public const ROUTING_CONFIRMED = 'confirmed';  // the counter accepted it — being prepared
    public const ROUTING_READY     = 'ready';      // prepared, waiting for the patient
    public const ROUTING_DISPENSED = 'dispensed';
    public const ROUTING_REJECTED  = 'rejected';

    // ── the queue filter the app sends ────────────────────────────────────
    public const QUEUE_INCOMING  = 'incoming';
    public const QUEUE_PREPARING = 'preparing';
    public const QUEUE_READY     = 'ready';
    public const QUEUE_DISPENSED = 'dispensed';

    /** @var list<string> */
    public const QUEUE_STATUSES = [self::QUEUE_INCOMING, self::QUEUE_PREPARING, self::QUEUE_READY, self::QUEUE_DISPENSED];

    /** `stock_movements.reference_type` for a desk adjustment. */
    public const MOVEMENT_REFERENCE_TYPE = 'medicine_pharmacy_stock';

    /** Clinical statuses a counter can still act on. */
    private const OPEN_RX_STATUSES = ['active', 'partially_dispensed'];

    /** Routing statuses from which the counter has work to do. */
    private const OPEN_ROUTING_STATUSES = [self::ROUTING_PENDING, self::ROUTING_SENT, self::ROUTING_CONFIRMED, self::ROUTING_READY];

    /** Where the patient app opens from a notification. */
    private const ACTION_URL_PRESCRIPTIONS = '/prescriptions';
    private const ACTION_URL_RESERVATIONS  = '/pharmacy';

    public function __construct(
        private readonly PharmacyRoutingService $routing,
        private readonly PrescriptionService $prescriptions,
        private readonly PharmacyStockReportService $stockReports,
    ) {
    }

    // ══════════════════════════════════════════════════════════════════════
    // Facility context
    // ══════════════════════════════════════════════════════════════════════

    /**
     * The facility a provider token names. The id came from auth middleware,
     * not from the user, so the demo-isolation scope is lifted: a token bound
     * to a facility must resolve that facility whatever its `is_demo` flag.
     */
    public function facility(?string $facilityId): ?Facility
    {
        if (! $facilityId || ! Str::isUuid($facilityId)) {
            return null;
        }

        return Facility::withoutGlobalScope('isolate_demo')->find($facilityId);
    }

    /**
     * Directory listings (`care_facilities`) this facility owns — the rows
     * reservations and stock are keyed on. Resolved only through the explicit
     * `facility_id` link, never by name.
     *
     * @return list<string>
     */
    public function listingIds(string $facilityId): array
    {
        return CareFacility::query()
            ->where('facility_id', $facilityId)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * The listing to put a stock line or a route's contact details against —
     * the pharmacy listing when there is one, else any linked listing.
     */
    public function primaryListing(string $facilityId): ?CareFacility
    {
        return $this->stockReports->listingFor($facilityId)
            ?? CareFacility::query()->where('facility_id', $facilityId)->orderBy('created_at')->first();
    }

    /**
     * The ONE active dispensing route for a facility, created on first use.
     *
     * `pharmacy_type` is 'in_facility' because the route IS the facility's own
     * counter; `routing_method` 'api' because the script travels inside
     * OpesCare, not by fax or print.
     */
    public function routeFor(Facility $facility): PharmacyRoute
    {
        $existing = PharmacyRoute::query()
            ->where('facility_id', $facility->id)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $listing = $this->primaryListing($facility->id);

        return PharmacyRoute::create([
            'facility_id'    => $facility->id,
            'pharmacy_name'  => $facility->name,
            'pharmacy_type'  => 'in_facility',
            'contact_email'  => $listing?->email,
            'contact_phone'  => $listing?->phone_primary,
            'routing_method' => 'api',
            'is_active'      => true,
        ]);
    }

    /** @return list<string> ids of every active route that dispenses at this facility */
    public function routeIds(string $facilityId): array
    {
        return PharmacyRoute::query()
            ->where('facility_id', $facilityId)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    // ══════════════════════════════════════════════════════════════════════
    // Home
    // ══════════════════════════════════════════════════════════════════════

    /** @return array{facility: array{id: string, name: string}, counts: array<string, int>} */
    public function home(Facility $facility): array
    {
        $fid = (string) $facility->id;

        return [
            'facility' => ['id' => $fid, 'name' => (string) $facility->name],
            'counts'   => [
                'incoming'             => $this->queueQuery($fid, self::QUEUE_INCOMING)->count(),
                'preparing'            => $this->queueQuery($fid, self::QUEUE_PREPARING)->count(),
                'ready'                => $this->queueQuery($fid, self::QUEUE_READY)->count(),
                'reservations_pending' => $this->reservationsQuery($fid)->deskStatus(MedicineReservation::DESK_PENDING)->count(),
                'deliveries_open'      => $this->reservationsQuery($fid)->openDeliveries()->count(),
                'low_stock'            => $this->stockQuery($fid)->low()->count(),
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // Prescriptions
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Scripts routed to this facility, by bench position. No status = every
     * script the counter still has work on.
     *
     * @return Collection<int, Prescription>
     */
    public function queue(string $facilityId, ?string $status): Collection
    {
        return $this->queueQuery($facilityId, $status)
            ->with(['items', 'patient', 'prescriber:id,name'])
            ->orderByDesc('prescribed_at')
            ->limit(200)
            ->get();
    }

    private function queueQuery(string $facilityId, ?string $status): Builder
    {
        $query = Prescription::query()->whereIn('pharmacy_route_id', $this->routeIds($facilityId));

        return match ($status) {
            self::QUEUE_INCOMING => $query
                ->whereIn('pharmacy_routing_status', [self::ROUTING_PENDING, self::ROUTING_SENT])
                ->whereIn('status', self::OPEN_RX_STATUSES)
                ->tap(fn (Builder $q) => $this->notExpired($q)),
            self::QUEUE_PREPARING => $query
                ->where('pharmacy_routing_status', self::ROUTING_CONFIRMED)
                ->whereIn('status', self::OPEN_RX_STATUSES),
            self::QUEUE_READY => $query
                ->where('pharmacy_routing_status', self::ROUTING_READY)
                ->whereIn('status', self::OPEN_RX_STATUSES),
            self::QUEUE_DISPENSED => $query
                ->where(fn (Builder $q) => $q
                    ->where('pharmacy_routing_status', self::ROUTING_DISPENSED)
                    ->orWhere('status', 'dispensed')),
            default => $query
                ->whereIn('pharmacy_routing_status', self::OPEN_ROUTING_STATUSES)
                ->whereIn('status', self::OPEN_RX_STATUSES),
        };
    }

    private function notExpired(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    /**
     * Counter lookup by EXACT Health ID — never a prefix, never a LIKE. Patient
     * enumeration is forbidden platform-wide; the pharmacist has the card in
     * front of them or they have nothing.
     *
     * Returns the patient's open, unexpired scripts that this counter may
     * fill: the ones routed here and the ones routed nowhere yet. A script
     * routed to another pharmacy is left out — it is not this counter's to
     * fill, and listing it would say where the patient shops.
     *
     * An unknown Health ID is an empty answer, not an error: the response
     * shape is identical either way, so it says nothing about whether a
     * record exists.
     *
     * @return array{patient: ?Patient, prescriptions: Collection<int, Prescription>}
     */
    public function lookup(string $facilityId, string $healthId): array
    {
        $patient = Patient::resolveHealthId($healthId);

        if (! $patient) {
            return ['patient' => null, 'prescriptions' => new Collection()];
        }

        $routeIds = $this->routeIds($facilityId);

        $prescriptions = Prescription::query()
            ->with(['items', 'patient', 'prescriber:id,name'])
            ->where('patient_id', $patient->id)
            ->whereIn('status', self::OPEN_RX_STATUSES)
            ->tap(fn (Builder $q) => $this->notExpired($q))
            ->where(fn (Builder $q) => $q
                ->whereNull('pharmacy_route_id')
                ->orWhereIn('pharmacy_route_id', $routeIds))
            ->orderByDesc('prescribed_at')
            ->get();

        return ['patient' => $patient, 'prescriptions' => $prescriptions];
    }

    /**
     * A prescription this counter may act on: routed here, or not routed
     * anywhere yet. Routed elsewhere, or nonexistent, is the same 404.
     *
     * @throws PharmacyDeskException
     */
    public function find(Facility $facility, string $id): Prescription
    {
        $rx = Str::isUuid($id)
            ? Prescription::query()->with(['items', 'patient', 'prescriber:id,name'])->find($id)
            : null;

        if (! $rx) {
            throw PharmacyDeskException::notFound('prescription');
        }

        if ($rx->pharmacy_route_id !== null && ! in_array((string) $rx->pharmacy_route_id, $this->routeIds($facility->id), true)) {
            throw PharmacyDeskException::notFound('prescription');
        }

        return $rx;
    }

    /**
     * Claim a script for this counter: the un-routed walk-in script becomes
     * routed here (PharmacyRoutingService::assignPharmacy, then the FK). A
     * script already routed here is returned untouched. Never re-routes a
     * script away from another pharmacy.
     *
     * @throws PharmacyDeskException
     */
    public function routeToFacility(Prescription $rx, Facility $facility): Prescription
    {
        $route = $this->routeFor($facility);

        if ($rx->pharmacy_route_id !== null) {
            if ((string) $rx->pharmacy_route_id !== (string) $route->id
                && ! in_array((string) $rx->pharmacy_route_id, $this->routeIds($facility->id), true)) {
                throw PharmacyDeskException::notFound('prescription');
            }

            return $rx;
        }

        $listing = $this->primaryListing($facility->id);

        $rx = $this->routing->assignPharmacy((string) $rx->id, [
            'name'    => $facility->name,
            'address' => $listing?->address,
            'phone'   => $listing?->phone_primary,
        ]);

        $rx->update(['pharmacy_route_id' => $route->id]);

        return $rx->fresh(['items', 'patient', 'prescriber:id,name']);
    }

    /**
     * incoming → preparing. The counter has seen the script and is filling it.
     *
     * @throws PharmacyDeskException
     */
    public function accept(Facility $facility, Prescription $rx): Prescription
    {
        $rx = $this->routeToFacility($rx, $facility);

        if (! in_array($rx->pharmacy_routing_status, [self::ROUTING_PENDING, self::ROUTING_SENT], true)) {
            throw PharmacyDeskException::invalidTransition();
        }

        $this->assertDispensable($rx);

        return $this->routing->confirmReceipt((string) $rx->id)->load(['items', 'patient', 'prescriber:id,name']);
    }

    /**
     * preparing → ready. The medicines are bagged; the patient is told.
     *
     * @throws PharmacyDeskException
     */
    public function markReady(Facility $facility, Prescription $rx): Prescription
    {
        $rx = $this->routeToFacility($rx, $facility);

        if ($rx->pharmacy_routing_status !== self::ROUTING_CONFIRMED) {
            throw PharmacyDeskException::invalidTransition();
        }

        $this->assertDispensable($rx);

        $rx->update(['pharmacy_routing_status' => self::ROUTING_READY]);
        $rx = $rx->fresh(['items', 'patient', 'prescriber:id,name']);

        $this->notifyPatient(
            $rx->patient,
            'prescription_ready',
            'pharmacy_prescription_ready',
            ['pharmacy' => $facility->name, 'reference' => $rx->reference],
            self::ACTION_URL_PRESCRIPTIONS,
            ['prescription_id' => (string) $rx->id],
        );

        return $rx;
    }

    /**
     * Hand the medicines over. Allowed from any open bench position — a
     * walk-in patient with a script is dispensed to on the spot, without an
     * accept/ready ceremony — but never twice, and never for a script that
     * is voided, amended, entered in error or past its expiry.
     *
     * Per-item quantities and the pharmacist's note are recorded on the
     * items' `dispense_notes` (the one mutable field of an item); the
     * clinical order itself is untouched. PrescriptionService then marks
     * every item and the prescription dispensed, stamps the dispenser
     * (`$actorId` — the provider the token names) and audits it.
     *
     * @param  list<array{id: string, quantity_dispensed?: int|null}>  $items
     * @throws PharmacyDeskException
     */
    public function dispense(Facility $facility, Prescription $rx, string $actorId, array $items = [], ?string $notes = null): Prescription
    {
        $rx = $this->routeToFacility($rx, $facility);

        if ($rx->status === 'dispensed' || $rx->pharmacy_routing_status === self::ROUTING_DISPENSED) {
            throw PharmacyDeskException::invalidTransition();
        }

        $this->assertDispensable($rx);

        $lines = $rx->items->keyBy(fn (PrescriptionItem $item) => (string) $item->id);

        foreach ($items as $line) {
            if (! isset($lines[(string) ($line['id'] ?? '')])) {
                throw PharmacyDeskException::itemNotOnPrescription();
            }
        }

        $notes = $notes !== null ? trim($notes) : null;

        DB::transaction(function () use ($rx, $actorId, $items, $notes, $lines) {
            $touched = [];

            foreach ($items as $line) {
                /** @var PrescriptionItem $item */
                $item  = $lines[(string) $line['id']];
                $parts = [];

                if (isset($line['quantity_dispensed'])) {
                    $parts[] = __('pharmacy_api.dispense_note', ['quantity' => (int) $line['quantity_dispensed']]);
                }
                if ($notes !== null && $notes !== '') {
                    $parts[] = $notes;
                }

                if ($parts !== []) {
                    $item->dispense_notes = implode(' · ', $parts);
                    $item->save();
                    $touched[(string) $item->id] = true;
                }
            }

            // A note with no per-item lines applies to every line.
            if ($notes !== null && $notes !== '' && $items === []) {
                foreach ($lines as $item) {
                    $item->dispense_notes = $notes;
                    $item->save();
                }
            }

            try {
                // No 'notes' option on purpose: the service would copy it onto
                // every item and overwrite the per-item lines written above.
                $this->prescriptions->dispense($rx, $actorId, []);
            } catch (\LogicException $e) {
                throw $e->getMessage() === 'PRESCRIPTION_ALREADY_DISPENSED'
                    ? PharmacyDeskException::invalidTransition()
                    : PharmacyDeskException::notDispensable();
            }

            $this->routing->markDispensed((string) $rx->id);
        });

        $rx = $rx->fresh(['items', 'patient', 'prescriber:id,name']);

        $this->notifyPatient(
            $rx->patient,
            'prescription_dispensed',
            'pharmacy_prescription_dispensed',
            ['pharmacy' => $facility->name, 'reference' => $rx->reference],
            self::ACTION_URL_PRESCRIPTIONS,
            ['prescription_id' => (string) $rx->id],
        );

        return $rx;
    }

    /** @throws PharmacyDeskException */
    private function assertDispensable(Prescription $rx): void
    {
        if (! $rx->isDispensable()) {
            throw PharmacyDeskException::notDispensable();
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // Reservations
    // ══════════════════════════════════════════════════════════════════════

    private function reservationsQuery(string $facilityId): Builder
    {
        return MedicineReservation::query()->whereIn('care_facility_id', $this->listingIds($facilityId));
    }

    /**
     * Reservations placed at this pharmacy. No status = everything still
     * open (pending, accepted, ready — deliveries on the road included).
     *
     * @return Collection<int, MedicineReservation>
     */
    public function reservations(string $facilityId, ?string $status): Collection
    {
        $query = $this->reservationsQuery($facilityId)->with(['medicine', 'patient']);

        if ($status !== null && $status !== '') {
            $query->deskStatus($status);
        } else {
            $query->whereIn('status', [
                MedicineReservation::STATUS_PENDING,
                MedicineReservation::STATUS_ACCEPTED,
                MedicineReservation::STATUS_READY,
            ]);
        }

        return $query->orderByDesc('created_at')->limit(200)->get();
    }

    /** @throws PharmacyDeskException */
    public function findReservation(string $facilityId, string $id): MedicineReservation
    {
        $reservation = Str::isUuid($id)
            ? $this->reservationsQuery($facilityId)->with(['medicine', 'patient', 'careFacility'])->find($id)
            : null;

        if (! $reservation) {
            throw PharmacyDeskException::notFound('reservation');
        }

        return $reservation;
    }

    /**
     * The reservation state machine.
     *
     *   pending → accepted → ready → collected             (pickup)
     *   pending → accepted → ready → dispatched → delivered (delivery)
     *   any open state → cancelled, with a reason
     *
     * Anything else is a 409 invalid_transition. The row is never rewound and
     * never deleted; a cancelled hold keeps its history.
     *
     * @param  'accept'|'ready'|'collected'|'dispatch'|'delivered'|'cancel'  $action
     * @throws PharmacyDeskException
     */
    public function transitionReservation(MedicineReservation $reservation, string $action, ?string $reason = null, ?string $pharmacyName = null): MedicineReservation
    {
        $from = $reservation->deskStatus();
        $now  = now();

        // A closed hold — collected, delivered or cancelled — admits nothing,
        // whatever the step: the answer is always "not from here".
        if (in_array($from, [MedicineReservation::DESK_COLLECTED, MedicineReservation::DESK_DELIVERED, MedicineReservation::DESK_CANCELLED], true)) {
            throw PharmacyDeskException::invalidTransition();
        }

        switch ($action) {
            case 'accept':
                $this->requireFrom($from, [MedicineReservation::DESK_PENDING]);
                $reservation->fill([
                    'status'       => MedicineReservation::STATUS_ACCEPTED,
                    'accepted_at'  => $now,
                    'confirmed_at' => $reservation->confirmed_at ?? $now,
                ])->save();
                break;

            case 'ready':
                $this->requireFrom($from, [MedicineReservation::DESK_ACCEPTED]);
                $reservation->fill([
                    'status'   => MedicineReservation::STATUS_READY,
                    'ready_at' => $now,
                ])->save();
                $this->notifyReservation($reservation, 'reservation_ready', 'pharmacy_reservation_ready', $pharmacyName);
                break;

            case 'collected':
                if ($reservation->isDelivery()) {
                    throw PharmacyDeskException::collectIsForPickup();
                }
                $this->requireFrom($from, [MedicineReservation::DESK_READY]);
                $reservation->fill([
                    'status'       => MedicineReservation::STATUS_COLLECTED,
                    'collected_at' => $now,
                ])->save();
                break;

            case 'dispatch':
                if (! $reservation->isDelivery()) {
                    throw PharmacyDeskException::dispatchIsForDelivery();
                }
                $this->requireFrom($from, [MedicineReservation::DESK_READY]);
                $reservation->fill([
                    'delivery_status' => MedicineReservation::DELIVERY_DISPATCHED,
                    'dispatched_at'   => $now,
                ])->save();
                $this->notifyReservation($reservation, 'reservation_dispatched', 'pharmacy_reservation_dispatched', $pharmacyName);
                break;

            case 'delivered':
                $this->requireFrom($from, [MedicineReservation::DESK_DISPATCHED]);
                $reservation->fill([
                    'status'          => MedicineReservation::STATUS_COLLECTED,
                    'delivery_status' => MedicineReservation::DELIVERY_DELIVERED,
                    'delivered_at'    => $now,
                    'collected_at'    => $now,
                ])->save();
                $this->notifyReservation($reservation, 'reservation_delivered', 'pharmacy_reservation_delivered', $pharmacyName);
                break;

            case 'cancel':
                $this->requireFrom($from, [
                    MedicineReservation::DESK_PENDING,
                    MedicineReservation::DESK_ACCEPTED,
                    MedicineReservation::DESK_READY,
                    MedicineReservation::DESK_DISPATCHED,
                ]);
                $reservation->fill([
                    'status'           => MedicineReservation::STATUS_CANCELLED,
                    'cancelled_at'     => $now,
                    'cancelled_reason' => $reason !== null ? Str::limit(trim($reason), 255, '') : null,
                ])->save();
                break;

            default:
                throw PharmacyDeskException::invalidTransition();
        }

        return $reservation->refresh()->load(['medicine', 'patient']);
    }

    /**
     * @param  list<string>  $allowed
     * @throws PharmacyDeskException
     */
    private function requireFrom(string $from, array $allowed): void
    {
        if (! in_array($from, $allowed, true)) {
            throw PharmacyDeskException::invalidTransition();
        }
    }

    private function notifyReservation(MedicineReservation $reservation, string $key, string $type, ?string $pharmacyName): void
    {
        $patient = $reservation->relationLoaded('patient') ? $reservation->patient : $reservation->patient()->first();

        $pharmacyName ??= $reservation->careFacility?->facility_name;

        $this->notifyPatient(
            $patient,
            $key,
            $type,
            ['pharmacy' => (string) $pharmacyName, 'reference' => (string) $reservation->reference],
            self::ACTION_URL_RESERVATIONS,
            ['reservation_id' => (string) $reservation->id],
        );
    }

    // ══════════════════════════════════════════════════════════════════════
    // Stock
    // ══════════════════════════════════════════════════════════════════════

    private function stockQuery(string $facilityId): Builder
    {
        return MedicinePharmacyStock::query()->whereIn('care_facility_id', $this->listingIds($facilityId));
    }

    /**
     * The pharmacy's public listing lines. `$q` searches the CATALOGUE
     * (medicine names — not personal data, so substring matching is fine);
     * `$lowOnly` keeps the lines that need attention.
     *
     * @return Collection<int, MedicinePharmacyStock>
     */
    public function stock(string $facilityId, ?string $q, bool $lowOnly): Collection
    {
        $query = $this->stockQuery($facilityId)->with('medicine');

        if ($q !== null && trim($q) !== '') {
            $term = trim($q);
            $query->whereHas('medicine', fn (Builder $m) => $m->matchingTerm($term));
        }

        if ($lowOnly) {
            $query->low();
        }

        return $query
            ->orderBy(Medicine::select('name')->whereColumn('medicines.id', 'medicine_pharmacy_stocks.medicine_id'))
            ->limit(500)
            ->get();
    }

    /** @throws PharmacyDeskException */
    public function findStock(string $facilityId, string $id): MedicinePharmacyStock
    {
        $stock = Str::isUuid($id)
            ? $this->stockQuery($facilityId)->with(['medicine', 'careFacility'])->find($id)
            : null;

        if (! $stock) {
            throw PharmacyDeskException::notFound('stock');
        }

        return $stock;
    }

    /**
     * Change a line's pack count by `$delta` and say why.
     *
     * The count can never go below zero (422 — the request is wrong, not the
     * state). The new status is derived from the new count against the line's
     * reorder level, and the write goes through PharmacyStockReportService so
     * it is stamped, audited and — if the row was seeded fiction — claimed
     * as real. Then one StockMovement row: the ledger entry that says who
     * moved how much, and why.
     *
     * @throws PharmacyDeskException
     */
    public function adjustStock(Facility $facility, MedicinePharmacyStock $stock, int $delta, string $reason, string $actorId): MedicinePharmacyStock
    {
        return DB::transaction(function () use ($facility, $stock, $delta, $reason, $actorId) {
            /** @var MedicinePharmacyStock $locked */
            $locked = MedicinePharmacyStock::query()->whereKey($stock->id)->lockForUpdate()->firstOrFail();

            $before = (int) ($locked->packs_available ?? 0);
            $after  = $before + $delta;

            if ($after < 0) {
                throw PharmacyDeskException::stockNegative($before, $delta);
            }

            $listing  = $locked->careFacility;
            $medicine = $locked->medicine;

            if (! $listing || ! $medicine) {
                throw PharmacyDeskException::notFound('stock');
            }

            $this->stockReports->report($listing, $medicine, [
                'stock_status'        => $locked->statusForQuantity($after),
                'packs_available'     => $after,
                'pack_size'           => $locked->pack_size,
                'unit_price'          => $locked->unit_price,
                'reservation_enabled' => (bool) $locked->reservation_enabled,
            ], $actorId);

            StockMovement::create([
                'facility_id'       => $facility->id,
                'inventory_item_id' => null,
                'movement_type'     => 'adjustment',
                'quantity'          => $delta,
                'reference_type'    => self::MOVEMENT_REFERENCE_TYPE,
                'reference_id'      => $locked->id,
                'reason'            => __('pharmacy_api.movement_reason', ['reason' => trim($reason)]),
                'performed_by'      => $actorId,
                'performed_at'      => now(),
            ]);

            $this->stockReports->touchListingFreshness($listing);

            return $locked->fresh(['medicine']);
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    // Telling the patient
    // ══════════════════════════════════════════════════════════════════════

    /**
     * One bell entry (the `notifications` table the patient app's feed reads)
     * and, when the push layer is present, one push — in the patient's own
     * language. Best-effort on both counts: the counter action is already
     * committed, and telling the patient must never undo it.
     *
     * Lock-screen rule: the title and message name the pharmacy and the
     * reference, never a medicine.
     *
     * @param  array<string, string>  $params
     * @param  array<string, string>  $data
     */
    private function notifyPatient(?Patient $patient, string $key, string $type, array $params, string $actionUrl, array $data): void
    {
        if (! $patient) {
            return;
        }

        $hasPush = class_exists(PatientPush::class) && method_exists(PatientPush::class, 'send');
        $locale  = $hasPush && method_exists(PatientPush::class, 'localeFor')
            ? PatientPush::localeFor($patient)
            : app()->getLocale();

        $title   = __("pharmacy_api.notifications.{$key}.title", $params, $locale);
        $message = __("pharmacy_api.notifications.{$key}.message", $params, $locale);
        $action  = __("pharmacy_api.notifications.{$key}.action", [], $locale);

        rescue(fn () => $patient->notifications()->create([
            'id'      => (string) Str::uuid(),
            'type'    => $type,
            'data'    => array_merge([
                'type'         => $type,
                'title'        => $title,
                'message'      => $message,
                'severity'     => 'normal',
                'action_url'   => $actionUrl,
                'action_label' => $action,
            ], $data),
            'read_at' => null,
        ]));

        if ($hasPush) {
            // Typed so the app lands on the reservation or prescription screen
            // (the sound is the message chime either way).
            $pushType = isset($data['reservation_id']) ? 'reservation' : 'prescription';
            rescue(fn () => PatientPush::send(
                $patient,
                $pushType,
                $title,
                $message,
                array_merge($data, ['action_url' => $actionUrl]),
            ), null, false);
        }
    }
}
