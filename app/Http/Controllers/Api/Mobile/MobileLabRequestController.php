<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\PresentsLabResults;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabTestRequest;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Mobile Patient API — asking for a laboratory test, and giving the sample.
 *
 * The write here is a REQUEST, never an order. A patient cannot place a
 * laboratory order in this system and this controller does not let them: the
 * row lands in `lab_test_requests` with status `submitted`, and only a
 * clinician can turn it into a `lab_orders` row. App\Models\LabTestRequest
 * enforces the rest — the record is immutable and can be cancelled but never
 * deleted, the same way App\Models\Prescription is.
 *
 * The catalogue is whatever laboratories have actually published
 * (`lab_test_availability`). When nothing is published the endpoint returns an
 * empty list and the app says so; it does not fall back to a hardcoded menu of
 * tests no nearby laboratory can run.
 *
 * Sample collection reads real appointments and real uncollected orders. Sample
 * type, fasting hours and preparation instructions are deliberately absent from
 * the payload: no table in this schema records them, and inventing preparation
 * advice for a clinical procedure is the one thing this app must never do.
 *
 * Isolation: `patient_id` comes only from `$request->attributes`, set by
 * AuthenticateMobilePatient from the bearer token — never from the route, the
 * query string, a header or the body.
 */
class MobileLabRequestController extends Controller
{
    use PresentsLabResults;

    /** Below this many characters the catalogue's default page is returned, not a search. */
    private const MIN_SEARCH_LENGTH = 2;

    /** Catalogue page size. The app shows fewer; this bounds the query. */
    private const CATALOGUE_LIMIT = 60;

    /** How many lab visits the collection screen can switch between. */
    private const VISIT_LIMIT = 10;

    /**
     * GET /api/mobile/labs/catalogue?q=
     *
     * The orderable tests laboratories have published, de-duplicated by code.
     *
     * `category` is null throughout: `lab_test_availability` records no
     * category and this endpoint will not invent one by guessing from the
     * test's name. The app already renders the row without it.
     */
    public function catalogue(Request $request): JsonResponse
    {
        $this->resolvePatientId($request);

        $term = trim((string) $request->query('q', ''));

        $query = DB::table('lab_test_availability')
            ->whereNotIn(DB::raw('lower(availability_status)'), ['unavailable', 'discontinued', 'withdrawn']);

        // Provenance allow-list: only rows a real source published. Seeded,
        // fixture or unclaimed (NULL) rows never reach a patient or prescriber.
        \App\Support\AvailabilityProvenance::constrain($query, 'lab_test_availability.source_system', \App\Models\LabTestAvailability::LIVE_SOURCE_SYSTEMS);

        if (mb_strlen($term) >= self::MIN_SEARCH_LENGTH) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

            $query->where(function ($sub) use ($like) {
                $sub->where('test_name', 'ilike', $like)
                    ->orWhere('local_test_code', 'ilike', $like)
                    ->orWhere('loinc_code', 'ilike', $like);
            });
        }

        $rows = $query
            ->orderBy('test_name')
            ->limit(self::CATALOGUE_LIMIT * 4)
            ->get(['test_name', 'local_test_code', 'loinc_code', 'specimen_type']);

        // One entry per test, not one per facility that offers it: the patient
        // is choosing a test, not a laboratory.
        $catalogue = [];
        foreach ($rows as $row) {
            $code = $this->blankToNull($row->local_test_code)
                ?? $this->blankToNull($row->loinc_code)
                ?? Str::upper(Str::slug((string) $row->test_name, '_'));

            if ($code === '' || isset($catalogue[$code])) {
                continue;
            }

            $catalogue[$code] = [
                'code'     => $code,
                'name'     => (string) $row->test_name,
                'category' => null,
                'specimen' => $this->blankToNull($row->specimen_type),
            ];

            if (count($catalogue) >= self::CATALOGUE_LIMIT) {
                break;
            }
        }

