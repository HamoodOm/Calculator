@extends('auth.layouts.app')

@section('title', 'إدارة الأدوار')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">إدارة الأدوار</h1>
        <p class="text-gray-600 mt-1">إدارة الأدوار والصلاحيات للمستخدمين</p>
    </div>
    @if(auth()->user()->hasPermission(\App\Models\Permission::ROLES_CREATE))
    <a href="{{ route('roles.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
        إضافة دور جديد
    </a>
    @endif
</div>

@if (session('status'))
    <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded mb-6">
        {{ session('status') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الدور</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المعرف</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الوصف</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الصلاحيات</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستخدمين</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($roles as $role)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <span class="font-medium text-gray-900">{{ $role->name }}</span>
                        @if($role->is_system)
                        <span class="mr-2 px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">نظامي</span>
                        @endif
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $role->slug }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $role->description ?? '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($role->isSuperAdmin())
                    <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">كل الصلاحيات</span>
                    @else
                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">{{ $role->permissions->count() }} صلاحية</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $role->users_count }} مستخدم
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="flex items-center gap-2">
                        @if(auth()->user()->hasPermission(\App\Models\Permission::ROLES_EDIT))
                        <a href="{{ route('roles.edit', $role) }}" class="text-indigo-600 hover:text-indigo-900">تعديل</a>
                        @endif

                        @if(auth()->user()->hasPermission(\App\Models\Permission::ROLES_DELETE) && !$role->is_system)
                        <form method="POST" action="{{ route('roles.destroy', $role) }}" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                    لا توجد أدوار
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
