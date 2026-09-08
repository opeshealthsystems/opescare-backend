<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Facility;
use App\Models\Patient;
use App\Modules\Appointments\Services\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAppointmentsController extends Controller
{
    public function __construct(private AppointmentService $appointmentService) {}

    private function actorId(): string
    {
        return Auth::id() ?? session('auth_email', 'system');
    }

    public function index(Request $request)
    {
        if (!Auth::check()) return redirect()->route('login');

        $query = Appointment::with(['patient', 'facility', 'provider']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Ids are UUIDs; anything else is a hand-edited query string and would
        // otherwise reach Postgres as an invalid uuid cast (a 500, not a filter).
        $facilityId = (string) $request->input('facility_id', '');
        if ($facilityId !== '' && \Illuminate\Support\Str::isUuid($facilityId)) {
            $query->where('facility_id', $facilityId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('scheduled_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('scheduled_at', '<=', $dateTo);
        }

        if ($search = trim((string) $request->input('search'))) {
            // Exact Health ID only — a LIKE over patients is the enumeration
            // search the platform contract forbids, admin desk or not. The
            // lookup follows a merge, so the appointments of a retired ID show
            // under its survivor.
            $patient = Patient::resolveHealthId($search);
            $query->where('patient_id', $patient?->getKey() ?? '00000000-0000-0000-0000-000000000000');
        }

        $appointments = $query->orderByDesc('scheduled_at')
            ->paginate(25)
            ->withQueryString();

        $today = now()->toDateString();

        $statsBase = Appointment::whereDate('scheduled_at', $today);
        $stats = [
            'total_today'     => (clone $statsBase)->count(),
            'confirmed_today' => (clone $statsBase)->where('status', 'confirmed')->count(),
            'cancelled_today' => (clone $statsBase)->where('status', 'cancelled')->count(),
            'no_show_today'   => (clone $statsBase)->where('status', 'no_show')->count(),
        ];

        // The facility filter's options. Without them the select renders with
        // only "All facilities" and the filter can never be used.
        $facilities = Facility::orderBy('name')->get(['id', 'name']);

        return view('portals.admin.appointments.index', compact('appointments', 'stats', 'facilities'));
    }

    public function show(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $appointment = Appointment::with(['patient', 'provider', 'facility'])
            ->findOrFail($id);

        return view('portals.admin.appointments.show', compact('appointment'));
    }

    public function cancel(Request $request, string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $request->validate([
            'reason' => 'nullable|string|max:300',
        ]);

        $appointment = Appointment::findOrFail($id);

        try {
            $this->appointmentService->cancel(
                $appointment,
                $request->input('reason', ''),
                $this->actorId()
            );
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', __('flash.appointment_cancel_failed_reason', ['error' => $e->getMessage()]));
        }

        return redirect()->back()->with('success', __('flash.appointment_cancelled'));
    }

    /**
     * "Delete" a cancelled appointment — which marks it entered-in-error.
     *
     * $appointment->delete() was a hard DELETE (Appointment has no SoftDeletes)
     * and `preconsult_questionnaire_responses.appointment_id` cascades from it,
     * so removing a cancelled booking silently took the patient's pre-consult
     * answers with it and left the queue entries pointing at nothing.
     *
     * An appointment is a care event: it is marked, never overwritten.
     * `entered_in_error` is the platform's existing word for a record that
     * should not have been created — ImportRollbackService uses it for the same
     * reason — and lang/{en,fr}/enums.php already renders it, so the admin list
     * shows the state correctly with no new vocabulary.
     */
    public function destroy(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $appointment = Appointment::findOrFail($id);

        if ($appointment->status !== 'cancelled') {
            return redirect()->back()->with('error', __('flash.appointment_only_cancelled_deletable'));
        }

        $appointment->status = 'entered_in_error';
        $appointment->save();

        return redirect()->route('portals.admin.appointments.index')
            ->with('success', __('flash.appointment_marked_entered_in_error'));
    }
}
