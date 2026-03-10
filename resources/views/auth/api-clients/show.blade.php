@extends('auth.layouts.app')

@section('title', 'تفاصيل عميل API')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <a href="{{ route('api-clients.index') }}" class="text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        العودة للقائمة
    </a>

    @if(auth()->user()->hasPermission(\App\Models\Permission::API_CLIENTS_MANAGE))
    <div class="flex gap-2">
        <a href="{{ route('api-clients.mappings', $apiClient) }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z" />
            </svg>
            ربط الدورات
        </a>
        <a href="{{ route('api-clients.edit', $apiClient) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
            </svg>
            تعديل
        </a>
    </div>
    @endif
</div>

@if (session('status'))
    <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded mb-6">
        {{ session('status') }}
    </div>
@endif

@if (session('new_credentials'))
    <div class="bg-yellow-50 border border-yellow-400 p-6 rounded-lg mb-6">
        <div class="flex items-start">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <div class="mr-4 flex-1">
                <h3 class="text-lg font-bold text-yellow-800 mb-2">بيانات الاعتماد الجديدة - احفظها الآن!</h3>
                <p class="text-yellow-700 text-sm mb-4">لن يتم عرض هذه البيانات مرة أخرى. يرجى حفظها في مكان آمن.</p>

                <div class="space-y-3">
                    <div class="bg-white p-3 rounded border border-yellow-300">
                        <label class="text-xs text-gray-500 block mb-1">API Key</label>
                        <code class="text-sm break-all select-all">{{ session('new_credentials.api_key') }}</code>
                    </div>
                    <div class="bg-white p-3 rounded border border-yellow-300">
                        <label class="text-xs text-gray-500 block mb-1">API Secret</label>
                        <code class="text-sm break-all select-all">{{ session('new_credentials.api_secret') }}</code>
                    </div>
                    @if(session('new_credentials.webhook_secret'))
                    <div class="bg-white p-3 rounded border border-yellow-300">
                        <label class="text-xs text-gray-500 block mb-1">Webhook Secret</label>
                        <code class="text-sm break-all select-all">{{ session('new_credentials.webhook_secret') }}</code>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Client Information -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Main Info -->
    <div class="lg:col-span-2 bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-bold text-gray-800">معلومات العميل</h2>
            @if($apiClient->active)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    نشط
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                    معطل
                </span>
            @endif
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-xs text-gray-500">الاسم</label>
                    <p class="font-medium">{{ $apiClient->name }}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-500">المعرف (Slug)</label>
                    <p><code class="bg-gray-100 px-2 py-0.5 rounded">{{ $apiClient->slug }}</code></p>
                </div>
                <div>
                    <label class="text-xs text-gray-500">المؤسسة</label>
                    <p class="font-medium">{{ $apiClient->institution->name ?? '-' }}</p>
                </div>
                <div>
                    <label class="text-xs text-gray-500">تاريخ الإنشاء</label>
                    <p>{{ $apiClient->created_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>

            @if($apiClient->description)
            <div>
                <label class="text-xs text-gray-500">الوصف</label>
                <p class="text-gray-700">{{ $apiClient->description }}</p>
            </div>
            @endif

            <div>
                <label class="text-xs text-gray-500">API Key</label>
                <p><code class="bg-gray-100 px-2 py-0.5 rounded">{{ $apiClient->api_key }}</code></p>
            </div>

            @if($apiClient->webhook_url)
            <div>
                <label class="text-xs text-gray-500">رابط Webhook</label>
                <p class="text-blue-600">{{ $apiClient->webhook_url }}</p>
            </div>
            @endif

            @if($apiClient->allowed_ips)
            <div>
                <label class="text-xs text-gray-500">عناوين IP المسموح بها</label>
                <p>{{ is_array($apiClient->allowed_ips) ? implode(', ', $apiClient->allowed_ips) : $apiClient->allowed_ips }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Statistics -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-800">الإحصائيات</h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="text-center p-4 bg-blue-50 rounded-lg">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['today_requests'] }}</div>
                <div class="text-sm text-gray-600">طلبات اليوم</div>
            </div>
            <div class="text-center p-4 bg-green-50 rounded-lg">
                <div class="text-3xl font-bold text-green-600">{{ $stats['today_certificates'] }}</div>
                <div class="text-sm text-gray-600">شهادات اليوم</div>
            </div>
            <div class="text-center p-4 bg-purple-50 rounded-lg">
                <div class="text-3xl font-bold text-purple-600">{{ number_format($apiClient->total_certificates) }}</div>
                <div class="text-sm text-gray-600">إجمالي الشهادات</div>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <div class="text-3xl font-bold text-gray-600">{{ $stats['success_rate'] }}%</div>
                <div class="text-sm text-gray-600">نسبة النجاح</div>
            </div>
        </div>
    </div>
</div>

<!-- Scopes & Limits -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <!-- Scopes -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-800">الصلاحيات</h2>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-2">
                @foreach($apiClient->scopes ?? [] as $scope)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                        {{ $scope }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Rate Limits -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-800">حدود الاستخدام</h2>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-gray-600">حد الدقيقة</span>
                <span class="font-bold">{{ $apiClient->rate_limit }} طلب/دقيقة</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">حد اليوم</span>
                <span class="font-bold">{{ number_format($apiClient->daily_limit) }} طلب/يوم</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600">الاستخدام اليومي</span>
                <span class="font-bold">{{ number_format($apiClient->daily_requests) }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Course Mappings -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-800">الدورات المربوطة</h2>
        <a href="{{ route('api-clients.mappings', $apiClient) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">
            إدارة الربط
        </a>
    </div>
    <div class="p-6">
        @if($apiClient->courseMappings->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($apiClient->courseMappings->take(6) as $mapping)
                    <div class="border rounded-lg p-3 {{ $mapping->active ? 'border-gray-200' : 'border-red-200 bg-red-50' }}">
                        <div class="font-medium text-gray-900">{{ $mapping->external_course_name }}</div>
                        <div class="text-sm text-gray-500">
                            <code class="bg-gray-100 px-1 rounded">{{ $mapping->external_course_id }}</code>
                        </div>
                        <div class="text-sm text-indigo-600 mt-1">
                            {{ $mapping->track->name_ar ?? '-' }}
                        </div>
                    </div>
                @endforeach
            </div>
            @if($apiClient->courseMappings->count() > 6)
                <p class="text-center text-gray-500 mt-4">
                    و {{ $apiClient->courseMappings->count() - 6 }} دورة أخرى...
                </p>
            @endif
        @else
            <p class="text-center text-gray-500 py-8">
                لم يتم ربط أي دورات بعد.
                <a href="{{ route('api-clients.mappings', $apiClient) }}" class="text-indigo-600 hover:underline">إضافة ربط</a>
            </p>
        @endif
    </div>
</div>

<!-- Recent Logs -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-800">آخر الطلبات</h2>
        <a href="{{ route('api-clients.logs', $apiClient) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">
            عرض الكل
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الوقت</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">نقطة النهاية</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">زمن الاستجابة</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($recentLogs as $log)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->created_at->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <code class="bg-gray-100 px-1 rounded">{{ $log->endpoint }}</code>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($log->status_code >= 200 && $log->status_code < 300)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    {{ $log->status_code }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                    {{ $log->status_code }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $log->response_time_ms ?? '-' }} ms
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            لا توجد طلبات بعد
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Actions -->
@if(auth()->user()->hasPermission(\App\Models\Permission::API_CLIENTS_MANAGE))
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-bold text-gray-800">إجراءات</h2>
    </div>
    <div class="p-6 flex flex-wrap gap-4">
        <!-- Regenerate Credentials -->
        <form action="{{ route('api-clients.regenerate', $apiClient) }}" method="POST"
            onsubmit="return confirm('هل أنت متأكد؟ سيتم إلغاء بيانات الاعتماد الحالية.')">
            @csrf
            <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                </svg>
                إعادة إنشاء بيانات الاعتماد
            </button>
        </form>

        <!-- Toggle Status -->
        <form action="{{ route('api-clients.toggle', $apiClient) }}" method="POST">
            @csrf
            @method('PATCH')
            <button type="submit" class="bg-{{ $apiClient->active ? 'red' : 'green' }}-500 text-white px-4 py-2 rounded-lg hover:bg-{{ $apiClient->active ? 'red' : 'green' }}-600 transition flex items-center gap-2">
                @if($apiClient->active)
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                    </svg>
                    تعطيل العميل
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    تفعيل العميل
                @endif
            </button>
        </form>

        <!-- Delete -->
        <form action="{{ route('api-clients.destroy', $apiClient) }}" method="POST"
            onsubmit="return confirm('هل أنت متأكد من حذف هذا العميل؟ لا يمكن التراجع عن هذا الإجراء.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                حذف العميل
            </button>
        </form>
    </div>
</div>
@endif
@endsection
