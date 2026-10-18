<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Services\DiagnosticCentre\DiagnosticCentreDashboardService;
use App\Services\Portal\PortalContextService;
use App\Support\StaffCapabilities;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The diagnostic centre landing (/facility/diagnostic) — lab and imaging on
 * one page, for the manager or administrator of a centre that runs both.
 *
 * A surface only: every figure comes from DiagnosticCentreDashboardService,
 * which itself only re-reads the lab and imaging desks. Facility from
 * PortalContextService, never the request. The role gate is
 * `capability:lab_results,imaging` on the route (routes/diagnostic.php); the
 * FACILITY gate is here — a laboratory that does no imaging is sent to its
 * own dashboard, because a page half full of imaging zeros would read as a
 * quiet imaging department the lab does not have.
 *
 * NOTHING HERE READS BILLING. Care is never gated on payment.
 */
class DiagnosticCentreController extends Controller
{
    public function __construct(
        private readonly PortalContextService $context,
        private readonly DiagnosticCentreDashboardService $desk,
    ) {
    }

    public function dashboard(): View|RedirectResponse
    {
        $facilityId = $this->context->facilityId();

        abort_if($facilityId === null, 403, __('api.capability_forbidden'));

        if (! DiagnosticCentreDashboardService::appliesTo($facilityId)) {
            return redirect()->route('portals.lab_manager.dashboard')
                ->with('info', __('diagnostic_desk.not_a_centre'));
        }

        $this->context->auditPatientAccess('diagnostic_centre_dashboard', 'Facility', $facilityId, null, [
            'facility_id' => $facilityId,
        ]);

        $user = auth()->user();

        return view('portals.diagnostic.dashboard', $this->desk->dashboard($facilityId) + [
            'facilityType' => DiagnosticCentreDashboardService::facilityType($facilityId),
            'canLab'       => StaffCapabilities::canAt($user, $facilityId, 'lab_results'),
            'canImaging'   => StaffCapabilities::canAt($user, $facilityId, 'imaging'),
            'canCatalogue' => StaffCapabilities::canAt($user, $facilityId, 'service_catalogue'),
        ]);
    }
}
