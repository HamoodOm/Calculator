<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;

class AddTracksViewPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add the new TRACKS_VIEW permission if it doesn't exist
        DB::table('permissions')->insertOrIgnore([
            'name' => 'عرض المسارات',
            'slug' => Permission::TRACKS_VIEW,
            'description' => 'الوصول إلى صفحة إدارة المسارات',
            'group' => 'إدارة المسارات',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Get the permission ID
        $permission = DB::table('permissions')->where('slug', Permission::TRACKS_VIEW)->first();

        if ($permission) {
            // Grant this permission to super-admin role
            $superAdmin = DB::table('roles')->where('slug', 'super-admin')->first();
            if ($superAdmin) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $superAdmin->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Grant to admin role
            $admin = DB::table('roles')->where('slug', 'admin')->first();
            if ($admin) {
                DB::table('role_permission')->insertOrIgnore([
                    'role_id' => $admin->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Get permission ID
        $permission = DB::table('permissions')->where('slug', Permission::TRACKS_VIEW)->first();

        if ($permission) {
            // Remove from role_permission pivot
            DB::table('role_permission')->where('permission_id', $permission->id)->delete();
            // Delete permission
            DB::table('permissions')->where('id', $permission->id)->delete();
        }
    }
}
