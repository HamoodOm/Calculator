<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Super Admin - has all permissions (handled automatically in code)
        // Level 0: Highest privilege
        $superAdmin = Role::updateOrCreate(
            ['slug' => Role::SUPER_ADMIN],
            [
                'name' => 'المسؤول العام',
                'description' => 'لديه جميع الصلاحيات والوصول الكامل للنظام',
                'is_system' => true,
                'level' => 0,
            ]
        );

        // Developer - has all permissions + debug features
        // Level 5: Reserved for special roles
        $developer = Role::updateOrCreate(
            ['slug' => Role::DEVELOPER],
            [
                'name' => 'المطور',
                'description' => 'لديه جميع الصلاحيات مع ميزات التصحيح والتطوير',
                'is_system' => true,
                'level' => 5,
            ]
        );

        // TS Admin - Teacher & Student Admin (all pages access)
        // Level 20: Admin tier starts
        $tsAdmin = Role::updateOrCreate(
            ['slug' => Role::TS_ADMIN],
            [
                'name' => 'مسؤول المعلمين والطلاب',
                'description' => 'لديه وصول لجميع صفحات المعلمين والطلاب',
                'is_system' => true,
                'level' => 20,
            ]
        );

        // T Admin - Teacher Admin (teacher pages only)
        // Level 25: Same tier as S Admin
        $tAdmin = Role::updateOrCreate(
            ['slug' => Role::T_ADMIN],
            [
                'name' => 'مسؤول المعلمين',
                'description' => 'لديه وصول لصفحات المعلمين البسيطة والمتقدمة',
                'is_system' => true,
                'level' => 25,
            ]
        );

        // S Admin - Student Admin (student pages only)
        // Level 25: Same tier as T Admin
        $sAdmin = Role::updateOrCreate(
            ['slug' => Role::S_ADMIN],
            [
                'name' => 'مسؤول الطلاب',
                'description' => 'لديه وصول لصفحات الطلاب البسيطة والمتقدمة',
                'is_system' => true,
                'level' => 25,
            ]
        );

        // TS User - Teacher & Student Simple User
        // Level 30: User tier starts
        $tsUser = Role::updateOrCreate(
            ['slug' => Role::TS_USER],
            [
                'name' => 'مستخدم المعلمين والطلاب',
                'description' => 'لديه وصول لصفحات المعلمين والطلاب البسيطة فقط',
                'is_system' => true,
                'level' => 30,
            ]
        );

        // T User - Teacher Simple User
        // Level 35: Same tier as S User
        $tUser = Role::updateOrCreate(
            ['slug' => Role::T_USER],
            [
                'name' => 'مستخدم المعلمين',
                'description' => 'لديه وصول لصفحة المعلمين البسيطة فقط',
                'is_system' => true,
                'level' => 35,
            ]
        );

        // S User - Student Simple User
        // Level 35: Same tier as T User
        $sUser = Role::updateOrCreate(
            ['slug' => Role::S_USER],
            [
                'name' => 'مستخدم الطلاب',
                'description' => 'لديه وصول لصفحة الطلاب البسيطة فقط',
                'is_system' => true,
                'level' => 35,
            ]
        );

        // Get all permissions
        $allPermissions = Permission::all()->pluck('id', 'slug');

        // Developer gets all permissions including debug and institutions
        $developerPermissions = array_values($allPermissions->toArray());
        $developer->syncPermissions($developerPermissions);

        // Assign permissions to TS Admin (all teacher & student + track permissions + user management for role delegation)
        $tsAdmin->syncPermissions([
            $allPermissions[Permission::TEACHER_SIMPLE_VIEW],
            $allPermissions[Permission::TEACHER_ADMIN_VIEW],
            $allPermissions[Permission::TEACHER_ADMIN_EDIT],
            $allPermissions[Permission::STUDENT_SIMPLE_VIEW],
            $allPermissions[Permission::STUDENT_ADMIN_VIEW],
            $allPermissions[Permission::STUDENT_ADMIN_EDIT],
            $allPermissions[Permission::TRACKS_CREATE],
            $allPermissions[Permission::TRACKS_DELETE],
            $allPermissions[Permission::USERS_VIEW],
            $allPermissions[Permission::USERS_CREATE],
            $allPermissions[Permission::USERS_EDIT],
        ]);

        // Assign permissions to T Admin (teacher only + track permissions + user management for role delegation)
        $tAdmin->syncPermissions([
            $allPermissions[Permission::TEACHER_SIMPLE_VIEW],
            $allPermissions[Permission::TEACHER_ADMIN_VIEW],
            $allPermissions[Permission::TEACHER_ADMIN_EDIT],
            $allPermissions[Permission::TRACKS_CREATE],
            $allPermissions[Permission::TRACKS_DELETE],
            $allPermissions[Permission::USERS_VIEW],
            $allPermissions[Permission::USERS_CREATE],
            $allPermissions[Permission::USERS_EDIT],
        ]);

        // Assign permissions to S Admin (student only + track permissions + user management for role delegation)
        $sAdmin->syncPermissions([
            $allPermissions[Permission::STUDENT_SIMPLE_VIEW],
            $allPermissions[Permission::STUDENT_ADMIN_VIEW],
            $allPermissions[Permission::STUDENT_ADMIN_EDIT],
            $allPermissions[Permission::TRACKS_CREATE],
            $allPermissions[Permission::TRACKS_DELETE],
            $allPermissions[Permission::USERS_VIEW],
            $allPermissions[Permission::USERS_CREATE],
            $allPermissions[Permission::USERS_EDIT],
        ]);

        // Assign permissions to TS User (simple pages only)
        $tsUser->syncPermissions([
            $allPermissions[Permission::TEACHER_SIMPLE_VIEW],
            $allPermissions[Permission::STUDENT_SIMPLE_VIEW],
        ]);

        // Assign permissions to T User (teacher simple only)
        $tUser->syncPermissions([
            $allPermissions[Permission::TEACHER_SIMPLE_VIEW],
        ]);

        // Assign permissions to S User (student simple only)
        $sUser->syncPermissions([
            $allPermissions[Permission::STUDENT_SIMPLE_VIEW],
        ]);
    }
}
