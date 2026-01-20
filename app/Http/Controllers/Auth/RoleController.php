<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->get();

        return view('auth.roles.index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = Permission::getGrouped();
        $themeColors = Role::getAvailableThemeColors();

        // Get existing levels for reference
        $existingLevels = Role::orderBy('level')->get(['name', 'level', 'slug']);

        return view('auth.roles.create', [
            'permissions' => $permissions,
            'themeColors' => $themeColors,
            'existingLevels' => $existingLevels,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles', 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'level' => ['nullable', 'integer', 'min:0', 'max:99'],
            'theme_hover' => ['nullable', 'string', 'max:50'],
            'theme_text' => ['nullable', 'string', 'max:50'],
            'theme_accent' => ['nullable', 'string', 'max:50'],
            'theme_badge' => ['nullable', 'string', 'max:50'],
            'custom_hex_color' => ['nullable', 'string', 'max:10', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_system' => false,
            'level' => $request->input('level', 99),
            'theme_hover' => $request->theme_hover,
            'theme_text' => $request->theme_text,
            'theme_accent' => $request->theme_accent,
            'theme_badge' => $request->theme_badge,
            'custom_hex_color' => $request->custom_hex_color,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('roles.index')
            ->with('status', 'تم إنشاء الدور بنجاح!');
    }

    /**
     * Show the form for editing a role.
     */
    public function edit(Role $role)
    {
        $permissions = Permission::getGrouped();
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();
        $themeColors = Role::getAvailableThemeColors();

        // Get existing levels for reference (exclude current role)
        $existingLevels = Role::where('id', '!=', $role->id)
            ->orderBy('level')
            ->get(['name', 'level', 'slug']);

        return view('auth.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissionIds' => $rolePermissionIds,
            'themeColors' => $themeColors,
            'existingLevels' => $existingLevels,
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        // Prevent editing system roles (except permissions and theme for super-admin)
        if ($role->is_system && !$role->isSuperAdmin()) {
            return back()->withErrors(['error' => 'لا يمكن تعديل الأدوار النظامية.']);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug,' . $role->id, 'regex:/^[a-z0-9-]+$/'],
            'description' => ['nullable', 'string', 'max:500'],
            'level' => ['nullable', 'integer', 'min:0', 'max:99'],
            'theme_hover' => ['nullable', 'string', 'max:50'],
            'theme_text' => ['nullable', 'string', 'max:50'],
            'theme_accent' => ['nullable', 'string', 'max:50'],
            'theme_badge' => ['nullable', 'string', 'max:50'],
            'custom_hex_color' => ['nullable', 'string', 'max:10', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        // Build update data
        $updateData = [
            'name' => $request->name,
            'description' => $request->description,
            'theme_hover' => $request->theme_hover,
            'theme_text' => $request->theme_text,
            'theme_accent' => $request->theme_accent,
            'theme_badge' => $request->theme_badge,
            'custom_hex_color' => $request->custom_hex_color,
        ];

        // Don't allow changing slug or level for system roles
        if (!$role->is_system) {
            $updateData['slug'] = $request->slug;
            $updateData['level'] = $request->input('level', 99);
        }

        $role->update($updateData);

        // Super admin always has all permissions
        if (!$role->isSuperAdmin()) {
            $role->syncPermissions($request->permissions ?? []);
        }

        return redirect()->route('roles.index')
            ->with('status', 'تم تحديث الدور بنجاح!');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        // Prevent deleting system roles
        if ($role->is_system) {
            return back()->withErrors(['error' => 'لا يمكن حذف الأدوار النظامية.']);
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return back()->withErrors(['error' => 'لا يمكن حذف دور له مستخدمين. قم بنقل المستخدمين أولاً.']);
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('status', 'تم حذف الدور بنجاح!');
    }
}
