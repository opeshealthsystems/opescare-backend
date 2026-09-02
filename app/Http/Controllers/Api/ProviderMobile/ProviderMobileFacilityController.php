<?php

namespace App\Http\Controllers\Api\ProviderMobile;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\MobileFacilityContext;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Provider Mobile API — Facility Context Switching
 *
 * Providers working at multiple facilities can switch context to scope
 * all subsequent API calls (patient lookup, queue, tasks) to the active facility.
 */
class ProviderMobileFacilityController extends Controller
{
    /**
     * List facilities the provider is eligible to access.
     *
     * GET /api/provider-mobile/facilities
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $this->resolveUserId($request);

        // In a full RBAC system, filter by facility_staff / provider assignments.
        // Here we return all active facilities as accessible.
        $facilities = Facility::where('is_active', true)
            ->orWhereNull('is_active')
            ->get(['id', 'name', 'facility_type', 'country_code'])
            ->map(fn ($f) => [
                'id'            => $f->id,
                'name'          => $f->name,
                'facility_type' => $f->facility_type ?? null,
                'country_code'  => $f->country_code ?? null,
                'is_current'    => false,
            ]);

        // Mark current context
        $current = MobileFacilityContext::currentFor($userId);
        if ($current) {
            $facilities = $facilities->map(function ($f) use ($current) {
                $f['is_current'] = ($f['id'] === $current->facility_id);
                return $f;
            });
        }

        return response()->json(['data' => $facilities->values()]);
    }

    /**
     * Switch the active facility context.
     *
     * POST /api/provider-mobile/facilities/{id}/switch
     */
    public function switchFacility(Request $request, string $id): JsonResponse
    {
        $userId = $this->resolveUserId($request);

        $facility = Facility::findOrFail($id);

        $context = MobileFacilityContext::switchTo(
            $userId,
            $facility->id,
            $request->input('session_id')
        );

        return response()->json([
            'status'       => 'switched',
            'facility_id'  => $facility->id,
            'facility_name'=> $facility->name,
            'switched_at'  => $context->switched_at->toIso8601String(),
        ]);
    }

    /**
     * Get the current active facility context.
     *
     * GET /api/provider-mobile/facilities/current
     */
    public function current(Request $request): JsonResponse
    {
        $userId  = $this->resolveUserId($request);
        $context = MobileFacilityContext::currentFor($userId);

        if (!$context) {
            return response()->json(['data' => null, 'message' => __('api.no_facility_context_set')], 200);
        }

        return response()->json([
            'data' => [
                'facility_id'   => $context->facility_id,
                'facility_name' => $context->facility?->name,
                'switched_at'   => $context->switched_at?->toIso8601String(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------

    private function resolveUserId(Request $request): string
    {
        if ($request->has('_user_id')) {
            return $request->input('_user_id');
        }
        return User::value('id') ?? 'demo-provider';
    }
}
