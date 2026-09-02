<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'facility_id', 'employee_number',
        'first_name', 'last_name', 'email', 'phone',
        'job_title', 'department', 'staff_category',
        'employment_type', 'hire_date', 'contract_end_date',
        'status', 'notes',
    ];

    protected $casts = [
        'hire_date'         => 'date',
        'contract_end_date' => 'date',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(ProfessionalLicense::class);
    }

    public function departmentAssignments(): HasMany
    {
        return $this->hasMany(DepartmentAssignment::class);
    }

    public function rosterAssignments(): HasMany
    {
        return $this->hasMany(RosterAssignment::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
