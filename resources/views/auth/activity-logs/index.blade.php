@extends('auth.layouts.app')

@section('title', 'سجل النشاطات')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">سجل النشاطات</h1>
            <p class="text-gray-600 mt-1">متابعة جميع الإجراءات في النظام</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('activity-logs.export', request()->query()) }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                <svg class="w-4 h-4 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                تصدير CSV
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">بحث</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="بحث..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">الإجراء</label>
                <select name="action" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">الكل</option>
                    @foreach($actions as $value => $label)
                        <option value="{{ $value }}" {{ request('action') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المستخدم</label>
                <select name="user_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">الكل</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            @if($institutions->count() > 0)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المؤسسة</label>
                <select name="institution" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">الكل</option>
                    @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}" {{ request('institution') == $inst->id ? 'selected' : '' }}>{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">من تاريخ</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>

        <div class="mt-4 flex gap-2">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm">
                تصفية
            </button>
            <a href="{{ route('activity-logs.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition text-sm">
                إعادة تعيين
            </a>
        </div>
    </form>

    {{-- Logs Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'created_at', 'dir' => request('sort') === 'created_at' && request('dir') === 'desc' ? 'asc' : 'desc']) }}" class="hover:text-gray-700">
                                التاريخ
                                @if(request('sort') === 'created_at')
                                    <span class="mr-1">{{ request('dir') === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">المستخدم</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'action', 'dir' => request('sort') === 'action' && request('dir') === 'desc' ? 'asc' : 'desc']) }}" class="hover:text-gray-700">
                                الإجراء
                                @if(request('sort') === 'action')
                                    <span class="mr-1">{{ request('dir') === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الوصف</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">المؤسسة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">IP</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->created_at->format('Y-m-d') }}
                            <br>
                            <span class="text-xs text-gray-400">{{ $log->created_at->format('H:i:s') }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->user)
                                <a href="{{ route('users.edit', $log->user) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">
                                    {{ $log->user_name }}
                                </a>
                            @else
                                <span class="text-sm text-gray-500">{{ $log->user_name ?? 'غير معروف' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $log->action_color }}">
                                {{ $log->action_label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 max-w-md truncate" title="{{ $log->description }}">
                            {{ \Str::limit($log->description, 60) }}
                            @if($log->model_name)
                                <br>
                                <span class="text-xs text-gray-400">{{ $log->model_type_label }}: {{ $log->model_name }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->institution?->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-400">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            لا توجد سجلات نشاط
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
