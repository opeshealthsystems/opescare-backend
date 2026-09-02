<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Documents\DocumentIssuanceService;
use App\Services\Pharmacy\ControlledSubstanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class ControlledSubstanceController extends Controller {
    public function __construct(
        private readonly ControlledSubstanceService $service,
        private readonly DocumentIssuanceService    $issuance
    ) {}

    public function dispense(Request $request): JsonResponse {
        $validated = $request->validate([
            'facility_id'          => ['required','uuid','exists:facilities,id'],
            'patient_id'           => ['required','uuid','exists:patients,id'],
            'prescription_id'      => ['required','uuid'],
            'prescription_item_id' => ['required','uuid'],
            'drug_code'            => ['required','string','max:50'],
            'drug_name'            => ['required','string','max:255'],
            'schedule'             => ['required','in:schedule_i,schedule_ii,schedule_iii,schedule_iv,schedule_v'],
            'quantity_dispensed'   => ['required','numeric','min:0.01'],
            'unit'                 => ['required','string','max:30'],
            'dispensed_by'         => ['required','uuid','exists:users,id'],
            'dispensed_at'         => ['sometimes','date'],
            'witness_id'           => ['sometimes','nullable','uuid','exists:users,id'],
            'lot_number'           => ['sometimes','nullable','string','max:50'],
            'expiry_date'          => ['sometimes','nullable','date'],
            'notes'                => ['sometimes','nullable','string'],
        ]);
        $dispensing = $this->service->dispense($validated);

        $facilityId = $request->attributes->get('facility_id');
        if ($facilityId) {
            try {
                $dispensingId = is_array($dispensing) ? ($dispensing['id'] ?? null) : ($dispensing->id ?? null);
                $this->issuance->issueFromModel(
                    'NRX',
                    'Narcotic Prescription — ' . $validated['drug_name'],
                    ['dispensing_id' => $dispensingId, 'patient_id' => $validated['patient_id'], 'prescription_id' => $validated['prescription_id'], 'drug_name' => $validated['drug_name'], 'drug_code' => $validated['drug_code'], 'schedule' => $validated['schedule'], 'quantity_dispensed' => $validated['quantity_dispensed'], 'unit' => $validated['unit'], 'dispensed_by' => $validated['dispensed_by']],
                    $facilityId,
                    $validated['patient_id'],
                    null,
                    $validated['dispensed_by']
                );
            } catch (\Throwable) {}
        }

        return response()->json(['data' => $dispensing], Response::HTTP_CREATED);
    }

    public function confirmWitness(Request $request, string $id): JsonResponse {
        $validated  = $request->validate(['witness_id' => ['required','uuid','exists:users,id']]);
        $dispensing = $this->service->confirmWitness($id, $validated['witness_id']);
        return response()->json(['data' => $dispensing]);
    }

    public function reconcile(Request $request): JsonResponse {
        $validated = $request->validate([
            'facility_id'    => ['required','uuid','exists:facilities,id'],
            'drug_code'      => ['required','string','max:50'],
            'actual_balance' => ['required','numeric','min:0'],
            'reconciler_id'  => ['required','uuid','exists:users,id'],
        ]);
        $inventory = $this->service->reconcileInventory(
            $validated['facility_id'], $validated['drug_code'],
            (float) $validated['actual_balance'], $validated['reconciler_id']
        );
        return response()->json(['data' => $inventory]);
    }

    public function log(Request $request): JsonResponse {
        $validated = $request->validate([
            'facility_id' => ['required','uuid','exists:facilities,id'],
            'from'        => ['sometimes','date_format:Y-m-d'],
            'to'          => ['sometimes','date_format:Y-m-d','after_or_equal:from'],
        ]);
        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $to   = isset($validated['to'])   ? Carbon::parse($validated['to'])->endOfDay()     : Carbon::now()->endOfDay();
        $log  = $this->service->getDispenseLog($validated['facility_id'], $from, $to);
        return response()->json(['data' => $log]);
    }

    public function inventory(Request $request): JsonResponse {
        $validated = $request->validate(['facility_id' => ['required','uuid','exists:facilities,id']]);
        $inventory = $this->service->getInventory($validated['facility_id']);
        return response()->json(['data' => $inventory]);
    }
}
