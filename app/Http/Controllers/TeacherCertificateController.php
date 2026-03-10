<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherCertificateRequest;
use App\Services\CertificateService;
use App\Services\TemplateResolver;
use App\Services\FontRegistry;
use App\Services\ActivityLogService;
use App\Support\PrintFlags;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class TeacherCertificateController extends Controller
{
    public function index()
    {
                // $fonts = (new FontRegistry())->families();
                // return view('teacher.index', [
                //     'tracks' => config('certificates.tracks_teacher'),
                //     'fonts'  => $fonts,
                // ]);

                $fonts = (new FontRegistry())->families();
                return view('teacher.index', [
                    'tracks' => config('certificates.tracks_teacher'),
                    'fonts'  => $fonts,
]);
    }

    public function store(TeacherCertificateRequest $request, CertificateService $certs, TemplateResolver $resolver)
    {
        $trackKey = $request->input('track_key');
        $gender   = $request->input('gender', 'male');
        $pair     = config('certificates.tracks_teacher')[$trackKey];

        $tpl = $resolver->resolve('teacher', $trackKey, $gender);
        $pos = $tpl['positions'];
        $bg  = $tpl['bg_abs'];
        $style = $tpl['style'];

        // --- merge advanced style overrides ---
        if (is_array($inColors = $request->input('style.colors'))) {
            $style['colors'] = array_merge($style['colors'] ?? [], array_filter($inColors, function ($v) {
                return is_string($v) && $v !== '';
            }));
        }

        // language defaults (already in your code)
if (is_array($inFont = $request->input('style.font'))) {
    $style['font'] = array_merge(
        $style['font'] ?? [],
        array_filter($inFont, fn ($v) => is_string($v) && $v !== '')
    );
}

/* ✅ per-field font (UNCOMMENT / ADD THIS) */
if (is_array($inPer = $request->input('style.font_per'))) {
    $style['font_per'] = array_merge(
        $style['font_per'] ?? [],
        array_filter($inPer, fn ($v) => is_string($v) && $v !== '')
    );
}

/* (optional) per-field weight – you already merge this */
if (is_array($inWeight = $request->input('style.weight_per'))) {
    $style['weight_per'] = array_merge(
        $style['weight_per'] ?? [],
        array_filter($inWeight, fn ($v) => $v !== null && $v !== '')
    );
}
        
        // --------------------------------------


        // push duration mode + dates into style (shared for both Arabic & English)
        $style['duration_mode'] = $request->input('duration_mode', 'range');
        $style['duration_from'] = $request->input('duration_from') ?: null;
        $style['duration_to']   = $request->input('duration_to')   ?: null;

        $style['duration_from_ar'] = $request->input('duration_from_ar') ?: $request->input('duration_from');
        $style['duration_from_en'] = $request->input('duration_from_en') ?: $request->input('duration_from');
        $style['_print_flags']     = PrintFlags::fromRequest($request);
        $style['duration_from_ar'] = $request->input('duration_from_ar') ?: $request->input('duration_from');
        $style['duration_from_en'] = $request->input('duration_from_en') ?: $request->input('duration_from');
        $style['_print_flags']     = PrintFlags::fromRequest($request);

        $incomingStyle = (array) $request->input('style', []);

        if (!empty($incomingStyle['font']) && is_array($incomingStyle['font'])) {
            $style['font'] = array_merge($style['font'] ?? [], $incomingStyle['font']);
        }

        if (!empty($incomingStyle['font_per']) && is_array($incomingStyle['font_per'])) {
            $style['font_per'] = array_merge($style['font_per'] ?? [], $incomingStyle['font_per']);
        }

        //per-field sizes
        if (!empty($incomingStyle['size_per']) && is_array($incomingStyle['size_per'])) {
            $style['size_per'] = array_merge(
                $style['size_per'] ?? [],
                array_filter(
                    $incomingStyle['size_per'],
                    fn($v) => $v !== null && $v !== '' && is_numeric($v)
                )
            );
        }


        // دمج إحداثيات المُحرر
        if ($json = $request->input('custom_positions')) {
            $over = json_decode($json, true);
            if (is_array($over)) {
                foreach ($over as $k => $v) {
                    if (!is_array($v)) continue;
                    // Allow new keys like 'photo' to be merged even if not present in template pos
                    if (!isset($pos[$k]) || !is_array($pos[$k])) {
                        $pos[$k] = [];
                    }
                    $allowed = ['top','left','right','width','height','font','radius','border','border_color'];
                    $pos[$k] = array_merge($pos[$k], array_intersect_key($v, array_flip($allowed)));
                 }
            }
        }

        // صورة اختيارية
                $photoAbs = null;
        $remove = $request->boolean('remove_photo');

        if ($remove) {
            if (session()->has('teacher_last_photo')) {
                $old = session('teacher_last_photo');
                if (is_string($old) && @is_file($old)) { @unlink($old); }
            }
            session()->forget('teacher_last_photo');
        }

        if ($request->hasFile('photo')) {
            $tmpRel  = $request->file('photo')->store('tmp_uploads', 'local');
            $photoAbs = \Storage::disk('local')->path($tmpRel);
            session(['teacher_last_photo' => $photoAbs]);
        } elseif (!$remove) {
            $photoAbs = session('teacher_last_photo');
        }

        $relPath = $certs->generateSingle(
            nameAr: $request->input('name_ar'),
            nameEn: $request->input('name_en'),
            trackAr: $pair['ar'],
            trackEn: $pair['en'],
            certificateDate: $request->input('certificate_date'),
            durationFrom:    $request->input('duration_from'), 
            positions:       $pos,
            backgroundAbs:   $bg,
            style:           $style,   
            photoAbs:        $photoAbs
        );

        $url = URL::temporarySignedRoute('download', now()->addMinutes(60), [
            'p' => Crypt::encryptString($relPath),
        ]);

        return back()->with(['success'=>'تم إنشاء الشهادة بنجاح.', 'download_url'=>$url]);
    }

    public function preview(TeacherCertificateRequest $request, CertificateService $certs, TemplateResolver $resolver)
    {
        $trackKey = $request->input('track_key');
        $gender   = $request->input('gender', 'male');
        $pair     = config('certificates.tracks_teacher')[$trackKey];

        $tpl = $resolver->resolve('teacher', $trackKey, $gender);
        $pos = $tpl['positions'];
        $bg  = $tpl['bg_abs'];
        $style = $tpl['style'];

        // --- merge advanced style overrides ---
        if (is_array($inColors = $request->input('style.colors'))) {
            $style['colors'] = array_merge($style['colors'] ?? [], array_filter($inColors, function ($v) {
                return is_string($v) && $v !== '';
            }));
        }


        // language defaults (already in your code)
if (is_array($inFont = $request->input('style.font'))) {
    $style['font'] = array_merge(
        $style['font'] ?? [],
        array_filter($inFont, fn ($v) => is_string($v) && $v !== '')
    );
}

/*  per-field font  */
if (is_array($inPer = $request->input('style.font_per'))) {
    $style['font_per'] = array_merge(
        $style['font_per'] ?? [],
        array_filter($inPer, fn ($v) => is_string($v) && $v !== '')
    );
}

/* (optional) per-field weight – you already merge this */
if (is_array($inWeight = $request->input('style.weight_per'))) {
    $style['weight_per'] = array_merge(
        $style['weight_per'] ?? [],
        array_filter($inWeight, fn ($v) => $v !== null && $v !== '')
    );
}
        // --------------------------------------


        // push duration mode + dates into style (shared for both Arabic & English)
        $style['duration_mode'] = $request->input('duration_mode', 'range');
        $style['duration_from'] = $request->input('duration_from') ?: null;
        $style['duration_to']   = $request->input('duration_to')   ?: null;

        $style['duration_from_ar'] = $request->input('duration_from_ar') ?: $request->input('duration_from');
        $style['duration_from_en'] = $request->input('duration_from_en') ?: $request->input('duration_from');
        $style['_print_flags']     = PrintFlags::fromRequest($request);
        $style['duration_from_ar'] = $request->input('duration_from_ar') ?: $request->input('duration_from');
        $style['duration_from_en'] = $request->input('duration_from_en') ?: $request->input('duration_from');
        $style['_print_flags']     = PrintFlags::fromRequest($request);

        $incomingStyle = (array) $request->input('style', []);

        if (!empty($incomingStyle['font']) && is_array($incomingStyle['font'])) {
            $style['font'] = array_merge($style['font'] ?? [], $incomingStyle['font']);
        }

        if (!empty($incomingStyle['font_per']) && is_array($incomingStyle['font_per'])) {
            $style['font_per'] = array_merge($style['font_per'] ?? [], $incomingStyle['font_per']);
        }


        //         $sty['duration_from_ar'] = $request->input('duration_from_ar') ?: $request->input('duration_from');
        // $sty['duration_from_en'] = $request->input('duration_from_en') ?: $request->input('duration_from');
        // $sty['_print_flags']     = PrintFlags::fromRequest($request);
        if ($json = $request->input('custom_positions')) {
                    $over = json_decode($json, true);
                    \Log::info('Controller received custom_positions', ['raw' => $json, 'decoded' => $over]);
                    if (is_array($over)) {
                        foreach ($over as $k => $v) {
                            if (!isset($pos[$k]) || !is_array($v)) continue;
                            $allowed = ['top','left','right','width','height','font','radius','border','border_color'];
                            $pos[$k] = array_merge($pos[$k], array_intersect_key($v, array_flip($allowed)));
                            // ✅ Log font size for each field after merge
                            if (isset($v['font'])) {
                                \Log::info("Controller: Merged font size for {$k}", ['font' => $pos[$k]['font']]);
                            }
                        }
                    }
                }

        $photoAbs = null;
        $remove = $request->boolean('remove_photo');

        if ($remove) {
            if (session()->has('teacher_last_photo')) {
                $old = session('teacher_last_photo');
                if (is_string($old) && @is_file($old)) { @unlink($old); }
            }
            session()->forget('teacher_last_photo');
        }

        if ($request->hasFile('photo')) {
            $tmpRel  = $request->file('photo')->store('tmp_uploads', 'local');
            $photoAbs = \Storage::disk('local')->path($tmpRel);
            session(['teacher_last_photo' => $photoAbs]);
        } elseif (!$remove) {
            $photoAbs = session('teacher_last_photo');
        }

        // Turn on the debug panel for preview (query param ?debug=1 to enable)
        $style['_debug_panel']     = $request->boolean('debug', false);

        // Optional: flip mPDF autoLangToFont off during preview to diagnose font override
        $style['_debug_force_css'] = $request->boolean('force_css', false);

        $absPath = $certs->generatePreview(
            nameAr: $request->input('name_ar'),
            nameEn: $request->input('name_en'),
            trackAr: $pair['ar'],
            trackEn: $pair['en'],
            certificateDate: $request->input('certificate_date'),
            durationFrom:    $request->input('duration_from'), 
            positions:       $pos,
            backgroundAbs:   $bg,
            // style:           $sty,
            style:           $style,   
            photoAbs:        $photoAbs
        );

        return response()->file($absPath, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function download(Request $request)
    {
        $encrypted = $request->query('p');
        abort_unless($encrypted, 404);

        try {
            $relative = Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            \Log::error("Failed to decrypt download path", [
                'error' => $e->getMessage(),
                'encrypted_length' => strlen($encrypted)
            ]);
            abort(403, 'Invalid signature');
        }

        \Log::debug("Download request", [
            'relative_path' => $relative,
            'exists' => Storage::disk('local')->exists($relative),
            'full_path' => Storage::disk('local')->path($relative)
        ]);

        if (!Storage::disk('local')->exists($relative)) {
            \Log::warning("Download file not found", ['path' => $relative]);
            abort(404);
        }

        $filename = basename($relative);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        // Log the download
        ActivityLogService::logDownload($filename, $extension, [
            'file_path' => $relative,
        ]);

        return Storage::disk('local')->download($relative, $filename);
    }
}
