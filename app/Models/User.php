<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use \App\Traits\IsDemoRecord;
    use HasFactory, Notifiable, HasUuids;

    // role_id and is_demo are intentionally excluded from $fillable.
    // Assign roles via: $user->role_id = $roleId; $user->save();
    // Toggle demo via: $user->forceFill(['is_demo' => true])->save();
    protected $fillable = [
        'name',
        'email',
        'password',
        'primary_facility_id',
        'patient_id',
        'status',
        // NOT: role_id, is_demo
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Two-factor secrets/recovery codes are encrypted at rest. Assigned
            // explicitly during enrollment (intentionally NOT in $fillable).
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'profile_completed_at' => 'datetime',
        ];
    }

    /** True once the user has completed two-factor enrollment. */
    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /** True if this user's role requires MFA per config/mfa.php. */
    public function requiresTwoFactor(): bool
    {
        return in_array($this->roleName(), (array) config('mfa.required_roles', []), true);
    }

    public function patient(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Patient::class);
    }

    public function primaryFacility()
    {
        return $this->belongsTo(Facility::class, 'primary_facility_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function roleName(): ?string
    {
        return $this->role?->name;
    }

    public function dashboardProfile(): ?\App\Models\DashboardProfile
    {
        return $this->role?->dashboardProfile;
    }

    public function facilityRoleAssignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FacilityRoleAssignment::class);
    }

    /**
     * Get the role for this user at a specific facility.
     * Falls back to global role_id for backward compatibility.
     */
    public function roleAtFacility(string $facilityId): ?Role
    {
        // Guard: facility_id is a uuid column on Postgres — a malformed id
        // (e.g. a tampered session value) would otherwise throw a 22P02 cast
        // error and surface as a 500. Treat invalid ids as "no facility role".
        if (! \Illuminate\Support\Str::isUuid($facilityId)) {
            return $this->role ?? null;
        }

        $assignment = $this->facilityRoleAssignments()
            ->active()
            ->where('facility_id', $facilityId)
            ->with('role')
            ->first();

        if ($assignment) {
            return $assignment->role;
        }

        // Backward compatibility fallback
        return $this->role ?? null;
    }
}
