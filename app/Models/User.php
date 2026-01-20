<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'institution_id',
        'custom_color',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the role of the user.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the institution of the user.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->role && $this->role->slug === $roleSlug;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->role && in_array($this->role->slug, $roleSlugs);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    /**
     * Check if user is developer
     */
    public function isDeveloper(): bool
    {
        return $this->hasRole(Role::DEVELOPER);
    }

    /**
     * Check if user is super user (super-admin or developer)
     */
    public function isSuperUser(): bool
    {
        return $this->isSuperAdmin() || $this->isDeveloper();
    }

    /**
     * Get the role level of this user (lower = higher privilege)
     */
    public function getRoleLevel(): int
    {
        return $this->role ? $this->role->getLevel() : 99;
    }

    /**
     * Check if this user can see/manage another user
     */
    public function canManageUser(User $targetUser): bool
    {
        // Super users can manage anyone
        if ($this->isSuperUser()) {
            return true;
        }

        // Can't manage users above or equal level (except self for profile)
        if ($targetUser->role && $this->role) {
            if (!$this->role->isAbove($targetUser->role) && $targetUser->id !== $this->id) {
                return false;
            }
        }

        // Institution-based isolation: can only manage users in same institution
        if (!$this->isSuperUser() && $this->institution_id !== null) {
            if ($targetUser->institution_id !== $this->institution_id) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user can see a role
     */
    public function canSeeRole(Role $role): bool
    {
        if (!$this->role) {
            return false;
        }
        return $this->role->canSee($role);
    }

    /**
     * Check if using a custom hex color
     */
    public function hasHexColor(): bool
    {
        return !empty($this->custom_color) && str_starts_with($this->custom_color, '#');
    }

    /**
     * Get the hex color value
     */
    public function getHexColor(): ?string
    {
        return $this->hasHexColor() ? $this->custom_color : null;
    }

    /**
     * Adjust hex color brightness
     */
    protected function adjustHexBrightness(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Get the theme colors for this user based on institution, custom color, or role
     *
     * Priority:
     * 1. User's custom_color for bg (if set)
     * 2. Institution's header_color for bg
     * 3. Role-based defaults for bg
     *
     * For other theme elements (hover, text, accent, badge):
     * - Use role's custom theme colors if set
     * - Fall back to defaults based on bg color
     */
    public function getThemeColors(): array
    {
        // Get role's custom theme colors (hover, text, accent, badge)
        $roleTheme = $this->role ? $this->role->getThemeColors() : [
            'hover' => 'hover:bg-gray-900',
            'text' => 'text-gray-100',
            'accent' => 'text-gray-300',
            'badge' => 'bg-gray-600',
        ];

        // Check for user's custom hex color first
        if ($this->hasHexColor()) {
            return [
                'bg' => '',
                'bg_hex' => $this->custom_color,
                'hover' => 'hover:brightness-90',
                'text' => 'text-white',
                'accent' => 'text-gray-200',
                'badge' => $roleTheme['badge'] ?? '',
                'badge_hex' => $roleTheme['badge_hex'] ?? $this->adjustHexBrightness($this->custom_color, -30),
            ];
        }

        // Check institution's hex color
        if ($this->institution && $this->institution->hasHexColor()) {
            $instTheme = $this->institution->getThemeColors();
            return [
                'bg' => '',
                'bg_hex' => $instTheme['bg_hex'],
                'hover' => $roleTheme['hover'] ?? $instTheme['hover'],
                'text' => $roleTheme['text'] ?? $instTheme['text'],
                'accent' => $roleTheme['accent'] ?? $instTheme['accent'],
                'badge' => $roleTheme['badge'] ?? '',
                'badge_hex' => $roleTheme['badge_hex'] ?? $instTheme['badge_hex'] ?? null,
            ];
        }

        // Determine background color source (Tailwind classes)
        $bgColor = null;

        if ($this->custom_color) {
            $bgColor = $this->custom_color;
        } elseif ($this->institution) {
            $bgColor = $this->institution->header_color;
        }

        // If we have a custom bg color, build theme around it
        if ($bgColor) {
            $baseTheme = $this->getColorTheme($bgColor);

            return [
                'bg' => $baseTheme['bg'],
                'hover' => $roleTheme['hover'] ?? $baseTheme['hover'],
                'text' => $roleTheme['text'] ?? $baseTheme['text'],
                'accent' => $roleTheme['accent'] ?? $baseTheme['accent'],
                'badge' => $roleTheme['badge'] ?? $baseTheme['badge'],
            ];
        }

        // Fall back to role-based theme
        return $this->getRoleTheme();
    }

    /**
     * Get role-based theme colors (fallback)
     */
    protected function getRoleTheme(): array
    {
        $roleSlug = $this->role?->slug ?? 'guest';

        $themes = [
            'super-admin' => ['bg' => 'bg-purple-700', 'hover' => 'hover:bg-purple-800', 'text' => 'text-purple-100', 'accent' => 'text-purple-200', 'badge' => 'bg-purple-500'],
            'developer' => ['bg' => 'bg-rose-700', 'hover' => 'hover:bg-rose-800', 'text' => 'text-rose-100', 'accent' => 'text-rose-200', 'badge' => 'bg-rose-500'],
            'ts-admin' => ['bg' => 'bg-indigo-700', 'hover' => 'hover:bg-indigo-800', 'text' => 'text-indigo-100', 'accent' => 'text-indigo-200', 'badge' => 'bg-indigo-500'],
            't-admin' => ['bg' => 'bg-blue-700', 'hover' => 'hover:bg-blue-800', 'text' => 'text-blue-100', 'accent' => 'text-blue-200', 'badge' => 'bg-blue-500'],
            's-admin' => ['bg' => 'bg-teal-700', 'hover' => 'hover:bg-teal-800', 'text' => 'text-teal-100', 'accent' => 'text-teal-200', 'badge' => 'bg-teal-500'],
            'ts-user' => ['bg' => 'bg-gray-700', 'hover' => 'hover:bg-gray-800', 'text' => 'text-gray-100', 'accent' => 'text-gray-200', 'badge' => 'bg-gray-500'],
            't-user' => ['bg' => 'bg-slate-600', 'hover' => 'hover:bg-slate-700', 'text' => 'text-slate-100', 'accent' => 'text-slate-200', 'badge' => 'bg-slate-500'],
            's-user' => ['bg' => 'bg-zinc-600', 'hover' => 'hover:bg-zinc-700', 'text' => 'text-zinc-100', 'accent' => 'text-zinc-200', 'badge' => 'bg-zinc-500'],
            'guest' => ['bg' => 'bg-gray-800', 'hover' => 'hover:bg-gray-900', 'text' => 'text-gray-100', 'accent' => 'text-gray-300', 'badge' => 'bg-gray-600'],
        ];

        return $themes[$roleSlug] ?? $themes['guest'];
    }

    /**
     * Get theme colors for a specific color class
     */
    protected function getColorTheme(string $color): array
    {
        $colorMap = [
            'bg-purple-700' => ['bg' => 'bg-purple-700', 'hover' => 'hover:bg-purple-800', 'text' => 'text-purple-100', 'accent' => 'text-purple-200', 'badge' => 'bg-purple-500'],
            'bg-indigo-700' => ['bg' => 'bg-indigo-700', 'hover' => 'hover:bg-indigo-800', 'text' => 'text-indigo-100', 'accent' => 'text-indigo-200', 'badge' => 'bg-indigo-500'],
            'bg-blue-700' => ['bg' => 'bg-blue-700', 'hover' => 'hover:bg-blue-800', 'text' => 'text-blue-100', 'accent' => 'text-blue-200', 'badge' => 'bg-blue-500'],
            'bg-teal-700' => ['bg' => 'bg-teal-700', 'hover' => 'hover:bg-teal-800', 'text' => 'text-teal-100', 'accent' => 'text-teal-200', 'badge' => 'bg-teal-500'],
            'bg-green-700' => ['bg' => 'bg-green-700', 'hover' => 'hover:bg-green-800', 'text' => 'text-green-100', 'accent' => 'text-green-200', 'badge' => 'bg-green-500'],
            'bg-yellow-700' => ['bg' => 'bg-yellow-700', 'hover' => 'hover:bg-yellow-800', 'text' => 'text-yellow-100', 'accent' => 'text-yellow-200', 'badge' => 'bg-yellow-500'],
            'bg-orange-700' => ['bg' => 'bg-orange-700', 'hover' => 'hover:bg-orange-800', 'text' => 'text-orange-100', 'accent' => 'text-orange-200', 'badge' => 'bg-orange-500'],
            'bg-red-700' => ['bg' => 'bg-red-700', 'hover' => 'hover:bg-red-800', 'text' => 'text-red-100', 'accent' => 'text-red-200', 'badge' => 'bg-red-500'],
            'bg-pink-700' => ['bg' => 'bg-pink-700', 'hover' => 'hover:bg-pink-800', 'text' => 'text-pink-100', 'accent' => 'text-pink-200', 'badge' => 'bg-pink-500'],
            'bg-gray-700' => ['bg' => 'bg-gray-700', 'hover' => 'hover:bg-gray-800', 'text' => 'text-gray-100', 'accent' => 'text-gray-200', 'badge' => 'bg-gray-500'],
            'bg-slate-700' => ['bg' => 'bg-slate-700', 'hover' => 'hover:bg-slate-800', 'text' => 'text-slate-100', 'accent' => 'text-slate-200', 'badge' => 'bg-slate-500'],
            'bg-cyan-700' => ['bg' => 'bg-cyan-700', 'hover' => 'hover:bg-cyan-800', 'text' => 'text-cyan-100', 'accent' => 'text-cyan-200', 'badge' => 'bg-cyan-500'],
            'bg-emerald-700' => ['bg' => 'bg-emerald-700', 'hover' => 'hover:bg-emerald-800', 'text' => 'text-emerald-100', 'accent' => 'text-emerald-200', 'badge' => 'bg-emerald-500'],
            'bg-rose-700' => ['bg' => 'bg-rose-700', 'hover' => 'hover:bg-rose-800', 'text' => 'text-rose-100', 'accent' => 'text-rose-200', 'badge' => 'bg-rose-500'],
            'bg-amber-700' => ['bg' => 'bg-amber-700', 'hover' => 'hover:bg-amber-800', 'text' => 'text-amber-100', 'accent' => 'text-amber-200', 'badge' => 'bg-amber-500'],
        ];

        return $colorMap[$color] ?? $this->getRoleTheme();
    }

    /**
     * Check if user can manage institutions
     */
    public function canManageInstitutions(): bool
    {
        return $this->isSuperUser() || $this->hasPermission(Permission::INSTITUTIONS_MANAGE);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if (!$this->role) {
            return false;
        }

        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->role->hasPermission($permissionSlug);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        if (!$this->role) {
            return false;
        }

        // Super admin has all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }

        foreach ($permissionSlugs as $slug) {
            if ($this->role->hasPermission($slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all permissions for the user
     */
    public function permissions()
    {
        if (!$this->role) {
            return collect([]);
        }

        return $this->role->permissions;
    }

    /**
     * Check if user can access teacher simple page
     */
    public function canAccessTeacherSimple(): bool
    {
        return $this->hasPermission(Permission::TEACHER_SIMPLE_VIEW);
    }

    /**
     * Check if user can access teacher admin page
     */
    public function canAccessTeacherAdmin(): bool
    {
        return $this->hasPermission(Permission::TEACHER_ADMIN_VIEW);
    }

    /**
     * Check if user can access student simple page
     */
    public function canAccessStudentSimple(): bool
    {
        return $this->hasPermission(Permission::STUDENT_SIMPLE_VIEW);
    }

    /**
     * Check if user can access student admin page
     */
    public function canAccessStudentAdmin(): bool
    {
        return $this->hasPermission(Permission::STUDENT_ADMIN_VIEW);
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers(): bool
    {
        return $this->hasAnyPermission([
            Permission::USERS_VIEW,
            Permission::USERS_CREATE,
            Permission::USERS_EDIT,
            Permission::USERS_DELETE,
        ]);
    }

    /**
     * Check if user can manage roles
     */
    public function canManageRoles(): bool
    {
        return $this->hasAnyPermission([
            Permission::ROLES_VIEW,
            Permission::ROLES_CREATE,
            Permission::ROLES_EDIT,
            Permission::ROLES_DELETE,
        ]);
    }

    /**
     * Assign a role to the user
     */
    public function assignRole(Role $role): void
    {
        $this->role_id = $role->id;
        $this->save();
    }

    /**
     * Remove role from user
     */
    public function removeRole(): void
    {
        $this->role_id = null;
        $this->save();
    }

    /**
     * Check if this user can assign a specific role to other users
     */
    public function canAssignRole(Role $role): bool
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->canAssignRole($role);
    }

    /**
     * Get roles that this user can assign to other users
     */
    public function getAssignableRoles()
    {
        if (!$this->role) {
            return collect([]);
        }

        return $this->role->getAssignableRolesQuery()->get();
    }

    /**
     * Check if user can assign any roles at all
     */
    public function canAssignRoles(): bool
    {
        if (!$this->role) {
            return false;
        }

        $assignableSlugs = Role::getAssignableRoles($this->role->slug);
        return !empty($assignableSlugs);
    }
}
