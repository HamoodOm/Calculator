{{-- resources/views/partials/print-flags.blade.php --}}
<div class="card" style="padding: 12px; border: 1px solid #ddd; border-radius: 8px; margin-top: 12px;">
  <h4 style="margin:0 0 10px;">خيارات الطباعة</h4>

  <div style="display:flex; gap:16px; flex-wrap:wrap; align-items:center; margin-bottom:10px;">
    <label style="display:flex; align-items:center; gap:6px;">
      <input type="checkbox" id="arabic_only" name="arabic_only" value="1">
      <span>العربية فقط</span>
    </label>
    <label style="display:flex; align-items:center; gap:6px;">
      <input type="checkbox" id="english_only" name="english_only" value="1">
      <span>الإنجليزية فقط</span>
    </label>
    <small style="opacity:.75">(إذا اخترت أحدهما سيتم تعطيل الآخر تلقائيًا)</small>
  </div>

  <hr style="margin: 8px 0;">

  <div style="display:grid; grid-template-columns: repeat(2, minmax(200px, 1fr)); gap:10px;">
    <div>
      <input type="hidden" name="print[ar_name]" value="0">
      <label style="display:flex; align-items:center; gap:6px;">
        <input type="checkbox" name="print[ar_name]" value="1" class="print-flag print-flag-ar" checked>
        <span>الاسم (عربي)</span>
      </label>
    </div>
    <div>
      <input type="hidden" name="print[en_name]" value="0">
      <label style="display:flex; align-items:center; gap:6px;">
        <input type="checkbox" name="print[en_name]" value="1" class="print-flag print-flag-en" checked>
        <span>الاسم (إنجليزي)</span>
      </label>
    </div>
    <div>
      <input type="hidden" name="print[ar_track]" value="0">
      <label style="display:flex; align-items:center; gap:6px;">
        <input type="checkbox" name="print[ar_track]" value="1" class="print-flag print-flag-ar" checked>
        <span>المسار (عربي)</span>
      </label>
    </div>
    <div>
      <input type="hidden" name="print[en_track]" value="0">
      <label style="display:flex; align-items:center; gap:6px;">
        <input type="checkbox" name="print[en_track]" value="1" class="print-flag print-flag-en" checked>
        <span>المسار (إنجليزي)</span>
      </label>
    </div>
    <div>
      <input type="hidden" name="print[ar_duration]" value="0">
      <label style="display:flex; align-items:center; gap:6px;">
        <input type="checkbox" name="print[ar_duration]" value="1" class="print-flag print-flag-ar" checked>
        <span>من التاريخ (عربي)</span>
      </label>
    </div>
    <div>
      <input type="hidden" name="print[en_duration]" value="0">
      <label style="display:flex; align-items:center; gap:6px;">
        <input type="checkbox" name="print[en_duration]" value="1" class="print-flag print-flag-en" checked>
        <span>From date (English)</span>
      </label>
    </div>
  </div>
</div>
