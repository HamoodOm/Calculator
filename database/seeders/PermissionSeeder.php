<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            // Teacher permissions
            [
                'name' => 'عرض شهادات المعلمين (بسيط)',
                'slug' => Permission::TEACHER_SIMPLE_VIEW,
                'description' => 'الوصول إلى صفحة شهادات المعلمين البسيطة',
                'group' => 'المعلمين',
            ],
            [
                'name' => 'عرض إدارة شهادات المعلمين',
                'slug' => Permission::TEACHER_ADMIN_VIEW,
                'description' => 'الوصول إلى صفحة إدارة شهادات المعلمين المتقدمة',
                'group' => 'المعلمين',
            ],
            [
                'name' => 'تعديل إعدادات شهادات المعلمين',
                'slug' => Permission::TEACHER_ADMIN_EDIT,
                'description' => 'تعديل إعدادات شهادات المعلمين',
                'group' => 'المعلمين',
            ],

            // Student permissions
            [
                'name' => 'عرض شهادات الطلاب (بسيط)',
                'slug' => Permission::STUDENT_SIMPLE_VIEW,
                'description' => 'الوصول إلى صفحة شهادات الطلاب البسيطة',
                'group' => 'الطلاب',
            ],
            [
                'name' => 'عرض إدارة شهادات الطلاب',
                'slug' => Permission::STUDENT_ADMIN_VIEW,
                'description' => 'الوصول إلى صفحة إدارة شهادات الطلاب المتقدمة',
                'group' => 'الطلاب',
            ],
            [
                'name' => 'تعديل إعدادات شهادات الطلاب',
                'slug' => Permission::STUDENT_ADMIN_EDIT,
                'description' => 'تعديل إعدادات شهادات الطلاب',
                'group' => 'الطلاب',
            ],

            // User management permissions
            [
                'name' => 'عرض المستخدمين',
                'slug' => Permission::USERS_VIEW,
                'description' => 'عرض قائمة المستخدمين',
                'group' => 'إدارة المستخدمين',
            ],
            [
                'name' => 'إنشاء المستخدمين',
                'slug' => Permission::USERS_CREATE,
                'description' => 'إنشاء مستخدمين جدد',
                'group' => 'إدارة المستخدمين',
            ],
            [
                'name' => 'تعديل المستخدمين',
                'slug' => Permission::USERS_EDIT,
                'description' => 'تعديل بيانات المستخدمين',
                'group' => 'إدارة المستخدمين',
            ],
            [
                'name' => 'حذف المستخدمين',
                'slug' => Permission::USERS_DELETE,
                'description' => 'حذف المستخدمين',
                'group' => 'إدارة المستخدمين',
            ],

            // Role management permissions
            [
                'name' => 'عرض الأدوار',
                'slug' => Permission::ROLES_VIEW,
                'description' => 'عرض قائمة الأدوار والصلاحيات',
                'group' => 'إدارة الأدوار',
            ],
            [
                'name' => 'إنشاء الأدوار',
                'slug' => Permission::ROLES_CREATE,
                'description' => 'إنشاء أدوار جديدة',
                'group' => 'إدارة الأدوار',
            ],
            [
                'name' => 'تعديل الأدوار',
                'slug' => Permission::ROLES_EDIT,
                'description' => 'تعديل الأدوار وصلاحياتها',
                'group' => 'إدارة الأدوار',
            ],
            [
                'name' => 'حذف الأدوار',
                'slug' => Permission::ROLES_DELETE,
                'description' => 'حذف الأدوار',
                'group' => 'إدارة الأدوار',
            ],

            // Track management permissions
            [
                'name' => 'إنشاء المسارات',
                'slug' => Permission::TRACKS_CREATE,
                'description' => 'إنشاء مسارات جديدة للشهادات',
                'group' => 'إدارة المسارات',
            ],
            [
                'name' => 'تعديل المسارات',
                'slug' => Permission::TRACKS_EDIT,
                'description' => 'تعديل المسارات وإعداداتها',
                'group' => 'إدارة المسارات',
            ],
            [
                'name' => 'حذف المسارات',
                'slug' => Permission::TRACKS_DELETE,
                'description' => 'حذف المسارات',
                'group' => 'إدارة المسارات',
            ],

            // Institution management permissions
            [
                'name' => 'عرض المؤسسات',
                'slug' => Permission::INSTITUTIONS_VIEW,
                'description' => 'عرض قائمة المؤسسات والأقسام',
                'group' => 'إدارة المؤسسات',
            ],
            [
                'name' => 'إدارة المؤسسات',
                'slug' => Permission::INSTITUTIONS_MANAGE,
                'description' => 'إنشاء وتعديل وحذف المؤسسات',
                'group' => 'إدارة المؤسسات',
            ],

            // Debug permissions
            [
                'name' => 'عرض معلومات التصحيح',
                'slug' => Permission::DEBUG_VIEW,
                'description' => 'عرض معلومات التصحيح والتطوير',
                'group' => 'التطوير',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
