<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddStorageManagePermission extends Migration
{
    public function up(): void
    {
        // Add storage.manage permission
        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'إدارة التخزين',
            'slug' => 'storage.manage',
            'description' => 'عرض وإدارة الملفات المؤقتة وملفات الشهادات المولدة',
            'group' => 'storage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Assign to super-admin and developer roles
        $superAdminRole = DB::table('roles')->where('slug', 'super-admin')->first();
        $developerRole = DB::table('roles')->where('slug', 'developer')->first();

        if ($superAdminRole) {
            DB::table('role_permission')->insert([
                'role_id' => $superAdminRole->id,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($developerRole) {
            DB::table('role_permission')->insert([
                'role_id' => $developerRole->id,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $permission = DB::table('permissions')->where('slug', 'storage.manage')->first();
        if ($permission) {
            DB::table('role_permission')->where('permission_id', $permission->id)->delete();
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
}
