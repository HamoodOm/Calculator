// public/js/print-flags.js
(function () {
  function qs(sel) { return document.querySelector(sel); }
  function qsa(sel) { return Array.from(document.querySelectorAll(sel)); }
  function setChecked(list, value) { list.forEach(el => el.checked = !!value); }

  function onReady() {
    var arOnly = qs('#arabic_only');
    var enOnly = qs('#english_only');
    if (!arOnly || !enOnly) return;

    var arFlags = qsa('.print-flag-ar');
    var enFlags = qsa('.print-flag-en');

    arOnly.addEventListener('change', () => {
      if (arOnly.checked) {
        enOnly.checked = false;
        setChecked(arFlags, true);
        setChecked(enFlags, false);
      }
    });
    enOnly.addEventListener('change', () => {
      if (enOnly.checked) {
        arOnly.checked = false;
        setChecked(enFlags, true);
        setChecked(arFlags, false);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }
})();