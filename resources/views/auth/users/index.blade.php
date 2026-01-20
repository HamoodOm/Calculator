@extends('auth.layouts.app')

@section('title', 'إدارة المستخدمين')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">إدارة المستخدمين</h1>
        <p class="text-gray-600 mt-1">إدارة حسابات المستخدمين وصلاحياتهم</p>
    </div>
    @if(auth()->user()->hasPermission(\App\Models\Permission::USERS_CREATE))
    <a href="{{ route('users.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
        إضافة مستخدم جديد
    </a>
    @endif
</div>

@if (session('status'))
    <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded mb-6">
        {{ session('status') }}
    </div>
@endif

{{-- Filters Section --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        {{-- Search --}}
        <div>
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">بحث</label>
            <input type="text" name="search" id="search" value="{{ $currentFilters['search'] ?? '' }}" placeholder="الاسم أو البريد..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        {{-- Role Filter --}}
        <div>
            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">الدور</label>
            <select name="role" id="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">الكل</option>
                <option value="none" {{ ($currentFilters['role'] ?? '') === 'none' ? 'selected' : '' }}>بدون دور</option>
                @foreach($roles as $role)
                <option value="{{ $role->slug }}" {{ ($currentFilters['role'] ?? '') === $role->slug ? 'selected' : '' }}>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Institution Filter (Super users only) --}}
        @if(auth()->user()->isSuperUser() && $institutions->isNotEmpty())
        <div>
            <label for="institution" class="block text-sm font-medium text-gray-700 mb-1">المؤسسة</label>
            <select name="institution" id="institution" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">الكل</option>
                <option value="none" {{ ($currentFilters['institution'] ?? '') === 'none' ? 'selected' : '' }}>بدون مؤسسة</option>
                @foreach($institutions as $institution)
                <option value="{{ $institution->id }}" {{ ($currentFilters['institution'] ?? '') == $institution->id ? 'selected' : '' }}>{{ $institution->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Status Filter --}}
        <div>
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
            <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">الكل</option>
                <option value="active" {{ ($currentFilters['status'] ?? '') === 'active' ? 'selected' : '' }}>نشط</option>
                <option value="inactive" {{ ($currentFilters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>معطل</option>
            </select>
        </div>

        {{-- Hidden inputs for sort (handled by clickable table headers) --}}
        <input type="hidden" name="sort" value="{{ $currentFilters['sort'] ?? 'created_at' }}">
        <input type="hidden" name="dir" value="{{ $currentFilters['dir'] ?? 'desc' }}">

        <div class="flex items-end gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm transition">
                تطبيق
            </button>
            <a href="{{ route('users.index') }}" class="px-4 py-2 text-gray-600 hover:text-gray-800 text-sm">
                إعادة تعيين
            </a>
        </div>
    </form>
</div>

@php
    $currentSort = $currentFilters['sort'] ?? 'created_at';
    $currentDir = $currentFilters['dir'] ?? 'desc';

    function sortLink($column, $label, $currentSort, $currentDir, $currentFilters) {
        $newDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
        $params = array_merge($currentFilters, ['sort' => $column, 'dir' => $newDir]);
        $isActive = $currentSort === $column;
        $icon = '';
        if ($isActive) {
            $icon = $currentDir === 'asc' ? '↑' : '↓';
        }
        return [
            'url' => route('users.index', $params),
            'icon' => $icon,
            'isActive' => $isActive,
        ];
    }
@endphp

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                @php $sort = sortLink('name', 'المستخدم', $currentSort, $currentDir, $currentFilters); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        المستخدم {{ $sort['icon'] }}
                    </a>
                </th>

                @php $sort = sortLink('email', 'البريد الإلكتروني', $currentSort, $currentDir, $currentFilters); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        البريد الإلكتروني {{ $sort['icon'] }}
                    </a>
                </th>

                @php $sort = sortLink('role', 'الدور', $currentSort, $currentDir, $currentFilters); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        الدور {{ $sort['icon'] }}
                    </a>
                </th>

                @if(auth()->user()->isSuperUser())
                @php $sort = sortLink('institution', 'المؤسسة', $currentSort, $currentDir, $currentFilters); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        المؤسسة {{ $sort['icon'] }}
                    </a>
                </th>
                @endif

                @php $sort = sortLink('is_active', 'الحالة', $currentSort, $currentDir, $currentFilters); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        الحالة {{ $sort['icon'] }}
                    </a>
                </th>

                @php $sort = sortLink('created_at', 'تاريخ الإنشاء', $currentSort, $currentDir, $currentFilters); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        تاريخ الإنشاء {{ $sort['icon'] }}
                    </a>
                </th>

                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($users as $user)
            @php
                $canManage = auth()->user()->canManageUser($user);
                // Check for hex colors (user custom color or institution hex color)
                $userHasHexColor = $user->custom_color && str_starts_with($user->custom_color, '#');
                $instHasHexColor = $user->institution && $user->institution->hasHexColor();
                $avatarHexStyle = '';
                $avatarClass = 'bg-indigo-100';
                $textClass = 'text-indigo-600';

                if ($userHasHexColor) {
                    $avatarHexStyle = "background-color: {$user->custom_color};";
                    $avatarClass = '';
                    $textClass = 'text-white';
                } elseif ($instHasHexColor) {
                    $avatarHexStyle = "background-color: {$user->institution->custom_hex_color};";
                    $avatarClass = '';
                    $textClass = 'text-white';
                } elseif ($user->custom_color) {
                    $avatarClass = $user->custom_color;
                    $textClass = 'text-white';
                } elseif ($user->institution) {
                    $avatarClass = $user->institution->header_color;
                    $textClass = 'text-white';
                }
            @endphp
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="w-10 h-10 {{ $avatarClass }} rounded-full flex items-center justify-center" @if($avatarHexStyle) style="{{ $avatarHexStyle }}" @endif>
                            <span class="{{ $textClass }} font-medium">{{ mb_substr($user->name, 0, 1) }}</span>
                        </div>
                        <div class="mr-3">
                            <span class="font-medium text-gray-900">{{ $user->name }}</span>
                            @if($user->id === auth()->id())
                            <span class="mr-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded">أنت</span>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" dir="ltr">
                    {{ $user->email }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($user->role)
                    <span class="px-2 py-1 text-xs bg-indigo-100 text-indigo-800 rounded">{{ $user->role->name }}</span>
                    @else
                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">غير محدد</span>
                    @endif
                </td>
                @if(auth()->user()->isSuperUser())
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($user->institution)
                    @php
                        $instBadgeStyle = $instHasHexColor ? "background-color: {$user->institution->custom_hex_color};" : '';
                        $instBadgeClass = $instHasHexColor ? '' : $user->institution->header_color;
                    @endphp
                    <a href="{{ route('institutions.edit', $user->institution) }}" class="px-2 py-1 text-xs {{ $instBadgeClass }} text-white rounded hover:opacity-80 transition" @if($instBadgeStyle) style="{{ $instBadgeStyle }}" @endif>
                        {{ $user->institution->name }}
                    </a>
                    @else
                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded">-</span>
                    @endif
                </td>
                @endif
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($user->is_active)
                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded">نشط</span>
                    @else
                    <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded">معطل</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" dir="ltr">
                    <div>{{ $user->created_at->format('Y-m-d') }}</div>
                    <div class="text-xs text-gray-400">{{ $user->created_at->format('H:i') }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="flex items-center gap-2">
                        @if($canManage && auth()->user()->hasPermission(\App\Models\Permission::USERS_EDIT))
                        <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900">تعديل</a>
                        @endif

                        @if($canManage && auth()->user()->hasPermission(\App\Models\Permission::USERS_EDIT) && $user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="{{ $user->is_active ? 'text-yellow-600 hover:text-yellow-900' : 'text-green-600 hover:text-green-900' }}">
                                {{ $user->is_active ? 'تعطيل' : 'تفعيل' }}
                            </button>
                        </form>
                        @endif

                        @if($canManage && auth()->user()->hasPermission(\App\Models\Permission::USERS_DELETE) && $user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
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
                <td colspan="{{ auth()->user()->isSuperUser() ? 7 : 6 }}" class="px-6 py-4 text-center text-gray-500">
                    لا يوجد مستخدمين
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
<div class="mt-6">
    {{ $users->links() }}
</div>
@endif

{{-- Debug Info (Developer Only) --}}
@if(auth()->user()->isDeveloper())
<div class="mt-6 p-4 bg-gray-800 text-gray-200 rounded-lg text-sm font-mono">
    <h3 class="font-bold text-yellow-400 mb-2">Debug Info (Developer Only):</h3>
    <p>Total Users: {{ $users->total() }}</p>
    <p>Current Page: {{ $users->currentPage() }}</p>
    <p>Filters: {{ json_encode($currentFilters) }}</p>
</div>
@endif
@endsection
