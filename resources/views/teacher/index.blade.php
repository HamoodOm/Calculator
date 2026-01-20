<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <title>صفحة المعلم (إداري) — المحرر الكامل</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Admin Teacher Theme (Legacy) - Indigo/Purple */
    body {
      background: linear-gradient(135deg, #e0e7ff 0%, #eef2ff 50%, #e0e7ff 100%);
      background-attachment: fixed;
    }
    .a4-landscape { width: 1122.52px; height: 793.70px; border: 1px solid #c7d2fe; background: #fff; }
    .iframed { width: 100%; height: 100%; border: 0; }
    #pe-stage { position: relative; }
    #pe-stage .pe-box { position: absolute; z-index: 2; }
  </style>
</head>
<body class="min-h-screen">
  @include('layouts.partials.header')

  <div class="max-w-full mx-auto p-6">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-bold">صفحة المعلم (إداري) — المحرر الكامل</h1>
      @if(auth()->user()->canAccessTeacherSimple())
      <a href="{{ route('teacher.index') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
        الواجهة المبسطة للمعلم
      </a>
      @endif
    </div>

    @if ($errors->any())
      <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded">
        <ul class="list-disc mr-6 text-red-700">
          @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
      </div>
    @endif

    

    <div class="grid xl:grid-cols-2 gap-6">
      <form id="teacherForm"
            action="{{ route('teacher.admin.store') }}"
            method="POST"
            enctype="multipart/form-data"  {{-- مهم لرفع الصور --}}
            class="space-y-6 bg-white p-6 rounded shadow"
            target="_self">
        @csrf


<!-- Separate Arabic/English duration (optional; falls back to duration_from)
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div>
    <label class="block mb-1">من (عربي)</label>
    <input type="date" name="duration_from_ar" class="w-full border rounded px-2 py-1">
  </div>
  <div>
    <label class="block mb-1">From (English)</label>
    <input type="date" name="duration_from_en" class="w-full border rounded px-2 py-1">
  </div>
</div> -->




      {{-- Duration controls (shared for both Arabic & English) --}}
      <div class="grid md:grid-cols-3 gap-4 mt-4">
        <div class="col-span-1">
          <label class="block text-sm font-medium mb-1">نوع التاريخ</label>
          <div class="flex items-center gap-4 text-sm">
            <label class="inline-flex items-center gap-1">
              <input type="radio" name="duration_mode" value="range" checked>
              <span>نطاق (من - إلى)</span>
            </label>
            <label class="inline-flex items-center gap-1">
              <input type="radio" name="duration_mode" value="end">
              <span>تاريخ نهاية فقط</span>
            </label>
          </div>
        </div>

        <div id="dur-from-wrap">
          <label class="block text-sm font-medium mb-1">من</label>
          <input type="date" name="duration_from" class="w-full border rounded px-3 py-2">
        </div>

        <div id="dur-to-wrap">
          <label class="block text-sm font-medium mb-1"><span id="dur-to-label">إلى</span></label>
          <input type="date" name="duration_to" class="w-full border rounded px-3 py-2">
        </div>
      </div>

      <script>
      (function(){
        const radios  = document.querySelectorAll('input[name="duration_mode"]');
        const fromDiv = document.getElementById('dur-from-wrap');
        const toLbl   = document.getElementById('dur-to-label');

        function applyMode() {
          const mode = Array.from(radios).find(r => r.checked)?.value || 'range';
          if (mode === 'end') {
            fromDiv.style.display = 'none';
            toLbl.textContent = 'في';
          } else {
            fromDiv.style.display = '';
            toLbl.textContent = 'إلى';
          }
        }
        radios.forEach(r => r.addEventListener('change', applyMode));
        applyMode();
      })();
      </script>




      <!-- {{-- Duration controls (shared for both Arabic & English) --}}
<div class="grid md:grid-cols-3 gap-4">
  <div class="col-span-1">
    <label class="block text-sm font-medium mb-1">نوع التاريخ</label>
    <div class="flex items-center gap-4 text-sm">
      <label class="inline-flex items-center gap-1">
        <input type="radio" name="duration_mode" value="range" checked>
        <span>نطاق (من - إلى)</span>
      </label>
      <label class="inline-flex items-center gap-1">
        <input type="radio" name="duration_mode" value="end">
        <span>تاريخ نهاية فقط</span>
      </label>
    </div>
  </div>

  <div id="dur-from-wrap">
    <label class="block text-sm font-medium mb-1">من</label>
    <input type="date" name="duration_from" class="w-full border rounded px-3 py-2">
  </div>

  <div id="dur-to-wrap">
    <label class="block text-sm font-medium mb-1"><span id="dur-to-label">إلى</span></label>
    <input type="date" name="duration_to" class="w-full border rounded px-3 py-2">
  </div>
</div>

        <script>
        (function(){
          const radios = document.querySelectorAll('input[name="duration_mode"]');
          const fromWrap = document.getElementById('dur-from-wrap');
          const toLabel  = document.getElementById('dur-to-label');

          function applyMode() {
            const mode = Array.from(radios).find(r => r.checked)?.value || 'range';
            if (mode === 'end') {
              // show only "end" date, hide "from"
              fromWrap.style.display = 'none';
              toLabel.textContent = 'حتى';   // Arabic "until"
            } else {
              fromWrap.style.display = '';
              toLabel.textContent = 'إلى';   // Arabic "to"
            }
          }
          radios.forEach(r => r.addEventListener('change', applyMode));
          applyMode();
        })();
        </script> -->


{{-- <input type="checkbox" name="ar_duration" value="1" checked> طباعة التاريخ بالعربية --}}
{{-- <input type="checkbox" name="en_duration" value="1" checked> طباعة التاريخ بالإنجليزية --}}


        @include('partials.print-flags')

        @push('scripts')
          <script src="{{ asset('js/print-flags.js') }}" defer></script>
        @endpush



        @if (session('success'))
          <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded text-green-800">{{ session('success') }}</div>
          @if (session('download_url'))
            <a href="{{ session('download_url') }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">تحميل الشهادة PDF</a>
          @endif
        @endif

        <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label>اسم المتدرّب (عربي)</label>
            <input type="text" name="name_ar" value="{{ old('name_ar') }}" required class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label>Trainee Name (English)</label>
            <input type="text" name="name_en" value="{{ old('name_en') }}" required class="w-full border rounded px-3 py-2">
          </div>
        </div>

        <div class="grid md:grid-cols-3 gap-4">
          <div class="md:col-span-2">
            <div class="flex justify-between items-center mb-1">
              <label>اسم المسار</label>
              <button type="button" onclick="openAddTrackModal()" class="px-2 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700">
                + إضافة مسار جديد
              </button>
            </div>
            <div class="flex gap-2">
              <select name="track_key" id="track-select" dusk="track-select" required class="flex-1 border rounded px-3 py-2">
                <option value="" disabled selected>اختر المسار</option>
                @foreach ($tracks as $key => $pair)
                  <option value="{{ $key }}"
                          data-track-id="{{ $pair['id'] ?? '' }}"
                          @selected(old('track_key')===$key)>
                    {{ $pair['ar'] }} — {{ $pair['en'] }}
                  </option>
                @endforeach
              </select>
              <button type="button"
                      id="delete-track-btn"
                      onclick="deleteSelectedTrack()"
                      class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                      title="حذف المسار المحدد">
                حذف
              </button>
            </div>
          </div>
          <div>
            <label>الجنس</label>
            <select name="gender" dusk="gender-select" required class="w-full border rounded px-3 py-2">
              <option value="male" @selected(old('gender')==='male')>ذكر</option>
              <option value="female" @selected(old('gender')==='female')>أنثى</option>
            </select>
          </div>
        </div>

        <!-- <div class="grid md:grid-cols-2 gap-4">
          <div>
            <label>تاريخ إصدار الشهادة (اختياري)</label>
            <input type="date" name="certificate_date" value="{{ old('certificate_date') }}" class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label>خلال الفترة من (اختياري)</label>
            <input type="date" name="duration_from" value="{{ old('duration_from') }}" class="w-full border rounded px-3 py-2">
          </div>
        </div> -->
        <!-- <div class="grid md:grid-cols-3 gap-4 mt-4">
          <div>
            <label class="block text-sm font-medium mb-1">تاريخ إصدار الشهادة (اختياري)</label>
            <input type="date" name="certificate_date" value="{{ old('certificate_date') }}" class="w-full border rounded px-3 py-2">
          </div>
        </div> -->


        <div>
          <label class="block text-sm font-medium mb-1">صورة المتدرّب (اختيارية)</label>
          <input type="file" name="photo"
                 accept="image/jpeg,image/png,image/webp"
                 class="w-full border rounded px-3 py-2">
          <div class="mt-2 flex items-center gap-3">
            <input type="hidden" id="remove_photo" name="remove_photo" value="0">
            <button type="button" id="btn-remove-photo"
                    class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700">
              إزالة الصورة
            </button>
            <span class="text-xs text-gray-500">لن تُستخدم الصورة في المعاينة أو الإنشاء وسيُحذف الأثر المؤقت.</span>
          </div>

        </div>

        {{-- المُحرر داخل نفس النموذج --}}
        @include('partials.position-editor', ['role' => 'teacher'])

        <div class="flex gap-2 flex-wrap">
          <button type="submit"
                  formaction="{{ route('teacher.admin.preview') }}"
                  formtarget="previewFrame"
                  class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-800">
            معاينة PDF
          </button>
          <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">
            إنشاء PDF
          </button>
          <button type="submit"
                  formaction="{{ route('teacher.admin.save') }}"
                  class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
            حفظ الإعدادات الافتراضية
          </button>
        </div>
      </form>

      <div class="bg-white p-4 rounded shadow overflow-auto">
        <div class="a4-landscape">
          <iframe name="previewFrame" class="iframed"></iframe>
        </div>
        <p class="text-xs text-gray-500 mt-2">المعاينة من نفس محرّك PDF.</p>
      </div>
    </div>
  </div>

  {{-- Add Track Modal --}}
  <div id="addTrackModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">إضافة مسار جديد</h2>
          <button type="button" onclick="closeAddTrackModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
        </div>

        <form action="{{ route('teacher.admin.tracks.add') }}" method="POST" enctype="multipart/form-data">
          @csrf

          <div class="space-y-4">
            <div>
              <label class="block mb-1 font-medium">اسم المسار بالعربية <span class="text-red-500">*</span></label>
              <input type="text" name="name_ar" required class="w-full border rounded px-3 py-2" placeholder="مثال: تطوير الويب المتكامل">
            </div>

            <div>
              <label class="block mb-1 font-medium">Track Name in English <span class="text-red-500">*</span></label>
              <input type="text" name="name_en" required class="w-full border rounded px-3 py-2" placeholder="Example: Full-Stack Web Development">
            </div>

            <div>
              <label class="block mb-1 font-medium">قالب شهادة الذكور (صورة) <span class="text-red-500">*</span></label>
              <input type="file" name="male_certificate" accept="image/jpeg,image/jpg,image/png" required class="w-full border rounded px-3 py-2">
              <p class="text-xs text-gray-500 mt-1">صيغ مقبولة: JPG, JPEG, PNG (حجم أقصى: 10 ميجابايت)</p>
            </div>

            <div>
              <label class="block mb-1 font-medium">قالب شهادة الإناث (صورة) <span class="text-red-500">*</span></label>
              <input type="file" name="female_certificate" accept="image/jpeg,image/jpg,image/png" required class="w-full border rounded px-3 py-2">
              <p class="text-xs text-gray-500 mt-1">صيغ مقبولة: JPG, JPEG, PNG (حجم أقصى: 10 ميجابايت)</p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded p-3 text-sm">
              <p class="font-medium mb-1">ملاحظة:</p>
              <ul class="list-disc mr-5 space-y-1 text-gray-700">
                <li>سيتم إنشاء المسار الجديد بإعدادات افتراضية للمواضع والأحجام</li>
                <li>يمكنك تعديل الإعدادات لاحقاً من خلال اختيار المسار واستخدام المحرر</li>
                <li>تأكد من رفع قالبي الشهادات (ذكور وإناث) بدقة جيدة</li>
              </ul>
            </div>
          </div>

          <div class="flex gap-2 mt-6">
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
              إضافة المسار
            </button>
            <button type="button" onclick="closeAddTrackModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
              إلغاء
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- Track Management JavaScript --}}
  <script>
    function openAddTrackModal() {
      document.getElementById('addTrackModal').classList.remove('hidden');
    }

    function closeAddTrackModal() {
      document.getElementById('addTrackModal').classList.add('hidden');
    }

    function deleteSelectedTrack() {
      const trackSelect = document.getElementById('track-select');
      const selectedOption = trackSelect.options[trackSelect.selectedIndex];

      if (!selectedOption || !selectedOption.value) {
        alert('الرجاء اختيار مسار للحذف');
        return;
      }

      const trackId = selectedOption.getAttribute('data-track-id');
      const trackName = selectedOption.textContent.trim();

      if (!trackId) {
        alert('لا يمكن حذف هذا المسار (مسار من ملف الإعدادات)');
        return;
      }

      if (!confirm(`هل أنت متأكد من حذف المسار: ${trackName}؟\n\nسيتم حذف:\n- بيانات المسار\n- قوالب الشهادات المرفقة\n- جميع الإعدادات المخزنة\n\nهذا الإجراء لا يمكن التراجع عنه.`)) {
        return;
      }

      // Create form and submit
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = `/teacher/admin/tracks/${trackId}`;

      const csrfField = document.createElement('input');
      csrfField.type = 'hidden';
      csrfField.name = '_token';
      csrfField.value = '{{ csrf_token() }}';

      const methodField = document.createElement('input');
      methodField.type = 'hidden';
      methodField.name = '_method';
      methodField.value = 'DELETE';

      form.appendChild(csrfField);
      form.appendChild(methodField);
      document.body.appendChild(form);
      form.submit();
    }

    // Remove photo handler
    const removePhotoBtn = document.getElementById('btn-remove-photo');
    const photoInput = document.querySelector('input[name="photo"]');
    const removePhotoFlag = document.getElementById('remove_photo');

    if (removePhotoBtn && photoInput && removePhotoFlag) {
      removePhotoBtn.addEventListener('click', function() {
        photoInput.value = ''; // Clear the file input
        removePhotoFlag.value = '1'; // Set flag to remove
        alert('سيتم إزالة الصورة في المعاينة أو الإنشاء التالي.');
      });
    }

    // Close modal on outside click
    document.getElementById('addTrackModal')?.addEventListener('click', function(e) {
      if (e.target === this) {
        closeAddTrackModal();
      }
    });
  </script>
</body>
</html>
