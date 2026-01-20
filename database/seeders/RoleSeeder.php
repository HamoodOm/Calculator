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
        $superAdmin = Role::updateOrCreate(
            ['slug' => Role::SUPER_ADMIN],
            [
                'name' => 'المسؤول العام',
                'description' => 'لديه جميع الصلاحيات والوصول الكامل للنظام',
                'is_system' => true,
            ]
        );

        // Developer - has all permissions + debug features
        $developer = Role::updateOrCreate(
            ['slug' => Role::DEVELOPER],
            [
                'name' => 'المطور',
                'description' => 'لديه جميع الصلاحيات مع ميزات التصحيح والتطوير',
                'is_system' => true,
            ]
        );

        // TS Admin - Teacher & Student Admin (all pages access)
        $tsAdmin = Role::updateOrCreate(
            ['slug' => Role::TS_ADMIN],
            [
                'name' => 'مسؤول المعلمين والطلاب',
                'description' => 'لديه وصول لجميع صفحات المعلمين والطلاب',
                'is_system' => true,
            ]
        );

        // T Admin - Teacher Admin (teacher pages only)
        $tAdmin = Role::updateOrCreate(
            ['slug' => Role::T_ADMIN],
            [
                'name' => 'مسؤول المعلمين',
                'description' => 'لديه وصول لصفحات المعلمين البسيطة والمتقدمة',
                'is_system' => true,
            ]
        );

        // S Admin - Student Admin (student pages only)
        $sAdmin = Role::updateOrCreate(
            ['slug' => Role::S_ADMIN],
            [
                'name' => 'مسؤول الطلاب',
                'description' => 'لديه وصول لصفحات الطلاب البسيطة والمتقدمة',
                'is_system' => true,
            ]
        );

        // TS User - Teacher & Student Simple User
        $tsUser = Role::updateOrCreate(
            ['slug' => Role::TS_USER],
            [
                'name' => 'مستخدم المعلمين والطلاب',
                'description' => 'لديه وصول لصفحات المعلمين والطلاب البسيطة فقط',
                'is_system' => true,
            ]
        );

        // T User - Teacher Simple User
        $tUser = Role::updateOrCreate(
            ['slug' => Role::T_USER],
            [
                'name' => 'مستخدم المعلمين',
                'description' => 'لديه وصول لصفحة المعلمين البسيطة فقط',
                'is_system' => true,
            ]
        );

        // S User - Student Simple User
        $sUser = Role::updateOrCreate(
            ['slug' => Role::S_USER],
            [
                'name' => 'مستخدم الطلاب',
                'description' => 'لديه وصول لصفحة الطلاب البسيطة فقط',
                'is_system' => true,
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
