<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\IntegrationClient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * RequireApiAdminRole
 *
 * Ensures API endpoints marked as admin-only are accessed only by users with
 * admin-level roles. Responds with 403 Forbidden for non-admin users.
 *
 * Admin roles include:
 * - Platform-level: super_admin, platform_admin, system_admin, product_admin
 * - Facility-level: facility_admin, clinic_admin, hospital_admin, facility_ceo
 * - Support/Compliance: support_agent, support_manager, compliance_officer, etc.
 */
class RequireApiAdminRole
{
    /**
     * List of roles permitted to access admin API endpoints.
     * These align with the portals/admin roles from EnsurePortalAccess.
     */
    private const ADMIN_ROLES = [
        // Facility administration
        'facility_admin', 'clinic_admin', 'hospital_admin', 'facility_ceo',
        'department_manager', 'branch_admin', 'finance',
        // Platform administration
        'platform_admin', 'super_admin', 'product_admin', 'system_admin',
        'legal_admin', 'country_admin', 'regional_admin',
        // Compliance & security
        'privacy_officer', 'data_protection_officer', 'security_officer',
        'compliance_officer', 'audit_reviewer', 'emergency_access_reviewer',
        // Support & CS
        'support_agent', 'support_manager', 'customer_success',
        'implementation_lead', 'training_support',
        // Partner governance
        'partner_admin', 'partner_reviewer', 'partner_compliance', 'partner_technical',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $user = Auth::user() ?? $this->integrationClientOwner($request);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $roleName = $user->role?->name;

        if (!$roleName || !in_array($roleName, self::ADMIN_ROLES, true)) {
            Log::warning('unauthorized_admin_api_access', [
                'user_id'  => $user->id,
                'role'     => $roleName,
                'path'     => $request->path(),
                'method'   => $request->method(),
                'ip'       => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'This action requires administrative privileges.',
            ], 403);
        }

        return $next($request);
    }

    private function integrationClientOwner(Request $request): ?User
    {
        // Path 1: VerifyIntegrationClient sets the full model as an attribute
        $client  = $request->attributes->get('integration_client');
        $ownerId = $client?->created_by ?? $request->attributes->get('provider_id');

        // Path 2: VerifyBearerToken sets integration_client_id (JWT client)
        if (!$ownerId) {
            $bearerClientId = $request->attributes->get('integration_client_id');
            if (is_string($bearerClientId) && $bearerClientId !== '') {
                $ownerId = IntegrationClient::where('client_id', $bearerClientId)
                    ->value('created_by');
            }
        }

        // Path 3: VerifySdkToken sets sdk_client_id
        if (!$ownerId) {
            $sdkClientId = $request->attributes->get('sdk_client_id');
            if (is_string($sdkClientId) && $sdkClientId !== '') {
                $ownerId = IntegrationClient::where('client_id', $sdkClientId)
                    ->value('created_by');
            }
        }

        if (!is_string($ownerId) || $ownerId === '') {
            return null;
        }

        return User::with('role')->find($ownerId);
    }
}
