@php
  // $role = 'teacher' | 'student'
@endphp



<div class="bg-white p-4 rounded shadow mb-6">
  <h2 class="text-lg font-semibold mb-3">تحرير مواضع العناصر بالسحب</h2>

  <style>
    #pe-stage { position: relative; }
    #pe-stage .pe-box { position:absolute; z-index:2; touch-action:none; user-select:none; }
  </style>

  <div class="grid md:grid-cols-3 gap-4 mb-4">
    <div>
      <label class="block text-xs mb-1">نص عربي للتجربة</label>
      <input id="pe-name-ar" type="text" value="الاسم العربي" class="w-full border rounded px-3 py-2">
    </div>
    <div>
      <label class="block text-xs mb-1">Sample English text</label>
      <input id="pe-name-en" type="text" value="English Name" class="w-full border rounded px-3 py-2">
    </div>
    <div class="grid grid-cols-2 gap-2">
      <button type="button" id="pe-reset" class="px-3 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-800 mt-6">إرجاع الإحداثيات</button>
      <button type="button" id="pe-apply" class="px-3 py-2 rounded bg-sky-600 hover:bg-sky-700 text-white mt-6">تثبيت الإحداثيات</button>
    </div>
  </div>

  <div class="grid md:grid-cols-3 gap-4 mb-3">
    <div class="col-span-2 flex items-center gap-3">
      <span class="text-sm text-gray-700">حجم الصورة (مم):</span>
      <label class="text-xs">العرض
        <input id="pe-photo-w" type="number" min="10" max="120" step="1" value="30" class="border rounded px-2 py-1 w-20 ml-1">
      </label>
      <label class="text-xs">الارتفاع
        <input id="pe-photo-h" type="number" min="10" max="120" step="1" value="30" class="border rounded px-2 py-1 w-20 ml-1">
      </label>
    </div>
  </div>

  <div class="overflow-auto border rounded">
    <div id="pe-stage"
         style="width:1122.52px;height:793.70px;background:#f3f4f6;background-size:100% 100%;background-repeat:no-repeat;">
      @php
        $boxes = [
          ['key'=>'cert_date','label'=>'تاريخ الإصدار','dir'=>'rtl','align'=>'left','type'=>'text'],
          ['key'=>'ar_name','label'=>'الاسم (عربي)','dir'=>'rtl','align'=>'left','type'=>'text'],
          ['key'=>'ar_track','label'=>'المسار (عربي)','dir'=>'rtl','align'=>'left','type'=>'text'],
          ['key'=>'ar_from','label'=>'من (عربي)','dir'=>'rtl','align'=>'left','type'=>'text'],
          ['key'=>'en_name','label'=>'Name (EN)','dir'=>'ltr','align'=>'right','type'=>'text'],
          ['key'=>'en_track','label'=>'Track (EN)','dir'=>'ltr','align'=>'right','type'=>'text'],
          ['key'=>'en_from','label'=>'From (EN)','dir'=>'ltr','align'=>'right','type'=>'text'],
          ['key'=>'photo','label'=>'PHOTO','dir'=>'ltr','align'=>'center','type'=>'photo'],
        ];
      @endphp
      @foreach ($boxes as $b)
        <div class="pe-box"
             data-key="{{ $b['key'] }}"
             data-type="{{ $b['type'] }}"
             data-dir="{{ $b['dir'] }}"
             data-align="{{ $b['align'] }}"
             style="left:10px;top:10px;width:200px;height:40px;border:1px dashed rgba(2,132,199,.6);background:rgba(59,130,246,.06);display:flex;align-items:center;justify-content:{{ $b['align']==='right'?'flex-end':($b['align']==='center'?'center':'flex-start') }};padding:4px;">
          @if ($b['type']==='photo')
            <img class="pe-photo" alt="" style="max-width:100%;max-height:100%;display:none;">
            <span class="pe-text" style="font-size:16px;color:#0f172a;">PHOTO</span>
          @else
            <span class="pe-text" style="font-size:18px;color:#111827;">{{ $b['label'] }}</span>
          @endif
        </div>
      @endforeach
    </div>
  </div>
</div>

