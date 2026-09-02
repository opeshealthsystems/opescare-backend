<?php

namespace App\Http\Controllers\MedicalId;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AdminRolesController extends Controller
{
    private const PROTECTED_ROLES = [
        'super-admin',
        'admin',
        'superadmin',
        'super_admin',
        'system',
    ];

    private function actorId(): string
    {
        return Auth::id() ?? session('auth_email', 'system');
    }

    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $roles = Role::withCount('users')->orderBy('name')->paginate(25)->withQueryString();

        return view('portals.admin.roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:80|unique:roles,name',
            'description' => 'nullable|string',
            'portal'      => 'nullable|string',
        ]);

        Role::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->back()->with('success', __('flash.role_created'));
    }

    public function update(Request $request, string $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $role = Role::findOrFail($id);

        if (in_array(strtolower($role->name), self::PROTECTED_ROLES, true)) {
            return redirect()->back()->with('error', __('flash.role_builtin_locked'));
        }

        $validated = $request->validate([
            'description' => 'nullable|string',
            'portal'      => 'nullable|string',
        ]);

        $role->update([
            'description' => $validated['description'] ?? $role->description,
        ]);

        return redirect()->back()->with('success', __('flash.role_updated'));
    }

    public function destroy(string $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $role = Role::findOrFail($id);

        if ($role->users()->exists()) {
            return redirect()->back()->with('error', __('flash.role_delete_has_users'));
        }

        $role->delete();

        return redirect()->back()->with('success', __('flash.role_deleted'));
    }

    public function users(Request $request, string $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $role = Role::findOrFail($id);

        $users = User::where('role_id', $role->id)
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('portals.admin.roles.users', compact('role', 'users'));
    }
}
