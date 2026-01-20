<?php

namespace App\Services;

final class FontRegistry
{
    private string $publicFontsDir;

    public function __construct(?string $publicFontsDir = null)
    {
        $this->publicFontsDir = $publicFontsDir ?: public_path('fonts');
    }

    /**
     * Build mPDF config from /public/fonts.
     * Returns: ['fontDir' => [...], 'fontdata' => ['Family' => ['R'=>file,'B'=>file,'I'=>file,'BI'=>file]]]
     */
    public function mpdfConfig(): array
    {
        $fontDir = is_dir($this->publicFontsDir) ? [$this->publicFontsDir] : [];
        $fontdata = [];

        if (!$fontDir) {
            return ['fontDir' => [], 'fontdata' => []];
        }

        $files = glob($this->publicFontsDir.'/*.{ttf,otf,woff,woff2,TTF,OTF,WOFF,WOFF2}', GLOB_BRACE) ?: [];

        // Group files into families; map various weights/styles to mPDF’s R/B/I/BI
        $families = []; // 'Family Name' => ['R'=>null,'B'=>null,'I'=>null,'BI'=>null]
        foreach ($files as $abs) {
            $base = basename($abs);
            $name = preg_replace('/\.(ttf|otf|woff2?|TTF|OTF|WOFF2?)$/', '', $base);

            // Try to split "Family-Style" or "Family Style"
            if (!preg_match('/^(.*?)(?:[-_ ](Regular|Book|Normal|Bold|Italic|Oblique|BoldItalic|BI|Light|ExtraLight|Thin|Medium|SemiBold|DemiBold|Black|Heavy))?$/i', $name, $m)) {
                $family = $name;
                $style  = 'Regular';
            } else {
                $family = trim($m[1]) ?: $name;
                $style  = $m[2] ?? 'Regular';
            }

            $variant = match (strtolower($style)) {
                'bold', 'semibold', 'demibold', 'extrabold', 'black', 'heavy' => 'B',
                'italic', 'oblique'                                              => 'I',
                'bolditalic', 'bi'                                               => 'BI',
                default                                                          => 'R', // Regular/Book/Normal/Light/Thin/Medium → Regular
            };

            $families[$family] ??= ['R'=>null,'B'=>null,'I'=>null,'BI'=>null];
            $families[$family][$variant] = $base;

            // If only Bold exists, also use it for R as a minimal fallback
            if ($variant === 'B' && $families[$family]['R'] === null) {
                $families[$family]['R'] = $base;
            }
        }

        // Ensure each family has at least an R file, or clone whatever exists
        foreach ($families as $family => $v) {
            $fallback = $v['R'] ?? $v['B'] ?? $v['I'] ?? $v['BI'];
            if (!$fallback) continue; // skip empty groups just in case
            foreach (['R','B','I','BI'] as $k) {
                $v[$k] ??= $fallback;
            }
            $fontdata[$family] = [
                'R'  => $v['R'],
                'B'  => $v['B'],
                'I'  => $v['I'],
                'BI' => $v['BI'],
            ];
        }

        return ['fontDir' => $fontDir, 'fontdata' => $fontdata];
    }

    /** Family names usable in CSS font-family and in mPDF config */
    public function families(): array
    {
        $cfg   = $this->mpdfConfig();
        $names = array_keys($cfg['fontdata']);
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);
        if (!in_array('DejaVu Sans', $names, true)) {
            array_unshift($names, 'DejaVu Sans'); // safe built-in fallback
        }
        return $names;
    }
}