<script>
// ✅ Make variables accessible to both script blocks
window.PE = window.PE || {};
(function(){
  const ROLE = @json($role);
  const pxPerMm = 3.7795275591, PAGE_W_MM = 297.0, PAGE_H_MM = 210.0;

  const stage   = document.getElementById('pe-stage');
  if (!stage) return;

  // ✅ استخدم الأقرب: form.closest
  const form = stage.closest('form') || document.querySelector('form');
  if (!form) { console.warn('Place the editor inside the form'); return; }

  // hidden field داخل نفس النموذج
  let hidden = form.querySelector('input[name="custom_positions"]');
  if (!hidden) { hidden = document.createElement('input'); hidden.type='hidden'; hidden.name='custom_positions'; form.appendChild(hidden); }

  const boxes   = Array.from(stage.querySelectorAll('.pe-box'));
  const nameAr  = document.getElementById('pe-name-ar');
  const nameEn  = document.getElementById('pe-name-en');
  const btnReset= document.getElementById('pe-reset');
  const btnApply= document.getElementById('pe-apply');
  const inputW  = document.getElementById('pe-photo-w');
  const inputH  = document.getElementById('pe-photo-h');

  // حقول حقيقية
  const selTrack  = form.querySelector('select[name="track_key"]');
  const selGender = form.querySelector('select[name="gender"]');
  const filePhoto   = form.querySelector('input[name="photo"]');
  const inputRemove = document.getElementById('remove_photo');
  const btnRemove   = document.getElementById('btn-remove-photo');

  function mmToPx(mm){ return mm * pxPerMm; }
  function pxToMm(px){ return px / pxPerMm; }
  function computeLeftMm(item) {
    const width = (typeof item.width === 'number') ? item.width : 60.0;
    if (typeof item.left === 'number') return item.left;
    if (typeof item.right === 'number') return PAGE_W_MM - (item.right + width);
    return 30.0;
  }

  let template = { bg:null, pos:{}, style:{} };
  let originalTemplate = null; // ✅ Store original template (with DB values) for reset
  let working  = {};
  let photoURL = null;

  // ✅ Expose to window.PE for access from second script block
  window.PE.template = template;
  window.PE.originalTemplate = originalTemplate;
  window.PE.working = working;
  window.PE.stage = stage;

  function applyStyleFor(key, span) {
    const colors = (template.style && template.style.colors) ? template.style.colors : {};
    const color  = colors[key] || '#111827';
    span.style.color = color;

    const font = (key.startsWith('en_') ? (template.style.font?.en) : (template.style.font?.ar)) || null;
    if (font) span.style.fontFamily = `'${font}', system-ui, sans-serif`;

  }
  window.PE.applyStyleFor = applyStyleFor; // ✅ Expose

  function renderBoxes() {
    boxes.forEach(box => {
      const key  = box.dataset.key;
      const type = box.dataset.type;
      const t = working[key]; if (!t) return;

      const leftPx  = mmToPx(t.left);
      const topPx   = mmToPx(t.top);
      const widthPx = mmToPx((typeof t.width === 'number') ? t.width : 90.0);

      box.style.left = `${leftPx}px`;
      box.style.top  = `${topPx}px`;
      box.style.width= `${widthPx}px`;

      if (type === 'photo') {
        const hPx = mmToPx((typeof t.height === 'number') ? t.height : 30.0);
        box.style.height = `${hPx}px`;

        const img = box.querySelector('.pe-photo');
        const span= box.querySelector('.pe-text');
        if (photoURL) {
          img.src = photoURL;
          img.style.display = 'block';
          span.style.display = 'none';
        } else {
          img.src = '';
          img.style.display = 'none';
          span.style.display = 'block';
        }
      } else {
        const fontMm  = (typeof t.font === 'number') ? t.font : 6.0;
        const fontPx  = mmToPx(fontMm);
        const heightPx= Math.max(8.0, fontMm * 1.8) * pxPerMm;
        box.style.height = `${heightPx}px`;

        const span = box.querySelector('.pe-text');
        span.style.fontSize = `${fontPx}px`;
        applyStyleFor(key, span);

        if (key === 'ar_name')       span.textContent = nameAr.value || 'الاسم العربي';
        else if (key === 'en_name')  span.textContent = nameEn.value || 'English Name';
        else if (key === 'ar_track') span.textContent = selTrack?.selectedOptions[0]?.text.split(' — ')[0] || 'المسار بالعربية';
        else if (key === 'en_track') span.textContent = selTrack?.selectedOptions[0]?.text.split(' — ')[1] || 'Track (EN)';
        else if (key === 'cert_date')span.textContent = 'dd/mm/yyyy';
        else if (key === 'ar_from')  span.textContent = 'dd/mm/yyyy';
        else if (key === 'en_from')  span.textContent = 'from dd/mm/yyyy';

        const dir   = box.dataset.dir  || 'rtl';
        const align = box.dataset.align|| 'right';
        span.style.direction = dir;
        box.style.justifyContent = (align === 'right') ? 'flex-end' : (align==='center'?'center':'flex-start');
        span.style.textAlign = align;
      }
    });

    if (working.photo) {
      inputW.value = (typeof working.photo.width  === 'number') ? working.photo.width  : 30;
      inputH.value = (typeof working.photo.height === 'number') ? working.photo.height : 30;
    }
  }
  window.PE.renderBoxes = renderBoxes; // ✅ Expose

  function writeHidden() {
    const out = {};
    for (const [k, v] of Object.entries(working)) {
      out[k] = { top: +v.top, left: +v.left };
      if (typeof v.width  === 'number')  out[k].width  = +v.width;
      if (typeof v.height === 'number')  out[k].height = +v.height;
      if (typeof v.font   === 'number')  out[k].font   = +v.font;
    }
    hidden.value = JSON.stringify(out);
    console.log('✅ writeHidden called', {
      hiddenFieldName: hidden.name,
      hiddenFieldValue: hidden.value,
      working: working
    });
  }
  window.PE.writeHidden = writeHidden; // ✅ Expose

  async function loadTemplate() {
    const track = selTrack?.value;
    const gender= selGender?.value || 'male';
    if (!track) return;

    try {
      const endpoint = @json(route('template.info'));
      const url = `${endpoint}?role=${encodeURIComponent(ROLE)}&track_key=${encodeURIComponent(track)}&gender=${encodeURIComponent(gender)}`;
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
      if (!res.ok) throw new Error('fetch failed');
      const data = await res.json();
      if (!data.success) throw new Error('payload error');

      template.bg   = data.background_url;
      template.pos  = data.positions || {};
      template.style= data.style || {};
      stage.style.backgroundImage = `url('${template.bg}')`;

      // ✅ Store deep copy of original template (with DB values) for reset
      originalTemplate = JSON.parse(JSON.stringify({
        bg: template.bg,
        pos: template.pos,
        style: template.style
      }));
      window.PE.originalTemplate = originalTemplate;

      working = {};
      window.PE.working = working; // ✅ Update reference after reassignment!
      ['cert_date','ar_name','ar_track','ar_from','en_name','en_track','en_from','photo'].forEach(k=>{
        const it = template.pos[k] || {};
        working[k] = {
          top:   (typeof it.top === 'number') ? it.top : 30.0,
          left:  computeLeftMm(it),
          width: (typeof it.width === 'number') ? it.width : (k==='photo'?30:(k.startsWith('en_')?120:90)),
          font:  (typeof it.font === 'number') ? it.font : (k.startsWith('en_')?5.5:6.0),
        };
        if (k === 'photo') {
          working[k].height = (typeof it.height === 'number') ? it.height : 30.0;
        }
      });

      renderBoxes();
      writeHidden();
    } catch(e){
      console.error('Template load failed', e);
    }
  }
  window.PE.loadTemplate = loadTemplate; // ✅ Expose

  // سحب
  let dragging = null, startX=0, startY=0, startLeft=0, startTop=0;
  function onDown(e) {
    dragging = e.currentTarget;
    const rect = stage.getBoundingClientRect();
    const boxRect = dragging.getBoundingClientRect();
    startX = (e.touches ? e.touches[0].clientX : e.clientX);
    startY = (e.touches ? e.touches[0].clientY : e.clientY);
    startLeft = pxToMm(boxRect.left - rect.left);
    startTop  = pxToMm(boxRect.top  - rect.top);
    e.preventDefault();
  }
  function onMove(e) {
    if (!dragging) return;
    const key = dragging.dataset.key;
    const dx = ((e.touches ? e.touches[0].clientX : e.clientX) - startX);
    const dy = ((e.touches ? e.touches[0].clientY : e.clientY) - startY);
    const dmmX = pxToMm(dx), dmmY = pxToMm(dy);

    const wmm = working[key].width || 90.0;
    working[key].left = Math.max(0, Math.min(PAGE_W_MM - wmm, startLeft + dmmX));
    working[key].top  = Math.max(0, Math.min(PAGE_H_MM - 8,  startTop  + dmmY));

    renderBoxes();
    writeHidden();
  }
  function onUp(){ dragging=null; }

  boxes.forEach(b=>{
    b.addEventListener('mousedown', onDown);
    b.addEventListener('touchstart', onDown, {passive:false});
  });
  window.addEventListener('mousemove', onMove, {passive:false});
  window.addEventListener('touchmove', onMove, {passive:false});
  window.addEventListener('mouseup', onUp);
  window.addEventListener('touchend', onUp);

  // معاينة صورة من input الملف (فوريًا)
  if (filePhoto) {
    filePhoto.addEventListener('change', ()=>{
      const f = filePhoto.files && filePhoto.files[0];
      if (!f) { photoURL = null; renderBoxes(); return; }
      // بعض المتصفحات لا تُعطي type دقيق؛ نقبلها ونجرّب القراءة
      const fr = new FileReader();
      fr.onload = (ev)=>{ photoURL = ev.target.result; renderBoxes(); };
      fr.readAsDataURL(f);
      if (inputRemove) inputRemove.value = '0';
    });
  }
  if (btnRemove) {
    btnRemove.addEventListener('click', ()=>{
      if (filePhoto) filePhoto.value = '';
      photoURL = null;
      if (inputRemove) inputRemove.value = '1';
      renderBoxes(); writeHidden();
    });
  }

  // حجم الصورة
  inputW.addEventListener('input', ()=>{
    if (!working.photo) return;
    const val = parseFloat(inputW.value || '30');
    working.photo.width = Math.max(10, Math.min(120, val));
    renderBoxes(); writeHidden();
  });
  inputH.addEventListener('input', ()=>{
    if (!working.photo) return;
    const val = parseFloat(inputH.value || '30');
    working.photo.height = Math.max(10, Math.min(120, val));
    renderBoxes(); writeHidden();
  });

  // إعادة + تثبيت
  btnReset.addEventListener('click', () => window.PE.loadTemplate()); // ✅ Call exposed function
  btnApply.addEventListener('click', ()=>{
    writeHidden();
    const previewBtn = form.querySelector('button[formaction*="preview"]');
    if (previewBtn) previewBtn.click(); else alert('تم تثبيت الإحداثيات. استخدم زر الإنشاء لتطبيقها.');
  });

  // أمان: اكتب الإحداثيات قبل أي submit
  form.addEventListener('submit', (e) => {
    console.log('📤 Form submit event triggered');
    writeHidden();
  });

  // حمّل القالب عندما يختار المستخدم المسار/الجنس
  form.querySelector('select[name="track_key"]')?.addEventListener('change', () => window.PE.loadTemplate()); // ✅
  form.querySelector('select[name="gender"]')?.addEventListener('change', () => window.PE.loadTemplate()); // ✅

  // إذا كان هناك قيمة مبدئية للمسار/الجنس شغّل التحميل
  if (selTrack?.value) window.PE.loadTemplate(); // ✅
})();
</script>


