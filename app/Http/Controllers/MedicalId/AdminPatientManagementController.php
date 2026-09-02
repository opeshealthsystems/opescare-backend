<?php

namespace App\Http\Controllers\MedicalId;

use App\Enums\IdentityStatus;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminPatientManagementController extends Controller
{
    private function actorId(): string
    {
        return Auth::id() ?? session('auth_email', 'system');
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Patient has no 'facility' relationship; the listing doesn't use it.
        $query = Patient::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('health_id', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone_number_hash', hash('sha256', $search));
            });
        }

        if ($facilityId = $request->input('facility_id')) {
            $query->where('facility_id', $facilityId);
        }

        if ($identityStatus = $request->input('identity_status')) {
            $query->where('identity_status', $identityStatus);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $patients = $query->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total'       => Patient::count(),
            'active'      => Patient::whereIn('identity_status', [
                IdentityStatus::Active->value,
                IdentityStatus::Verified->value,
                IdentityStatus::VerifiedByFacility->value,
            ])->count(),
            'provisional' => Patient::where('identity_status', IdentityStatus::Provisional->value)->count(),
            'suspended'   => Patient::where('identity_status', IdentityStatus::Suspended->value)->count(),
        ];

        $identityStatuses = IdentityStatus::cases();

        return view('portals.admin.patients.index', compact('patients', 'stats', 'identityStatuses'));
    }

    public function show(string $id): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $patient = Patient::with('facility')->findOrFail($id);

        $recentVisits = collect();
        if (class_exists(\App\Models\Visit::class)) {
            $recentVisits = \App\Models\Visit::where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        } elseif (class_exists(\App\Models\Consultation::class)) {
            $recentVisits = \App\Models\Consultation::where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        $prescriptions = collect();
        if (class_exists(\App\Models\Prescription::class)) {
            $prescriptions = \App\Models\Prescription::where('patient_id', $patient->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        return view('portals.admin.patients.show', compact('patient', 'recentVisits', 'prescriptions'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $patient = Patient::findOrFail($id);

        $validated = $request->validate([
            'first_name'   => ['sometimes', 'string', 'max:100'],
            'last_name'    => ['sometimes', 'string', 'max:100'],
            'middle_name'  => ['sometimes', 'nullable', 'string', 'max:100'],
            'date_of_birth'=> ['sometimes', 'nullable', 'date'],
            'sex'          => ['sometimes', 'nullable', 'string', 'max:20'],
            'email'        => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'country_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'address'      => ['sometimes', 'nullable', 'string'],
        ]);

        $patient->fill($validated);
        $patient->save();

        return redirect()->back()->with('success', __('flash.patient_updated'));
    }

    public function suspend(string $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $patient = Patient::findOrFail($id);
        $patient->identity_status = IdentityStatus::Suspended;
        $patient->save();

        return redirect()->back()->with('success', __('flash.patient_suspended'));
    }

    public function activate(string $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $patient = Patient::findOrFail($id);
        $patient->identity_status = IdentityStatus::Active;
        $patient->save();

        return redirect()->back()->with('success', __('flash.patient_activated'));
    }

    public function destroy(string $id): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $patient = Patient::findOrFail($id);

        if ($patient->identity_status !== IdentityStatus::EnteredInError) {
            return redirect()->back()->with(
                'error',
                'Patient records can only be deleted when status is entered_in_error'
            );
        }

        $patient->delete();

        return redirect()->route('admin.patients.index')->with('success', __('flash.patient_permanently_deleted'));
    }
}
