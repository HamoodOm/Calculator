@extends('auth.layouts.app')

@section('title', 'إضافة مسار جديد')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('tracks.index') }}" class="text-gray-600 hover:text-gray-900">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800">إضافة مسار جديد</h1>
            <p class="text-gray-600 mt-1">إنشاء مسار شهادات جديد للمعلمين أو الطلاب</p>
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

    <form action="{{ route('tracks.store') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf

        <!-- Track Type -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">نوع المسار <span class="text-red-500">*</span></label>
            <div class="flex gap-4">
                <label class="flex items-center">
                    <input type="radio" name="type" value="teacher" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300" {{ old('type', 'teacher') === 'teacher' ? 'checked' : '' }}>
                    <span class="mr-2 text-sm text-gray-700">مسار معلمين</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" name="type" value="student" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300" {{ old('type') === 'student' ? 'checked' : '' }}>
                    <span class="mr-2 text-sm text-gray-700">مسار طلاب</span>
                </label>
            </div>
        </div>

        <!-- Name Arabic -->
        <div>
            <label for="name_ar" class="block text-sm font-medium text-gray-700 mb-1">
                الاسم بالعربية <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name_ar" id="name_ar" value="{{ old('name_ar') }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="مثال: دورة تدريب المعلمين" required>
        </div>

        <!-- Name English -->
        <div>
            <label for="name_en" class="block text-sm font-medium text-gray-700 mb-1">
                الاسم بالإنجليزية <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name_en" id="name_en" value="{{ old('name_en') }}"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Example: Teacher Training Course" required dir="ltr">
        </div>

        @if(auth()->user()->isSuperUser() && $institutions->isNotEmpty())
        <!-- Institution -->
        <div>
            <label for="institution_id" class="block text-sm font-medium text-gray-700 mb-1">المؤسسة</label>
            <select name="institution_id" id="institution_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">عام (بدون مؤسسة)</option>
                @foreach($institutions as $institution)
                    <option value="{{ $institution->id }}" {{ old('institution_id') == $institution->id ? 'selected' : '' }}>
                        {{ $institution->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-sm text-gray-500">اختر المؤسسة التي ينتمي إليها المسار</p>
        </div>
        @endif

        <!-- Certificate Templates -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">قوالب الشهادات</h3>
            <p class="text-sm text-gray-500 mb-4">قم برفع صور قوالب الشهادات للذكور والإناث (JPEG, PNG - حتى 10MB)</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Male Certificate -->
                <div>
                    <label for="male_certificate" class="block text-sm font-medium text-gray-700 mb-1">
                        شهادة الذكور <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-400 transition" id="male_drop_zone">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="male_certificate" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                    <span>رفع ملف</span>
                                    <input id="male_certificate" name="male_certificate" type="file" class="sr-only" accept="image/jpeg,image/png" required>
                                </label>
                                <p class="pr-1">أو سحب وإفلات</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG حتى 10MB</p>
                        </div>
                    </div>
                    <div id="male_preview" class="mt-2 hidden">
                        <img src="" alt="معاينة" class="max-h-40 mx-auto rounded border">
                    </div>
                </div>

                <!-- Female Certificate -->
                <div>
                    <label for="female_certificate" class="block text-sm font-medium text-gray-700 mb-1">
                        شهادة الإناث <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-400 transition" id="female_drop_zone">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="female_certificate" class="relative cursor-pointer rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                    <span>رفع ملف</span>
                                    <input id="female_certificate" name="female_certificate" type="file" class="sr-only" accept="image/jpeg,image/png" required>
                                </label>
                                <p class="pr-1">أو سحب وإفلات</p>
                            </div>
                            <p class="text-xs text-gray-500">PNG, JPG حتى 10MB</p>
                        </div>
                    </div>
                    <div id="female_preview" class="mt-2 hidden">
                        <img src="" alt="معاينة" class="max-h-40 mx-auto rounded border">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-4 pt-6 border-t">
            <a href="{{ route('tracks.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition">
                إلغاء
            </a>
            <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition">
                إنشاء المسار
            </button>
        </div>
    </form>
</div>

<script>
    // Preview uploaded images
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
