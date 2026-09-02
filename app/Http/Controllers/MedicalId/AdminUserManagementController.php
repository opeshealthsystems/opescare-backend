<?php

namespace App\Http\Controllers\MedicalId;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequirePlatformAdmin;
use App\Models\Facility;
use App\Models\Role;
use App\Models\FacilityRoleAssignment;
use App\Models\User;
use App\Services\Portal\PortalContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * AdminUserManagementController — the admin screen for staff accounts.
 *
 * It gained the ability to set users.primary_facility_id because it had to.
 * Every staff portal now resolves its acting facility through
 * PortalContextService::facilityId() and returns 409 when nothing resolves,
 * replacing an old Facility::value('id') fallback that used to hand a
 * facility-less user whichever facility happened to sort first. That fallback
 * was the bug; removing it was right. But it also means an account created
 * without a facility can now open no portal at all — and this screen, the only
 * place a platform admin creates staff, had no control for the column.
 *
 * Two rules govern the assignment, both borrowed rather than invented:
 *
 * 1. THE TIER TEST IS RequirePlatformAdmin::isPlatformTier(). A platform-tier
 *    admin (the OpesCare company roles) may assign any facility. Anyone else
 *    may only ever name the facility they are already acting in, resolved from
 *    PortalContextService — never from the form. Naming another facility is
 *    refused with a 403 rather than quietly rewritten, so a tampered field
 *    cannot pass for an accident. This is the same contract
 *    FacilityStaffController runs on.
 *
 * 2. THE PICKER IS BOUNDED AND SEARCH-DRIVEN. The facility register holds
 *    thousands of rows; a <select> containing all of them is both dead page
 *    weight on every load and a directory of the register published by a page
 *    that has no business publishing one.
 */
class AdminUserManagementController extends Controller
{
    /** The most facilities the picker will ever render at once. */
    private const FACILITY_PICKER_LIMIT = 25;

    public function __construct(private readonly PortalContextService $ctx) {}

    private function actorId(): string
    {
        return Auth::id() ?? session('auth_email', 'system');
    }

    // ── Facility assignment: authorisation ───────────────────────────────

    /** Is the acting admin in the platform-owner role tier? */
    private function actorIsPlatformTier(): bool
    {
        return RequirePlatformAdmin::isPlatformTier(Auth::user());
    }

    /**
     * Decide which facility id may actually be written.
     *
     * A platform-tier admin gets what they asked for (validation has already
     * proved the facility exists). Anyone else gets the facility they are
     * acting in, and is refused outright if they named a different one — that
     * request is an attempt to grant an account access to another facility's
     * patient record, which is precisely the class of bug the portal hardening
     * was closing.
     *
     * @param  string|null  $requested    the validated primary_facility_id, or null
     * @param  bool         $wasSubmitted whether the field was present in the request at all
     */
    private function resolveAssignedFacilityId(?string $requested, bool $wasSubmitted): ?string
    {
        if ($this->actorIsPlatformTier()) {
            return $requested;
        }

        // Facility tier. Session-resolved, never request-resolved.
        $acting = $this->ctx->facilityId();

        abort_if(
            $acting === null,
            403,
            'This account has no facility context, so it cannot assign one.'
        );

        abort_if(
            $wasSubmitted && $requested !== null && $requested !== $acting,
            403,
            'You may only assign users to the facility you administer.'
        );

        return $acting;
    }

    /**
     * Options for the facility picker.
     *
     * Never `Facility::all()`. The list is capped at FACILITY_PICKER_LIMIT and
     * narrowed by a `facility_q` search on the same GET route, and the facility
     * already on the record is always kept in the list so opening an edit form
     * can never silently drop the current value. A facility-tier admin has
     * exactly one assignable facility, so their picker is that single row and
     * there is nothing to search.
     *
     * @param  string|null  $keepId  facility already assigned to the record being edited
     */
    private function facilityOptions(Request $request, ?string $keepId = null): Collection
    {
        if (! $this->actorIsPlatformTier()) {
            $acting = $this->ctx->facilityId();

            return $acting
                ? Facility::query()->select('id', 'name')->whereKey($acting)->get()
                : collect();
        }

        $query = Facility::query()->select('id', 'name')->orderBy('name');

        $term = trim((string) $request->input('facility_q', ''));

        if ($term !== '') {
            // PostgreSQL: ILIKE for case-insensitive matching, with the LIKE
            // metacharacters escaped so a name containing % or _ searches for
            // itself rather than for everything.
            $query->where('name', 'ilike', '%' . addcslashes($term, '%_\\') . '%');
        }

        $options = $query->limit(self::FACILITY_PICKER_LIMIT)->get();

        if ($keepId !== null && ! $options->contains('id', $keepId)) {
            $current = Facility::query()->select('id', 'name')->find($keepId);

            if ($current) {
                $options = $options->prepend($current);
            }
        }

        return $options;
    }

