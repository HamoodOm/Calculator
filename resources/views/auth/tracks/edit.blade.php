@extends('auth.layouts.app')

@section('title', 'تعديل المسار')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('tracks.index') }}" class="text-gray-600 hover:text-gray-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">تعديل المسار</h1>
            <p class="text-gray-600 mt-1">{{ $track->name_ar }}</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded mb-6">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Track Info Card -->
    <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
            <div>
                <span class="text-gray-500">المفتاح:</span>
                <code class="bg-white px-2 py-1 rounded text-gray-800">{{ $track->key }}</code>
            </div>
            <div>
                <span class="text-gray-500">النوع:</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $type === 'student' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                    {{ $type === 'student' ? 'طلاب' : 'معلمين' }}
                </span>
            </div>
            <div>
                <span class="text-gray-500">الحالة:</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $track->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                    {{ $track->active ? 'مفعل' : 'معطل' }}
                </span>
            </div>
            <div>
                <span class="text-gray-500">تاريخ الإنشاء:</span>
                <span class="text-gray-800">{{ $track->created_at->format('Y-m-d') }}</span>
            </div>
        </div>
    </div>

    <form action="{{ route('tracks.update', $track) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf
        @method('PUT')

        <!-- Name Arabic -->
        <div>
            <label for="name_ar" class="block text-sm font-medium text-gray-700 mb-1">
                الاسم بالعربية <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name_ar" id="name_ar" value="{{ old('name_ar', $track->name_ar) }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required>
        </div>

        <!-- Name English -->
        <div>
            <label for="name_en" class="block text-sm font-medium text-gray-700 mb-1">
                الاسم بالإنجليزية <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name_en" id="name_en" value="{{ old('name_en', $track->name_en) }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required dir="ltr">
        </div>

        @if(auth()->user()->isSuperUser() && $institutions->isNotEmpty())
        <!-- Institution -->
        <div>
            <label for="institution_id" class="block text-sm font-medium text-gray-700 mb-1">المؤسسة</label>
            <select name="institution_id" id="institution_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">عام (بدون مؤسسة)</option>
                @foreach($institutions as $institution)
                    <option value="{{ $institution->id }}" {{ old('institution_id', $track->institution_id) == $institution->id ? 'selected' : '' }}>
                        {{ $institution->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        <!-- Current Certificate Templates -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">قوالب الشهادات الحالية</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                @if($maleSettings && $maleSettings->certificate_bg)
                <div class="text-center">
                    <p class="text-sm font-medium text-gray-700 mb-2">شهادة الذكور</p>
                    <img src="{{ asset($maleSettings->certificate_bg) }}" alt="شهادة الذكور" class="max-h-40 mx-auto rounded border shadow-sm">
                </div>
                @endif

                @if($femaleSettings && $femaleSettings->certificate_bg)
                <div class="text-center">
                    <p class="text-sm font-medium text-gray-700 mb-2">شهادة الإناث</p>
                    <img src="{{ asset($femaleSettings->certificate_bg) }}" alt="شهادة الإناث" class="max-h-40 mx-auto rounded border shadow-sm">
                </div>
                @endif
            </div>

            <h4 class="text-md font-medium text-gray-700 mb-4">تحديث القوالب (اختياري)</h4>
            <p class="text-sm text-gray-500 mb-4">اترك الحقول فارغة للاحتفاظ بالقوالب الحالية</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Male Certificate -->
                <div>
                    <label for="male_certificate" class="block text-sm font-medium text-gray-700 mb-1">
                        شهادة الذكور الجديدة
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-400 transition">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-10 w-10 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="male_certificate" class="cursor-pointer font-medium text-indigo-600 hover:text-indigo-500">
                                    <span>رفع ملف جديد</span>
                                    <input id="male_certificate" name="male_certificate" type="file" class="sr-only" accept="image/jpeg,image/png">
                                </label>
                            </div>
                        </div>
                    </div>
                    <div id="male_preview" class="mt-2 hidden">
                        <img src="" alt="معاينة" class="max-h-32 mx-auto rounded border">
                    </div>
                </div>

                <!-- Female Certificate -->
                <div>
                    <label for="female_certificate" class="block text-sm font-medium text-gray-700 mb-1">
                        شهادة الإناث الجديدة
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-400 transition">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-10 w-10 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="female_certificate" class="cursor-pointer font-medium text-indigo-600 hover:text-indigo-500">
                                    <span>رفع ملف جديد</span>
                                    <input id="female_certificate" name="female_certificate" type="file" class="sr-only" accept="image/jpeg,image/png">
                                </label>
                            </div>
                        </div>
                    </div>
                    <div id="female_preview" class="mt-2 hidden">
                        <img src="" alt="معاينة" class="max-h-32 mx-auto rounded border">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-between items-center pt-6 border-t">
            <div class="flex gap-2">
                <!-- Toggle Status -->
                <form action="{{ route('tracks.toggle', $track) }}" method="POST" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-4 py-2 border rounded-md transition {{ $track->active ? 'border-yellow-300 text-yellow-700 hover:bg-yellow-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                        {{ $track->active ? 'تعطيل المسار' : 'تفعيل المسار' }}
                    </button>
                </form>

                @if(auth()->user()->hasPermission(\App\Models\Permission::TRACKS_DELETE))
                <!-- Delete -->
                <form action="{{ route('tracks.destroy', $track) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المسار؟')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 border border-red-300 text-red-700 rounded-md hover:bg-red-50 transition">
                        حذف المسار
                    </button>
                </form>
                @endif
            </div>

            <div class="flex gap-4">
                <a href="{{ route('tracks.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                    إلغاء
                </a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                    حفظ التغييرات
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    function setupImagePreview(inputId, previewId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const img = preview.querySelector('img');

        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });
    }

    setupImagePreview('male_certificate', 'male_preview');
    setupImagePreview('female_certificate', 'female_preview');
</script>
@endsection