{{-- 002 الخيارات الاضافية وبعض الخصائص الاخرى --}}
<!-- 
@php
  // Build font families from /public/fonts dynamically
  $fontsPath = public_path('fonts');
  $fontFiles = [];
  if (is_dir($fontsPath)) {
    foreach (glob($fontsPath.'/*.{ttf,otf,woff2,woff}', GLOB_BRACE) as $f) {
      $fontFiles[] = basename($f);
    }
  }
  // group into families: remove -Regular/-Bold suffix when computing family display name
  $familyMap = [];
  foreach ($fontFiles as $base) {
    $nameNoExt = preg_replace('/\.(ttf|otf|woff2|woff)$/i', '', $base);
    if (preg_match('/^(.*?)(?:[-_ ](Regular|Bold|Italic|Oblique|Medium|Light|Black|SemiBold|ExtraBold|BI|BoldItalic))?$/i', $nameNoExt, $m)) {
      $fam = trim($m[1]);
      $wt  = isset($m[2]) ? strtolower($m[2]) : 'regular';
    } else {
      $fam = $nameNoExt; $wt = 'regular';
    }
    $familyMap[$fam] = $familyMap[$fam] ?? ['R'=>null,'B'=>null,'I'=>null,'BI'=>null];
    switch ($wt) {
      case 'bolditalic': case 'bi': $familyMap[$fam]['BI'] = $base; break;
      case 'italic': case 'oblique': $familyMap[$fam]['I'] = $base; break;
      case 'bold': case 'semibold': case 'extrabold': case 'black':
        $familyMap[$fam]['B'] = $base; if (!$familyMap[$fam]['R']) $familyMap[$fam]['R']=$base; break;
      default: $familyMap[$fam]['R'] = $base; break;
    }
  }
  // list of families for pickers
  //$fontFamilies = array_keys($familyMap);
  //sort($fontFamilies, SORT_NATURAL | SORT_FLAG_CASE);
 // if (!in_array('DejaVu Sans', $fontFamilies, true)) array_unshift($fontFamilies, 'DejaVu Sans');
  $fontFamilies = $fonts ?? (new \App\Services\FontRegistry())->families();
