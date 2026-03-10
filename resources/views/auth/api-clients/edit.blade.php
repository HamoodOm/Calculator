@extends('auth.layouts.app')

@section('title', 'تعديل عميل API')

@section('content')
<div class="mb-6">
    <a href="{{ route('api-clients.show', $apiClient) }}" class="text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        العودة للتفاصيل
    </a>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h1 class="text-xl font-bold text-gray-800">تعديل عميل API: {{ $apiClient->name }}</h1>
        <p class="text-gray-600 text-sm mt-1">
            <code class="bg-gray-100 px-2 py-0.5 rounded">{{ $apiClient->slug }}</code>
        </p>
    </div>

    <form action="{{ route('api-clients.update', $apiClient) }}" method="POST" class="p-6 space-y-6">
        @csrf
        @method('PUT')

        <!-- Basic Information -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                اسم العميل <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name" id="name" value="{{ old('name', $apiClient->name) }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                required>
            @error('name')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
            <textarea name="description" id="description" rows="2"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $apiClient->description) }}</textarea>
        </div>

        <!-- Scopes -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                الصلاحيات (Scopes) <span class="text-red-500">*</span>
            </label>
            <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                @foreach($scopes as $scope => $info)
                    <label class="flex items-start">
                        <input type="checkbox" name="scopes[]" value="{{ $scope }}"
                            {{ in_array($scope, old('scopes', $apiClient->scopes ?? [])) ? 'checked' : '' }}
                            class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div class="mr-3">
                            <span class="font-medium text-gray-900">{{ $info['name'] }}</span>
                            <p class="text-sm text-gray-500">{{ $info['description'] }}</p>
                        </div>
                    </label>
                @endforeach
            </div>
            @error('scopes')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Rate Limits -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="rate_limit" class="block text-sm font-medium text-gray-700 mb-1">
                    حد الطلبات في الدقيقة <span class="text-red-500">*</span>
                </label>
                <input type="number" name="rate_limit" id="rate_limit" value="{{ old('rate_limit', $apiClient->rate_limit) }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    min="1" max="1000" required>
            </div>

            <div>
                <label for="daily_limit" class="block text-sm font-medium text-gray-700 mb-1">
                    حد الطلبات اليومي <span class="text-red-500">*</span>
                </label>
                <input type="number" name="daily_limit" id="daily_limit" value="{{ old('daily_limit', $apiClient->daily_limit) }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    min="1" max="100000" required>
            </div>
        </div>

        <!-- Webhook -->
        <div>
            <label for="webhook_url" class="block text-sm font-medium text-gray-700 mb-1">رابط Webhook (اختياري)</label>
            <input type="url" name="webhook_url" id="webhook_url" value="{{ old('webhook_url', $apiClient->webhook_url) }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="https://example.com/webhook">
            <p class="text-gray-500 text-xs mt-1">سيتم إرسال إشعارات الشهادات المولدة لهذا الرابط</p>
        </div>

        <!-- Allowed IPs -->
        <div>
            <label for="allowed_ips" class="block text-sm font-medium text-gray-700 mb-1">عناوين IP المسموح بها (اختياري)</label>
            <input type="text" name="allowed_ips" id="allowed_ips"
                value="{{ old('allowed_ips', is_array($apiClient->allowed_ips) ? implode(', ', $apiClient->allowed_ips) : $apiClient->allowed_ips) }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="192.168.1.1, 10.0.0.0/24">
            <p class="text-gray-500 text-xs mt-1">اتركه فارغاً للسماح لجميع العناوين. استخدم فاصلة للفصل بين العناوين.</p>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('api-clients.show', $apiClient) }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">
                إلغاء
            </a>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                حفظ التغييرات
            </button>
        </div>
    </form>
</div>
@endsection
