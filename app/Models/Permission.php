<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'group',
    ];

    /**
     * Permission slugs
     */
    // Teacher permissions
    const TEACHER_SIMPLE_VIEW = 'teacher.simple.view';
    const TEACHER_ADMIN_VIEW = 'teacher.admin.view';
    const TEACHER_ADMIN_EDIT = 'teacher.admin.edit';

    // Student permissions
    const STUDENT_SIMPLE_VIEW = 'student.simple.view';
    const STUDENT_ADMIN_VIEW = 'student.admin.view';
    const STUDENT_ADMIN_EDIT = 'student.admin.edit';

    // User management permissions
    const USERS_VIEW = 'users.view';
    const USERS_CREATE = 'users.create';
    const USERS_EDIT = 'users.edit';
    const USERS_DELETE = 'users.delete';

    // Role management permissions
    const ROLES_VIEW = 'roles.view';
    const ROLES_CREATE = 'roles.create';
    const ROLES_EDIT = 'roles.edit';
    const ROLES_DELETE = 'roles.delete';

    // Track management permissions
    const TRACKS_VIEW = 'tracks.view';
    const TRACKS_VIEW_GLOBAL = 'tracks.view.global';
    const TRACKS_CREATE = 'tracks.create';
    const TRACKS_EDIT = 'tracks.edit';
    const TRACKS_DELETE = 'tracks.delete';

    // Institution management permissions
    const INSTITUTIONS_VIEW = 'institutions.view';
    const INSTITUTIONS_MANAGE = 'institutions.manage';

    // Activity logs permissions
    const ACTIVITY_LOGS_VIEW = 'activity-logs.view';

    // API Client management permissions
    const API_CLIENTS_VIEW = 'api-clients.view';
    const API_CLIENTS_MANAGE = 'api-clients.manage';

    // Debug permissions (developer only)
    const DEBUG_VIEW = 'debug.view';

    /**
     * Get the roles that have this permission.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission')
            ->withTimestamps();
    }

    /**
     * Get all permissions grouped by their group
     */
    public static function getGrouped()
    {
        return static::all()->groupBy('group');
    }
}
