<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>شهادات الطلاب</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Simple Student Theme - Light Green */
    body {
      background: linear-gradient(135deg, #d1fae5 0%, #ecfdf5 50%, #d1fae5 100%);
      background-attachment: fixed;
    }
    .preview-container {
      width: 100%;
      max-width: 1122px;
      margin: 0 auto;
    }
    .preview-frame {
      width: 100%;
      height: 600px;
      border: 1px solid #6ee7b7;
      background: #fff;
      border-radius: 8px;
    }
  </style>
</head>
<body class="min-h-screen">
  @include('layouts.partials.header')

  <div class="max-w-5xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">إصدار شهادات الطلاب</h1>
      @if(auth()->user()->canAccessStudentAdmin())
      <a href="{{ route('students.admin.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">
        المحرر المتقدم
      </a>
      @endif
    </div>

    @if ($errors->any())
      <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded">
        <p class="font-semibold text-red-800 mb-2">حدثت أخطاء:</p>
        <ul class="list-disc mr-6 text-red-700 space-y-1">
          @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
      </div>
    @endif

    @if (session('success'))
      <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded">
        <p class="text-green-800">{{ session('success') }}</p>
        @if (session('download_url'))
          <a href="{{ session('download_url') }}" class="inline-block mt-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
            تحميل الملف (ZIP)
          </a>
        @endif
      </div>
    @endif

    {{-- Main Form --}}
    <form id="studentsForm"
          action="{{ route('students.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
      @csrf

      <div class="p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-700 pb-2 border-b">بيانات الشهادات</h2>

        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">اسم المسار <span class="text-red-500">*</span></label>
            <select name="track_key" id="track-select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="" disabled selected>اختر المسار</option>
              @foreach ($tracks as $track)
                <option value="{{ $track->key }}" @selected(old('track_key')===$track->key)>
                  {{ $track->name_ar }} — {{ $track->name_en }}
                </option>
              @endforeach
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">الجنس <span class="text-red-500">*</span></label>
            <select name="gender" id="gender-select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="male" @selected(old('gender')==='male')>ذكر</option>
              <option value="female" @selected(old('gender')==='female')>أنثى</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">
            ملف الطلاب (CSV أو Excel) <span class="text-red-500">*</span>
          </label>
          <input type="file" name="students_file" accept=".csv,.xlsx" required
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <div class="mt-2 flex gap-3 text-xs text-gray-600">
            <a href="{{ route('students.template', 'csv') }}" class="text-blue-600 hover:underline">📄 تحميل قالب CSV</a>
            <a href="{{ route('students.template', 'xlsx') }}" class="text-blue-600 hover:underline">📊 تحميل قالب Excel</a>
          </div>
          <p class="text-xs text-gray-500 mt-1">الأعمدة المطلوبة: name_ar, name_en (اختياري: student_id, photo_filename)</p>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">صور المتدرّبين (ZIP) — اختياري</label>
          <input type="file" name="images_zip" id="images_zip" accept=".zip"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <p class="text-xs text-gray-500 mt-1">
            ملف ZIP يحتوي على صور الطلاب بصيغة JPG أو PNG. أسماء الملفات: {student_id}.jpg أو {photo_filename}
          </p>
          <div class="mt-2">
            <button type="button" id="btn-remove-zip" class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 text-sm">
              إزالة ملف الصور
            </button>
          </div>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
        <div class="grid md:grid-cols-2 gap-3">
          <button type="submit"
                  formaction="{{ route('students.preview') }}"
                  formtarget="previewFrame"
                  class="px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition-colors">
            👁️ معاينة PDF
          </button>

          <button type="submit"
                  class="px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors">
            📦 إنشاء شهادات PDF (ZIP)
          </button>

          <button type="submit"
                  formaction="{{ route('students.preview.image') }}"
                  formtarget="previewFrame"
                  class="px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium transition-colors">
            🖼️ معاينة صورة
          </button>

          <button type="submit"
                  formaction="{{ route('students.store.images') }}"
                  class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">
            🎨 إنشاء شهادات صور (ZIP)
          </button>
        </div>
      </div>
    </form>

    {{-- Preview Section --}}
    <div class="bg-white rounded-lg shadow-md p-6">
      <h2 class="text-lg font-semibold text-gray-700 mb-4">المعاينة</h2>
      <div class="preview-container">
        <iframe name="previewFrame" class="preview-frame"></iframe>
      </div>
      <p class="text-xs text-gray-500 mt-3 text-center">
        ستظهر هنا معاينة الشهادة بعد الضغط على زر المعاينة
      </p>
    </div>
  </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const removeZipBtn = document.getElementById('btn-remove-zip');
  const zipInput = document.getElementById('images_zip');

  if (removeZipBtn && zipInput) {
    removeZipBtn.addEventListener('click', function() {
      zipInput.value = '';
      alert('تم إزالة ملف الصور المحدد.');
    });
  }
});
</script>
</body>
</html>
