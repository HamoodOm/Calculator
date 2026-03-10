<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of users with filters and sorting.
     */
    public function index(Request $request)
    {
        $currentUser = Auth::user();

        $query = User::with(['role', 'institution']);

        // Institution-based filtering for non-super users
        if (!$currentUser->isSuperUser()) {
            if ($currentUser->institution_id) {
                $query->where('institution_id', $currentUser->institution_id);
            } else {
                $query->whereNull('institution_id');
            }

            // Filter by visible roles (hide roles above current user)
            $query->where(function ($q) use ($currentUser) {
                $q->whereNull('role_id');
                if ($currentUser->role) {
                    $visibleRoleSlugs = $this->getVisibleRoleSlugs($currentUser);
                    $q->orWhereHas('role', function ($roleQuery) use ($visibleRoleSlugs) {
                        $roleQuery->whereIn('slug', $visibleRoleSlugs);
                    });
                }
            });
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply role filter
        if ($request->filled('role')) {
            if ($request->role === 'none') {
                $query->whereNull('role_id');
            } else {
                $query->whereHas('role', function ($q) use ($request) {
                    $q->where('slug', $request->role);
                });
            }
        }

        // Apply institution filter (super users only)
        if ($currentUser->isSuperUser() && $request->filled('institution')) {
            if ($request->institution === 'none') {
                $query->whereNull('institution_id');
            } else {
                $query->where('institution_id', $request->institution);
            }
        }

        // Apply status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Apply sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $direction = $sortDir === 'asc' ? 'asc' : 'desc';

        $allowedSorts = ['name', 'email', 'created_at', 'is_active', 'role', 'institution'];

        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        // Handle relationship sorting
        if ($sortBy === 'role') {
            $query->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                  ->orderBy('roles.name', $direction)
                  ->select('users.*');
        } elseif ($sortBy === 'institution') {
            $query->leftJoin('institutions', 'users.institution_id', '=', 'institutions.id')
                  ->orderBy('institutions.name', $direction)
                  ->select('users.*');
        } else {
            $query->orderBy($sortBy, $direction);
        }

        $users = $query->paginate(15)->withQueryString();

        // Get filter options
        $roles = $this->getVisibleRoles($currentUser);
        $institutions = $currentUser->isSuperUser() ? Institution::all() : collect([]);

        return view('auth.users.index', [
            'users' => $users,
            'roles' => $roles,
            'institutions' => $institutions,
            'currentFilters' => $request->only(['search', 'role', 'institution', 'status', 'sort', 'dir']),
        ]);
    }

    /**
     * Get role slugs that the current user can see.
     */
    private function getVisibleRoleSlugs(User $user): array
    {
        if ($user->isSuperUser()) {
            return Role::pluck('slug')->toArray();
        }

        if (!$user->role) {
            return [];
        }

        $userLevel = $user->role->getLevel();
        return Role::all()->filter(function ($role) use ($userLevel) {
            return $role->getLevel() >= $userLevel;
        })->pluck('slug')->toArray();
    }

    /**
     * Get roles that the current user can see.
     */
    private function getVisibleRoles(User $user)
    {
        if ($user->isSuperUser()) {
            return Role::all();
        }

        if (!$user->role) {
            return collect([]);
        }

        $userLevel = $user->role->getLevel();
        return Role::all()->filter(function ($role) use ($userLevel) {
            return $role->getLevel() >= $userLevel;
        });
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $currentUser = Auth::user();

        // Get only roles that the current user can assign
        $roles = $currentUser->getAssignableRoles();

        // If user can't assign any roles, they shouldn't be here
        if ($roles->isEmpty()) {
            return redirect()->route('users.index')
                ->withErrors(['error' => 'ليس لديك صلاحية لإنشاء مستخدمين.']);
        }

        // Get institutions (super users only, or user's institution)
        $institutions = collect([]);
        $canSelectInstitution = false;

        if ($currentUser->isSuperUser()) {
            $institutions = Institution::where('is_active', true)->get();
            $canSelectInstitution = true;
        } elseif ($currentUser->institution_id) {
            $institutions = Institution::where('id', $currentUser->institution_id)->get();
        }

        $colors = Institution::getAvailableColors();

        return view('auth.users.create', [
            'roles' => $roles,
            'institutions' => $institutions,
            'canSelectInstitution' => $canSelectInstitution,
            'colors' => $colors,
            'defaultInstitutionId' => $currentUser->institution_id,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role_id' => ['nullable', 'exists:roles,id'],
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'custom_color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        // Check if user is trying to assign a role
        if ($request->filled('role_id')) {
            $roleToAssign = Role::find($request->role_id);

            // Verify the current user can assign this role
            if ($roleToAssign && !$currentUser->canAssignRole($roleToAssign)) {
                return back()->withErrors(['role_id' => 'ليس لديك صلاحية لمنح هذا الدور.'])
                    ->withInput();
            }
        }

        // Validate institution assignment
        $institutionId = null;
        if ($currentUser->isSuperUser()) {
            $institutionId = $request->institution_id;
        } elseif ($currentUser->institution_id) {
            $institutionId = $currentUser->institution_id;
        }

        // Validate custom color permission
        $customColor = null;
        if ($request->filled('custom_color') && $currentUser->isSuperUser()) {
            $customColor = $request->custom_color;
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'institution_id' => $institutionId,
            'custom_color' => $customColor,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Log user creation
        ActivityLogService::logCreate($user);

        return redirect()->route('users.index')
            ->with('status', 'تم إنشاء المستخدم بنجاح!');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user)
    {
        $currentUser = Auth::user();

        // Check if current user can manage this user
        if (!$currentUser->canManageUser($user)) {
            return redirect()->route('users.index')
                ->withErrors(['error' => 'ليس لديك صلاحية لتعديل هذا المستخدم.']);
        }

        // Get only roles that the current user can assign
        $roles = $currentUser->getAssignableRoles();

        // If the user being edited has a role the current user can't assign,
        // include it in the list but disable the ability to change from it
        $userCurrentRole = $user->role;
        $canChangeRole = true;

        if ($userCurrentRole && !$currentUser->canAssignRole($userCurrentRole)) {
            // Current user can't modify this user's role
            $canChangeRole = false;
            // Include the current role in the list for display
            if (!$roles->contains('id', $userCurrentRole->id)) {
                $roles = $roles->push($userCurrentRole);
            }
        }

        // Get institutions
        $institutions = collect([]);
        $canSelectInstitution = false;

        if ($currentUser->isSuperUser()) {
            $institutions = Institution::all();
            $canSelectInstitution = true;
        } elseif ($currentUser->institution_id) {
            $institutions = Institution::where('id', $currentUser->institution_id)->get();
        }

        $colors = Institution::getAvailableColors();
        $canChangeColor = $currentUser->isSuperUser();

        return view('auth.users.edit', [
            'user' => $user,
            'roles' => $roles,
            'institutions' => $institutions,
            'currentUser' => $currentUser,
            'canChangeRole' => $canChangeRole,
            'canSelectInstitution' => $canSelectInstitution,
            'canChangeColor' => $canChangeColor,
            'colors' => $colors,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $currentUser = Auth::user();

        // Check if current user can manage this user
        if (!$currentUser->canManageUser($user)) {
            return back()->withErrors(['error' => 'ليس لديك صلاحية لتعديل هذا المستخدم.']);
        }

        // Prevent non-super-admins from editing super-admins
        if ($user->isSuperAdmin() && !$currentUser->isSuperAdmin()) {
            return back()->withErrors(['error' => 'لا يمكنك تعديل مستخدم مسؤول عام.']);
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role_id' => ['nullable', 'exists:roles,id'],
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'custom_color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];

        // Password is optional on update
        if ($request->filled('password')) {
            $rules['password'] = ['confirmed', Password::min(8)];
        }

        $request->validate($rules);

        // Check if user is trying to assign a role
        if ($request->filled('role_id')) {
            $roleToAssign = Role::find($request->role_id);

            // Verify the current user can assign this role
            if ($roleToAssign && !$currentUser->canAssignRole($roleToAssign)) {
                return back()->withErrors(['role_id' => 'ليس لديك صلاحية لمنح هذا الدور.'])
                    ->withInput();
            }
        }

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'is_active' => $request->boolean('is_active', true),
        ];

        // Handle institution assignment
        if ($currentUser->isSuperUser()) {
            $updateData['institution_id'] = $request->institution_id;
        }

        // Handle custom color
        if ($currentUser->isSuperUser()) {
            $updateData['custom_color'] = $request->custom_color;
        }

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        // Prevent removing super-admin role from self
        if ($currentUser->id === $user->id) {
            $newRole = $request->role_id ? Role::find($request->role_id) : null;
            if ($user->isSuperAdmin() && (!$newRole || !$newRole->isSuperAdmin())) {
                return back()->withErrors(['error' => 'لا يمكنك إزالة دور المسؤول العام من نفسك.']);
            }
            // Prevent deactivating self
            if (!$request->boolean('is_active', true)) {
                return back()->withErrors(['error' => 'لا يمكنك تعطيل حسابك.']);
            }
        }

        // Store old values for logging
        $oldValues = $user->toArray();

        $user->update($updateData);

        // Log user update
        ActivityLogService::logUpdate($user, $oldValues);

        return redirect()->route('users.index')
            ->with('status', 'تم تحديث المستخدم بنجاح!');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $currentUser = Auth::user();

        // Prevent deleting self
        if ($currentUser->id === $user->id) {
            return back()->withErrors(['error' => 'لا يمكنك حذف حسابك.']);
        }

        // Check if current user can manage this user
        if (!$currentUser->canManageUser($user)) {
            return back()->withErrors(['error' => 'ليس لديك صلاحية لحذف هذا المستخدم.']);
        }

        // Prevent non-super-admins from deleting super-admins
        if ($user->isSuperAdmin() && !$currentUser->isSuperAdmin()) {
            return back()->withErrors(['error' => 'لا يمكنك حذف مستخدم مسؤول عام.']);
        }

        // Prevent deleting the last super-admin
        if ($user->isSuperAdmin()) {
            $superAdminCount = User::whereHas('role', function ($query) {
                $query->where('slug', Role::SUPER_ADMIN);
            })->count();

            if ($superAdminCount <= 1) {
                return back()->withErrors(['error' => 'لا يمكن حذف آخر مسؤول عام.']);
            }
        }

        // Log user deletion before deleting
        ActivityLogService::logDelete($user);

        $user->delete();

        return redirect()->route('users.index')
            ->with('status', 'تم حذف المستخدم بنجاح!');
    }

    /**
     * Toggle user active status.
     */
    public function toggleActive(User $user)
    {
        $currentUser = Auth::user();

        // Prevent toggling self
        if ($currentUser->id === $user->id) {
            return back()->withErrors(['error' => 'لا يمكنك تعطيل/تفعيل حسابك.']);
        }

        // Check if current user can manage this user
        if (!$currentUser->canManageUser($user)) {
            return back()->withErrors(['error' => 'ليس لديك صلاحية لتعديل هذا المستخدم.']);
        }

        // Prevent non-super-admins from toggling super-admins
        if ($user->isSuperAdmin() && !$currentUser->isSuperAdmin()) {
            return back()->withErrors(['error' => 'لا يمكنك تعديل مستخدم مسؤول عام.']);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        // Log toggle action
        ActivityLogService::logToggle($user, $user->is_active);

        $status = $user->is_active ? 'تم تفعيل المستخدم بنجاح!' : 'تم تعطيل المستخدم بنجاح!';

        return back()->with('status', $status);
    }
}