    /**
     * Record a facility assignment on the audit trail.
     *
     * Moving an account between facilities changes which patient records it can
     * reach, so it is a security event and not a profile edit. The event is
     * stamped with the facility the account was moved INTO, which is the one
     * that gained the access.
     */
    /**
     * Keep `facility_role_assignments` in step with the assignment just made.
     *
     * `primary_facility_id` alone is enough to get the user past
     * RequireFacilityContext -- the middleware seeds `active_facility_id` from
     * it -- but not enough to resolve their role correctly.
     * `User::roleAtFacility()` looks for an active assignment and, finding
     * none, falls back to the global `users.role_id`. That fallback is the
     * backward-compatibility path for accounts predating this table, and
     * leaning on it means a role at a facility is inferred rather than
     * recorded: nothing distinguishes "nurse at this hospital" from "nurse
     * everywhere", which is the wrong shape for a platform where one person may
     * hold different roles at different facilities.
     *
     * `PublicPageController::submitStaffInvite()` writes the same row for the
     * invite path; this is the platform-admin equivalent.
     *
     * Deactivated rather than deleted: an assignment records access that was
     * once granted, and the table is UNIQUE on (user_id, facility_id, role_id),
     * so a user moved away and back must revive the original row rather than
     * collide with it.
     */
    private function syncFacilityRoleAssignment(
        User $user,
        ?string $previousFacilityId,
        ?string $facilityId,
        ?string $roleId
    ): void {
        // Withdraw every standing assignment that is not what this user now
        // holds -- the previous facility, and any stale role at a facility they
        // are staying at.
        FacilityRoleAssignment::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->when(
                $facilityId !== null && $roleId !== null,
                fn ($q) => $q->where(fn ($inner) => $inner
                    ->where('facility_id', '!=', $facilityId)
                    ->orWhere('role_id', '!=', $roleId))
            )
            ->update(['is_active' => false, 'expires_at' => now()]);

        if ($facilityId === null || $roleId === null) {
            return;
        }

        FacilityRoleAssignment::updateOrCreate(
            [
                'user_id'     => $user->id,
                'facility_id' => $facilityId,
                'role_id'     => $roleId,
            ],
            [
                'is_active'   => true,
                'expires_at'  => null,
                'assigned_by' => Auth::id(),
                'assigned_at' => now(),
            ]
        );
    }

    private function auditFacilityAssignment(User $user, ?string $before, ?string $after): void
    {
        $this->ctx->auditPatientAccess(
            actionType:   'admin_user_facility_assigned',
            resourceType: 'User',
            resourceId:   $user->id,
            extra: [
                'facility_id'  => $after,
                'before_state' => ['primary_facility_id' => $before],
                'after_state'  => ['primary_facility_id' => $after],
            ],
        );
    }

    // ── Screens ──────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        if (!Auth::check()) return redirect()->route('login');

