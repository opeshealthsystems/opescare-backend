<?php

namespace App\Http\Controllers\Api\Mobile\Desk;

use App\Http\Controllers\Controller;
use App\Http\Resources\Desk\DeskPatientResource;
use App\Modules\PatientIdentity\Exceptions\DuplicateIdentityFound;
use App\Services\FrontDesk\DeskActor;
use App\Services\FrontDesk\FrontDeskRefusal;
use App\Services\FrontDesk\FrontDeskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Front desk mobile API — registering a walk-in.
 *
 *   POST /api/mobile/desk/patients {first_name, last_name, date_of_birth, sex, phone?}
 *     → 201 {data: ProviderPatientSummary}
 *     → 409 {error_code: duplicate_identity, message}  when the MPI finds a match
 *
 * The record is created through PatientIdentityService and only there. A
 * DuplicateIdentityFound is the platform doing its job — the desk is told to
 * look the person up by their existing Health ID — and is never bypassed.
 * The 409 body names no Health ID and no name: the desk learns that a match
 * exists, not who.
 */
class DeskPatientController extends Controller
{
    public function __construct(private readonly FrontDeskService $desk)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $actor = DeskActor::fromRequest($request);

        if (! $actor) {
            return $this->refuse('provider_not_found', 401);
        }

        $validated = $request->validate([
            'first_name'    => ['required', 'string', 'min:1', 'max:100'],
            'last_name'     => ['required', 'string', 'min:1', 'max:100'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            // patients.sex is constrained to these two values.
            'sex'           => ['required', 'in:male,female'],
            'phone'         => ['nullable', 'string', 'max:32'],
        ]);

        try {
            $patient = $this->desk->registerWalkIn($actor, $validated);
        } catch (DuplicateIdentityFound $e) {
            return response()->json([
                'error_code' => 'duplicate_identity',
                'message'    => __('desk_api.duplicate_identity'),
            ], 409);
        } catch (FrontDeskRefusal $e) {
            return $this->refuse($e->key, $e->status);
        }

        return response()->json(['data' => DeskPatientResource::make($patient)], 201);
    }

    private function refuse(string $key, int $status): JsonResponse
    {
        return response()->json([
            'error_code' => $key,
            'message'    => __('desk_api.' . $key),
        ], $status);
    }
}
