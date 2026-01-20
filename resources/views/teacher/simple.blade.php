<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>شهادات المعلمين</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Simple Teacher Theme - Light Blue */
    body {
      background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 50%, #e0f2fe 100%);
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
      border: 1px solid #bae6fd;
      background: #fff;
      border-radius: 8px;
    }
  </style>
</head>
<body class="min-h-screen">
  @include('layouts.partials.header')

  <div class="max-w-5xl mx-auto p-6">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">إصدار شهادات المعلمين</h1>
      @if(auth()->user()->canAccessTeacherAdmin())
      <a href="{{ route('teacher.admin.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 text-sm">
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
            تحميل الشهادة
          </a>
        @endif
      </div>
    @endif

    {{-- Main Form --}}
    <form id="teacherForm"
          action="{{ route('teacher.store') }}"
          method="POST"
          enctype="multipart/form-data"
          class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
      @csrf

      <div class="p-6 space-y-4">
        <h2 class="text-lg font-semibold text-gray-700 pb-2 border-b">بيانات الشهادة</h2>

        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">اسم المسار <span class="text-red-500">*</span></label>
            <select name="track_key" id="track-select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="" disabled selected>اختر المسار</option>
              @foreach ($tracks as $track)
                <option value="{{ $track->key ?? $track['key'] }}" @selected(old('track_key')===$track->key)>
                  {{ $track->name_ar ?? $track['name_ar'] }} — {{ $track->name_en ?? $track['name_en'] }}
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

        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">
              الاسم بالعربية <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name_ar" value="{{ old('name_ar', 'أحمد محمد') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="مثال: أحمد محمد">
          </div>

          <div>
            <label class="block text-sm font-medium mb-2">
              Name in English <span class="text-red-500">*</span>
            </label>
            <input type="text" name="name_en" value="{{ old('name_en', 'Ahmed Mohammed') }}" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="Example: Ahmed Mohammed">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">صورة المعلم (اختياري)</label>
          <input type="file" name="photo" id="teacher_photo_simple" accept="image/*"
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          <input type="hidden" id="remove_photo_simple" name="remove_photo" value="0">
          <p class="text-xs text-gray-500 mt-1">
            صورة شخصية للمعلم بصيغة JPG أو PNG (اختياري)
          </p>
          <div class="mt-2">
            <button type="button" id="btn-remove-photo-simple" class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 text-sm">
              إزالة الصورة
            </button>
          </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium mb-2">نوع التاريخ</label>
            <div class="flex flex-col gap-2 text-sm">
              <label class="inline-flex items-center gap-2">
                <input type="radio" name="date_mode" value="range" id="date-mode-range" checked>
                <span>نطاق (من - إلى)</span>
              </label>
              <label class="inline-flex items-center gap-2">
                <input type="radio" name="date_mode" value="end" id="date-mode-end">
                <span>تاريخ نهاية فقط</span>
              </label>
            </div>
          </div>

          <div id="dur-from-wrap">
            <label class="block text-sm font-medium mb-2">من</label>
            <input type="date" name="duration_from" value="{{ old('duration_from') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>

          <div id="dur-to-wrap">
            <label class="block text-sm font-medium mb-2"><span id="dur-to-label">إلى</span></label>
            <input type="date" name="duration_to" value="{{ old('duration_to') }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>
        </div>
      </div>

      {{-- Action Buttons --}}
      <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
        <div class="grid md:grid-cols-2 gap-3">
          <button type="submit"
                  formaction="{{ route('teacher.preview') }}"
                  formtarget="previewFrame"
                  class="px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium transition-colors">
            👁️ معاينة PDF
          </button>

          <button type="submit"
                  class="px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium transition-colors">
            📦 إنشاء شهادة PDF
          </button>

          <button type="submit"
                  formaction="{{ route('teacher.preview.image') }}"
                  formtarget="previewFrame"
                  class="px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-medium transition-colors">
            🖼️ معاينة صورة
          </button>

          <button type="submit"
                  formaction="{{ route('teacher.store.image') }}"
                  class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors">
            🎨 إنشاء شهادة صورة
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
  (function(){
    // Track settings from server
    const trackSettings = @json($trackSettings ?? []);

    // Duration mode toggle
    const modeRadios = document.querySelectorAll('input[name="date_mode"]');
    const durFromWrap = document.getElementById('dur-from-wrap');
    const durToLabel = document.getElementById('dur-to-label');
    const trackSelect = document.getElementById('track-select');
    const genderSelect = document.getElementById('gender-select');

    function applyDurationMode() {
      const mode = Array.from(modeRadios).find(r => r.checked)?.value || 'range';
      if (mode === 'end') {
        durFromWrap.style.display = 'none';
        durToLabel.textContent = 'في';
      } else {
        durFromWrap.style.display = '';
        durToLabel.textContent = 'إلى';
      }
    }

    function updateDateModeBasedOnSettings() {
      const trackKey = trackSelect?.value;
      const gender = genderSelect?.value;

      if (trackKey && gender && trackSettings[trackKey] && trackSettings[trackKey][gender]) {
        const dateType = trackSettings[trackKey][gender].date_type;
        const durationRadio = document.getElementById('date-mode-range');
        const endRadio = document.getElementById('date-mode-end');

        if (dateType === 'end') {
          endRadio.checked = true;
          durationRadio.disabled = true;
          durationRadio.parentElement.style.opacity = '0.5';
          endRadio.disabled = false;
          endRadio.parentElement.style.opacity = '1';
        } else {
          durationRadio.checked = true;
          durationRadio.disabled = false;
          durationRadio.parentElement.style.opacity = '1';
          endRadio.disabled = true;
          endRadio.parentElement.style.opacity = '0.5';
        }

        applyDurationMode();
      } else {
        const durationRadio = document.getElementById('date-mode-range');
        const endRadio = document.getElementById('date-mode-end');
        durationRadio.disabled = false;
        endRadio.disabled = false;
        durationRadio.parentElement.style.opacity = '1';
        endRadio.parentElement.style.opacity = '1';
        durationRadio.checked = true;
        applyDurationMode();
      }
    }

    modeRadios.forEach(r => r.addEventListener('change', applyDurationMode));
    trackSelect?.addEventListener('change', updateDateModeBasedOnSettings);
    genderSelect?.addEventListener('change', updateDateModeBasedOnSettings);

    applyDurationMode();
    updateDateModeBasedOnSettings();

    // Remove photo handler
    const removePhotoBtn = document.getElementById('btn-remove-photo-simple');
    const photoInput = document.getElementById('teacher_photo_simple');
    const removePhotoFlag = document.getElementById('remove_photo_simple');

    if (removePhotoBtn && photoInput && removePhotoFlag) {
      removePhotoBtn.addEventListener('click', function() {
        photoInput.value = ''; // Clear the file input
        removePhotoFlag.value = '1'; // Set flag to remove
        alert('سيتم إزالة الصورة في المعاينة أو الإنشاء التالي.');
      });
    }
  })();
  </script>
</body>
</html>
