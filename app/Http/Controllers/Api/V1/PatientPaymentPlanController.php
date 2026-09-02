<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PatientPaymentPlanResource;
use App\Http\Resources\PaymentPlanInstallmentResource;
use App\Models\PatientPaymentPlan;
use App\Services\Billing\PaymentPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class PatientPaymentPlanController extends Controller
{
    public function __construct(private readonly PaymentPlanService $service) {}

    public function store(Request $request): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $validated = $request->validate([
            'patient_id'         => ['required', 'uuid', 'exists:patients,id'],
            'invoice_id'         => ['required', 'uuid', 'exists:invoices,id'],
            'total_amount'       => ['required', 'numeric', 'min:0.01'],
            'down_payment'       => ['sometimes', 'numeric', 'min:0'],
            'installment_amount' => ['required', 'numeric', 'min:0.01'],
            'installment_count'  => ['required', 'integer', 'min:1', 'max:120'],
            'frequency'          => ['required', 'in:weekly,biweekly,monthly'],
            'next_due_date'      => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'notes'              => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);

        $validated['facility_id'] = $facilityId;

        try {
            $plan = $this->service->createPlan($validated);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['total_amount' => [$e->getMessage()]]], 422);
        }

        return response()->json(['data' => PatientPaymentPlanResource::make($plan)], Response::HTTP_CREATED);
    }

    public function show(string $id): JsonResponse
    {
        $summary = $this->service->getPlanSummary($id);
        return response()->json(['data' => $summary]);
    }

    public function recordPayment(Request $request, string $id, string $installmentId): JsonResponse
    {
        $validated = $request->validate([
            'amount'    => ['required', 'numeric', 'min:0.01'],
            'reference' => ['required', 'string', 'max:100'],
        ]);

        $plan = PatientPaymentPlan::findOrFail($id);
        abort_unless(
            $plan->installments()->where('id', $installmentId)->exists(),
            Response::HTTP_NOT_FOUND,
            'Installment not found for this plan.',
        );

        $installment = $this->service->recordInstallmentPayment(
            $installmentId,
            (float) $validated['amount'],
            $validated['reference'],
        );

        return response()->json(['data' => PaymentPlanInstallmentResource::make($installment)]);
    }

    public function forPatient(Request $request, string $patientId): JsonResponse
    {
        $facilityId = $request->attributes->get('facility_id');
        if (!$facilityId) {
            return response()->json(['message' => __('api.facility_unresolved'), 'error_code' => 'FACILITY_UNRESOLVABLE'], 403);
        }

        $plans = PatientPaymentPlan::where('patient_id', $patientId)
            ->where('facility_id', $facilityId)
            ->withCount('installments')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => PatientPaymentPlanResource::collection($plans)]);
    }
}
