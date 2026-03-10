<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create API Clients View permission
        $viewPermission = Permission::firstOrCreate(
            ['slug' => Permission::API_CLIENTS_VIEW],
            [
                'name' => 'عرض عملاء API',
                'description' => 'عرض قائمة عملاء API والمنصات الخارجية',
                'group' => 'إدارة API',
            ]
        );

        // Create API Clients Manage permission
        $managePermission = Permission::firstOrCreate(
            ['slug' => Permission::API_CLIENTS_MANAGE],
            [
                'name' => 'إدارة عملاء API',
                'description' => 'إنشاء وتعديل وحذف عملاء API وربط الدورات',
                'group' => 'إدارة API',
            ]
        );

        // Assign to super_admin role if it exists
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching([
                $viewPermission->id,
                $managePermission->id,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('slug', [
            Permission::API_CLIENTS_VIEW,
            Permission::API_CLIENTS_MANAGE,
        ])->delete();
    }
};
