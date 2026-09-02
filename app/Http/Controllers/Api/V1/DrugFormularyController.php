<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\DrugFormularyResource;
use App\Services\Pharmacy\FormularyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DrugFormularyController extends Controller {
    public function __construct(private readonly FormularyService $service) {}

    public function search(Request $request): JsonResponse {
        $validated = $request->validate([
            'q'             => ['required','string','min:2','max:100'],
            'facility_id'   => ['sometimes','nullable','uuid'],
            'is_controlled' => ['sometimes','boolean'],
            'is_available'  => ['sometimes','boolean'],
            'drug_class'    => ['sometimes','string','max:100'],
            'form'          => ['sometimes','in:tablet,capsule,liquid,injection,topical,inhaler,other'],
        ]);
        $filters = array_filter(
            array_intersect_key($validated, array_flip(['is_controlled','is_available','drug_class','form'])),
            fn ($v) => $v !== null
        );
        $results = $this->service->search($validated['q'], $validated['facility_id'] ?? null, $filters);
        return response()->json(['data' => DrugFormularyResource::collection($results)]);
    }

    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'facility_id'         => ['sometimes','nullable','uuid','exists:facilities,id'],
            'generic_name'        => ['required','string','max:255'],
            'brand_names'         => ['sometimes','array'],
            'brand_names.*'       => ['string','max:255'],
            'drug_code'           => ['required','string','max:50'],
            'drug_class'          => ['required','string','max:100'],
            'form'                => ['required','in:tablet,capsule,liquid,injection,topical,inhaler,other'],
            'strength'            => ['required','string','max:50'],
            'unit'                => ['required','string','max:30'],
            'is_available'        => ['sometimes','boolean'],
            'is_controlled'       => ['sometimes','boolean'],
            'requires_prior_auth' => ['sometimes','boolean'],
            'restricted_to'       => ['sometimes','nullable','array'],
            'restricted_to.*'     => ['string','max:100'],
            'notes'               => ['sometimes','nullable','string'],
            'created_by'          => ['required','uuid','exists:users,id'],
        ]);
        $entry = $this->service->add($validated);
        return response()->json(['data' => DrugFormularyResource::make($entry)], Response::HTTP_CREATED);
    }

    public function toggleAvailability(Request $request, string $id): JsonResponse {
        $validated = $request->validate(['is_available' => ['required','boolean']]);
        $entry = $this->service->toggleAvailability($id, $validated['is_available']);
        return response()->json(['data' => DrugFormularyResource::make($entry)]);
    }

    public function controlled(Request $request): JsonResponse {
        $validated = $request->validate(['facility_id' => ['sometimes','nullable','uuid']]);
        $entries = $this->service->getControlledSubstances($validated['facility_id'] ?? null);
        return response()->json(['data' => DrugFormularyResource::collection($entries)]);
    }
}
