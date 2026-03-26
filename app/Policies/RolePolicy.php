<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     * Super admins bypass all checks.
     */
    public function before(User $user, $ability)
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::ROLES_VIEW);
    }

    /**
     * Determine whether the user can view the role.
     * Users can only view roles at or below their level.
     */
    public function view(User $user, Role $role): bool
    {
        if (!$user->hasPermission(Permission::ROLES_VIEW)) {
            return false;
        }

        return $user->canSeeRole($role);
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission(Permission::ROLES_CREATE);
    }

    /**
     * Determine whether the user can update the role.
     * Prevents editing system roles (except super-admin).
     * Prevents editing roles above user's level.
     */
    public function update(User $user, Role $role): bool
    {
        if (!$user->hasPermission(Permission::ROLES_EDIT)) {
            return false;
        }

        // Can't edit system roles (except super-admin theme/permissions)
        if ($role->is_system && !$role->isSuperAdmin()) {
            return false;
        }

        // Can't edit roles above user's own level
        return $user->canSeeRole($role);
    }

    /**
     * Determine whether the user can delete the role.
     */
    public function delete(User $user, Role $role): bool
    {
        if (!$user->hasPermission(Permission::ROLES_DELETE)) {
            return false;
        }

        // Can't delete system roles
        if ($role->is_system) {
            return false;
        }

        // Can't delete roles with assigned users
        if ($role->users()->count() > 0) {
            return false;
        }

        // Can't delete roles above user's level
        return $user->canSeeRole($role);
    }

    /**
     * Determine whether the user can create a role at a specific level.
     * Prevents privilege escalation by ensuring users can only create
     * roles with a level higher than their own (lower privilege).
     */
    public function createAtLevel(User $user, int $level): bool
    {
        if (!$user->hasPermission(Permission::ROLES_CREATE)) {
            return false;
        }

        // User can only create roles at a level >= their own level
        return $level >= $user->getRoleLevel();
    }

    /**
     * Determine whether the user can assign a specific permission to a role.
     * Users can only assign permissions they themselves possess.
     */
    public function assignPermission(User $user, string $permissionSlug): bool
    {
        return $user->hasPermission($permissionSlug);
    }
}
