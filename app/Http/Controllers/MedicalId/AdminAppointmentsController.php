<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
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

        $query = Appointment::with(['patient', 'facility']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($facilityId = $request->input('facility_id')) {
            $query->where('facility_id', $facilityId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('scheduled_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('scheduled_at', '<=', $dateTo);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('health_id', 'like', "%{$search}%");
            });
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

        return view('portals.admin.appointments.index', compact('appointments', 'stats'));
    }

    public function show(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $appointment = Appointment::with(['patient', 'provider', 'facility', 'notes'])
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

    public function destroy(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $appointment = Appointment::findOrFail($id);

        if ($appointment->status !== 'cancelled') {
            return redirect()->back()->with('error', __('flash.appointment_only_cancelled_deletable'));
        }

        $appointment->delete();

        return redirect()->route('portals.admin.appointments.index')->with('success', __('flash.appointment_deleted'));
    }
}
