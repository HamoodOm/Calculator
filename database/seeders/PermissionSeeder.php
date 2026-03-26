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
                'name' => 'عرض المسارات',
                'slug' => Permission::TRACKS_VIEW,
                'description' => 'الوصول إلى صفحة إدارة المسارات',
                'group' => 'إدارة المسارات',
            ],
            [
                'name' => 'عرض المسارات العامة',
                'slug' => Permission::TRACKS_VIEW_GLOBAL,
                'description' => 'عرض المسارات العامة (غير مرتبطة بمؤسسة)',
                'group' => 'إدارة المسارات',
            ],
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

            // Activity logs permissions
            [
                'name' => 'عرض سجل النشاطات',
                'slug' => Permission::ACTIVITY_LOGS_VIEW,
                'description' => 'عرض سجل نشاطات المستخدمين في النظام',
                'group' => 'إدارة النظام',
            ],

            // API Client management permissions
            [
                'name' => 'عرض عملاء API',
                'slug' => Permission::API_CLIENTS_VIEW,
                'description' => 'عرض قائمة عملاء API والمنصات الخارجية',
                'group' => 'إدارة API',
            ],
            [
                'name' => 'إدارة عملاء API',
                'slug' => Permission::API_CLIENTS_MANAGE,
                'description' => 'صلاحية كاملة لإدارة عملاء API (تشمل جميع الصلاحيات الفرعية)',
                'group' => 'إدارة API',
            ],
            [
                'name' => 'إنشاء عملاء API',
                'slug' => Permission::API_CLIENTS_CREATE,
                'description' => 'إنشاء عملاء API جدد',
                'group' => 'إدارة API',
            ],
            [
                'name' => 'تعديل عملاء API',
                'slug' => Permission::API_CLIENTS_EDIT,
                'description' => 'تعديل إعدادات عملاء API',
                'group' => 'إدارة API',
            ],
            [
                'name' => 'حذف عملاء API',
                'slug' => Permission::API_CLIENTS_DELETE,
                'description' => 'حذف عملاء API',
                'group' => 'إدارة API',
            ],
            [
                'name' => 'إدارة بيانات اعتماد API',
                'slug' => Permission::API_CLIENTS_CREDENTIALS,
                'description' => 'إعادة إنشاء مفاتيح API وبيانات الاعتماد',
                'group' => 'إدارة API',
            ],
            [
                'name' => 'عرض ربط الدورات',
                'slug' => Permission::API_CLIENTS_MAPPINGS_VIEW,
                'description' => 'عرض ربط الدورات لعملاء API',
                'group' => 'إدارة API',
            ],
            [
                'name' => 'إنشاء ربط الدورات',
                'slug' => Permission::API_CLIENTS_MAPPINGS_CREATE,
                'description' => 'إنشاء ربط دورات جديدة لعملاء API',
                'group' => 'إدارة API',
            ],
            [
                'name' => 'تعديل ربط الدورات',
                'slug' => Permission::API_CLIENTS_MAPPINGS_EDIT,
                'description' => 'تعديل ربط الدورات لعملاء API',
                'group' => 'إدارة API',
            ],
            [
                'name' => 'حذف ربط الدورات',
                'slug' => Permission::API_CLIENTS_MAPPINGS_DELETE,
                'description' => 'حذف ربط الدورات لعملاء API',
                'group' => 'إدارة API',
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
