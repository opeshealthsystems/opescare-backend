<?php

namespace App\Http\Controllers\Api\Mobile\Lab;

use App\Http\Resources\LabDesk\LabDeskHomeResource;
use App\Http\Resources\LabDesk\LabDeskOrderDetailResource;
use App\Http\Resources\LabDesk\LabDeskOrderResource;
use App\Models\LabOrder;
use App\Services\Lab\LabDeskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Mobile Provider API — the LAB DESK's order work.
 *
 *   GET  /api/mobile/lab/home                 counts for the home screen
 *   GET  /api/mobile/lab/orders               the worklist (?status=&urgency=)
 *   GET  /api/mobile/lab/orders/{id}          one order with results + ranges
 *   POST /api/mobile/lab/orders/{id}/collect  the sample is in
 *   POST /api/mobile/lab/orders/{id}/results  append analytes
 *   POST /api/mobile/lab/orders/{id}/release  release to patient + clinician
 *
 * Gate: `capability:lab_results`; `/collect` is also open to
 * `sample_collection` (routes/mobile_lab_desk.php). The rules — facility
 * scope, append-only results, final release, the flag vocabulary — live in
 * LabDeskService; this file validates input and shapes output.
 */
class LabDeskController extends LabDeskBaseController
{
    public function home(Request $request): JsonResponse
    {
        $this->applyLocale($request);

        $facilityId = $this->facilityId($request);
        if ($facilityId === null) {
            return $this->noFacility();
        }

        return response()->json([
            'data' => LabDeskHomeResource::make($this->desk->home($facilityId)),
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $this->applyLocale($request);

        $facilityId = $this->facilityId($request);
        if ($facilityId === null) {
            return $this->noFacility();
        }

        $validated = $request->validate([
            'status'  => ['nullable', 'string', Rule::in(['ordered', 'collected', 'in_progress', 'resulted'])],
            'urgency' => ['nullable', 'string', Rule::in(['routine', 'urgent', 'stat'])],
        ]);

        $status  = $validated['status'] ?? null;
        $urgency = $validated['urgency'] ?? null;

        $orders = $this->desk->orders($facilityId, $status, $urgency);

        $this->audit(
            $request,
            $orders->pluck('patient_id')->all(),
            'lab_desk_worklist',
            'LabOrder',
            null,
            'view'
        );

        return response()->json([
            'data' => LabDeskOrderResource::collection($orders),
            'meta' => [
                'status'  => $status ?? 'open',
                'urgency' => $urgency,
                'count'   => $orders->count(),
            ],
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->applyLocale($request);

        $order = $this->order($request, $id);
        if (! $order instanceof LabOrder) {
            return $order;
        }

        $this->audit($request, [$order->patient_id], 'lab_desk_review', 'LabOrder', $order->id, 'view');

        return $this->detail($order);
    }

    public function collect(Request $request, string $id): JsonResponse
    {
        $this->applyLocale($request);

        $order = $this->order($request, $id);
        if (! $order instanceof LabOrder) {
            return $order;
        }

        $collected = $this->desk->collect($order, $this->userId($request));

        $this->audit($request, [$order->patient_id], 'lab_desk_sample_collection', 'LabOrder', $order->id, 'update');

        return $this->detail($collected);
    }

    public function results(Request $request, string $id): JsonResponse
    {
        $this->applyLocale($request);

        $order = $this->order($request, $id);
        if (! $order instanceof LabOrder) {
            return $order;
        }

        $validated = $request->validate([
            'results'                   => ['required', 'array', 'min:1', 'max:100'],
            'results.*.parameter_name'  => ['required', 'string', 'max:200'],
            'results.*.value'           => ['required', function (string $attribute, mixed $value, \Closure $fail) {
                if (! is_scalar($value) || is_bool($value)) {
                    $fail(__('lab_api.validation.value_scalar'));

                    return;
                }
                $length = mb_strlen(trim((string) $value));
                if ($length < 1 || $length > 100) {
                    $fail(__('lab_api.validation.value_scalar'));
                }
            }],
            'results.*.unit'            => ['nullable', 'string', 'max:50'],
            'results.*.reference_range' => ['nullable', 'string', 'max:100'],
            'results.*.flag'            => ['nullable', 'string', Rule::in([...LabDeskService::FLAGS, ...array_map('strtolower', LabDeskService::FLAGS)])],
            'results.*.loinc_code'      => ['nullable', 'string', 'max:30'],
            'results.*.notes'           => ['nullable', 'string', 'max:1000'],
        ], [
            'results.required'  => __('lab_api.validation.results_required'),
            'results.min'       => __('lab_api.validation.results_required'),
            'results.*.flag.in' => __('lab_api.validation.flag'),
        ]);

        $updated = $this->desk->addResults($order, array_values($validated['results']), $this->userId($request));

        $this->audit($request, [$order->patient_id], 'lab_desk_result_entry', 'LabOrder', $order->id, 'create');

        return $this->detail($updated, 201);
    }

    public function release(Request $request, string $id): JsonResponse
    {
        $this->applyLocale($request);

        $order = $this->order($request, $id);
        if (! $order instanceof LabOrder) {
            return $order;
        }

        $released = $this->desk->release($order, $this->userId($request));

        $this->audit($request, [$order->patient_id], 'lab_desk_release', 'LabOrder', $order->id, 'update');

        return $this->detail($released);
    }

    // ──────────────────────────────────────────────────────────────────

    /**
     * The order in this facility's scope, or the response that says it is
     * not — a 404 whether it exists elsewhere or not at all.
     */
    private function order(Request $request, string $id): LabOrder|JsonResponse
    {
        $facilityId = $this->facilityId($request);
        if ($facilityId === null) {
            return $this->noFacility();
        }

        return $this->desk->findOrder($facilityId, $id)
            ?? $this->notFound('lab_api.order_not_found', 'order_not_found');
    }

    private function detail(LabOrder $order, int $status = 200): JsonResponse
    {
        $order = $this->desk->detail($order);

        return response()->json([
            'data' => LabDeskOrderDetailResource::make($order)
                ->withReferenceRanges($this->desk->referenceRanges($order)),
        ], $status);
    }
}
