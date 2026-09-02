<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermissionMatrix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AccessControlController
 *
 * Manages Roles, Permissions, and the Role-Permission matrix.
 * All routes are protected by VerifyIntegrationClient (admin B2B auth).
 *
 * Endpoints:
 *  GET    /v1/admin/access-control/roles                       — list all roles
 *  POST   /v1/admin/access-control/roles                       — create a role
 *  GET    /v1/admin/access-control/roles/{id}                  — show role with permissions
 *  PUT    /v1/admin/access-control/roles/{id}                  — update role name/description
 *  POST   /v1/admin/access-control/roles/{id}/permissions      — assign permissions to role
 *  DELETE /v1/admin/access-control/roles/{id}/permissions/{perm} — revoke permission from role
 *  GET    /v1/admin/access-control/permissions                 — list all permissions
 *  POST   /v1/admin/access-control/permissions                 — create a permission
 *  GET    /v1/admin/access-control/matrix                      — view full role-permission matrix
 */
class AccessControlController extends Controller
{
    // ── Roles ─────────────────────────────────────────────────────────────

    public function listRoles(): JsonResponse
    {
        $roles = Role::withCount('permissions')->orderBy('name')->get();

        return response()->json([
            'data' => $roles->map(fn ($r) => [
                'id'               => $r->id,
                'name'             => $r->name,
                'description'      => $r->description,
                'permissions_count'=> $r->permissions_count,
                'created_at'       => $r->created_at?->toISOString(),
            ]),
        ]);
    }

    public function createRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $role = Role::create($validated);

        return response()->json([
            'message' => __('api.role_created'),
            'data'    => $this->serializeRole($role),
        ], 201);
    }

    public function showRole(Role $role): JsonResponse
    {
        $role->load('permissions');

        return response()->json([
            'data' => array_merge($this->serializeRole($role), [
                'permissions' => $role->permissions->map(fn ($p) => [
                    'id'          => $p->id,
                    'name'        => $p->name,
                    'description' => $p->description,
                ]),
            ]),
        ]);
    }

    public function updateRole(Role $role, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:100', 'unique:roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $role->update($validated);

        return response()->json([
            'message' => __('api.role_updated'),
            'data'    => $this->serializeRole($role->fresh()),
        ]);
    }

    /**
     * Assign one or more permissions to a role.
     * POST body: { permission_ids: [uuid, ...] }
     * Uses sync-attach: adds only missing assignments, does not remove existing ones.
     */
    public function assignPermissions(Role $role, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'permission_ids'   => ['required', 'array', 'min:1'],
            'permission_ids.*' => ['required', 'uuid', 'exists:permissions,id'],
        ]);

        // syncWithoutDetaching preserves existing assignments — safe to call repeatedly
        $role->permissions()->syncWithoutDetaching($validated['permission_ids']);

        $role->load('permissions');

        return response()->json([
            'message' => __('api.permissions_assigned'),
            'data'    => [
                'role_id'     => $role->id,
                'permissions' => $role->permissions->pluck('name', 'id'),
            ],
        ]);
    }

    /**
     * Revoke a single permission from a role.
     */
    public function revokePermission(Role $role, Permission $permission): JsonResponse
    {
        // Block if RolePermissionMatrix marks this permission as explicitly blocked
        // (meaning it was ALWAYS denied — not just not assigned)
        if (RolePermissionMatrix::isBlocked($role->name, $permission->name)) {
            return response()->json([
                'message' => __('api.permission_governance_blocked'),
            ], 403);
        }

        $role->permissions()->detach($permission->id);

        return response()->json([
            'message' => __('api.permission_revoked_role'),
            'data'    => [
                'role_id'       => $role->id,
                'permission_id' => $permission->id,
            ],
        ]);
    }

    // ── Permissions ───────────────────────────────────────────────────────

    public function listPermissions(Request $request): JsonResponse
    {
        $query = Permission::orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $permissions = $query->get();

        return response()->json([
            'data' => $permissions->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'description' => $p->description,
                'created_at'  => $p->created_at?->toISOString(),
            ]),
            'count' => $permissions->count(),
        ]);
    }

    public function createPermission(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:200', 'unique:permissions,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $permission = Permission::create($validated);

        return response()->json([
            'message' => __('api.permission_created'),
            'data'    => [
                'id'          => $permission->id,
                'name'        => $permission->name,
                'description' => $permission->description,
            ],
        ], 201);
    }

    // ── Matrix ────────────────────────────────────────────────────────────

    /**
     * View the full role-permission governance matrix.
     * Shows which permissions are explicitly allowed, blocked, or need review.
     */
    public function matrix(): JsonResponse
    {
        $roles = Role::with('permissions:id,name')->orderBy('name')->get();
        $allPermissions = Permission::orderBy('name')->pluck('name', 'id');
        $governanceMatrix = RolePermissionMatrix::orderBy('role_name')->orderBy('permission_key')->get();

        $matrix = $roles->map(function (Role $role) use ($allPermissions, $governanceMatrix) {
            $assignedPermNames = $role->permissions->pluck('name')->toArray();
            $govRows = $governanceMatrix->where('role_name', $role->name)->keyBy('permission_key');

            return [
                'role_id'     => $role->id,
                'role_name'   => $role->name,
                'permissions' => $allPermissions->map(function ($permName, $permId) use ($assignedPermNames, $govRows) {
                    $gov = $govRows[$permName] ?? null;
                    return [
                        'permission_id'       => $permId,
                        'permission_name'     => $permName,
                        'is_assigned'         => in_array($permName, $assignedPermNames),
                        'is_explicitly_blocked' => $gov?->is_explicitly_blocked ?? false,
                        'governance_rationale'  => $gov?->rationale,
                        'governance_reviewed_at'=> $gov?->reviewed_at?->toISOString(),
                    ];
                })->values(),
            ];
        });

        return response()->json(['data' => $matrix]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function serializeRole(Role $role): array
    {
        return [
            'id'          => $role->id,
            'name'        => $role->name,
            'description' => $role->description,
            'created_at'  => $role->created_at?->toISOString(),
            'updated_at'  => $role->updated_at?->toISOString(),
        ];
    }
}
