<?php

namespace App\Modules\Staff\Services;

use App\Models\StaffProfile;
use App\Models\ProfessionalLicense;
use App\Models\DepartmentAssignment;
use App\Models\Facility;
use Illuminate\Support\Collection;

class StaffService
{
    public function listStaff(string $facilityId, array $filters = []): Collection
    {
        $query = StaffProfile::where('facility_id', $facilityId)
            ->orderBy('last_name')
            ->orderBy('first_name');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['department'])) {
            $query->where('department', $filters['department']);
        }
        if (!empty($filters['staff_category'])) {
            $query->where('staff_category', $filters['staff_category']);
        }
        if (!empty($filters['search'])) {
            $q = $filters['search'];
            $query->where(function ($sub) use ($q) {
                $sub->where('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%")
                    ->orWhere('employee_number', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        return $query->with(['licenses'])->get();
    }

    public function createStaffProfile(string $facilityId, array $data): StaffProfile
    {
        $profile = StaffProfile::create(array_merge($data, ['facility_id' => $facilityId]));

        if (!empty($data['department'])) {
            DepartmentAssignment::create([
                'staff_profile_id' => $profile->id,
                'department'       => $data['department'],
                'is_primary'       => true,
                'assigned_from'    => $data['hire_date'] ?? now()->toDateString(),
            ]);
        }

        return $profile;
    }

    public function updateStaffStatus(string $profileId, string $status): StaffProfile
    {
        $allowed = ['active', 'inactive', 'on_leave', 'suspended', 'terminated'];
        if (!in_array($status, $allowed)) {
            throw new \InvalidArgumentException("Invalid status: {$status}");
        }

        $profile = StaffProfile::findOrFail($profileId);
        $profile->update(['status' => $status]);
        return $profile;
    }

    public function addLicense(string $profileId, array $data): ProfessionalLicense
    {
        return ProfessionalLicense::create(array_merge($data, [
            'staff_profile_id' => $profileId,
        ]));
    }

    public function getStaffProfile(string $profileId): StaffProfile
    {
        return StaffProfile::with(['licenses', 'departmentAssignments'])->findOrFail($profileId);
    }

    public function updateStaffProfile(string $profileId, array $data): StaffProfile
    {
        $allowed = [
            'first_name', 'last_name', 'email', 'phone',
            'job_title', 'department', 'staff_category',
            'employment_type', 'contract_end_date', 'notes',
        ];

        $profile = StaffProfile::findOrFail($profileId);
        $profile->update(array_intersect_key($data, array_flip($allowed)));

        return $profile->fresh();
    }

    public function generateEmployeeNumber(string $facilityId): string
    {
        $prefix = 'EMP';
        $year = date('Y');
        $count = StaffProfile::where('facility_id', $facilityId)->count() + 1;
        return $prefix . '-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
