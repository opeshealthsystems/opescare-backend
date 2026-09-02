<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Models\StaffProfile;
use App\Modules\Staff\Services\StaffService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminStaffManagementController extends Controller
{
    public function __construct(private StaffService $staffService)
    {
    }

    private function actorId(): string
    {
        return Auth::id() ?? session('auth_email', 'system');
    }

    public function index(Request $request)
    {
        if (!Auth::check()) return redirect()->route('login');

        $query = StaffProfile::query()->with(['facility', 'user']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($facilityId = $request->input('facility_id')) {
            $query->where('facility_id', $facilityId);
        }

        if ($jobTitle = $request->input('job_title')) {
            $query->where('job_title', 'like', "%{$jobTitle}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $staff = $query->paginate(25)->withQueryString();

        $stats = [
            'total'     => StaffProfile::count(),
            'active'    => StaffProfile::where('status', 'active')->count(),
            'on_leave'  => StaffProfile::where('status', 'on_leave')->count(),
            'suspended' => StaffProfile::where('status', 'suspended')->count(),
        ];

        return view('portals.admin.staff.index', compact('staff', 'stats'));
    }

    public function show(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $profile = StaffProfile::with(['facility', 'user'])->findOrFail($id);

        $assignments = null;
        if (method_exists($profile, 'departmentAssignments')) {
            $assignments = $profile->departmentAssignments()->get();
        }

        $currentShift = null;
        if (method_exists($profile, 'shifts')) {
            $currentShift = $profile->shifts()
                ->where('status', 'active')
                ->latest()
                ->first();
        }

        $recentActivity = null;
        if (method_exists($profile, 'activityLogs')) {
            $recentActivity = $profile->activityLogs()->latest()->limit(10)->get();
        }

        return view('portals.admin.staff.show', compact('profile', 'assignments', 'currentShift', 'recentActivity'));
    }

    public function suspend(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $profile = StaffProfile::findOrFail($id);

        try {
            $this->staffService->updateStaffStatus($id, 'suspended');
        } catch (\Throwable $e) {
            $profile->status = 'suspended';
            $profile->save();
        }

        return redirect()->back()->with('success', __('flash.staff_member_suspended'));
    }

    public function activate(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $profile = StaffProfile::findOrFail($id);

        try {
            $this->staffService->updateStaffStatus($id, 'active');
        } catch (\Throwable $e) {
            $profile->status = 'active';
            $profile->save();
        }

        return redirect()->back()->with('success', __('flash.staff_member_activated'));
    }
}
