@extends('auth.layouts.app')

@section('title', 'إدارة المسارات')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">إدارة المسارات</h1>
        <p class="text-gray-600 mt-1">إدارة مسارات الشهادات للمعلمين والطلاب</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('tracks.export') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
            تصدير CSV
        </a>
        @if(auth()->user()->hasPermission(\App\Models\Permission::TRACKS_CREATE))
        <a href="{{ route('tracks.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            إضافة مسار
        </a>
        @endif
    </div>
</div>

@if (session('status'))
    <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded mb-6">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded mb-6">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Statistics Cards -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</div>
        <div class="text-sm text-gray-500">إجمالي المسارات</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-blue-600">{{ $stats['teacher'] }}</div>
        <div class="text-sm text-gray-500">مسارات المعلمين</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-purple-600">{{ $stats['student'] }}</div>
        <div class="text-sm text-gray-500">مسارات الطلاب</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-green-600">{{ $stats['active'] }}</div>
        <div class="text-sm text-gray-500">مسارات مفعلة</div>
    </div>
    <div class="bg-white rounded-lg shadow p-4">
        <div class="text-2xl font-bold text-red-600">{{ $stats['inactive'] }}</div>
        <div class="text-sm text-gray-500">مسارات معطلة</div>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('tracks.index') }}" class="flex flex-wrap gap-4 items-end">
        <!-- Search -->
        <div class="flex-1 min-w-[200px]">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">بحث</label>
            <input type="text" name="search" id="search" value="{{ $currentFilters['search'] }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="اسم المسار أو المفتاح...">
        </div>

        <!-- Type Filter -->
        <div class="w-40">
            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">النوع</label>
            <select name="type" id="type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">الكل</option>
                <option value="teacher" {{ $currentFilters['type'] === 'teacher' ? 'selected' : '' }}>معلمين</option>
                <option value="student" {{ $currentFilters['type'] === 'student' ? 'selected' : '' }}>طلاب</option>
            </select>
        </div>

        <!-- Status Filter -->
        <div class="w-40">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
            <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">الكل</option>
                <option value="active" {{ $currentFilters['status'] === 'active' ? 'selected' : '' }}>مفعل</option>
                <option value="inactive" {{ $currentFilters['status'] === 'inactive' ? 'selected' : '' }}>معطل</option>
            </select>
        </div>

        @if(auth()->user()->isSuperUser() && $institutions->isNotEmpty())
        <!-- Institution Filter -->
        <div class="w-48">
            <label for="institution" class="block text-sm font-medium text-gray-700 mb-1">المؤسسة</label>
            <select name="institution" id="institution" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">الكل</option>
                <option value="global" {{ $currentFilters['institution'] === 'global' ? 'selected' : '' }}>عام (بدون مؤسسة)</option>
                @foreach($institutions as $institution)
                    <option value="{{ $institution->id }}" {{ $currentFilters['institution'] == $institution->id ? 'selected' : '' }}>
                        {{ $institution->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- Buttons -->
        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                تصفية
            </button>
            <a href="{{ route('tracks.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition">
                إعادة تعيين
            </a>
        </div>
    </form>
</div>

@php
    $currentSort = $currentFilters['sort'] ?? 'created_at';
    $currentDir = $currentFilters['dir'] ?? 'desc';

    function trackSortLink($column, $label, $currentSort, $currentDir, $currentFilters) {
        $newDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
        $params = array_merge($currentFilters, ['sort' => $column, 'dir' => $newDir]);
        $isActive = $currentSort === $column;
        $icon = '';
        if ($isActive) {
            $icon = $currentDir === 'asc' ? '↑' : '↓';
        }
        return [
            'url' => route('tracks.index', $params),
            'icon' => $icon,
            'isActive' => $isActive,
        ];
    }
@endphp

<!-- Tracks Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                @php $sort = trackSortLink('name_ar', 'الاسم', $currentSort, $currentDir, $currentFilters); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        الاسم {{ $sort['icon'] }}
                    </a>
                </th>

                @php $sort = trackSortLink('key', 'المفتاح', $currentSort, $currentDir, $currentFilters); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        المفتاح {{ $sort['icon'] }}
                    </a>
                </th>

                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    النوع
                </th>

                @if(auth()->user()->isSuperUser())
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    المؤسسة
                </th>
                @endif

                @php $sort = trackSortLink('active', 'الحالة', $currentSort, $currentDir, $currentFilters); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        الحالة {{ $sort['icon'] }}
                    </a>
                </th>

                @php $sort = trackSortLink('created_at', 'تاريخ الإنشاء', $currentSort, $currentDir, $currentFilters); @endphp
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <a href="{{ $sort['url'] }}" class="hover:text-indigo-600 {{ $sort['isActive'] ? 'text-indigo-600 font-bold' : '' }}">
                        تاريخ الإنشاء {{ $sort['icon'] }}
                    </a>
                </th>

                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    الإجراءات
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($tracks as $track)
                @php
                    $isStudent = str_starts_with($track->key, 's_');
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">{{ $track->name_ar }}</div>
                        <div class="text-sm text-gray-500">{{ $track->name_en }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $track->key }}</code>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($isStudent)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                طلاب
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                معلمين
                            </span>
                        @endif
                    </td>
                    @if(auth()->user()->isSuperUser())
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        @if($track->institution)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                {{ $track->institution->name }}
                            </span>
                        @else
                            <span class="text-gray-400">عام</span>
                        @endif
                    </td>
                    @endif
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($track->active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                مفعل
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                معطل
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $track->created_at->format('Y-m-d') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center gap-2">
                            <!-- Toggle -->
                            @if(auth()->user()->hasPermission(\App\Models\Permission::TRACKS_EDIT))
                            <form action="{{ route('tracks.toggle', $track) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-{{ $track->active ? 'yellow' : 'green' }}-600 hover:text-{{ $track->active ? 'yellow' : 'green' }}-900" title="{{ $track->active ? 'تعطيل' : 'تفعيل' }}">
                                    @if($track->active)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </button>
                            </form>
                            @endif

                            <!-- Edit -->
                            @if(auth()->user()->hasPermission(\App\Models\Permission::TRACKS_EDIT))
                            <a href="{{ route('tracks.edit', $track) }}" class="text-indigo-600 hover:text-indigo-900" title="تعديل">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </a>
                            @endif

                            <!-- Delete -->
                            @if(auth()->user()->hasPermission(\App\Models\Permission::TRACKS_DELETE))
                            <form action="{{ route('tracks.destroy', $track) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المسار؟ سيتم حذف جميع الإعدادات المرتبطة به.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="حذف">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ auth()->user()->isSuperUser() ? 7 : 6 }}" class="px-6 py-12 text-center text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-lg font-medium">لا توجد مسارات</p>
                        <p class="mt-1">قم بإضافة مسار جديد للبدء</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($tracks->hasPages())
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
        {{ $tracks->links() }}
    </div>
    @endif
</div>
@endsection
