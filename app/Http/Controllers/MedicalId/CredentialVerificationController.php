<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\ProviderCredential;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Platform-admin review queue for professional qualifications. A credential a
 * professional adds is UNVERIFIED (verified_at null) until reviewed here;
 * approving stamps verified_at/verified_by (which drives the verified badge on
 * the professional's profile), rejecting suspends it with a reason.
 *
 * Reachable only inside the platform-admin route group (RequirePlatformAdmin).
 */
class CredentialVerificationController extends Controller
{
    public function index(Request $request): View
    {
        $pending = ProviderCredential::with('provider.role')
            ->whereNull('verified_at')
            ->where('status', '!=', 'suspended')
            ->orderBy('created_at')
            ->paginate(20);

        $verifiedTotal = ProviderCredential::whereNotNull('verified_at')->count();

        return view('portals.admin.verifications.index', [
            'pending'       => $pending,
            'verifiedTotal' => $verifiedTotal,
        ]);
    }

    public function verify(Request $request, string $id): RedirectResponse
    {
        $credential = ProviderCredential::whereNull('verified_at')->findOrFail($id);

        $credential->update([
            'status'      => 'active',
            'verified_at' => now(),
            'verified_by' => Auth::id(),
        ]);

        return redirect()->route('portals.admin.verifications.index')
            ->with('success', __('professional.credential_verified'));
    }

    public function reject(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $credential = ProviderCredential::whereNull('verified_at')->findOrFail($id);

        $credential->update([
            'status'      => 'suspended',
            'verified_by' => Auth::id(),
            'notes'       => $validated['notes'] ?? null,
        ]);

        return redirect()->route('portals.admin.verifications.index')
            ->with('success', __('professional.credential_rejected'));
    }
}
