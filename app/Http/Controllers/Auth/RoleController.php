<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Role::class);

        // Get sorting parameters
        $sortColumn = $request->get('sort', 'level');
        $sortDirection = $request->get('dir', 'asc');

        // Validate sort column
        $allowedSorts = ['name', 'slug', 'level', 'users_count', 'permissions_count'];
        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'level';
        }

        // Build query
        $query = Role::with('permissions')->withCount(['users', 'permissions']);

        // Apply sorting
        if ($sortColumn === 'permissions_count') {
            $query->orderBy('permissions_count', $sortDirection);
        } elseif ($sortColumn === 'users_count') {
            $query->orderBy('users_count', $sortDirection);
        } else {
            $query->orderBy($sortColumn, $sortDirection);
        }

        $roles = $query->get();

        // Pass current filters for sorting links
        $currentFilters = [
            'sort' => $sortColumn,
            'dir' => $sortDirection,
        ];

        return view('auth.roles.index', [
            'roles' => $roles,
            'currentFilters' => $currentFilters,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $this->authorize('create', Role::class);

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
        $this->authorize('create', Role::class);

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

        $level = $request->input('level', 99);

        // Prevent privilege escalation: ensure new role level >= user's level
        if (!Gate::allows('create-role-at-level', $level)) {
            return back()->withErrors(['level' => 'لا يمكنك إنشاء دور بمستوى صلاحية أعلى من مستواك.'])
                ->withInput();
        }

        // Validate permission escalation: user can only assign permissions they have
        if ($request->has('permissions') && !auth()->user()->isSuperAdmin()) {
            $requestedPermissions = Permission::whereIn('id', $request->permissions)->pluck('slug');
            foreach ($requestedPermissions as $slug) {
                if (!auth()->user()->hasPermission($slug)) {
                    return back()->withErrors(['permissions' => 'لا يمكنك منح صلاحيات لا تملكها.'])
                        ->withInput();
                }
            }
        }

        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'is_system' => false,
            'level' => $level,
            'theme_hover' => $request->theme_hover,
            'theme_text' => $request->theme_text,
            'theme_accent' => $request->theme_accent,
            'theme_badge' => $request->theme_badge,
            'custom_hex_color' => $request->custom_hex_color,
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        // Log role creation
        ActivityLogService::logCreate($role, null, [
            'permissions_count' => count($request->permissions ?? []),
        ]);

        return redirect()->route('roles.index')
            ->with('status', 'تم إنشاء الدور بنجاح!');
    }

    /**
     * Show the form for editing a role.
     */
    public function edit(Role $role)
    {
        $this->authorize('update', $role);

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
        $this->authorize('update', $role);

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

        // Validate permission escalation for non-super-admins
        if ($request->has('permissions') && !auth()->user()->isSuperAdmin()) {
            $requestedPermissions = Permission::whereIn('id', $request->permissions)->pluck('slug');
            foreach ($requestedPermissions as $slug) {
                if (!auth()->user()->hasPermission($slug)) {
                    return back()->withErrors(['permissions' => 'لا يمكنك منح صلاحيات لا تملكها.'])
                        ->withInput();
                }
            }
        }

        // Store old values for logging
        $oldValues = $role->toArray();
        $oldPermissions = $role->permissions->pluck('id')->toArray();

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
            $newLevel = $request->input('level', 99);

            // Prevent privilege escalation via level change
            if (!Gate::allows('create-role-at-level', $newLevel)) {
                return back()->withErrors(['level' => 'لا يمكنك تعيين مستوى صلاحية أعلى من مستواك.'])
                    ->withInput();
            }

            $updateData['level'] = $newLevel;
        }

        $role->update($updateData);

        // Super admin always has all permissions
        if (!$role->isSuperAdmin()) {
            $role->syncPermissions($request->permissions ?? []);
        }

        // Log role update
        $newPermissions = $request->permissions ?? [];
        ActivityLogService::logUpdate($role, $oldValues, null, [
            'permissions_changed' => $oldPermissions != $newPermissions,
            'old_permissions_count' => count($oldPermissions),
            'new_permissions_count' => count($newPermissions),
        ]);

        return redirect()->route('roles.index')
            ->with('status', 'تم تحديث الدور بنجاح!');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        // Data integrity checks (even super admin shouldn't bypass these)
        if ($role->is_system) {
            return back()->withErrors(['error' => 'لا يمكن حذف الأدوار النظامية.']);
        }

        if ($role->users()->count() > 0) {
            return back()->withErrors(['error' => 'لا يمكن حذف دور له مستخدمين. قم بنقل المستخدمين أولاً.']);
        }

        $this->authorize('delete', $role);

        // Log role deletion before deleting
        ActivityLogService::logDelete($role);

        $role->delete();

        return redirect()->route('roles.index')
            ->with('status', 'تم حذف الدور بنجاح!');
    }
}
