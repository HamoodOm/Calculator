<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_system',
        'level',
        'theme_hover',
        'theme_text',
        'theme_accent',
        'theme_badge',
        'custom_hex_color',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * Role slugs - used for role checks
     */
    const SUPER_ADMIN = 'super-admin';
    const DEVELOPER = 'developer';
    const TS_ADMIN = 'ts-admin';
    const T_ADMIN = 't-admin';
    const S_ADMIN = 's-admin';
    const TS_USER = 'ts-user';
    const T_USER = 't-user';
    const S_USER = 's-user';

    /**
     * Role hierarchy levels (lower number = higher privilege)
     * Super users: 0-10 (reserved for special roles)
     * Admins: 20-29 (with 5 step difference for customization)
     * Users: 30-39 (with 5 step difference for customization)
     */
    const ROLE_LEVELS = [
        self::SUPER_ADMIN => 0,
        self::DEVELOPER => 5,
        self::TS_ADMIN => 20,
        self::T_ADMIN => 25,
        self::S_ADMIN => 25,
        self::TS_USER => 30,
        self::T_USER => 35,
        self::S_USER => 35,
    ];

    /**
     * Get the permissions for the role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Get the users for the role.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions->contains('slug', $permissionSlug);
    }

    /**
     * Check if this is the super admin role
     */
    public function isSuperAdmin(): bool
    {
        return $this->slug === self::SUPER_ADMIN;
    }

    /**
     * Check if this is the developer role
     */
    public function isDeveloper(): bool
    {
        return $this->slug === self::DEVELOPER;
    }

    /**
     * Check if this is a super user (super-admin or developer)
     */
    public function isSuperUser(): bool
    {
        return $this->isSuperAdmin() || $this->isDeveloper();
    }

    /**
     * Get the hierarchy level of this role (lower = higher privilege)
     * Uses database level if set, otherwise falls back to predefined levels
     */
    public function getLevel(): int
    {
        // If level is set in database, use that
        if ($this->level !== null && $this->level !== 99) {
            return $this->level;
        }

        // Fall back to predefined levels for system roles
        return self::ROLE_LEVELS[$this->slug] ?? 99;
    }

    /**
     * Check if using a custom hex color for badge
     */
    public function hasHexColor(): bool
    {
        return !empty($this->custom_hex_color) && str_starts_with($this->custom_hex_color, '#');
    }

    /**
     * Get the hex color value
     */
    public function getHexColor(): ?string
    {
        return $this->hasHexColor() ? $this->custom_hex_color : null;
    }

    /**
     * Get theme colors for this role
     * Returns only non-bg colors (hover, text, accent, badge)
     * Background comes from institution
     */
    public function getThemeColors(): array
    {
        $defaults = [
            'hover' => 'hover:bg-gray-900',
            'text' => 'text-gray-100',
            'accent' => 'text-gray-300',
            'badge' => 'bg-gray-600',
        ];

        $theme = [
            'hover' => $this->theme_hover ?? $defaults['hover'],
            'text' => $this->theme_text ?? $defaults['text'],
            'accent' => $this->theme_accent ?? $defaults['accent'],
            'badge' => $this->theme_badge ?? $defaults['badge'],
        ];

        // If custom hex color is set, use it for badge
        if ($this->hasHexColor()) {
            $theme['badge'] = '';
            $theme['badge_hex'] = $this->custom_hex_color;
        }

        return $theme;
    }

    /**
     * Get available theme color options
     */
    public static function getAvailableThemeColors(): array
    {
        return [
            'hover' => [
                'hover:bg-gray-900' => 'رمادي داكن',
                'hover:bg-indigo-800' => 'نيلي',
                'hover:bg-blue-800' => 'أزرق',
                'hover:bg-green-800' => 'أخضر',
                'hover:bg-red-800' => 'أحمر',
                'hover:bg-purple-800' => 'بنفسجي',
                'hover:bg-teal-800' => 'أزرق مخضر',
                'hover:bg-orange-800' => 'برتقالي',
            ],
            'text' => [
                'text-gray-100' => 'رمادي فاتح',
                'text-white' => 'أبيض',
                'text-gray-200' => 'رمادي',
                'text-indigo-100' => 'نيلي فاتح',
                'text-blue-100' => 'أزرق فاتح',
            ],
            'accent' => [
                'text-gray-300' => 'رمادي',
                'text-gray-400' => 'رمادي داكن',
                'text-indigo-200' => 'نيلي',
                'text-blue-200' => 'أزرق',
                'text-green-200' => 'أخضر',
            ],
            'badge' => [
                'bg-gray-600' => 'رمادي',
                'bg-indigo-600' => 'نيلي',
                'bg-blue-600' => 'أزرق',
                'bg-green-600' => 'أخضر',
                'bg-red-600' => 'أحمر',
                'bg-purple-600' => 'بنفسجي',
                'bg-teal-600' => 'أزرق مخضر',
                'bg-orange-600' => 'برتقالي',
                'bg-yellow-600' => 'أصفر',
            ],
        ];
    }

    /**
     * Check if this role is above another role in hierarchy
     */
    public function isAbove(Role $role): bool
    {
        return $this->getLevel() < $role->getLevel();
    }

    /**
     * Check if this role can see another role (same level or below)
     */
    public function canSee(Role $role): bool
    {
        // Super admin and developer can see all
        if ($this->isSuperUser()) {
            return true;
        }
        return $this->getLevel() <= $role->getLevel();
    }

    /**
     * Assign a permission to the role
     */
    public function givePermission(Permission $permission): void
    {
        if (!$this->hasPermission($permission->slug)) {
            $this->permissions()->attach($permission);
        }
    }

    /**
     * Remove a permission from the role
     */
    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->detach($permission);
    }

    /**
     * Sync permissions for the role
     */
    public function syncPermissions(array $permissionIds): void
    {
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Get predefined role slugs that this role can assign to other users
     * Role hierarchy:
     * - super-admin: can assign any role (handled separately)
     * - developer: can assign ts-admin, t-admin, s-admin, ts-user, t-user, s-user
     * - ts-admin: can assign ts-user, t-user, s-user
     * - t-admin: can assign t-user only
     * - s-admin: can assign s-user only
     * - users (ts-user, t-user, s-user): cannot assign roles
     */
    public static function getAssignableRoleSlugs(string $roleSlug): array
    {
        $hierarchy = [
            self::DEVELOPER => [self::TS_ADMIN, self::T_ADMIN, self::S_ADMIN, self::TS_USER, self::T_USER, self::S_USER],
            self::TS_ADMIN => [self::TS_USER, self::T_USER, self::S_USER],
            self::T_ADMIN => [self::T_USER],
            self::S_ADMIN => [self::S_USER],
            self::TS_USER => [],
            self::T_USER => [],
            self::S_USER => [],
        ];

        return $hierarchy[$roleSlug] ?? [];
    }

    /**
     * Get roles that this role can assign (includes all roles for super-admin)
     * This method is used for backward compatibility and dynamic role lists
     */
    public static function getAssignableRoles(string $roleSlug): array
    {
        // Super admin can assign any role including custom roles
        if ($roleSlug === self::SUPER_ADMIN) {
            return self::pluck('slug')->toArray();
        }

        return self::getAssignableRoleSlugs($roleSlug);
    }

    /**
     * Check if this role can assign a specific role to users
     */
    public function canAssignRole(Role $role): bool
    {
        // Super admin can assign any role
        if ($this->isSuperAdmin()) {
            return true;
        }

        $assignableRoles = self::getAssignableRoleSlugs($this->slug);
        return in_array($role->slug, $assignableRoles);
    }

    /**
     * Get all roles that this role can assign
     */
    public function getAssignableRolesQuery()
    {
        // Super admin can assign any role (including custom roles)
        if ($this->isSuperAdmin()) {
            return self::query();
        }

        $assignableSlugs = self::getAssignableRoleSlugs($this->slug);

        if (empty($assignableSlugs)) {
            return self::whereRaw('1 = 0'); // Return empty query
        }

        return self::whereIn('slug', $assignableSlugs);
    }
}
