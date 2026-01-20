<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TeacherEditorTest extends DuskTestCase
{
    /**
     * Helper: open /teacher, store page source/screenshot, and ensure selects exist.
     */
    private function openTeacherAndSelects(Browser $browser): void
    {
        $browser->visit('/teacher')
                // Save immediate state so we can see 500s or redirects
                ->storeSource('teacher_initial')
                ->screenshot('teacher_initial')
                // We expect to be on /teacher; if you have a different route, change this
                ->assertPathIs('/teacher');

        // Prefer dusk hooks if you add them; otherwise fall back to CSS by name/id
        $trackSelector  = '[dusk="track-select"], select[name="track_key"], select#track_key';
        $genderSelector = '[dusk="gender-select"], select[name="gender"], select#gender';

        $browser->waitFor($trackSelector, 10)
                ->waitFor($genderSelector, 10);
    }

    /**
     * Pick first non-empty track; set gender=male.
     */
    private function chooseTrackAndGender(Browser $browser): void
    {
        $browser->script([
            '(function(){',
            '  var sel = document.querySelector(\'[dusk="track-select"], select[name="track_key"], select#track_key\');',
            '  if (!sel) return;',
            '  var opt = Array.prototype.slice.call(sel.options).find(function(o){ return (o.value||"").trim() !== ""; });',
            '  if (opt){ sel.value = opt.value; sel.dispatchEvent(new Event("change",{bubbles:true})); }',
            '})();'
        ]);

        // gender
        $browser->script([
            '(function(){',
            '  var g = document.querySelector(\'[dusk="gender-select"], select[name="gender"], select#gender\');',
            '  if (!g) return;',
            '  var found = Array.prototype.slice.call(g.options).find(function(o){ return /male/i.test(o.value); });',
            '  g.value = found ? found.value : (g.options[0] ? g.options[0].value : "");',
            '  g.dispatchEvent(new Event("change",{bubbles:true}));',
            '})();'
        ]);

        // Allow fetch(template.info) to resolve & paint CSS background
        $browser->pause(700);
    }

    public function test_template_bg_shows_on_track_select(): void
    {
        $this->browse(function (Browser $browser) {
            $this->openTeacherAndSelects($browser);
            $this->chooseTrackAndGender($browser);

            // Your editor sets background on #pe-stage via CSS background-image
            $browser->waitFor('#pe-stage', 5)
                    ->waitUntil('(() => { const el = document.getElementById("pe-stage"); if (!el) return false; return getComputedStyle(el).backgroundImage.includes("url("); })()', 5)
                    ->screenshot('bg_after_select');

            // Optional: check boxes above background
            $browser->script([
                'window.__zCheck = (function(){',
                '  var box = document.querySelector(".pe-box");',
                '  return box ? parseInt(getComputedStyle(box).zIndex||"0",10) : 0;',
                '})();'
            ]);
            $z = (int)($browser->script('return window.__zCheck;')[0] ?? 0);
            $this->assertTrue($z >= 2, 'Draggable boxes should be above the background (z-index >= 2).');
        });
    }

    public function test_font_change_and_resize_updates_preview_and_payload(): void
    {
        $this->browse(function (Browser $browser) {
            $this->openTeacherAndSelects($browser);
            $this->chooseTrackAndGender($browser);

            // Open advanced options
            $browser->click('#adv-toggle')->waitFor('#adv-panel', 5);

            // Change Arabic name row: font Amiri (if available), size 9.5mm, bold
            $browser->script([
                '(function(){',
                '  var row = document.querySelector(\'[data-adv-row][data-key="ar_name"]\');',
                '  if (!row) return;',
                '  var fontSel = row.querySelector(".adv-font");',
                '  if (fontSel){',
                '    var amiri = Array.prototype.slice.call(fontSel.options).find(function(o){ return /amiri/i.test(o.value); });',
                '    var opt = amiri || fontSel.options[0];',
                '    if (opt){ fontSel.value = opt.value; fontSel.dispatchEvent(new Event("change",{bubbles:true})); }',
                '  }',
                '  var sizeIn = row.querySelector(".adv-size");',
                '  if (sizeIn){ sizeIn.value = "9.5"; sizeIn.dispatchEvent(new Event("input",{bubbles:true})); }',
                '  var bold = row.querySelector(".adv-bold");',
                '  if (bold && !bold.checked) bold.click();',
                '})();'
            ]);

            $browser->pause(400);

            // Inspect computed styles for the live preview span
            $browser->script([
                '(function(){',
                '  var span = document.querySelector(\'[data-key="ar_name"] .pe-text\');',
                '  if (!span){ window.__styleCheck = null; return; }',
                '  var cs = getComputedStyle(span);',
                '  window.__styleCheck = { family: cs.fontFamily, weight: parseInt(cs.fontWeight,10), fontSizePx: parseFloat(cs.fontSize) };',
                '})();'
            ]);
            $style = $browser->script('return window.__styleCheck;')[0] ?? null;
            $this->assertNotEmpty($style, 'Style inspection failed / element missing.');
            $this->assertMatchesRegularExpression('/Amiri|DejaVu|Tasees/i', $style['family'] ?? '');
            $this->assertTrue(((int)($style['weight'] ?? 0)) >= 600, 'Expected Bold font weight in preview.');

            $expectedPx = 9.5 * 3.7795275591; // mm -> px
            $actualPx   = (float)($style['fontSizePx'] ?? 0);
            $this->assertTrue(abs($actualPx - $expectedPx) < 1.2, "Preview fontSize px mismatch. Got {$actualPx}, expected ~{$expectedPx}");

            // Persist to hidden payload
            $browser->click('#pe-apply');

            // Check ar_name.font == 9.5 in hidden JSON
            $browser->script([
                '(function(){',
                '  var hidden = document.querySelector(\'input[name="custom_positions"]\');',
                '  window.__payloadTxt = hidden ? hidden.value : "";',
                '})();'
            ]);
            $payloadTxt = $browser->script('return window.__payloadTxt;')[0] ?? '';
            $this->assertNotEmpty($payloadTxt, 'custom_positions payload should not be empty.');
            $payload = json_decode($payloadTxt, true);
            $this->assertIsArray($payload, 'custom_positions must be JSON.');
            $this->assertEquals(9.5, (float)($payload['ar_name']['font'] ?? 0), 'Expected ar_name.font = 9.5mm in payload');
        });
    }
}
