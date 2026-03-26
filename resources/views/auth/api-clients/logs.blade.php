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
    <div class="text-sm text-gray-500">
        إجمالي النتائج: <span class="font-semibold">{{ $logs->total() }}</span>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('api-clients.logs', $apiClient) }}" class="flex flex-wrap gap-4 items-end">
        <div class="w-40">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">الحالة</label>
            <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <option value="">الكل</option>
                <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>ناجح (2xx)</option>
                <option value="error" {{ request('status') === 'error' ? 'selected' : '' }}>فشل (4xx/5xx)</option>
            </select>
        </div>

        <div class="w-48">
            <label for="date" class="block text-sm font-medium text-gray-700 mb-1">التاريخ</label>
            <input type="date" name="date" id="date" value="{{ request('date') }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
        </div>

        <div class="flex gap-2">
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm">
                تصفية
            </button>
            <a href="{{ route('api-clients.logs', $apiClient) }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition text-sm">
                إعادة تعيين
            </a>
        </div>
    </form>
</div>

<!-- Logs Table -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الوقت</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الطريقة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">نقطة النهاية</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">IP</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">زمن الاستجابة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الشهادة</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تفاصيل</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            <div>{{ $log->created_at->format('Y-m-d') }}</div>
                            <div class="text-xs">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                @if($log->method === 'GET') bg-blue-100 text-blue-800
                                @elseif($log->method === 'POST') bg-green-100 text-green-800
                                @elseif($log->method === 'PUT') bg-yellow-100 text-yellow-800
                                @elseif($log->method === 'DELETE') bg-red-100 text-red-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $log->method }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm max-w-xs">
                            <code class="bg-gray-100 px-2 py-0.5 rounded text-xs break-all">{{ $log->endpoint }}</code>
                        </td>
                        <td class="px-4 py-3">
                            @php $code = $log->response_code; @endphp
                            @if($code >= 200 && $code < 300)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    {{ $code }} ناجح
                                </span>
                            @elseif($code >= 400 && $code < 500)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ $code }} خطأ عميل
                                </span>
                            @elseif($code >= 500)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                    {{ $code }} خطأ خادم
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $code ?? '-' }}
                                </span>
                            @endif
                            @if($log->error_message)
                                <p class="text-xs text-red-500 mt-1 max-w-xs" title="{{ $log->error_message }}">
                                    {{ \Str::limit($log->error_message, 50) }}
                                </p>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->ip_address ?? '-' }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            @if($log->execution_time !== null)
                                <span class="{{ $log->execution_time > 3000 ? 'text-red-600 font-semibold' : ($log->execution_time > 1000 ? 'text-yellow-600 font-medium' : 'text-gray-600') }}">
                                    {{ $log->execution_time }} ms
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            @if($log->certificate_id)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    #{{ $log->certificate_id }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                            @if($log->request_data || $log->response_data || $log->error_message)
                                <button onclick="toggleLogDetails('log-{{ $log->id }}', this)"
                                    class="text-indigo-600 hover:text-indigo-800 text-xs underline focus:outline-none">
                                    عرض
                                </button>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                    </tr>
                    @if($log->request_data || $log->response_data || $log->error_message)
                    <tr id="log-{{ $log->id }}" class="hidden bg-gray-50">
                        <td colspan="8" class="px-4 py-4 border-b border-gray-100">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if($log->request_data)
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide">بيانات الطلب (Request)</h4>
                                    <pre class="bg-white border border-gray-200 rounded p-3 overflow-auto max-h-48 text-xs text-gray-700 text-left" dir="ltr">{{ json_encode($log->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                                @endif
                                @if($log->response_data)
                                <div>
                                    <h4 class="text-xs font-semibold text-gray-600 mb-1 uppercase tracking-wide">بيانات الاستجابة (Response)</h4>
                                    <pre class="bg-white border border-gray-200 rounded p-3 overflow-auto max-h-48 text-xs text-gray-700 text-left" dir="ltr">{{ json_encode($log->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                                @endif
                                @if($log->error_message && !$log->response_data)
                                <div class="md:col-span-2">
                                    <h4 class="text-xs font-semibold text-red-600 mb-1 uppercase tracking-wide">رسالة الخطأ</h4>
                                    <p class="bg-red-50 border border-red-200 rounded p-3 text-xs text-red-700">{{ $log->error_message }}</p>
                                </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
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
    </div>

    @if($logs->hasPages())
    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
        {{ $logs->links() }}
    </div>
    @endif
</div>

<script>
function toggleLogDetails(id, btn) {
    const row = document.getElementById(id);
    if (row) {
        const hidden = row.classList.toggle('hidden');
        btn.textContent = hidden ? 'عرض' : 'إخفاء';
    }
}
</script>
@endsection