        return response()->json(['data' => array_values($catalogue)]);
    }

    /**
     * POST /api/mobile/labs/requests
     *
     * File a request with the care team. Returns 201 with the stored request.
     *
     * `test_code` must name a test a laboratory has actually published — a free
     * text code would put an unrunnable test in a clinician's queue. The
     * request is written from the token's patient and the catalogue's own name
     * for the test; nothing about the patient comes from the body.
     */
    public function store(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);

        $data = $request->validate([
            'test_code'      => ['required', 'string', 'max:120'],
            'priority'       => ['sometimes', 'nullable', Rule::in(LabTestRequest::PRIORITIES)],
            'clinical_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $test = $this->findCatalogueEntry($data['test_code']);

        if ($test === null) {
            return response()->json([
                'message' => __('labs.request.unknown_test'),
                'errors'  => ['test_code' => [__('labs.request.unknown_test')]],
            ], 422);
        }

        $labRequest = LabTestRequest::create([
            'patient_id'     => $patientId,
            'facility_id'    => $test['facility_id'],
            'test_code'      => $test['code'],
            'test_name'      => $test['name'],
            'loinc_code'     => $test['loinc_code'],
            'priority'       => $data['priority'] ?? 'routine',
            'status'         => 'submitted',
            'clinical_notes' => $this->blankToNull($data['clinical_notes'] ?? null),
            'submitted_at'   => now(),
            // Provenance matches the patient the request belongs to, so a demo
            // reset never sweeps a real patient's request and vice versa.
            'is_demo'        => (bool) (Patient::query()->whereKey($patientId)->value('is_demo') ?? config('demo.enabled', false)),
        ]);

        return response()->json(['data' => $this->presentRequest($labRequest)], 201);
    }

    /**
     * GET /api/mobile/labs/collection[?appointment_id=]
     *
     * The lab visit to show, the other lab visits on file, the orders still
     * waiting on a sample, and the patient block.
     *
     * The visit shown is the named one, else the soonest upcoming, else the
     * most recent. Rescheduling and cancelling are not offered here — the
     * appointment screens own those writes.
     */
    public function collection(Request $request): JsonResponse
    {
        $patientId = $this->resolvePatientId($request);
        $patient   = Patient::query()->find($patientId);

        $visits = $this->labVisits($patientId);
        $chosen = $this->chooseVisit($visits, $request->query('appointment_id'));

        $pending = $this->pendingOrders($patientId, $patient);

        return response()->json([
            'data' => [
                'appointment' => $chosen !== null ? $this->presentVisit($chosen) : null,
                'others'      => $visits
                    ->reject(fn (Appointment $a) => $chosen !== null && $a->id === $chosen->id)
                    ->map(fn (Appointment $a) => $this->presentVisit($a))
                    ->values()
                    ->all(),
                'pending'     => $pending,
                'patient'     => $this->presentPatient($patient),
            ],
        ]);
    }

    // ── Catalogue ────────────────────────────────────────────────────────────

    /**
     * One published catalogue entry by code, or null.
     *
     * @return array<string, mixed>|null
     */
    private function findCatalogueEntry(string $code): ?array
    {
        $needle = trim($code);

        if ($needle === '') {
            return null;
        }

        $live = DB::table('lab_test_availability');
        \App\Support\AvailabilityProvenance::constrain($live, 'lab_test_availability.source_system', \App\Models\LabTestAvailability::LIVE_SOURCE_SYSTEMS);

        $row = $live
            ->whereNotIn(DB::raw('lower(availability_status)'), ['unavailable', 'discontinued', 'withdrawn'])
            ->where(function ($query) use ($needle) {
                $query->whereRaw('lower(local_test_code) = ?', [mb_strtolower($needle)])
                    ->orWhereRaw('lower(loinc_code) = ?', [mb_strtolower($needle)]);
            })
            ->orderBy('test_name')
            ->first(['facility_id', 'test_name', 'local_test_code', 'loinc_code']);

        if ($row === null) {
            return null;
        }

        return [
            // `lab_test_availability.facility_id` points at care_facilities;
            // the request stores the facility only as context for the reviewer.
            'facility_id' => $this->facilityIdFor($row->facility_id),
            'code'        => $this->blankToNull($row->local_test_code) ?? (string) $row->loinc_code,
            'name'        => (string) $row->test_name,
            'loinc_code'  => $this->blankToNull($row->loinc_code),
        ];
    }

    /**
     * `care_facilities.id` → the `facilities.id` the rest of the schema uses.
     * Null when the listing is not linked to a registered facility — a request
     * without a facility is still a valid request.
     */
    private function facilityIdFor(?string $careFacilityId): ?string
    {
        if ($careFacilityId === null) {
            return null;
        }

        $facilityId = DB::table('care_facilities')->where('id', $careFacilityId)->value('facility_id');

        return $facilityId !== null ? (string) $facilityId : null;
    }

    /** @return array<string, mixed> */
    private function presentRequest(LabTestRequest $request): array
    {
        return [
            'id'             => (string) $request->id,
            'test_code'      => $request->test_code,
            'test_name'      => $request->test_name,
            'priority'       => $request->priority,
            'status'         => $request->status,
            'clinical_notes' => $request->clinical_notes,
            'submitted_at'   => $request->submitted_at?->toIso8601String(),
        ];
    }

    // ── Sample collection ────────────────────────────────────────────────────

    /**
     * The patient's laboratory appointments.
     *
     * A visit counts as a lab visit when its type says so, or when it is the
     * encounter a still-uncollected lab order was raised against — the second
     * catches a general consultation the laboratory is drawing blood at.
     *
     * @return Collection<int, Appointment>
     */
    private function labVisits(string $patientId): Collection
    {
        $visitIds = LabOrder::query()
            ->where('patient_id', $patientId)
            ->whereNull('collected_at')
            ->whereNotNull('visit_id')
            ->pluck('visit_id')
            ->filter()
            ->unique()
            ->all();

        return Appointment::query()
            ->where('patient_id', $patientId)
            ->where(function ($query) use ($visitIds) {
                $query->where('appointment_type', 'ilike', '%lab%')
                    ->orWhere('appointment_type', 'ilike', '%sample%')
                    ->orWhere('appointment_type', 'ilike', '%analyse%');

                if ($visitIds !== []) {
                    $query->orWhereIn('visit_id', $visitIds);
                }
            })
            ->with(['facility:id,name', 'provider:id,name'])
            ->orderBy('scheduled_at')
            ->limit(self::VISIT_LIMIT)
            ->get();
    }

    /**
     * The visit to show: the named one, else the soonest upcoming, else the
     * most recent.
     *
     * @param  Collection<int, Appointment>  $visits
     */
    private function chooseVisit(Collection $visits, mixed $requestedId): ?Appointment
    {
        if ($visits->isEmpty()) {
            return null;
        }

        $id = is_string($requestedId) ? trim($requestedId) : '';

        if ($id !== '' && Str::isUuid($id)) {
            // Only from the patient's OWN visits — an id naming someone else's
            // appointment simply does not match, and the fallback applies.
            $named = $visits->firstWhere('id', $id);
            if ($named !== null) {
                return $named;
            }
        }

        return $visits->first(fn (Appointment $a) => $a->scheduled_at !== null && $a->scheduled_at->isFuture())
            ?? $visits->sortByDesc('scheduled_at')->first();
    }

    /**
     * One lab visit — the finder's appointment shape plus what a collection
     * needs. `facility_address` and `duration_minutes` are null when the
     * schema holds none; the app prints "—" rather than a guess.
     *
     * @return array<string, mixed>
     */
    private function presentVisit(Appointment $appointment): array
    {
        return [
            'id'               => (string) $appointment->id,
            'appointment_type' => $appointment->appointment_type,
            'status'           => $appointment->status,
            'facility_name'    => $appointment->facility?->name,
            'provider_name'    => $appointment->provider?->name,
            'scheduled_at'     => $appointment->scheduled_at?->toIso8601String(),
            'checked_in_at'    => $appointment->checked_in_at?->toIso8601String(),
            'reason'           => $appointment->reason,
            'is_upcoming'      => $appointment->scheduled_at !== null && $appointment->scheduled_at->isFuture(),
            'facility_address' => $this->facilityAddress($appointment->facility_id),
            'duration_minutes' => $this->slotDuration($appointment->appointment_slot_id),
        ];
    }

    /**
     * The street address, from the care-map listing for this facility.
     * `facilities` itself holds no address column.
     */
    private function facilityAddress(?string $facilityId): ?string
    {
        if ($facilityId === null) {
            return null;
        }

        $address = DB::table('care_facilities')
            ->where('facility_id', $facilityId)
            ->whereNotNull('address')
            ->orderByDesc('last_profile_update_at')
            ->value('address');

        return $this->blankToNull($address !== null ? (string) $address : null);
    }

    /**
     * Minutes, from the booked slot's own start and end. Null when the
     * appointment was not booked against a slot — the length is then genuinely
     * unknown and must not be assumed.
     */
    private function slotDuration(?string $slotId): ?int
    {
        if ($slotId === null) {
            return null;
        }

        $slot = DB::table('appointment_slots')->where('id', $slotId)->first(['starts_at', 'ends_at']);

        if ($slot === null || $slot->starts_at === null || $slot->ends_at === null) {
            return null;
        }

        $minutes = (int) round(
            Carbon::parse($slot->starts_at)->diffInSeconds(Carbon::parse($slot->ends_at), true) / 60
        );

        return $minutes > 0 ? $minutes : null;
    }

    /**
     * Lab orders the laboratory is still waiting on a sample for: not
     * cancelled, not resulted, and with no collection recorded.
     *
     * @return array<int, array<string, mixed>>
     */
    private function pendingOrders(string $patientId, ?Patient $patient): array
    {
        $orders = LabOrder::query()
            ->where('patient_id', $patientId)
            ->whereNull('collected_at')
            ->whereNotIn(DB::raw('lower(status)'), [
                'resulted', 'completed', 'final', 'released', 'verified',
                'cancelled', 'canceled', 'voided', 'entered_in_error', 'rejected',
            ])
            ->orderByDesc('ordered_at')
            ->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $results = LabResult::query()
            ->whereIn('lab_order_id', $orders->pluck('id')->all())
            ->get()
            ->groupBy('lab_order_id');

        $ranges = $this->loadReferenceRanges($results->flatten());
        $dob    = $this->patientDateOfBirth($patient);

        return $orders
            ->map(fn (LabOrder $order) => $this->presentOrder(
                $order,
                $results->get($order->id, collect()),
                $ranges,
                $patient?->sex,
                $dob
            ))
            ->values()
            ->all();
    }
}
