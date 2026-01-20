<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>شهادات المعلمين - المحرر المتقدم</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Admin Teacher Theme - Indigo/Purple */
    body {
      background: linear-gradient(135deg, #e0e7ff 0%, #eef2ff 50%, #e0e7ff 100%);
      background-attachment: fixed;
    }
    :root { --ed-width: 1122; } /* A4 landscape at standard scale (297mm ≈ 1122px) */
    .box { position:absolute; border:2px dashed rgba(99,102,241,.9); border-radius:8px; cursor:move; user-select:none; }
    .box .handle { position:absolute; bottom:-8px; right:-8px; width:12px; height:12px; border-radius:50%; background:#6366f1; cursor:nwse-resize; }
    .box .tag { position:absolute; top:-22px; left:0; font-size:11px; background:#fff; border:1px dashed rgba(99,102,241,.5); padding:0 6px; border-radius:6px; }
    .editor-shell { width:fit-content; max-width:none; }
    .editor-scroll { overflow:auto; -webkit-overflow-scrolling:touch; background:#f8fafc; border:1px solid #c7d2fe; border-radius:12px; padding:10px; }
    #canvas-wrap { position:relative; width: calc(var(--ed-width) * 1px); max-width:none; }
    #bg { width:100%; height:auto; display:block; border-radius:10px; }
    #grid { position:absolute; left:0; top:0; right:0; bottom:0; pointer-events:none; display:none;
            background-image: repeating-linear-gradient(0deg, rgba(99,102,241,.12), rgba(99,102,241,.12) 1px, transparent 1px, transparent 20px),
                              repeating-linear-gradient(90deg, rgba(99,102,241,.12), rgba(99,102,241,.12) 1px, transparent 1px, transparent 20px); }
    #preview-iframe { width:100%; height:560px; border:1px solid #c7d2fe; border-radius:10px; background:#fff; }
    .btn { padding:.5rem 1rem; border-radius:.6rem; }
    .kbd { font: 11px/1.6 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; background:#f3f4f6; border:1px solid #e5e7eb; padding:2px 6px; border-radius:6px; }
  </style>
</head>
<body class="min-h-screen text-gray-900">
  @include('layouts.partials.header')

  <div class="max-w-6xl mx-auto p-4 space-y-6">
    <div class="flex justify-between items-center">
      <h1 class="text-xl font-bold">إصدار شهادات المعلمين - المحرر المتقدم</h1>
      @if(auth()->user()->canAccessTeacherSimple())
      <a href="{{ route('teacher.index') }}" class="btn bg-blue-600 text-white hover:bg-blue-700">الواجهة المبسطة</a>
      @endif
    </div>

    {{-- Flash + Errors --}}
    @if(session('success'))
      <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded mb-3 flex items-center justify-between">
        <div>{{ session('success') }}</div>
        @if(session('download_url'))
          <a href="{{ session('download_url') }}" class="btn bg-green-600 text-white hover:bg-green-700">تحميل الشهادة</a>
        @endif
      </div>
    @endif

    @if(session('status'))
      <div class="bg-blue-50 border border-blue-200 text-blue-800 p-3 rounded mb-3">{{ session('status') }}</div>
    @endif

    @if($errors->any())
      <div class="bg-red-50 border border-red-200 text-red-800 p-3 rounded mb-3">
        <div class="font-semibold mb-1">حدثت أخطاء:</div>
        <ul class="list-disc ms-5">
          @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
        </ul>
      </div>
    @endif

    <div id="flash-area" class="hidden bg-red-50 border border-red-200 text-red-800 p-3 rounded mb-3"></div>

    {{-- ===== ONE FORM ===== --}}
    <form id="teacher-form"
          action="{{ route('teacher.admin.store') }}"
          method="post"
          enctype="multipart/form-data"
          class="bg-white p-4 rounded shadow space-y-4">
      @csrf

      <div class="grid md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">المسار (Track)</label>
          <div class="flex gap-2">
            <select name="track_key" id="track_key" class="flex-1 border rounded px-3 py-2" required>
              @foreach(($tracks ?? []) as $key => $pair)
                <option value="{{ $key }}" data-track-id="{{ $pair['id'] ?? '' }}">{{ $pair['ar'] ?? $pair['en'] ?? $key }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">إدارة المسارات</label>
          <div class="flex gap-1 flex-wrap">
            <button type="button" onclick="openAddTrackModal()" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 whitespace-nowrap text-sm">إضافة</button>
            @if(auth()->user()->hasPermission(\App\Models\Permission::TRACKS_EDIT))
            <button type="button" onclick="openEditTrackModal()" class="px-3 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 whitespace-nowrap text-sm">تعديل</button>
            @endif
            <button type="button" onclick="deleteSelectedTrack()" class="px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 whitespace-nowrap text-sm">حذف</button>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">الجنس</label>
          <select name="gender" id="gender" class="w-full border rounded px-3 py-2" required>
            <option value="male">ذكر</option>
            <option value="female">أنثى</option>
          </select>
        </div>
      </div>

      {{-- Name Inputs --}}
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">الاسم بالعربية <span class="text-red-500">*</span></label>
          <input type="text" name="name_ar" value="أحمد محمد" required class="w-full border rounded px-3 py-2" placeholder="مثال: أحمد محمد">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Name in English <span class="text-red-500">*</span></label>
          <input type="text" name="name_en" value="Ahmed Mohammed" required class="w-full border rounded px-3 py-2" placeholder="Example: Ahmed Mohammed">
        </div>
      </div>

      {{-- Photo Upload --}}
      <div>
        <label class="block text-sm font-medium mb-1">صورة المعلم (اختياري)</label>
        <input type="file" name="photo" id="teacher_photo" accept="image/*" class="w-full border rounded px-3 py-2">
        <input type="hidden" id="remove_photo" name="remove_photo" value="0">
        <div class="mt-2">
          <button type="button" id="btn-remove-photo" class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 text-sm">
            إزالة الصورة
          </button>
        </div>
      </div>

      {{-- Date Type / Range Option --}}
      <div class="bg-gray-50 border border-gray-200 rounded p-3">
        <h4 class="text-sm font-semibold mb-2">نوع التاريخ</h4>
        <div class="grid md:grid-cols-3 gap-4">
          <div class="col-span-1">
            <label class="block text-sm font-medium mb-1">اختر نوع التاريخ</label>
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
      </div>

      {{-- Print Flags --}}
      <div class="bg-gray-50 border border-gray-200 rounded p-3">
        <h4 class="text-sm font-semibold mb-2">خيارات الطباعة</h4>

        <div class="flex gap-4 flex-wrap items-center mb-3">
          <label class="flex items-center gap-2">
            <input type="checkbox" id="arabic_only" name="arabic_only" value="1">
            <span class="text-sm">العربية فقط</span>
          </label>
          <label class="flex items-center gap-2">
            <input type="checkbox" id="english_only" name="english_only" value="1">
            <span class="text-sm">الإنجليزية فقط</span>
          </label>
          <small class="text-gray-500">(إذا اخترت أحدهما سيتم تعطيل الآخر تلقائيًا)</small>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
          <div>
            <input type="hidden" name="print[ar_name]" value="0">
            <label class="flex items-center gap-2">
              <input type="checkbox" name="print[ar_name]" value="1" class="print-flag print-flag-ar" checked>
              <span>الاسم (عربي)</span>
            </label>
          </div>
          <div>
            <input type="hidden" name="print[ar_track]" value="0">
            <label class="flex items-center gap-2">
              <input type="checkbox" name="print[ar_track]" value="1" class="print-flag print-flag-ar" checked>
              <span>المسار (عربي)</span>
            </label>
          </div>
          <div>
            <input type="hidden" name="print[ar_from]" value="0">
            <label class="flex items-center gap-2">
              <input type="checkbox" name="print[ar_from]" value="1" class="print-flag print-flag-ar" checked>
              <span>من التاريخ (عربي)</span>
            </label>
          </div>
          <div>
            <input type="hidden" name="print[en_name]" value="0">
            <label class="flex items-center gap-2">
              <input type="checkbox" name="print[en_name]" value="1" class="print-flag print-flag-en" checked>
              <span>الاسم (إنجليزي)</span>
            </label>
          </div>
          <div>
            <input type="hidden" name="print[en_track]" value="0">
            <label class="flex items-center gap-2">
              <input type="checkbox" name="print[en_track]" value="1" class="print-flag print-flag-en" checked>
              <span>المسار (إنجليزي)</span>
            </label>
          </div>
          <div>
            <input type="hidden" name="print[en_from]" value="0">
            <label class="flex items-center gap-2">
              <input type="checkbox" name="print[en_from]" value="1" class="print-flag print-flag-en" checked>
              <span>From date (English)</span>
            </label>
          </div>
        </div>
      </div>

      {{-- Advanced Options --}}
      <div class="bg-white border border-gray-200 rounded p-3">
        <button type="button" id="adv-toggle" class="px-3 py-2 rounded bg-slate-700 text-white hover:bg-slate-800 text-sm">
          خيارات إضافية (الألوان والخطوط)
        </button>

        <div id="adv-panel" class="mt-4 hidden">
          <div class="text-sm text-slate-600 mb-3">
            اضبط لون الخط، نوع الخط، وحجم الخط لكل نص على حدة. التغييرات تظهر فوراً في المعاينة.
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full text-sm border">
              <thead>
                <tr class="bg-gray-50 border-b">
                  <th class="text-right py-2 px-2">الحقل</th>
                  <th class="text-right py-2 px-2">اللون</th>
                  <th class="text-right py-2 px-2">نوع الخط</th>
                  <th class="text-right py-2 px-2">الحجم (mm)</th>
                  <th class="text-right py-2 px-2">سميك</th>
                  <th class="text-right py-2 px-2">إعادة الضبط</th>
                </tr>
              </thead>
              <tbody>
                @php
                  $advFields = [
                    ['key'=>'cert_date','label'=>'تاريخ الشهادة'],
                    ['key'=>'ar_name','label'=>'الاسم (عربي)'],
                    ['key'=>'ar_track','label'=>'المسار (عربي)'],
                    ['key'=>'ar_from','label'=>'التاريخ (عربي)'],
                    ['key'=>'en_name','label'=>'Name (EN)'],
                    ['key'=>'en_track','label'=>'Track (EN)'],
                    ['key'=>'en_from','label'=>'Date (EN)'],
                  ];
                @endphp
                @foreach ($advFields as $field)
                  <tr class="border-b" data-adv-row data-key="{{ $field['key'] }}">
                    <td class="py-2 px-2">{{ $field['label'] }}</td>
                    <td class="py-2 px-2">
                      <input type="color" name="style[colors][{{ $field['key'] }}]" value="#111827" class="adv-color border rounded w-16">
                    </td>
                    <td class="py-2 px-2">
                      <select name="style[font_per][{{ $field['key'] }}]" class="adv-font border rounded px-2 py-1">
                        <option value="">(استخدام الافتراضي)</option>
                        @foreach ($fonts as $font)
                          <option value="{{ $font }}">{{ $font }}</option>
                        @endforeach
                      </select>
                    </td>
                    <td class="py-2 px-2">
                      <input type="number" step="0.1" min="3" max="20" placeholder="6.0" name="style[size_per][{{ $field['key'] }}]" class="adv-size border rounded px-2 py-1 w-20">
                    </td>
                    <td class="py-2 px-2">
                      <select name="style[weight_per][{{ $field['key'] }}]" class="adv-weight border rounded px-2 py-1 text-xs">
                        <option value="">(افتراضي)</option>
                        <option value="300">Light</option>
                        <option value="400">Regular</option>
                        <option value="700">Bold</option>
                      </select>
                    </td>
                    <td class="py-2 px-2">
                      <button type="button" class="adv-reset px-2 py-1 border rounded bg-gray-100 hover:bg-gray-200 text-xs" data-reset="{{ $field['key'] }}">Reset</button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{-- Toolbar ABOVE editor --}}
      <div class="flex flex-wrap items-center gap-3">
        <button type="button" id="btn-preview" class="btn bg-indigo-600 text-white hover:bg-indigo-700">معاينة PDF</button>
        <button type="submit" class="btn bg-green-600 text-white hover:bg-green-700">إنشاء شهادة PDF</button>

        {{-- Image generation buttons --}}
        <button type="button" id="btn-preview-image" class="btn bg-purple-600 text-white hover:bg-purple-700">معاينة صورة</button>
        <button type="button" id="btn-generate-image" class="btn bg-teal-600 text-white hover:bg-teal-700">إنشاء شهادة صورة</button>

        <button type="button" id="btn-save-defaults" class="btn bg-amber-600 text-white hover:bg-amber-700">حفظ الإعدادات الافتراضية</button>

        <div class="ms-auto flex flex-wrap items-center gap-3 text-sm">
          <label class="flex items-center gap-2">
            <input type="checkbox" id="toggle-grid"> إظهار الشبكة
          </label>
          <label class="flex items-center gap-2">
            <input type="checkbox" id="lock-photo" checked> قفل نسب الصورة
          </label>

          <div class="flex items-center gap-2">
            حجم المحرر:
            <input type="range" id="zoom" min="800" max="2600" step="10" value="1122" class="w-40">
            <span id="zoom-label" class="tabular-nums">1122px</span>
          </div>
          <div class="flex items-center gap-2">
            <button type="button" id="zoom-100" class="btn bg-white border hover:bg-gray-50">100%</button>
            <button type="button" id="zoom-150" class="btn bg-white border hover:bg-gray-50">150%</button>
            <button type="button" id="zoom-200" class="btn bg-white border hover:bg-gray-50">200%</button>
            <button type="button" id="fit-width" class="btn bg-white border hover:bg-gray-50">ملء العرض</button>
            <button type="button" id="fullscreen" class="btn bg-white border hover:bg-gray-50">شاشة كاملة</button>
          </div>

          <label class="flex items-center gap-2">
            <input type="checkbox" id="ignore-cache">
            تجاهل الحفظ المحلي
          </label>
          <button type="button" id="btn-defaults" class="btn bg-white border hover:bg-gray-50">استخدم مواقع القالب</button>
          <button type="button" id="btn-reset" class="btn bg-white border hover:bg-gray-50">إرجاع الإحداثيات</button>
        </div>
      </div>

      {{-- Position editor --}}
      <div class="editor-scroll">
        <div id="editor-shell" class="editor-shell">
          <div id="canvas-wrap" class="rounded">
            <img id="bg" src="" alt="Template background" />
            <div id="grid"></div>
            <!-- boxes created dynamically -->
          </div>
        </div>
      </div>

      {{-- Defaults inspector --}}
      <details id="defaults-panel" class="bg-gray-50 border border-gray-200 rounded p-3">
        <summary class="cursor-pointer text-sm font-semibold">إظهار القيم الافتراضية (من القالب)</summary>
        <div id="defaults-body" class="mt-2 text-xs leading-6 whitespace-pre-wrap"></div>
      </details>

      <input type="hidden" id="custom_positions" name="custom_positions" value="{}">
      <input type="date"   name="certificate_date" class="hidden">
    </form>

    {{-- Real PDF preview --}}
    <div class="bg-white p-4 rounded shadow space-y-4">
      <div class="text-sm font-semibold">المعاينة الحقيقية:</div>
      <iframe id="preview-iframe" class="w-full h-[70vh] border rounded"></iframe>
    </div>
  </div>

<script>
(function(){
  /* ==================== Helpers ==================== */
  const tplMap  = @json($tplMap ?? []);
  const byId    = id => document.getElementById(id);
  const form    = byId('teacher-form');
  const wrap    = byId('canvas-wrap');
  const shell   = byId('editor-shell');
  const bgImg   = byId('bg');
  const grid    = byId('grid');
  const selTrack= byId('track_key');
  const selGender=byId('gender');
  const flash   = byId('flash-area');
  const iframe  = byId('preview-iframe');
  const zoom    = byId('zoom');
  const zoomLabel = byId('zoom-label');
  const defaultsBody = byId('defaults-body');
  const ignoreCache = byId('ignore-cache');

  const labelMap = {
    ar_name:'الاسم العربي', en_name:'English Name', photo:'PHOTO',
    ar_track:'اسم المسار (ع)', en_track:'Track (En)',
    cert_date:'تاريخ إصدار الشهادة', ar_from:'من (ع)', en_from:'from (En)',
    level:'المستوى', hours:'الساعات', grade:'التقدير'
  };

  function showFlash(msg){
    flash.textContent = msg;
    flash.classList.remove('hidden');
    setTimeout(()=> flash.classList.add('hidden'), 7000);
  }

  function currentTpl(){ return (tplMap[selTrack.value]||{})[selGender.value] || null; }
  function refreshBg(){ const t=currentTpl(); bgImg.src = t?.bg_url || ''; }
  selTrack.addEventListener('change', ()=>{ refreshBg(); rebuildFromTemplate(true); });
  selGender.addEventListener('change', ()=>{ refreshBg(); rebuildFromTemplate(true); });
  refreshBg();

  /* ==================== Boxes ==================== */
  let boxes = []; // [{key, el}]
  function removeBoxes(){ boxes.forEach(b=>b.el.remove()); boxes=[]; }

  // Clamp helper
  function c01(x){ return Math.max(0, Math.min(1, x)); }

  function mmRectToPercent(r, page){
    const W = page.w, H = page.h;

    let widthMM;
    if (r.width !== undefined) {
      widthMM = +r.width;
    } else if (r.left !== undefined && r.right !== undefined) {
      widthMM = W - (+r.left) - (+r.right);
    } else {
      widthMM = W * 0.30;
    }
    if (!isFinite(widthMM) || widthMM <= 0) widthMM = W * 0.30;

    let heightMM;
    if (r.height !== undefined) {
      heightMM = +r.height;
    } else if (r.top !== undefined && r.bottom !== undefined) {
      heightMM = H - (+r.top) - (+r.bottom);
    } else {
      heightMM = H * 0.05;
    }
    if (!isFinite(heightMM) || heightMM <= 0) heightMM = H * 0.05;

    let leftMM;
    if (r.left !== undefined) {
      leftMM = +r.left;
    } else if (r.right !== undefined) {
      leftMM = W - (+r.right) - widthMM;
    } else {
      leftMM = 0;
    }

    let topMM;
    if (r.top !== undefined) {
      topMM = +r.top;
    } else if (r.bottom !== undefined) {
      topMM = H - (+r.bottom) - heightMM;
    } else {
      topMM = 0;
    }

    leftMM = Math.max(0, Math.min(W - widthMM, leftMM));
    topMM  = Math.max(0, Math.min(H - heightMM, topMM));

    let lp = c01(leftMM / W);
    let tp = c01(topMM  / H);
    let wp = c01(widthMM  / W);
    let hp = c01(heightMM / H);

    if (lp + wp > 1) lp = c01(1 - wp);
    if (tp + hp > 1) tp = c01(1 - hp);

    return { left_pct: lp, top_pct: tp, width_pct: wp, height_pct: hp, font: r.font || undefined };
  }

  function pctToPx(p, total){ return (total||0) * (p||0); }

  function percentToMm(pct, page){
    const W = page.w || 297;
    const H = page.h || 210;

    const leftMM = (pct.left_pct || 0) * W;
    const topMM = (pct.top_pct || 0) * H;
    const widthMM = (pct.width_pct || 0) * W;

    const t = currentTpl();
    const originalPos = (t && t.positions && t.positions[pct.key]) || {};
    const usesRight = originalPos.right !== undefined && originalPos.left === undefined;

    const result = {
      top: parseFloat(topMM.toFixed(2)),
      width: parseFloat(widthMM.toFixed(2))
    };

    if (usesRight) {
      const rightMM = W - leftMM - widthMM;
      result.right = parseFloat(rightMM.toFixed(2));
    } else {
      result.left = parseFloat(leftMM.toFixed(2));
    }

    if (pct.font !== undefined) {
      result.font = pct.font;
    }

    return result;
  }

  function buildBox(key, pct){
    const w = wrap.clientWidth;
    const h = bgImg.clientHeight || wrap.clientHeight;
    const el = document.createElement('div');
    el.className = 'box';
    el.id = 'box-'+key;
    el.dataset.key = key;
    el.style.left   = pctToPx(pct.left_pct,  w) + 'px';
    el.style.top    = pctToPx(pct.top_pct,   h) + 'px';
    el.style.width  = pctToPx(pct.width_pct, w) + 'px';
    el.style.height = pctToPx(pct.height_pct,h) + 'px';
    el.innerHTML = `<span class="tag">${labelMap[key] || key}</span><div class="handle"></div>`;
    wrap.appendChild(el);
    makeDraggable(el, key==='photo');
    boxes.push({key, el});
  }

  function inspectorDump(templatePos, page){
    const w = wrap.clientWidth, h = bgImg.clientHeight || wrap.clientHeight;
    const lines = [];
    lines.push(`Page mm: W=${page.w} H=${page.h}`);
    Object.entries(templatePos||{}).forEach(([key, r])=>{
      if (!r) return;
      const pct = mmRectToPercent(r, page);
      const px  = {
        left:  Math.round(pct.left_pct  * w),
        top:   Math.round(pct.top_pct   * h),
        width: Math.round(pct.width_pct * w),
        height:Math.round(pct.height_pct* h),
      };
      lines.push(
        `• ${key}  mm=${JSON.stringify(r)}  %={l:${(pct.left_pct*100).toFixed(1)} t:${(pct.top_pct*100).toFixed(1)} w:${(pct.width_pct*100).toFixed(1)} h:${(pct.height_pct*100).toFixed(1)}}  px=${JSON.stringify(px)}`
      );
    });
    defaultsBody.textContent = lines.join('\n');
  }

  function localKey(){ return 'teacher-pos:'+selTrack.value+':'+selGender.value; }

  function drawFromJson(json){
    removeBoxes();
    for (const [key, pct] of Object.entries(json)) buildBox(key, pct);
  }

  function rebuildFromTemplate(forceTemplate=false){
    const t = currentTpl(); if(!t) return;
    const page = t.page_mm || {w:297,h:210};

    inspectorDump(t.positions || {}, page);

    const canUseCache = !forceTemplate && !ignoreCache.checked;
    if (canUseCache) {
      const saved = localStorage.getItem(localKey());
      if (saved) { drawFromJson(JSON.parse(saved)); savePositions(true); return; }
    }

    removeBoxes();
    const pos = t.positions || {};
    for (const [key, rect] of Object.entries(pos)) {
      if (!rect) continue;
      buildBox(key, mmRectToPercent(rect, page));
    }
    savePositions(true);
  }

  bgImg.addEventListener('load', ()=>{
    rebuildFromTemplate(true);
    applyWidth(parseInt(getComputedStyle(document.documentElement).getPropertyValue('--ed-width')) || 1000, true);
  });

  /* ==================== Drag/Resize/Keyboard ==================== */
  function makeDraggable(el, keepAspect=false){
    let startX,startY,startL,startT,startW,startH,resizing=false;
    const handle = el.querySelector('.handle');

    el.addEventListener('mousedown',(e)=>{
      resizing = (e.target===handle);
      const rect = el.getBoundingClientRect();
      const wrect= wrap.getBoundingClientRect();
      startX = e.clientX; startY = e.clientY;
      startL = rect.left - wrect.left; startT = rect.top - wrect.top;
      startW = rect.width; startH = rect.height;
      document.addEventListener('mousemove', move);
      document.addEventListener('mouseup', up, {once:true});
      e.preventDefault();
    });

    function move(e){
      const w = wrap.clientWidth;
      const h = bgImg.clientHeight || wrap.clientHeight;
      const dx=e.clientX-startX, dy=e.clientY-startY;
      if(resizing){
        if (keepAspect || byId('lock-photo').checked){
          const k = startH / (startW || 1);
          const newW = Math.max(10, startW + dx);
          el.style.width  = newW + 'px';
          el.style.height = Math.max(10, newW * k) + 'px';
        }else{
          el.style.width  = Math.max(10, startW + dx) + 'px';
          el.style.height = Math.max(10, startH + dy) + 'px';
        }
      }else{
        el.style.left = Math.max(0, Math.min(w-10, startL+dx)) + 'px';
        el.style.top  = Math.max(0, Math.min(h-10, startT+dy)) + 'px';
      }
    }
    function up(){ document.removeEventListener('mousemove', move); savePositions(); }
  }

  // Keyboard nudging
  let activeBox = null;
  wrap.addEventListener('click', (e)=>{
    const b = e.target.closest('.box'); if (!b) return;
    activeBox = b;
  });
  document.addEventListener('keydown', (e)=>{
    if (!activeBox) return;
    const step = e.shiftKey ? 10 : 1;
    if (['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(e.key)) e.preventDefault();
    if (e.key==='ArrowLeft')  activeBox.style.left  = (activeBox.offsetLeft-step)+'px';
    if (e.key==='ArrowRight') activeBox.style.left  = (activeBox.offsetLeft+step)+'px';
    if (e.key==='ArrowUp')    activeBox.style.top   = (activeBox.offsetTop-step)+'px';
    if (e.key==='ArrowDown')  activeBox.style.top   = (activeBox.offsetTop+step)+'px';
    if (['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(e.key)) savePositions();
  });

  /* ==================== Save / Load ==================== */
  function pxToPct(px, total){ return Math.max(0, Math.min(1, total ? (px / total) : 0)); }

  function savePositions(silent=false){
    const w = wrap.clientWidth;
    const h = bgImg.clientHeight || wrap.clientHeight;
    const out = {};
    boxes.forEach(({key,el})=>{
      out[key] = {
        left_pct:  pxToPct(el.offsetLeft,  w),
        top_pct:   pxToPct(el.offsetTop,   h),
        width_pct: pxToPct(el.offsetWidth, w),
        height_pct:pxToPct(el.offsetHeight,h),
      };
    });

    byId('custom_positions').value = JSON.stringify(out);

    if (!ignoreCache.checked && !silent) {
      try { localStorage.setItem(localKey(), JSON.stringify(out)); } catch {}
    }
  }

  function convertPositionsToMm(){
    const t = currentTpl();
    const page = (t && t.page_mm) || {w:297, h:210};

    const pctPositions = JSON.parse(byId('custom_positions').value || '{}');
    const mmPositions = {};

    for (const [key, pct] of Object.entries(pctPositions)) {
      pct.key = key;

      const sizeInput = document.querySelector(`tr[data-key="${key}"] .adv-size`);
      if (sizeInput && sizeInput.value) {
        pct.font = parseFloat(sizeInput.value);
      } else if (t && t.positions && t.positions[key] && t.positions[key].font) {
        pct.font = t.positions[key].font;
      }

      mmPositions[key] = percentToMm(pct, page);
    }

    byId('custom_positions').value = JSON.stringify(mmPositions);
  }

  function reapplyFromHidden(){
    try {
      const j = JSON.parse(byId('custom_positions').value || '{}');
      boxes.forEach(({key,el})=>{
        const d = j[key]; if(!d) return;
        const w = wrap.clientWidth, h = bgImg.clientHeight || wrap.clientHeight;
        el.style.left   = (d.left_pct  * w) + 'px';
        el.style.top    = (d.top_pct   * h) + 'px';
        el.style.width  = (d.width_pct * w) + 'px';
        el.style.height = (d.height_pct* h) + 'px';
      });
    }catch{}
  }

  /* ==================== Zoom / Width ==================== */
  function applyWidth(px, silent){
    document.documentElement.style.setProperty('--ed-width', String(px));
    zoom.value = px; zoomLabel.textContent = px + 'px';
    setTimeout(()=> reapplyFromHidden(), 30);
    if (!silent) savePositions(true);
  }
  zoom.addEventListener('input', ()=> applyWidth(parseInt(zoom.value||'1122',10)));
  applyWidth(parseInt(zoom.value||'1122',10), true);

  byId('zoom-100').addEventListener('click', ()=> applyWidth(1122));  // 100% = A4 landscape standard
  byId('zoom-150').addEventListener('click', ()=> applyWidth(1683));  // 150% of 1122
  byId('zoom-200').addEventListener('click', ()=> applyWidth(2244));  // 200% of 1122
  byId('fit-width').addEventListener('click', ()=>{
    const vw = Math.max(document.documentElement.clientWidth||0, window.innerWidth||0);
    const px = Math.max(800, Math.min(2600, vw - 80));
    applyWidth(px);
  });
  byId('fullscreen').addEventListener('click', async ()=>{
    try {
      if (!document.fullscreenElement) await shell.requestFullscreen();
      else await document.exitFullscreen();
      setTimeout(()=> byId('fit-width').click(), 50);
    } catch {}
  });

  /* ==================== Toolbar actions ==================== */
  byId('toggle-grid').addEventListener('change', e=> grid.style.display = e.target.checked ? 'block':'none');

  byId('btn-reset').addEventListener('click', ()=>{
    try { localStorage.removeItem(localKey()); } catch {}
    rebuildFromTemplate(true);
  });

  byId('btn-defaults').addEventListener('click', ()=>{
    rebuildFromTemplate(true);
  });

  // Preview PDF
  byId('btn-preview').addEventListener('click', async ()=>{
    savePositions();
    const pctBackup = byId('custom_positions').value;
    convertPositionsToMm();
    flash.classList.add('hidden');
    const fd = new FormData(form);
    byId('custom_positions').value = pctBackup;
    try{
      const res = await fetch('{{ route('teacher.admin.preview') }}', {
        method:'POST',
        body:fd,
        headers:{
          'X-Requested-With':'fetch',
          'Accept':'application/pdf',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
      });
      if (!res.ok){
        let msg='حدث خطأ غير متوقع أثناء المعاينة.';
        const clonedRes = res.clone();
        try {
          const j = await res.json();
          if (j?.message) msg=j.message;
          if (j?.errors) { const lines=[]; Object.values(j.errors).forEach(a=>a.forEach(v=>lines.push(v))); if (lines.length) msg = lines.join(' • '); }
        } catch {
          try {
            const t = await clonedRes.text();
            if (t) msg = t.slice(0,400);
          } catch(e2) {}
        }
        showFlash(msg); return;
      }
      const blob = await res.blob(); iframe.src = URL.createObjectURL(blob);
    } catch(e){ console.error(e); showFlash('حدث خطأ غير متوقع أثناء المعاينة.'); }
  });

  // Preview Image
  byId('btn-preview-image').addEventListener('click', async ()=>{
    savePositions();
    const pctBackup = byId('custom_positions').value;
    convertPositionsToMm();
    flash.classList.add('hidden');
    const fd = new FormData(form);
    byId('custom_positions').value = pctBackup;
    try{
      const res = await fetch('{{ route('teacher.admin.preview.image') }}', {
        method:'POST',
        body:fd,
        headers:{
          'X-Requested-With':'fetch',
          'Accept':'image/png',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
      });
      if (!res.ok){
        let msg='حدث خطأ غير متوقع أثناء معاينة الصورة.';
        const clonedRes = res.clone();
        try {
          const j = await res.json();
          if (j?.message) msg=j.message;
          if (j?.errors) { const lines=[]; Object.values(j.errors).forEach(a=>a.forEach(v=>lines.push(v))); if (lines.length) msg = lines.join(' • '); }
        } catch {
          try {
            const t = await clonedRes.text();
            if (t) msg = t.slice(0,400);
          } catch(e2) {}
        }
        showFlash(msg); return;
      }
      const blob = await res.blob(); iframe.src = URL.createObjectURL(blob);
    } catch(e){ console.error(e); showFlash('حدث خطأ غير متوقع أثناء معاينة الصورة.'); }
  });

  // Generate Image
  byId('btn-generate-image').addEventListener('click', async ()=>{
    savePositions();
    convertPositionsToMm();
    flash.classList.add('hidden');
    const fd = new FormData(form);
    try{
      const res = await fetch('{{ route('teacher.admin.store.image') }}', {
        method:'POST',
        body:fd,
        headers:{
          'X-Requested-With':'fetch',
          'Accept':'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
      });
      if (!res.ok){
        let msg='حدث خطأ أثناء إنشاء الصورة.';
        try {
          const j = await res.json();
          if (j?.message) msg=j.message;
        } catch {}
        showFlash(msg); return;
      }
      const result = await res.json();
      if (result.success && result.download_url) {
        showFlash(result.message || 'تم إنشاء الصورة بنجاح!');
        setTimeout(() => {
          window.location.href = result.download_url;
        }, 500);
      } else {
        showFlash(result.message || 'حدث خطأ غير متوقع');
      }
    } catch(e){ console.error(e); showFlash('حدث خطأ غير متوقع أثناء إنشاء الصورة.'); }
  });

  // Include positions when generating
  form.addEventListener('submit', ()=> {
    savePositions();
    convertPositionsToMm();
  });

  // ==================== Print Flags Logic ====================
  const arabicOnly = byId('arabic_only');
  const englishOnly = byId('english_only');
  const arFlags = document.querySelectorAll('.print-flag-ar');
  const enFlags = document.querySelectorAll('.print-flag-en');

  arabicOnly.addEventListener('change', ()=>{
    if (arabicOnly.checked) {
      englishOnly.checked = false;
      arFlags.forEach(f=>f.checked=true);
      enFlags.forEach(f=>f.checked=false);
    }
  });

  englishOnly.addEventListener('change', ()=>{
    if (englishOnly.checked) {
      arabicOnly.checked = false;
      enFlags.forEach(f=>f.checked=true);
      arFlags.forEach(f=>f.checked=false);
    }
  });

  // ==================== Duration Mode Toggle ====================
  const durationRadios = document.querySelectorAll('input[name="duration_mode"]');
  const durFromWrap = byId('dur-from-wrap');
  const durToLabel = byId('dur-to-label');

  function applyDurationMode() {
    const mode = Array.from(durationRadios).find(r => r.checked)?.value || 'range';
    if (mode === 'end') {
      durFromWrap.style.display = 'none';
      durToLabel.textContent = 'في';
    } else {
      durFromWrap.style.display = '';
      durToLabel.textContent = 'إلى';
    }
  }
  durationRadios.forEach(r => r.addEventListener('change', applyDurationMode));
  applyDurationMode();

  // ==================== Advanced Options Toggle ====================
  const advToggle = byId('adv-toggle');
  const advPanel = byId('adv-panel');
  if (advToggle && advPanel) {
    advToggle.addEventListener('click', ()=> advPanel.classList.toggle('hidden'));
  }

  // ==================== Load Saved Settings from Database ====================
  let savedSettings = null;

  async function loadSavedSettings() {
    const trackKey = selTrack.value;
    const gender = selGender.value;
    if (!trackKey) return;

    try {
      const url = '{{ route('template.info') }}?role=teacher&track_key=' + encodeURIComponent(trackKey) + '&gender=' + encodeURIComponent(gender);
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) return;
      const data = await res.json();
      if (!data.success) return;

      savedSettings = data;

      const style = data.style || {};
      const colors = style.colors || {};
      const fontPer = style.font_per || {};
      const weightPer = style.weight_per || {};
      const sizePer = style.size_per || {};
      const positions = data.positions || {};

      document.querySelectorAll('tr[data-adv-row]').forEach(row => {
        const key = row.getAttribute('data-key');

        const colorInput = row.querySelector('.adv-color');
        if (colorInput && colors[key]) {
          colorInput.value = colors[key];
        }

        const fontSelect = row.querySelector('.adv-font');
        if (fontSelect && fontPer[key]) {
          fontSelect.value = fontPer[key];
        }

        const sizeInput = row.querySelector('.adv-size');
        if (sizeInput && positions[key] && typeof positions[key].font === 'number') {
          sizeInput.value = positions[key].font;
        }

        const weightSelect = row.querySelector('.adv-weight');
        if (weightSelect && weightPer[key]) {
          const weight = (typeof weightPer[key] === 'string')
            ? (weightPer[key].toLowerCase() === 'bold' ? '700' : (weightPer[key].toLowerCase() === 'light' ? '300' : '400'))
            : String(weightPer[key]);
          weightSelect.value = weight;
        }
      });

      const dateType = data.date_type || 'duration';
      const modeValue = (dateType === 'end') ? 'end' : 'range';
      durationRadios.forEach(r => {
        r.checked = (r.value === modeValue);
      });
      applyDurationMode();

    } catch(e) {
      console.error('Error loading saved settings:', e);
    }
  }

  selTrack.addEventListener('change', loadSavedSettings);
  selGender.addEventListener('change', loadSavedSettings);

  if (selTrack.value) {
    loadSavedSettings();
  }

  // ==================== Reset Functionality ====================
  document.querySelectorAll('button.adv-reset').forEach(btn => {
    btn.addEventListener('click', () => {
      const key = btn.dataset.reset;
      const row = btn.closest('tr[data-adv-row]');
      if (!row || !savedSettings) return;

      const style = savedSettings.style || {};
      const colors = style.colors || {};
      const fontPer = style.font_per || {};
      const weightPer = style.weight_per || {};
      const positions = savedSettings.positions || {};

      const colorInput = row.querySelector('.adv-color');
      if (colorInput) {
        const defaultColors = {
          cert_date: '#0f172a', ar_name: '#334155', ar_track: '#0891b2', ar_from: '#0891b2',
          en_name: '#0f172a', en_track: '#0891b2', en_from: '#0f172a'
        };
        colorInput.value = colors[key] || defaultColors[key] || '#111827';
      }

      const fontSelect = row.querySelector('.adv-font');
      if (fontSelect) {
        fontSelect.value = fontPer[key] || '';
      }

      const sizeInput = row.querySelector('.adv-size');
      if (sizeInput) {
        const defaultSize = (positions[key] && typeof positions[key].font === 'number')
          ? positions[key].font
          : (key.startsWith('en_') ? 5.5 : 6.0);
        sizeInput.value = defaultSize;
      }

      const weightSelect = row.querySelector('.adv-weight');
      if (weightSelect) {
        let weight = '400';
        if (weightPer[key]) {
          weight = (typeof weightPer[key] === 'string')
            ? (weightPer[key].toLowerCase() === 'bold' ? '700' : (weightPer[key].toLowerCase() === 'light' ? '300' : '400'))
            : String(weightPer[key]);
        }
        weightSelect.value = weight;
      }
    });
  });

  // ==================== Remove Photo ====================
  byId('btn-remove-photo').addEventListener('click', ()=>{
    const photoInput = byId('teacher_photo');
    const removeFlag = byId('remove_photo');
    if (photoInput) {
      photoInput.value = ''; // Clear the file input
      removeFlag.value = '1'; // Set flag to remove
      showFlash('سيتم إزالة الصورة في المعاينة أو الإنشاء التالي.', 'success');
    }
  });

  // ==================== Save Defaults ====================
  byId('btn-save-defaults').addEventListener('click', async ()=>{
    savePositions();
    const pctBackup = byId('custom_positions').value;
    convertPositionsToMm();
    const fd = new FormData(form);
    byId('custom_positions').value = pctBackup;
    // Remove photo for save operation
    fd.delete('photo');
    fd.delete('remove_photo');

    try {
      const res = await fetch('{{ route('teacher.admin.save') }}', {
        method: 'POST',
        body: fd,
        headers: {'X-Requested-With':'fetch'}
      });

      if (!res.ok) {
        const j = await res.json().catch(()=>({message:'خطأ غير متوقع'}));
        alert('خطأ: ' + (j.message || 'فشل حفظ الإعدادات'));
        return;
      }

      const result = await res.json();
      alert(result.message || 'تم حفظ الإعدادات الافتراضية بنجاح');

      await loadSavedSettings();
    } catch(e) {
      console.error(e);
      alert('حدث خطأ أثناء حفظ الإعدادات');
    }
  });
})();
</script>

{{-- Add Track Modal --}}
<div id="addTrackModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
  <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
    <div class="p-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">إضافة مسار جديد للمعلمين</h2>
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

{{-- Edit Track Modal --}}
@if(auth()->user()->hasPermission(\App\Models\Permission::TRACKS_EDIT))
<div id="editTrackModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
  <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
    <div class="p-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">تعديل المسار</h2>
        <button type="button" onclick="closeEditTrackModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
      </div>

      <form id="editTrackForm" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="space-y-4">
          <div>
            <label class="block mb-1 font-medium">اسم المسار بالعربية <span class="text-red-500">*</span></label>
            <input type="text" name="name_ar" id="edit_name_ar" required class="w-full border rounded px-3 py-2">
          </div>

          <div>
            <label class="block mb-1 font-medium">Track Name in English <span class="text-red-500">*</span></label>
            <input type="text" name="name_en" id="edit_name_en" required class="w-full border rounded px-3 py-2">
          </div>

          <div>
            <label class="block mb-1 font-medium">قالب شهادة الذكور (صورة)</label>
            <input type="file" name="male_certificate" accept="image/jpeg,image/jpg,image/png" class="w-full border rounded px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">اتركه فارغاً للإبقاء على القالب الحالي</p>
          </div>

          <div>
            <label class="block mb-1 font-medium">قالب شهادة الإناث (صورة)</label>
            <input type="file" name="female_certificate" accept="image/jpeg,image/jpg,image/png" class="w-full border rounded px-3 py-2">
            <p class="text-xs text-gray-500 mt-1">اتركه فارغاً للإبقاء على القالب الحالي</p>
          </div>
        </div>

        <div class="flex gap-2 mt-6">
          <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
            حفظ التغييرات
          </button>
          <button type="button" onclick="closeEditTrackModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
            إلغاء
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

{{-- Track Management JavaScript --}}
<script>
  function openAddTrackModal() {
    document.getElementById('addTrackModal').classList.remove('hidden');
  }

  function closeAddTrackModal() {
    document.getElementById('addTrackModal').classList.add('hidden');
  }

  function openEditTrackModal() {
    const trackSelect = document.getElementById('track_key');
    const selectedOption = trackSelect.options[trackSelect.selectedIndex];

    if (!selectedOption || !selectedOption.value) {
      alert('الرجاء اختيار مسار للتعديل');
      return;
    }

    const trackId = selectedOption.getAttribute('data-track-id');

    if (!trackId) {
      alert('لا يمكن تعديل هذا المسار (مسار من ملف الإعدادات)');
      return;
    }

    // Set form action - use the correct prefixed route
    const form = document.getElementById('editTrackForm');
    form.action = `/teacher/admin/tracks/${trackId}`;

    // Fetch track data from server
    fetch(`/teacher/admin/tracks/${trackId}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(response => response.json())
    .then(data => {
      document.getElementById('edit_name_ar').value = data.name_ar || '';
      document.getElementById('edit_name_en').value = data.name_en || '';
      document.getElementById('editTrackModal').classList.remove('hidden');
    })
    .catch(error => {
      console.error('Error fetching track data:', error);
      // Fallback to parsing from option text
      const trackName = selectedOption.textContent.trim();
      const parts = trackName.split(' - ');
      document.getElementById('edit_name_ar').value = parts[0] || trackName;
      document.getElementById('edit_name_en').value = parts[1] || '';
      document.getElementById('editTrackModal').classList.remove('hidden');
    });
  }

  function closeEditTrackModal() {
    document.getElementById('editTrackModal')?.classList.add('hidden');
  }

  document.getElementById('editTrackModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
      closeEditTrackModal();
    }
  });

  function deleteSelectedTrack() {
    const trackSelect = document.getElementById('track_key');
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

  document.getElementById('addTrackModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
      closeAddTrackModal();
    }
  });
</script>
</body>
</html>
