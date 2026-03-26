<?php

namespace App\Policies;

use App\Models\ApiClient;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApiClientPolicy
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
     * Determine whether the user can view any API clients.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(Permission::API_CLIENTS_VIEW);
    }

    /**
     * Determine whether the user can view the API client.
     * Scoped to user's institution for non-super users.
     */
    public function view(User $user, ApiClient $apiClient): bool
    {
        if (!$user->hasPermission(Permission::API_CLIENTS_VIEW)) {
            return false;
        }

        return $this->belongsToUserInstitution($user, $apiClient);
    }

    /**
     * Determine whether the user can create API clients.
     * Requires api-clients.create OR api-clients.manage.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            Permission::API_CLIENTS_CREATE,
            Permission::API_CLIENTS_MANAGE,
        ]);
    }

    /**
     * Determine whether the user can update the API client.
     * Requires api-clients.edit OR api-clients.manage.
     */
    public function update(User $user, ApiClient $apiClient): bool
    {
        if (!$user->hasAnyPermission([Permission::API_CLIENTS_EDIT, Permission::API_CLIENTS_MANAGE])) {
            return false;
        }

        return $this->belongsToUserInstitution($user, $apiClient);
    }

    /**
     * Determine whether the user can delete the API client.
     * Requires api-clients.delete OR api-clients.manage.
     */
    public function delete(User $user, ApiClient $apiClient): bool
    {
        if (!$user->hasAnyPermission([Permission::API_CLIENTS_DELETE, Permission::API_CLIENTS_MANAGE])) {
            return false;
        }

        return $this->belongsToUserInstitution($user, $apiClient);
    }

    /**
     * Determine whether the user can toggle the API client's active status.
     * Requires api-clients.edit OR api-clients.manage.
     */
    public function toggle(User $user, ApiClient $apiClient): bool
    {
        return $this->update($user, $apiClient);
    }

    /**
     * Determine whether the user can regenerate API client credentials.
     * Requires api-clients.credentials OR api-clients.manage.
     */
    public function regenerateCredentials(User $user, ApiClient $apiClient): bool
    {
        if (!$user->hasAnyPermission([Permission::API_CLIENTS_CREDENTIALS, Permission::API_CLIENTS_MANAGE])) {
            return false;
        }

        return $this->belongsToUserInstitution($user, $apiClient);
    }

    /**
     * Determine whether the user can view API client logs.
     */
    public function viewLogs(User $user, ApiClient $apiClient): bool
    {
        return $this->view($user, $apiClient);
    }

    /**
     * Determine whether the user can view course mappings.
     * Requires api-clients.mappings.view OR api-clients.view OR api-clients.manage.
     */
    public function viewMappings(User $user, ApiClient $apiClient): bool
    {
        if (!$user->hasAnyPermission([
            Permission::API_CLIENTS_MAPPINGS_VIEW,
            Permission::API_CLIENTS_VIEW,
            Permission::API_CLIENTS_MANAGE,
        ])) {
            return false;
        }

        return $this->belongsToUserInstitution($user, $apiClient);
    }

    /**
     * Determine whether the user can create course mappings.
     * Requires api-clients.mappings.create OR api-clients.manage.
     */
    public function createMapping(User $user, ApiClient $apiClient): bool
    {
        if (!$user->hasAnyPermission([
            Permission::API_CLIENTS_MAPPINGS_CREATE,
            Permission::API_CLIENTS_MANAGE,
        ])) {
            return false;
        }

        return $this->belongsToUserInstitution($user, $apiClient);
    }

    /**
     * Determine whether the user can update course mappings.
     * Requires api-clients.mappings.edit OR api-clients.manage.
     */
    public function updateMapping(User $user, ApiClient $apiClient): bool
    {
        if (!$user->hasAnyPermission([
            Permission::API_CLIENTS_MAPPINGS_EDIT,
            Permission::API_CLIENTS_MANAGE,
        ])) {
            return false;
        }

        return $this->belongsToUserInstitution($user, $apiClient);
    }

    /**
     * Determine whether the user can delete course mappings.
     * Requires api-clients.mappings.delete OR api-clients.manage.
     */
    public function deleteMapping(User $user, ApiClient $apiClient): bool
    {
        if (!$user->hasAnyPermission([
            Permission::API_CLIENTS_MAPPINGS_DELETE,
            Permission::API_CLIENTS_MANAGE,
        ])) {
            return false;
        }

        return $this->belongsToUserInstitution($user, $apiClient);
    }

    /**
     * Check if the API client belongs to the user's institution.
     * Super users (developer role) can access all institutions.
     */
    protected function belongsToUserInstitution(User $user, ApiClient $apiClient): bool
    {
        if ($user->isSuperUser()) {
            return true;
        }

        return $user->institution_id !== null
            && $apiClient->institution_id === $user->institution_id;
    }
}
