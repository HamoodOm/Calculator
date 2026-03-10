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

@php
    $currentSort = $currentFilters['sort'] ?? 'level';
    $currentDir = $currentFilters['dir'] ?? 'asc';

    function roleSortLink($column, $label, $currentSort, $currentDir) {
        $newDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
        $params = ['sort' => $column, 'dir' => $newDir];
        $isActive = $currentSort === $column;
        $icon = '';
        if ($isActive) {
            $icon = $currentDir === 'asc' ? '↑' : '↓';
        }
        return [
            'url' => route('roles.index', $params),
            'icon' => $icon,
            'isActive' => $isActive,
        ];
    }
@endphp

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                @php $sort = roleSortLink('name', 'الدور', $currentSort, $currentDir); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        الدور {{ $sort['icon'] }}
                    </a>
                </th>

                @php $sort = roleSortLink('slug', 'المعرف', $currentSort, $currentDir); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        المعرف {{ $sort['icon'] }}
                    </a>
                </th>

                @php $sort = roleSortLink('level', 'المستوى', $currentSort, $currentDir); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        المستوى {{ $sort['icon'] }}
                    </a>
                </th>

                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الوصف</th>

                @php $sort = roleSortLink('permissions_count', 'الصلاحيات', $currentSort, $currentDir); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        الصلاحيات {{ $sort['icon'] }}
                    </a>
                </th>

                @php $sort = roleSortLink('users_count', 'المستخدمين', $currentSort, $currentDir); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        المستخدمين {{ $sort['icon'] }}
                    </a>
                </th>

                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($roles as $role)
            @php
                // Level color scheme based on new hierarchy:
                // 0: Super Admin (purple), 5: Developer (rose)
                // 20-29: Admins (indigo/blue), 30-39: Users (teal/gray)
                $level = $role->getLevel();
                if ($level <= 0) {
                    $levelColor = 'bg-purple-100 text-purple-800';
                } elseif ($level <= 10) {
                    $levelColor = 'bg-rose-100 text-rose-800';
                } elseif ($level <= 20) {
                    $levelColor = 'bg-indigo-100 text-indigo-800';
                } elseif ($level <= 29) {
                    $levelColor = 'bg-blue-100 text-blue-800';
                } elseif ($level <= 30) {
                    $levelColor = 'bg-teal-100 text-teal-800';
                } elseif ($level <= 39) {
                    $levelColor = 'bg-cyan-100 text-cyan-800';
                } else {
                    $levelColor = 'bg-gray-100 text-gray-600';
                }
            @endphp
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
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs {{ $levelColor }} rounded" title="المستوى {{ $role->getLevel() }} - كلما كان الرقم أقل كانت الصلاحيات أعلى">
                        {{ $role->getLevel() }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $role->description ?? '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($role->isSuperAdmin())
                    <span class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded">كل الصلاحيات</span>
                    @else
                    <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">{{ $role->permissions_count }} صلاحية</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    @if($role->users_count > 0)
                    <a href="{{ route('users.index', ['role' => $role->slug]) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                        {{ $role->users_count }} مستخدم
                    </a>
                    @else
                    <span class="text-gray-400">0</span>
                    @endif
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
                <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                    لا توجد أدوار
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Level Explanation --}}
<div class="mt-4 p-4 bg-blue-50 rounded-lg">
    <h3 class="text-sm font-medium text-blue-800 mb-2">ملاحظة حول مستويات الأدوار:</h3>
    <p class="text-sm text-blue-700">
        المستوى يحدد التسلسل الهرمي للصلاحيات. كلما كان الرقم أقل، كانت الصلاحيات أعلى.
        المستخدمون يمكنهم إدارة المستخدمين الذين لديهم مستوى أعلى (رقم أكبر) فقط.
    </p>
</div>
@endsection
