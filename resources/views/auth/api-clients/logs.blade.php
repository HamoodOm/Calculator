@extends('auth.layouts.app')

@section('title', 'سجل طلبات API')

@section('content')
<div class="mb-6">
    <a href="{{ route('api-clients.show', $apiClient) }}" class="text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        العودة للتفاصيل
    </a>
</div>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">سجل طلبات API</h1>
        <p class="text-gray-600 mt-1">{{ $apiClient->name }}</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('api-clients.logs', $apiClient) }}" class="flex flex-wrap gap-4 items-end">
        <div class="w-40">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
            <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">الكل</option>
                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>ناجح</option>
                <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>فشل</option>
            </select>
        </div>

        <div class="w-48">
            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">التاريخ</label>
            <input type="date" name="date" id="date" value="{{ request('date') }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                تصفية
            </button>
            <a href="{{ route('api-clients.logs', $apiClient) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition">
                إعادة تعيين
            </a>
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الوقت</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الطريقة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نقطة النهاية</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">زمن الاستجابة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الشهادة</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($logs as $log)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <div>{{ $log->created_at->format('Y-m-d') }}</div>
                        <div class="text-xs">{{ $log->created_at->format('H:i:s') }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            @if($log->method === 'GET') bg-blue-100 text-blue-800
                            @elseif($log->method === 'POST') bg-green-100 text-green-800
                            @elseif($log->method === 'PUT') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ $log->method }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <code class="bg-gray-100 px-2 py-0.5 rounded">{{ $log->endpoint }}</code>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($log->status_code >= 200 && $log->status_code < 300)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                {{ $log->status_code }}
                            </span>
                        @elseif($log->status_code >= 400 && $log->status_code < 500)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                {{ $log->status_code }}
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                {{ $log->status_code }}
                            </span>
                        @endif
                        @if($log->error_message)
                            <p class="text-xs text-red-500 mt-1" title="{{ $log->error_message }}">
                                {{ \Str::limit($log->error_message, 30) }}
                            </p>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $log->ip_address }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $log->response_time_ms ?? '-' }} ms
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($log->certificate_id)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                #{{ $log->certificate_id }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="mt-2 text-lg font-medium">لا توجد طلبات</p>
                        <p class="mt-1">لم يتم تسجيل أي طلبات API بعد</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($logs->hasPages())
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
