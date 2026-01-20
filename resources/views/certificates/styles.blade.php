<style>
  /* تعريف الخط العربي (Amiri) */
  @font-face{
    font-family:'Amiri';
    src:url('{{ public_path('fonts/Amiri-Regular.ttf') }}') format('truetype');
    font-weight:normal; font-style:normal;
  }
  @font-face{
    font-family:'Amiri';
    src:url('{{ public_path('fonts/Amiri-Bold.ttf') }}') format('truetype');
    font-weight:bold; font-style:normal;
  }

  @font-face{
    font-family:'Tasees-Bold';
    src:url('{{ public_path('fonts/Tasees-Bold.ttf') }}') format('truetype');
    font-weight:bold; font-style:normal;
  }


  *{ font-family:'Amiri', DejaVu Sans, sans-serif; }
  html, body { margin:0; padding:0; }
  body { direction: rtl; unicode-bidi: bidi-override; }

  /* مساحة صفحة A4 أفقية (mm) لمطابقة mPDF */
  .page {
    position: relative;
    width: 297mm;
    height: 210mm;
  }

  .field { position: absolute; line-height: 1.25; color:#0f172a; }
  .rtl   { direction: rtl; unicode-bidi: embed; text-align: right; }
  .ltr   { direction: ltr; unicode-bidi: embed; text-align: left; }

  /* الأرقام والتواريخ يجب أن تُكتب يسار-لليمين */
  .num { direction: ltr; unicode-bidi: embed; }
</style>
