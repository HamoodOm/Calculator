@extends('auth.layouts.app')

@section('title', 'إضافة عميل API جديد')

@section('content')
<div class="mb-6">
    <a href="{{ route('api-clients.index') }}" class="text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        العودة للقائمة
    </a>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h1 class="text-xl font-bold text-gray-800">إضافة عميل API جديد</h1>
        <p class="text-gray-600 text-sm mt-1">إضافة منصة خارجية للاتصال بنظام الشهادات</p>
    </div>

    <form action="{{ route('api-clients.store') }}" method="POST" class="p-6 space-y-6">
        @csrf

        <!-- Basic Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    اسم العميل <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-red-500 @enderror"
                    placeholder="مثال: منصة FEP التعليمية" required>
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">
                    المعرف (Slug) <span class="text-red-500">*</span>
                </label>
                <input type="text" name="slug" id="slug" value="{{ old('slug') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('slug') border-red-500 @enderror"
                    placeholder="fep-platform" pattern="[a-z0-9-]+" required>
                <p class="text-gray-500 text-xs mt-1">أحرف إنجليزية صغيرة وأرقام وشرطات فقط</p>
                @error('slug')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">الوصف</label>
            <textarea name="description" id="description" rows="2"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="وصف مختصر للمنصة...">{{ old('description') }}</textarea>
        </div>

        <!-- Institution -->
        <div>
            <label for="institution_id" class="block text-sm font-medium text-gray-700 mb-1">
                المؤسسة <span class="text-red-500">*</span>
            </label>
            <select name="institution_id" id="institution_id"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('institution_id') border-red-500 @enderror" required>
                <option value="">اختر المؤسسة...</option>
                @foreach($institutions as $institution)
                    <option value="{{ $institution->id }}" {{ old('institution_id') == $institution->id ? 'selected' : '' }}>
                        {{ $institution->name }}
                    </option>
                @endforeach
            </select>
            @error('institution_id')
                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
            @enderror
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
                            {{ in_array($scope, old('scopes', [])) ? 'checked' : '' }}
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
                <input type="number" name="rate_limit" id="rate_limit" value="{{ old('rate_limit', 60) }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    min="1" max="1000" required>
            </div>

            <div>
                <label for="daily_limit" class="block text-sm font-medium text-gray-700 mb-1">
                    حد الطلبات اليومي <span class="text-red-500">*</span>
                </label>
                <input type="number" name="daily_limit" id="daily_limit" value="{{ old('daily_limit', 1000) }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    min="1" max="100000" required>
            </div>
        </div>

        <!-- Webhook -->
        <div>
            <label for="webhook_url" class="block text-sm font-medium text-gray-700 mb-1">رابط Webhook (اختياري)</label>
            <input type="url" name="webhook_url" id="webhook_url" value="{{ old('webhook_url') }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="https://example.com/webhook">
            <p class="text-gray-500 text-xs mt-1">سيتم إرسال إشعارات الشهادات المولدة لهذا الرابط</p>
        </div>

        <!-- Allowed IPs -->
        <div>
            <label for="allowed_ips" class="block text-sm font-medium text-gray-700 mb-1">عناوين IP المسموح بها (اختياري)</label>
            <input type="text" name="allowed_ips" id="allowed_ips" value="{{ old('allowed_ips') }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="192.168.1.1, 10.0.0.0/24">
            <p class="text-gray-500 text-xs mt-1">اتركه فارغاً للسماح لجميع العناوين. استخدم فاصلة للفصل بين العناوين.</p>
        </div>

        <!-- ===== Advanced Permissions / Capabilities ===== -->
        <div class="border-t pt-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">صلاحيات متقدمة وقيود</h3>

            <!-- Certificate Type Restrictions -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">أنواع الشهادات المسموح بها</label>
                <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                    <p class="text-xs text-gray-500 mb-2">اتركها فارغة للسماح بجميع الأنواع</p>
                    @foreach(\App\Models\ApiClient::getAvailableCertificateTypes() as $type => $label)
                        <label class="flex items-center">
                            <input type="checkbox" name="allowed_certificate_types[]" value="{{ $type }}"
                                {{ in_array($type, old('allowed_certificate_types', [])) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="mr-2 text-sm text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Max Per Request -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label for="max_per_request" class="block text-sm font-medium text-gray-700 mb-1">
                        الحد الأقصى للشهادات في الطلب الواحد
                    </label>
                    <input type="number" name="max_per_request" id="max_per_request"
                        value="{{ old('max_per_request', 1) }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        min="1" max="500">
                    <p class="text-gray-500 text-xs mt-1">عدد الشهادات في طلب API واحد</p>
                </div>

                <div>
                    <label for="contact_email" class="block text-sm font-medium text-gray-700 mb-1">
                        بريد التواصل (اختياري)
                    </label>
                    <input type="email" name="contact_email" id="contact_email"
                        value="{{ old('contact_email') }}"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="admin@platform.com">
                    <p class="text-gray-500 text-xs mt-1">للتواصل مع مسؤول هذه المنصة</p>
                </div>
            </div>

            <!-- Flags -->
            <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                <label class="flex items-start">
                    <input type="checkbox" name="expose_download_url" value="1"
                        {{ old('expose_download_url', '1') === '1' ? 'checked' : '' }}
                        class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <div class="mr-3">
                        <span class="font-medium text-gray-900 text-sm">إرسال رابط التحميل في الاستجابة</span>
                        <p class="text-xs text-gray-500">يتضمن رابط تحميل الشهادة في رد API</p>
                    </div>
                </label>

                <label class="flex items-start">
                    <input type="checkbox" name="require_webhook_success" value="1"
                        {{ old('require_webhook_success') ? 'checked' : '' }}
                        class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <div class="mr-3">
                        <span class="font-medium text-gray-900 text-sm">اشتراط نجاح Webhook</span>
                        <p class="text-xs text-gray-500">إذا فشل إرسال Webhook، اعتبر الطلب فاشلاً</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('api-clients.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">
                إلغاء
            </a>
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                إنشاء عميل API
            </button>
        </div>
    </form>
</div>

<script>
    // Auto-generate slug from name
    document.getElementById('name').addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .substring(0, 50);
        document.getElementById('slug').value = slug;
    });
</script>
@endsection