@endphp -->



<div class="bg-white p-4 rounded shadow mb-6">
  <button type="button" id="adv-toggle"
          class="px-3 py-2 rounded bg-slate-700 text-white hover:bg-slate-800">
    خيارات إضافية
  </button>

  <div id="adv-panel" class="mt-4 hidden">
    <div class="text-sm text-slate-600 mb-3">
      اضبط لون الخط، نوع الخط، وحجم الخط لكل نص على حدة. التغييرات تظهر فوراً في المعاينة.
    </div>

    @php
      $advBoxes = [
        ['key'=>'cert_date','label'=>'تاريخ الشهادة'],
        ['key'=>'ar_name','label'=>'الاسم (عربي)'],
        ['key'=>'ar_track','label'=>'المسار (عربي)'],
        ['key'=>'ar_from','label'=>'التاريخ (من/حتى بالعربية)'],
        ['key'=>'en_name','label'=>'Name (EN)'],
        ['key'=>'en_track','label'=>'Track (EN)'],
        ['key'=>'en_from','label'=>'Date (EN)'],
      ];

      $fontFamilies = $fonts ?? (new \App\Services\FontRegistry())->families();
      // simple font lists; available TTFs: Amiri, Tasees-Bold; mPDF built-in: DejaVu Sans
      $fontsAr = $fontFamilies;
      $fontsEn = $fontFamilies;
      // 2) Fields on the teacher template that you want font control for
      $fields = [
          'cert_date' => 'Certificate Date',
          'ar_name'   => 'Arabic Name',
          'ar_track'  => 'Arabic Track',
          'ar_from'   => 'Arabic From',
          'en_name'   => 'English Name',
          'en_track'  => 'English Track',
          'en_from'   => 'English From',
      ];
      // 3) Previously selected values (so the UI shows current selection)
      $selectedPer = data_get($style ?? [], 'font_per', []);

      // size 
      $selectedSizes = data_get($style ?? [], 'size_per', []);
      
    @endphp

    <!-- <div class="mt-4 border-t pt-3">
      <h4 class="text-sm font-semibold mb-2">Fonts (per field)</h4>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach($fields as $key => $label)
          <div>
            <label class="block text-xs text-gray-600 mb-1">{{ $label }}</label>
            <select
              name="style[font_per][{{ $key }}]"
              class="w-full rounded border-gray-300"
            >
              <option value="">(use language default)</option>
              @foreach($fontFamilies as $fam)
                <option value="{{ $fam }}"
                  {{ (($selectedPer[$key] ?? '') === $fam) ? 'selected' : '' }}>
                  {{ $fam }}
                </option>
              @endforeach
            </select>
          </div>
        @endforeach
      </div>
    </div> -->

