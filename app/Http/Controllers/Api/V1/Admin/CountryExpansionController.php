<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CountryLaunchApprovalResource;
use App\Models\Country;
use App\Models\CountryLaunchApproval;
use App\Modules\CountryExpansion\Services\CountryExpansionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CountryExpansionController — Country Launch & Expansion Management API.
 *
 * Manages the country-by-country launch approval pipeline for OpesCare.
 * A CountryLaunchApproval must be fully approved before any facilities
 * in that country can be onboarded for live operations.
 *
 * Checklist items typically include: legal framework review, data residency
 * compliance, health regulatory registration, payment integration, and
 * clinical governance sign-off.
 *
 * Routes protected by VerifyIntegrationClient middleware (super-admin only).
 *
 * Endpoints:
 *  POST  /v1/admin/countries/{country}/launch           — initiate launch approval
 *  GET   /v1/admin/countries/{country}/launch           — get launch status + missing checklist
 *  PUT   /v1/admin/countries/{country}/launch/checklist — update checklist items
 *  POST  /v1/admin/countries/{country}/launch/approve   — approve a completed checklist
 */
class CountryExpansionController extends Controller
{
    public function __construct(private readonly CountryExpansionService $service) {}

    /**
     * Initiate a launch approval process for a country.
     * Creates a CountryLaunchApproval record (idempotent — firstOrCreate).
     */
    public function initiateLaunch(Country $country): JsonResponse
    {
        $approval = $this->service->initiateLaunch($country);

        return response()->json([
            'message'  => __('api.launch_process_initiated', ['country' => $country->name]),
            'data'     => CountryLaunchApprovalResource::make($approval),
        ], 201);
    }

    /**
     * Get launch status and outstanding checklist items for a country.
     */
    public function launchStatus(Country $country): JsonResponse
    {
        $isApproved  = $this->service->isApprovedForLaunch($country);
        $approval    = CountryLaunchApproval::where('country_id', $country->id)->latest()->first();

        if (!$approval) {
            return response()->json([
                'country_id'    => $country->id,
                'country_name'  => $country->name,
                'is_approved'   => false,
                'approval'      => null,
                'missing_items' => [],
                'message'       => __('api.no_launch_approval'),
            ]);
        }

        $missing = $this->service->getMissingChecklist($approval);

        return response()->json([
            'country_id'    => $country->id,
            'country_name'  => $country->name,
            'is_approved'   => $isApproved,
            'approval'      => CountryLaunchApprovalResource::make($approval),
            'missing_items' => $missing,
            'ready_to_approve' => empty($missing),
        ]);
    }

    /**
     * Update checklist items on an existing launch approval.
     * Body: { checks: { legal_review: true, data_residency: true, ... } }
     */
    public function updateChecklist(Country $country, Request $request): JsonResponse
    {
        $approval = CountryLaunchApproval::where('country_id', $country->id)
            ->latest()
            ->firstOrFail();

        $validated = $request->validate([
            'checks'   => ['required', 'array'],
            'checks.*' => ['boolean'],
        ]);

        $this->service->updateChecklist($approval, $validated['checks']);

        $missing = $this->service->getMissingChecklist($approval->fresh());

        return response()->json([
            'message'          => __('api.checklist_updated'),
            'missing_items'    => $missing,
            'ready_to_approve' => empty($missing),
        ]);
    }

    /**
     * Approve a completed launch approval.
     * All checklist items must be passed before approval is permitted.
     * Body: { approved_by: uuid, notes? }
     */
    public function approveLaunch(Country $country, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'approved_by' => ['required', 'uuid'],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ]);

        $approval = CountryLaunchApproval::where('country_id', $country->id)
            ->latest()
            ->firstOrFail();

        try {
            $this->service->approveLaunch(
                $approval,
                $validated['approved_by'],
                $validated['notes'] ?? null
            );
        } catch (\RuntimeException $e) {
            return response()->json([
                'message'       => $e->getMessage(),
                'missing_items' => $this->service->getMissingChecklist($approval),
            ], 422);
        }

        return response()->json([
            'message' => __('api.launch_approved', ['country' => $country->name]),
            'data'    => CountryLaunchApprovalResource::make($approval->fresh()),
        ]);
    }
}
