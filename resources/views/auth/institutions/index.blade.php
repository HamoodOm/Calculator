@extends('auth.layouts.app')

@section('title', 'إدارة المؤسسات')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">إدارة المؤسسات</h1>
        <p class="text-gray-600 mt-1">إدارة المؤسسات والأقسام</p>
    </div>
    @if(auth()->user()->hasPermission(\App\Models\Permission::INSTITUTIONS_MANAGE))
    <a href="{{ route('institutions.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
        إضافة مؤسسة جديدة
    </a>
    @endif
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

<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المؤسسة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اللون</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المستخدمين</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المسارات</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($institutions as $institution)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        @php
                            $hasHexColor = $institution->hasHexColor();
                            $avatarStyle = $hasHexColor ? "background-color: {$institution->custom_hex_color};" : '';
                        @endphp
                        <div class="w-10 h-10 {{ !$hasHexColor ? $institution->header_color : '' }} rounded-full flex items-center justify-center" @if($hasHexColor) style="{{ $avatarStyle }}" @endif>
                            <span class="text-white font-medium">{{ mb_substr($institution->name, 0, 1) }}</span>
                        </div>
                        <div class="mr-3">
                            <a href="{{ route('institutions.edit', $institution) }}" class="font-medium text-gray-900 hover:text-indigo-600">{{ $institution->name }}</a>
                            @if($institution->description)
                            <p class="text-sm text-gray-500">{{ $institution->description }}</p>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="w-8 h-8 {{ !$hasHexColor ? $institution->header_color : '' }} rounded" @if($hasHexColor) style="{{ $avatarStyle }}" @endif></div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    @if($institution->users_count > 0)
                    <a href="{{ route('users.index', ['institution' => $institution->id]) }}" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                        {{ $institution->users_count }} مستخدم
                    </a>
                    @else
                    <span class="text-gray-400">0</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    @if($institution->tracks_count > 0)
                    <a href="{{ route('institutions.edit', $institution) }}#tracks" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                        {{ $institution->tracks_count }} مسار
                    </a>
                    @else
                    <span class="text-gray-400">0</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if(auth()->user()->hasPermission(\App\Models\Permission::INSTITUTIONS_MANAGE))
                    <form action="{{ route('institutions.toggle', $institution) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="px-2 py-1 text-xs {{ $institution->is_active ? 'bg-green-100 text-green-800 hover:bg-red-100 hover:text-red-800' : 'bg-red-100 text-red-800 hover:bg-green-100 hover:text-green-800' }} rounded transition" title="{{ $institution->is_active ? 'تعطيل المؤسسة' : 'تفعيل المؤسسة' }}">
                            {{ $institution->is_active ? 'نشطة' : 'معطلة' }}
                        </button>
                    </form>
                    @else
                    <span class="px-2 py-1 text-xs {{ $institution->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} rounded">
                        {{ $institution->is_active ? 'نشطة' : 'معطلة' }}
                    </span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div class="flex items-center gap-2">
                        @if(auth()->user()->hasPermission(\App\Models\Permission::INSTITUTIONS_MANAGE))
                        <a href="{{ route('institutions.edit', $institution) }}" class="text-indigo-600 hover:text-indigo-900">تعديل</a>

                        @if($institution->users_count == 0 && $institution->tracks_count == 0)
                        <form method="POST" action="{{ route('institutions.destroy', $institution) }}" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه المؤسسة؟')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">حذف</button>
                        </form>
                        @endif
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                    لا توجد مؤسسات
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