        $query = User::with(['role', 'primaryFacility']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleId = $request->input('role_id')) {
            $query->where('role_id', $roleId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->orderBy('name')->paginate(25)->withQueryString();
        $roles = Role::orderBy('name')->get();

        $stats = [
            'total'     => User::count(),
            'active'    => User::where('status', 'active')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'pending'   => User::where('status', 'pending')->count(),
        ];

        return view('portals.admin.users.index', [
            'users'              => $users,
            'roles'              => $roles,
            'stats'              => $stats,
            'facilityOptions'    => $this->facilityOptions($request),
            'facilityQuery'      => trim((string) $request->input('facility_q', '')),
            'facilityPickerOpen' => $this->actorIsPlatformTier(),
            'facilityPickerCap'  => self::FACILITY_PICKER_LIMIT,
        ]);
    }

    public function show(Request $request, string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $user = User::with(['role', 'facilityRoleAssignments', 'primaryFacility'])->findOrFail($id);

        return view('portals.admin.users.show', [
            'user'               => $user,
            // The edit form has always rendered a role <select>; the variable
            // behind it was never passed, so this page threw on every request.
            'roles'              => Role::orderBy('name')->get(),
            'facilityOptions'    => $this->facilityOptions($request, $user->primary_facility_id),
            'facilityQuery'      => trim((string) $request->input('facility_q', '')),
            'facilityPickerOpen' => $this->actorIsPlatformTier(),
            'facilityPickerCap'  => self::FACILITY_PICKER_LIMIT,
        ]);
    }

    // ── Writes ───────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        if (!Auth::check()) return redirect()->route('login');

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role_id'  => 'required|exists:roles,id',
            // `uuid` before `exists`: facilities.id is a uuid column, and
            // sending a non-uuid straight into the exists query would surface
            // as a Postgres 22P02 cast error (a 500) instead of a form error.
            'primary_facility_id' => ['bail', 'nullable', 'uuid', 'exists:facilities,id'],
        ]);

        $facilityId = $this->resolveAssignedFacilityId(
            $validated['primary_facility_id'] ?? null,
            $request->has('primary_facility_id')
        );

        $roleId = $validated['role_id'];

        $user = User::create([
            'name'                => $validated['name'],
            'email'               => $validated['email'],
            'password'            => Hash::make($validated['password']),
            'status'              => 'active',
            'primary_facility_id' => $facilityId,
        ]);

        $user->role_id = $roleId;
        $user->save();

        if ($facilityId !== null) {
            $this->syncFacilityRoleAssignment($user, null, $facilityId, $roleId);
            $this->auditFacilityAssignment($user, null, $facilityId);
        }

        return redirect()->route('admin.users.index')->with('success', __('flash.user_created'));
    }

    public function update(Request $request, string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'status'  => 'required|string',
            'primary_facility_id' => ['bail', 'nullable', 'uuid', 'exists:facilities,id'],
        ]);

        $roleChanged = (string) $user->role_id !== (string) $validated['role_id'];

        $user->name   = $validated['name'];
        $user->email  = $validated['email'];
        $user->status = $validated['status'];
        $user->role_id = $validated['role_id'];

        // A form that does not carry the field leaves the assignment alone;
        // one that does is authorised before anything is written.
        $facilityChangedFrom = null;
        $facilityChanged     = false;

        if ($request->has('primary_facility_id')) {
            $assigned = $this->resolveAssignedFacilityId($validated['primary_facility_id'] ?? null, true);

            if ($assigned !== $user->primary_facility_id) {
                $facilityChangedFrom      = $user->primary_facility_id;
                $facilityChanged          = true;
                $user->primary_facility_id = $assigned;
            }
        }

        $user->save();

        // The role can change without the facility changing, and the assignment
        // records the pair -- so it is re-synced whenever either side moves.
        if ($facilityChanged || $roleChanged) {
            $this->syncFacilityRoleAssignment(
                $user,
                $facilityChanged ? $facilityChangedFrom : $user->primary_facility_id,
                $user->primary_facility_id,
                $user->role_id
            );
        }

        if ($facilityChanged) {
            $this->auditFacilityAssignment($user, $facilityChangedFrom, $user->primary_facility_id);
        }

        return redirect()->back()->with('success', __('flash.user_updated'));
    }

    public function suspend(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $user = User::findOrFail($id);
        $user->status = 'suspended';
        $user->save();

        return redirect()->back()->with('success', __('flash.user_suspended'));
    }

    public function activate(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $user = User::findOrFail($id);
        $user->status = 'active';
        $user->save();

        return redirect()->back()->with('success', __('flash.user_activated'));
    }

    public function resetPassword(Request $request, string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->input('password'));
        $user->save();

        return redirect()->back()->with('success', __('flash.user_password_reset'));
    }

    public function destroy(string $id)
    {
        if (!Auth::check()) return redirect()->route('login');

        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', __('flash.user_delete_self'));
        }

        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminCount = User::where('role_id', $superAdminRole->id)->count();
            if ($superAdminCount <= 1 && $user->role_id === $superAdminRole->id) {
                return redirect()->back()->with('error', __('flash.user_delete_last_super_admin'));
            }
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', __('flash.user_deleted'));
    }
}
