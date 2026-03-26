<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddGranularApiClientPermissions extends Migration
{
    /**
     * Run the migrations.
     * Adds granular API client permissions while preserving backward compatibility.
     */
    public function up()
    {
        $now = now();

        // Add new granular permissions (only if they don't already exist)
        $newPermissions = [
            [
                'name' => 'إنشاء عملاء API',
                'slug' => Permission::API_CLIENTS_CREATE,
                'description' => 'إنشاء عملاء API جدد',
                'group' => 'إدارة API',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'تعديل عملاء API',
                'slug' => Permission::API_CLIENTS_EDIT,
                'description' => 'تعديل إعدادات عملاء API',
                'group' => 'إدارة API',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'حذف عملاء API',
                'slug' => Permission::API_CLIENTS_DELETE,
                'description' => 'حذف عملاء API',
                'group' => 'إدارة API',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'إدارة بيانات اعتماد API',
                'slug' => Permission::API_CLIENTS_CREDENTIALS,
                'description' => 'إعادة إنشاء مفاتيح API وبيانات الاعتماد',
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
        $managePermission = DB::table('permissions')
            ->where('slug', Permission::API_CLIENTS_MANAGE)
            ->first();

        if ($managePermission) {
            $rolesWithManage = DB::table('role_permission')
                ->where('permission_id', $managePermission->id)
                ->pluck('role_id');

            $newPermIds = DB::table('permissions')
                ->whereIn('slug', [
                    Permission::API_CLIENTS_CREATE,
                    Permission::API_CLIENTS_EDIT,
                    Permission::API_CLIENTS_DELETE,
                    Permission::API_CLIENTS_CREDENTIALS,
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
            Permission::API_CLIENTS_CREATE,
            Permission::API_CLIENTS_EDIT,
            Permission::API_CLIENTS_DELETE,
            Permission::API_CLIENTS_CREDENTIALS,
        ];

        $permIds = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');

        DB::table('role_permission')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('slug', $slugs)->delete();
    }
}
