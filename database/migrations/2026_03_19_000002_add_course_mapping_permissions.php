<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCourseMappingPermissions extends Migration
{
    /**
     * Run the migrations.
     * Adds course mapping permissions for granular access control.
     */
    public function up()
    {
        $now = now();

        $newPermissions = [
            [
                'name' => 'عرض ربط الدورات',
                'slug' => Permission::API_CLIENTS_MAPPINGS_VIEW,
                'description' => 'عرض ربط الدورات لعملاء API',
                'group' => 'إدارة API',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'إنشاء ربط الدورات',
                'slug' => Permission::API_CLIENTS_MAPPINGS_CREATE,
                'description' => 'إنشاء ربط دورات جديدة لعملاء API',
                'group' => 'إدارة API',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'تعديل ربط الدورات',
                'slug' => Permission::API_CLIENTS_MAPPINGS_EDIT,
                'description' => 'تعديل ربط الدورات لعملاء API',
                'group' => 'إدارة API',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'حذف ربط الدورات',
                'slug' => Permission::API_CLIENTS_MAPPINGS_DELETE,
                'description' => 'حذف ربط الدورات لعملاء API',
                'group' => 'إدارة API',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($newPermissions as $perm) {
            if (!DB::table('permissions')->where('slug', $perm['slug'])->exists()) {
                DB::table('permissions')->insert($perm);
            }
        }

        // Assign new permissions to roles that already have api-clients.manage
        // (api-clients.manage is the superset, so those roles should get all granular permissions too)
        $managePermission = DB::table('permissions')
            ->where('slug', Permission::API_CLIENTS_MANAGE)
            ->first();

        if ($managePermission) {
            $rolesWithManage = DB::table('role_permission')
                ->where('permission_id', $managePermission->id)
                ->pluck('role_id');

            $newPermIds = DB::table('permissions')
                ->whereIn('slug', [
                    Permission::API_CLIENTS_MAPPINGS_VIEW,
                    Permission::API_CLIENTS_MAPPINGS_CREATE,
                    Permission::API_CLIENTS_MAPPINGS_EDIT,
                    Permission::API_CLIENTS_MAPPINGS_DELETE,
                ])
                ->pluck('id');

            foreach ($rolesWithManage as $roleId) {
                foreach ($newPermIds as $permId) {
                    if (!DB::table('role_permission')->where(['role_id' => $roleId, 'permission_id' => $permId])->exists()) {
                        DB::table('role_permission')->insert([
                            'role_id' => $roleId,
                            'permission_id' => $permId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $slugs = [
            Permission::API_CLIENTS_MAPPINGS_VIEW,
            Permission::API_CLIENTS_MAPPINGS_CREATE,
            Permission::API_CLIENTS_MAPPINGS_EDIT,
            Permission::API_CLIENTS_MAPPINGS_DELETE,
        ];

        $permIds = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');

        DB::table('role_permission')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('slug', $slugs)->delete();
    }
}