<!-- <div class="mt-4 border-t pt-3">
  <h4 class="text-sm font-semibold mb-2">Text size (per field)</h4>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    @foreach($fields as $key => $label)
      <div>
        <label class="block text-xs text-gray-600 mb-1">{{ $label }}</label>
        <input
          type="number" step="0.1" min="4" max="120"
          name="style[size_per][{{ $key }}]"
          value="{{ old("style.size_per.$key", $selectedSizes[$key] ?? '') }}"
          class="w-full rounded border-gray-300"
          placeholder="(use template default)"
        />
        <p class="text-[11px] text-gray-500 mt-1">Points (pt). Leave blank to use template default.</p>
      </div>
    @endforeach
  </div>
</div> -->

    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead>
          <tr class="border-b">
            <th class="text-right py-2 pr-2">الحقل</th>
            <th class="text-right py-2">اللون</th>
            <th class="text-right py-2">نوع الخط</th>
            <th class="text-right py-2">الحجم (mm)</th>
            <th class="text-right py-2">سميك (Bold)</th>
            <th class="text-right py-2">إعادة الضبط</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($advBoxes as $row)
            @php $isEn = str_starts_with($row['key'], 'en_'); @endphp
            <tr class="border-b last:border-0" data-adv-row data-key="{{ $row['key'] }}">
              <td class="py-2 pr-2 whitespace-nowrap">{{ $row['label'] }}</td>
              <td class="py-2">
                <input type="color" class="adv-color border rounded"
                       name="style[colors][{{ $row['key'] }}]" value="#111827">
              </td>
              <td class="py-2">
                <!-- <select class="adv-font border rounded px-2 py-1"
                        name="style[font_per][{{ $row['key'] }}]">
                  @foreach(($isEn ? $fontsEn : $fontsAr) as $f)
                    <option value="{{ $f }}">{{ $f }}</option>
                  @endforeach
                </select> -->

                


                <select name="style[font_per][{{ $row['key'] }}]" class="...">
                  <option value="">(use language default)</option>
                  @foreach ($fontFamilies as $fam)
                    <option value="{{ $fam }}">{{ $fam }}</option>
                  @endforeach
                </select>
                
                <!-- <select name="style[font][ar]" class="...">@foreach($fontFamilies as $f)<option value="{{$f}}">{{$f}}</option>@endforeach</select>
                <select name="style[font][en]" class="...">@foreach($fontFamilies as $f)<option value="{{$f}}">{{$f}}</option>@endforeach</select> -->
              </td>
              <td class="py-2">
                <input type="number" step="0.1" min="3" max="20"
                       class="adv-size border rounded px-2 py-1 w-24"
                       data-size-mm placeholder="6.0">
                       
              </td>
              <td class="py-2">
                <label class="inline-flex items-center gap-2">
                  <input type="checkbox" class="adv-bold" name="style[weight_per][{{ $row['key'] }}]" value="700">
                  <span>Bold</span>
                </label>
              </td>
              <td class="py-2">
                <button type="button" class="adv-reset px-2 py-1 border rounded" data-reset="{{ $row['key'] }}">Reset</button>
              </td>

            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function(){

  
  // ---- Fallback: auto-create .pe-box elements if the HTML was removed ----
  function ensureBoxesExist(){
    // If boxes already exist, do nothing
    if (document.querySelectorAll('.pe-box[data-key]').length > 0) return;

    // Collect candidate keys from hidden payload
    const keys = [];
    try {
      const hidden = document.querySelector('input[name="custom_positions"]');
      if (hidden && hidden.value) {
        const payload = JSON.parse(hidden.value);
        Object.keys(payload).forEach(k => { if (!keys.includes(k)) keys.push(k); });
      }
    } catch (e) {}

    // Also collect from Advanced Options rows
    document.querySelectorAll('[data-adv-row][data-key]').forEach(row => {
      const k = row.getAttribute('data-key');
      if (k && !keys.includes(k)) keys.push(k);
    });

    if (keys.length === 0) return; // nothing to build

    const mount = document.getElementById('pe-overlay') || document.querySelector('.pe-layer');
    if (!mount) return;

    // Create simple .pe-box with .pe-text for each key
    keys.forEach((key, i) => {
      if (document.querySelector('.pe-box[data-key="'+key+'"]')) return; // don't duplicate
      const box = document.createElement('div');
      box.className = 'pe-box';
      box.setAttribute('data-key', key);
      // default CSS position (will be overwritten if positions exist)
      box.style.left = '100px';
      box.style.top  = (80 + i * 60) + 'px';
      box.style.minWidth  = '220px';
      box.style.minHeight = '40px';

      const span = document.createElement('span');
      span.className = 'pe-text';
      // fallback text label (non-binding)
      span.textContent = key.replace(/_/g, ' ');
      box.appendChild(span);

      mount.appendChild(box);
    });
  }




  const advToggle = document.getElementById('adv-toggle');
  const advPanel  = document.getElementById('adv-panel');
  if (advToggle && advPanel) {
    advToggle.addEventListener('click', () => advPanel.classList.toggle('hidden'));
  }

  // helpers to access rows & fields
  function advRows(){ return Array.from(document.querySelectorAll('tr[data-adv-row]')); }
  function rowKey(tr){ return tr.getAttribute('data-key'); }

  function boxSpanFor(key){
    const stage = window.PE.stage; // ✅ Use exposed variable
    const box = stage ? stage.querySelector(`.pe-box[data-key="${key}"]`) : null;
    return box ? box.querySelector('.pe-text') : null;
  }

  
  function getFallbackColor(key){
    const m={cert_date:'#0f172a',ar_name:'#334155',ar_track:'#0891b2',ar_from:'#0891b2',en_name:'#0f172a',en_track:'#0891b2',en_from:'#0f172a'};
    return m[key]||'#0f172a';
  }
  function getFallbackWeight(key){ return (key.endsWith('_name')||key.endsWith('_track'))?700:400; }

  // ✅ Create shortcuts to exposed variables
  const getTemplate = () => window.PE.template;
  const getWorking = () => window.PE.working;
  const renderBoxes = () => window.PE.renderBoxes();
  const writeHidden = () => window.PE.writeHidden();
  const applyStyleFor = (k,s) => window.PE.applyStyleFor(k,s);

  // initialize values from template/working and wire events
  function initAdvancedOptions(){
    const template = getTemplate();
    const working = getWorking();
    advRows().forEach(tr=>{
      const key=rowKey(tr);
      const color=tr.querySelector('.adv-color');
      const font =tr.querySelector('.adv-font');
      const sizeIn=tr.querySelector('.adv-size');
      const bold= tr.querySelector('.adv-bold');

      // seed from template (NO mutation here)
      var _col = (template.style && template.style.colors) ? template.style.colors[key] : undefined;
      if (_col === undefined || _col === null) _col = getFallbackColor(key);
      color.value = _col;

      // ✅ Only handle font select if it exists (might be commented out in HTML)
      if (font) {
        const per = (template.style && template.style.font_per) ? template.style.font_per[key] : null;
        let curFont = per
          || (key.indexOf('en_') === 0
                ? ((template.style && template.style.font) ? template.style.font.en : '')
                : ((template.style && template.style.font) ? template.style.font.ar : '')
            )
          || '';
        var _has = false;
        for (var i=0; i < font.options.length; i++) {
          if (font.options[i].value === curFont) { _has = true; break; }
        }
        if (curFont && !_has) {
          const opt=document.createElement('option'); opt.value=opt.textContent=curFont; font.appendChild(opt);
        }
        if (curFont) font.value = curFont;
        font.dataset.init=font.value||'';
      }

      sizeIn.value = (working[key] && typeof working[key].font==='number')
        ? working[key].font
        : (key.startsWith('en_')?5.5:6.0);

      const wInit = (template.style && template.style.weight_per) ? template.style.weight_per[key] : undefined;
      let w=(typeof wInit==='string'?(wInit.toLowerCase()==='bold'?700:(wInit.toLowerCase()==='normal'?400:parseInt(wInit)||undefined))
            :(typeof wInit==='number'?wInit:undefined));
      if (typeof w!=='number') w=getFallbackWeight(key);
      bold.checked=(w>=600);

      // remember initial values (to prune unchanged)
      color.dataset.init=color.value;
      sizeIn.dataset.init=sizeIn.value;
      bold.dataset.init=bold.checked?'1':'0';

      // handlers (mutate only on user action)
      color.addEventListener('input', ()=>{
        const t = window.PE.template; // ✅ Access directly
        t.style.colors=t.style.colors||{};
        t.style.colors[key]=color.value;
        const span=boxSpanFor(key); if(span) applyStyleFor(key,span);
      });

      // ✅ Only attach font event listener if font select exists
      if (font) {
        font.addEventListener('change', ()=>{
          const t = window.PE.template; // ✅ Access directly
          t.style.font_per=t.style.font_per||{};
          t.style.font_per[key]=font.value;
          const span=boxSpanFor(key); if(span) applyStyleFor(key,span);
        });
      }

      sizeIn.addEventListener('input', ()=>{
        const val=parseFloat(sizeIn.value);
        console.log(`🔧 Font size changed for ${key}:`, val);
        if(isFinite(val)){
          const w = window.PE.working; // ✅ Access directly
          w[key]=w[key]||{};
          w[key].font=val;
          console.log(`✅ Set working[${key}].font =`, val, 'Full working:', w[key]);
          renderBoxes();
          writeHidden();
        }
      });

      bold.addEventListener('change', ()=>{
        const t = window.PE.template; // ✅ Access directly
        t.style.weight_per=t.style.weight_per||{};
        t.style.weight_per[key]=bold.checked?'bold':'normal';
        const span=boxSpanFor(key); if(span) applyStyleFor(key,span);
      });
    });

    // per-row Reset
    document.querySelectorAll('button.adv-reset').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const key=btn.dataset.reset;
        const tr=btn.closest('tr[data-adv-row]'); if(!tr) return;
        const color=tr.querySelector('.adv-color');
        const font =tr.querySelector('.adv-font');
        const sizeIn=tr.querySelector('.adv-size');
        const bold= tr.querySelector('.adv-bold');

        const t = window.PE.template; // ✅ Access directly
        const w = window.PE.working; // ✅ Access directly
        const orig = window.PE.originalTemplate; // ✅ Get original (DB) values

        // ✅ Reset to original DB values instead of hardcoded fallbacks
        // Color: use original template color or fallback
        const origColor = (orig && orig.style && orig.style.colors && orig.style.colors[key])
          || getFallbackColor(key);
        color.value = origColor;
        if (t.style && t.style.colors) {
          t.style.colors[key] = origColor;
        }

        // Font: use original template font_per or language default
        if (font) {
          const origFontPer = (orig && orig.style && orig.style.font_per && orig.style.font_per[key]) || null;
          const origLangFont = (key.indexOf('en_') === 0)
            ? ((orig && orig.style && orig.style.font && orig.style.font.en) || '')
            : ((orig && orig.style && orig.style.font && orig.style.font.ar) || '');
          const resetFont = origFontPer || origLangFont;
          font.value = resetFont;
          font.dataset.init = font.value || '';
          if (t.style && t.style.font_per) {
            if (origFontPer) {
              t.style.font_per[key] = origFontPer;
            } else {
              delete t.style.font_per[key];
            }
          }
        }

        // Size: use original template position font size
        const origSize = (orig && orig.pos && orig.pos[key] && typeof orig.pos[key].font === 'number')
          ? orig.pos[key].font
          : (key.startsWith('en_') ? 5.5 : 6.0);
        sizeIn.value = origSize;

        // Weight: use original template weight or fallback
        const origWeightVal = (orig && orig.style && orig.style.weight_per && orig.style.weight_per[key]) || null;
        let origWeight;
        if (origWeightVal) {
          if (typeof origWeightVal === 'string') {
            origWeight = (origWeightVal.toLowerCase() === 'bold') ? 700 :
                         (origWeightVal.toLowerCase() === 'normal') ? 400 :
                         parseInt(origWeightVal) || getFallbackWeight(key);
          } else if (typeof origWeightVal === 'number') {
            origWeight = origWeightVal;
          } else {
            origWeight = getFallbackWeight(key);
          }
        } else {
          origWeight = getFallbackWeight(key);
        }
        bold.checked = origWeight >= 600;
        if (t.style && t.style.weight_per) {
          t.style.weight_per[key] = bold.checked ? 'bold' : 'normal';
        }

        w[key]=w[key]||{};
        w[key].font=parseFloat(sizeIn.value);
        renderBoxes();
        const span=boxSpanFor(key); if(span) applyStyleFor(key,span);

        // mark "unchanged" again so nothing posts
        color.dataset.init=color.value;
        sizeIn.dataset.init=sizeIn.value;
        bold.dataset.init=bold.checked?'1':'0';
      });
    });
  }

  // hook into template load and first render
  const _origLoad = window.PE.loadTemplate; // ✅ Access exposed function
  if (_origLoad) {
    window.PE.loadTemplate = async function(){
      await _origLoad();
      console.log('📋 loadTemplate completed, calling initAdvancedOptions()');
      initAdvancedOptions();
    };
  }

  // ✅ Always try to init after a short delay (in case template loaded before this script ran)
  setTimeout(() => {
    const t = window.PE.template;
    console.log('⏰ Delayed init check, template:', t);
    if (t && Object.keys(t.pos || {}).length > 0) {
      console.log('✅ Template loaded, calling initAdvancedOptions()');
      initAdvancedOptions();
    }
  }, 500);
})();
</script>