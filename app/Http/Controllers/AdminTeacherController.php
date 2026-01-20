<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherCertificateRequest;
use App\Models\Track;
use App\Models\TeacherSetting;
use App\Services\CertificateService;
use App\Services\TemplateResolver;
use App\Services\FontRegistry;
use App\Support\PrintFlags;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class AdminTeacherController extends Controller
{
    /**
     * Display the full teacher certificate editor (admin).
     */
    public function index(TemplateResolver $resolver)
    {
        // Clear photo session on page load (fresh start)
        if (session()->has('teacher_last_photo')) {
            $old = session('teacher_last_photo');
            if (is_string($old) && @is_file($old)) {
                @unlink($old);
            }
        }
        session()->forget('teacher_last_photo');

        $fonts = (new FontRegistry())->families();
        $user = auth()->user();

        // Get teacher tracks from database (exclude student tracks starting with 's_')
        // Filter by institution access
        $dbTracks = Track::where('active', true)
            ->accessibleBy($user)
            ->get();
        $tracks = [];

        foreach ($dbTracks as $track) {
            // Only include teacher tracks - exclude student tracks (starting with 's_')
            if (strpos($track->key, 's_') !== 0) {
                $tracks[$track->key] = [
                    'ar' => $track->getDisplayName($user->isSuperUser()),
                    'en' => $track->name_en,
                    'id' => $track->id,
                    'institution_id' => $track->institution_id,
                ];
            }
        }

        // Merge config tracks (for backward compatibility)
        $configTracks = config('certificates.tracks_teacher', []);
        foreach ($configTracks as $key => $names) {
            if (!isset($tracks[$key])) {
                $tracks[$key] = $names;
            }
        }

        // Build map for the editor (per track & gender)
        $tplMap = [];
        foreach (array_keys($tracks) as $trackKey) {
            foreach (['male','female'] as $gender) {
                try {
                    $tpl = $resolver->resolve('teacher', $trackKey, $gender);
                    $tplMap[$trackKey][$gender] = [
                        'bg_url'    => route('teacher.admin.bg', [$trackKey, $gender]),
                        'page_mm'   => [
                            'w' => (float) \Illuminate\Support\Arr::get($tpl, 'page_mm.w', 297),
                            'h' => (float) \Illuminate\Support\Arr::get($tpl, 'page_mm.h', 210),
                        ],
                        'positions' => $tpl['positions'] ?? [],
                    ];
                } catch (\Exception $e) {
                    // Track exists but has no settings - provide defaults
                    \Log::warning("Failed to resolve template for teacher track={$trackKey}, gender={$gender}: " . $e->getMessage());
                    $tplMap[$trackKey][$gender] = [
                        'bg_url'    => '', // Will show error in UI
                        'page_mm'   => ['w' => 297, 'h' => 210],
                        'positions' => [], // Empty positions - user must configure
                    ];
                }
            }
        }

        return view('teacher.admin', compact('tracks', 'tplMap', 'fonts'));
    }

    /**
     * Generate and download certificate (admin).
     * Uses image->PDF conversion approach for consistency with student side.
     */
    public function store(TeacherCertificateRequest $request, \App\Services\ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        $trackKey = $request->input('track_key');
        $gender   = $request->input('gender', 'male');
        $pair     = $this->getTrackNames($trackKey);

        // Use image settings for consistency with image->PDF approach
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

        // push duration mode + dates into style (shared for both Arabic & English)
        $style['duration_mode'] = $request->input('duration_mode', 'range');
        $style['duration_from'] = $request->input('duration_from') ?: null;
        $style['duration_to']   = $request->input('duration_to')   ?: null;

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
            $photoAbs = Storage::disk('local')->path($tmpRel);
            session(['teacher_last_photo' => $photoAbs]);
        } elseif (!$remove) {
            $photoAbs = session('teacher_last_photo');
        }

        // Generate image first
        $imageRel = $imageCerts->generateSingle(
            $request->input('name_ar'),
            $request->input('name_en'),
            $pair['ar'],
            $pair['en'],
            $request->input('certificate_date'),
            $request->input('duration_from'),
            $pos,
            $bg,
            $style,
            $photoAbs
        );

        $imageAbs = Storage::disk('local')->path($imageRel);

        // Convert image to PDF
        $pdfAbs = $this->convertImageToPdf($imageAbs);

        // Clean up temporary image
        @unlink($imageAbs);

        // Move PDF to storage and create download URL
        $pdfName = 'teacher_certificate_' . time() . '.pdf';
        $pdfRel = 'certificates/' . $pdfName;
        $pdfDestAbs = Storage::disk('local')->path($pdfRel);

        // Ensure directory exists
        $pdfDir = dirname($pdfDestAbs);
        if (!is_dir($pdfDir)) {
            @mkdir($pdfDir, 0775, true);
        }

        rename($pdfAbs, $pdfDestAbs);

        $url = URL::temporarySignedRoute('download', now()->addMinutes(60), [
            'p' => Crypt::encryptString($pdfRel),
        ]);

        return back()->with(['success'=>'تم إنشاء الشهادة بنجاح.', 'download_url'=>$url]);
    }

    /**
     * Preview certificate (admin).
     * Uses image->PDF conversion approach for consistency with student side.
     */
    public function preview(TeacherCertificateRequest $request, \App\Services\ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        $trackKey = $request->input('track_key');
        $gender   = $request->input('gender', 'male');
        $pair     = $this->getTrackNames($trackKey);

        // Use image settings for consistency with image->PDF approach
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

        // push duration mode + dates into style (shared for both Arabic & English)
        $style['duration_mode'] = $request->input('duration_mode', 'range');
        $style['duration_from'] = $request->input('duration_from') ?: null;
        $style['duration_to']   = $request->input('duration_to')   ?: null;

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

        //per-field sizes (THIS WAS MISSING - causing text size to not work in preview)
        if (!empty($incomingStyle['size_per']) && is_array($incomingStyle['size_per'])) {
            $style['size_per'] = array_merge(
                $style['size_per'] ?? [],
                array_filter(
                    $incomingStyle['size_per'],
                    fn($v) => $v !== null && $v !== '' && is_numeric($v)
                )
            );
        }

        if ($json = $request->input('custom_positions')) {
            $over = json_decode($json, true);
            \Log::info('Controller received custom_positions', ['raw' => $json, 'decoded' => $over]);
            if (is_array($over)) {
                foreach ($over as $k => $v) {
                    if (!is_array($v)) continue;
                    // Allow new keys like 'photo' to be merged even if not present in template pos
                    if (!isset($pos[$k]) || !is_array($pos[$k])) {
                        $pos[$k] = [];
                    }
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
            $photoAbs = Storage::disk('local')->path($tmpRel);
            session(['teacher_last_photo' => $photoAbs]);
        } elseif (!$remove) {
            $photoAbs = session('teacher_last_photo');
        }

        // Generate image first
        $imageRel = $imageCerts->generateSingle(
            $request->input('name_ar'),
            $request->input('name_en'),
            $pair['ar'],
            $pair['en'],
            $request->input('certificate_date'),
            $request->input('duration_from'),
            $pos,
            $bg,
            $style,
            $photoAbs
        );

        $imageAbs = Storage::disk('local')->path($imageRel);

        // Convert image to PDF
        $pdfAbs = $this->convertImageToPdf($imageAbs);

        // Clean up temporary image
        @unlink($imageAbs);

        return response()->file($pdfAbs, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Save teacher certificate defaults (admin only).
     */
    public function save(Request $request)
    {
        $request->validate([
            'track_key' => 'required|string',
            'gender'    => 'required|in:male,female',
        ]);

        $trackKey = $request->input('track_key');
        $gender   = $request->input('gender');

        // Get or create track
        $track = Track::where('key', $trackKey)->first();

        if (!$track) {
            // Try to create from config
            $trackConfig = config("certificates.tracks_teacher.{$trackKey}");
            if (!$trackConfig) {
                return back()->withErrors(['track_key' => 'Invalid track key']);
            }

            $track = Track::create([
                'key' => $trackKey,
                'name_ar' => $trackConfig['ar'],
                'name_en' => $trackConfig['en'],
                'active'  => true,
            ]);
        }

        // Extract positions from custom_positions if provided
        $positions = [];
        if ($json = $request->input('custom_positions')) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $positions = $decoded;
            }
        }

        // Build style array
        $style = [];

        // Language fonts
        if (is_array($inFont = $request->input('style.font'))) {
            $style['font'] = array_filter($inFont, fn ($v) => is_string($v) && $v !== '');
        }

        // Per-field fonts
        if (is_array($inFontPer = $request->input('style.font_per'))) {
            $style['font_per'] = array_filter($inFontPer, fn ($v) => is_string($v) && $v !== '');
        }

        // Per-field weights
        if (is_array($inWeight = $request->input('style.weight_per'))) {
            $style['weight_per'] = array_filter($inWeight, fn ($v) => $v !== null && $v !== '');
        }

        // Per-field sizes
        if (is_array($inSize = $request->input('style.size_per'))) {
            $style['size_per'] = array_filter($inSize, fn ($v) => is_numeric($v));
        }

        // Per-field alignment (if implemented)
        if (is_array($inAlign = $request->input('style.align_per'))) {
            $style['align_per'] = array_filter($inAlign, fn ($v) => is_string($v) && $v !== '');
        }

        // Colors
        if (is_array($inColors = $request->input('style.colors'))) {
            $style['colors'] = array_filter($inColors, fn ($v) => is_string($v) && $v !== '');
        }

        // Build print_defaults array
        $printDefaults = [
            'arabic_only'  => $request->boolean('arabic_only'),
            'english_only' => $request->boolean('english_only'),
        ];

        // Per-field print flags
        $printFields = (array) $request->input('print', []);
        foreach ($printFields as $k => $v) {
            $printDefaults[$k] = ($v === '1' || $v === 1 || $v === true);
        }

        // Determine certificate_bg (preserve existing DB value or use config default)
        $certificateBg = '';

        // First, check if there's an existing setting in the database
        $existingSetting = TeacherSetting::where('track_id', $track->id)
            ->where('gender', $gender)
            ->first();

        if ($existingSetting && $existingSetting->certificate_bg) {
            // Preserve existing database background
            $certificateBg = $existingSetting->certificate_bg;
        } else {
            // Fall back to config (for config-based tracks)
            $tpl = config("certificates.templates.teacher.{$trackKey}.{$gender}");
            $certificateBg = $tpl['bg'] ?? '';
        }

        // Date type
        $dateType = $request->input('duration_mode', 'duration') === 'range' ? 'duration' : 'end';

        // Upsert the setting
        TeacherSetting::updateOrCreate(
            [
                'track_id' => $track->id,
                'gender'   => $gender,
            ],
            [
                'certificate_bg'  => $certificateBg,
                'positions'       => $positions,
                'style'           => $style,
                'print_defaults'  => $printDefaults,
                'date_type'       => $dateType,
                'notes'           => $request->input('notes'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ الإعدادات الافتراضية بنجاح.'
        ]);
    }

    /**
     * Serve background image for editor (admin).
     */
    public function bg(string $trackKey, string $gender)
    {
        // Try config first
        $tpl = config("certificates.templates.teacher.{$trackKey}.{$gender}");

        if ($tpl && isset($tpl['bg'])) {
            $bgPath = public_path($tpl['bg']);
            if (is_file($bgPath)) {
                return response()->file($bgPath);
            }
        }

        // Try database
        $track = Track::where('key', $trackKey)->first();
        if ($track) {
            $setting = TeacherSetting::where('track_id', $track->id)
                ->where('gender', $gender)
                ->first();

            if ($setting && $setting->certificate_bg) {
                $bgPath = public_path($setting->certificate_bg);
                if (is_file($bgPath)) {
                    return response()->file($bgPath);
                }
            }
        }

        abort(404, 'Background not found');
    }

    /**
     * Add a new track with certificate templates.
     */
    public function addTrack(Request $request)
    {
        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'male_certificate' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'female_certificate' => 'required|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        // Generate unique track key
        $baseKey = 't_' . \Str::slug($request->input('name_en'), '_');
        $trackKey = $baseKey;
        $counter = 1;

        // Ensure uniqueness
        while (Track::where('key', $trackKey)->exists()) {
            $trackKey = $baseKey . '_' . $counter;
            $counter++;
        }

        // Create track with current user's institution
        $user = auth()->user();
        $track = Track::create([
            'key' => $trackKey,
            'name_ar' => $request->input('name_ar'),
            'name_en' => $request->input('name_en'),
            'active' => true,
            'institution_id' => $user->institution_id, // Assign to user's institution
        ]);

        // Upload certificate templates
        $uploadDir = public_path('images/templates/teacher');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Male certificate
        $maleFile = $request->file('male_certificate');
        $maleExt = $maleFile->getClientOriginalExtension();
        $maleName = $trackKey . '-male.' . $maleExt;
        $maleFile->move($uploadDir, $maleName);
        $malePath = 'images/templates/teacher/' . $maleName;

        // Female certificate
        $femaleFile = $request->file('female_certificate');
        $femaleExt = $femaleFile->getClientOriginalExtension();
        $femaleName = $trackKey . '-female.' . $femaleExt;
        $femaleFile->move($uploadDir, $femaleName);
        $femalePath = 'images/templates/teacher/' . $femaleName;

        // Default positions for new tracks
        $defaultPositions = [
            'cert_date' => [
                'top' => 23,
                'right' => 56,
                'width' => 78,
                'font' => 5,
            ],
            'ar_name' => [
                'top' => 78,
                'right' => 12,
                'width' => 90,
                'font' => 7,
            ],
            'en_name' => [
                'top' => 78,
                'left' => 13,
                'width' => 120,
                'font' => 6,
            ],
            'ar_track' => [
                'top' => 98,
                'right' => 12,
                'width' => 90,
                'font' => 6,
            ],
            'en_track' => [
                'top' => 98,
                'left' => 13,
                'width' => 120,
                'font' => 5.5,
            ],
            'ar_from' => [
                'top' => 118,
                'right' => 45,
                'width' => 45,
                'font' => 6,
            ],
            'en_from' => [
                'top' => 114,
                'left' => 50,
                'width' => 60,
                'font' => 5.2,
            ],
            'photo' => [
                'top' => 35,
                'left' => 30,
                'width' => 30,
                'height' => 30,
                'radius' => 6,
                'border' => 0.6,
                'border_color' => '#1f2937',
            ],
        ];

        // Default style
        $defaultStyle = [
            'font' => [
                'ar' => 'Amiri',
                'en' => 'DejaVu Sans',
            ],
            'colors' => [
                'cert_date' => '#0f172a',
                'ar_name' => '#334155',
                'en_name' => '#0f172a',
                'ar_track' => '#0891b2',
                'en_track' => '#0891b2',
                'ar_from' => '#64748b',
                'en_from' => '#64748b',
            ],
        ];

        // Default print flags
        $defaultPrintFlags = [
            'arabic_only' => false,
            'english_only' => false,
            'ar_name' => true,
            'en_name' => true,
            'ar_track' => true,
            'en_track' => true,
            'ar_from' => true,
            'en_from' => true,
        ];

        // Create teacher settings for male
        TeacherSetting::create([
            'track_id' => $track->id,
            'gender' => 'male',
            'certificate_bg' => $malePath,
            'positions' => $defaultPositions,
            'style' => $defaultStyle,
            'print_defaults' => $defaultPrintFlags,
            'date_type' => 'duration',
        ]);

        // Create teacher settings for female
        TeacherSetting::create([
            'track_id' => $track->id,
            'gender' => 'female',
            'certificate_bg' => $femalePath,
            'positions' => $defaultPositions,
            'style' => $defaultStyle,
            'print_defaults' => $defaultPrintFlags,
            'date_type' => 'duration',
        ]);

        return back()->with('success', 'تم إضافة المسار بنجاح.');
    }

    /**
     * Get track data for editing.
     */
    public function getTrack($id)
    {
        $track = Track::findOrFail($id);
        $user = auth()->user();

        // Check authorization - user must be super user or track must be in their institution
        if (!$user->isSuperUser() && $track->institution_id !== $user->institution_id) {
            abort(403, 'غير مصرح لك بعرض هذا المسار');
        }

        return response()->json([
            'id' => $track->id,
            'key' => $track->key,
            'name_ar' => $track->name_ar,
            'name_en' => $track->name_en,
            'active' => $track->active,
            'institution_id' => $track->institution_id,
        ]);
    }

    /**
     * Update a track (name and optionally certificate templates).
     */
    public function updateTrack(Request $request, $id)
    {
        $track = Track::findOrFail($id);
        $user = auth()->user();

        // Check authorization - user must be super user or track must be in their institution
        if (!$user->isSuperUser() && $track->institution_id !== $user->institution_id) {
            abort(403, 'غير مصرح لك بتعديل هذا المسار');
        }

        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'male_certificate' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
            'female_certificate' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        // Update track name
        $track->update([
            'name_ar' => $request->input('name_ar'),
            'name_en' => $request->input('name_en'),
        ]);

        // Update certificate templates if provided
        $uploadDir = public_path('images/templates/teacher');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Male certificate
        if ($request->hasFile('male_certificate')) {
            $maleFile = $request->file('male_certificate');
            $maleExt = $maleFile->getClientOriginalExtension();
            $maleName = $track->key . '-male.' . $maleExt;
            $maleFile->move($uploadDir, $maleName);
            $malePath = 'images/templates/teacher/' . $maleName;

            TeacherSetting::where('track_id', $track->id)
                ->where('gender', 'male')
                ->update(['certificate_bg' => $malePath]);
        }

        // Female certificate
        if ($request->hasFile('female_certificate')) {
            $femaleFile = $request->file('female_certificate');
            $femaleExt = $femaleFile->getClientOriginalExtension();
            $femaleName = $track->key . '-female.' . $femaleExt;
            $femaleFile->move($uploadDir, $femaleName);
            $femalePath = 'images/templates/teacher/' . $femaleName;

            TeacherSetting::where('track_id', $track->id)
                ->where('gender', 'female')
                ->update(['certificate_bg' => $femalePath]);
        }

        return back()->with('success', 'تم تعديل المسار بنجاح.');
    }

    /**
     * Delete a track and its templates.
     */
    public function deleteTrack($id)
    {
        $track = Track::findOrFail($id);
        $user = auth()->user();

        // Check authorization - user must be super user or track must be in their institution
        if (!$user->isSuperUser() && $track->institution_id !== $user->institution_id) {
            abort(403, 'غير مصرح لك بحذف هذا المسار');
        }

        // Get associated settings to delete certificate files
        $settings = TeacherSetting::where('track_id', $track->id)->get();

        foreach ($settings as $setting) {
            // Delete certificate file
            $certPath = public_path($setting->certificate_bg);
            if (is_file($certPath)) {
                @unlink($certPath);
            }
        }

        // Delete track (cascade will delete teacher_settings)
        $track->delete();

        return back()->with('success', 'تم حذف المسار بنجاح.');
    }

    /**
     * Get track names from database or config.
     *
     * @param string $trackKey
     * @return array{ar: string, en: string}
     */
    protected function getTrackNames(string $trackKey): array
    {
        // Try database first
        $track = Track::where('key', $trackKey)->first();
        if ($track) {
            return [
                'ar' => $track->name_ar,
                'en' => $track->name_en,
            ];
        }

        // Fallback to config
        $config = config("certificates.tracks_teacher.{$trackKey}");
        if ($config) {
            return $config;
        }

        // If not found anywhere, throw exception
        throw new \InvalidArgumentException("Track not found: {$trackKey}");
    }

    /**
     * Preview certificate as image (admin).
     */
    public function previewImage(TeacherCertificateRequest $request, \App\Services\ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        $trackKey = $request->input('track_key');
        $gender   = $request->input('gender', 'male');
        $pair     = $this->getTrackNames($trackKey);

        $tpl = $resolver->resolve('teacher', $trackKey, $gender); // true = use image settings
        $pos = $tpl['positions'];
        $bg  = $tpl['bg_abs'];
        $style = $tpl['style'];

        // Merge style overrides (same as PDF preview)
        if (is_array($inColors = $request->input('style.colors'))) {
            $style['colors'] = array_merge($style['colors'] ?? [], array_filter($inColors, function ($v) {
                return is_string($v) && $v !== '';
            }));
        }

        if (is_array($inFont = $request->input('style.font'))) {
            $style['font'] = array_merge($style['font'] ?? [], array_filter($inFont, fn ($v) => is_string($v) && $v !== ''));
        }

        if (is_array($inPer = $request->input('style.font_per'))) {
            $style['font_per'] = array_merge($style['font_per'] ?? [], array_filter($inPer, fn ($v) => is_string($v) && $v !== ''));
        }

        if (is_array($inWeight = $request->input('style.weight_per'))) {
            $style['weight_per'] = array_merge($style['weight_per'] ?? [], array_filter($inWeight, fn ($v) => $v !== null && $v !== ''));
        }

        if (is_array($inSize = $request->input('style.size_per'))) {
            $style['size_per'] = array_merge($style['size_per'] ?? [], array_filter($inSize, fn ($v) => is_numeric($v)));
        }

        $style['duration_mode'] = $request->input('duration_mode', 'range');
        $style['duration_from'] = $request->input('duration_from') ?: null;
        $style['duration_to']   = $request->input('duration_to')   ?: null;
        $style['_print_flags']  = PrintFlags::fromRequest($request);

        // Merge custom positions
        if ($json = $request->input('custom_positions')) {
            $over = json_decode($json, true);
            if (is_array($over)) {
                foreach ($over as $k => $v) {
                    if (!is_array($v)) continue;
                    if (!isset($pos[$k]) || !is_array($pos[$k])) {
                        $pos[$k] = [];
                    }
                    $allowed = ['top','left','right','width','height','font','radius','border','border_color'];
                    $pos[$k] = array_merge($pos[$k], array_intersect_key($v, array_flip($allowed)));
                }
            }
        }

        // Handle photo
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
            $photoAbs = Storage::disk('local')->path($tmpRel);
            session(['teacher_last_photo' => $photoAbs]);
        } elseif (!$remove) {
            $photoAbs = session('teacher_last_photo');
        }

        // Generate image
        $imageRel = $imageCerts->generateSingle(
            $request->input('name_ar'),
            $request->input('name_en'),
            $pair['ar'],
            $pair['en'],
            $request->input('certificate_date'),
            $request->input('duration_from'),
            $pos,
            $bg,
            $style,
            $photoAbs
        );

        $imageAbs = Storage::disk('local')->path($imageRel);

        return response()->file($imageAbs, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'inline; filename="preview.png"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Generate and download certificate as image (admin).
     */
    public function storeImage(TeacherCertificateRequest $request, \App\Services\ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        $trackKey = $request->input('track_key');
        $gender   = $request->input('gender', 'male');
        $pair     = $this->getTrackNames($trackKey);

        $tpl = $resolver->resolve('teacher', $trackKey, $gender); // true = use image settings
        $pos = $tpl['positions'];
        $bg  = $tpl['bg_abs'];
        $style = $tpl['style'];

        // Merge style overrides
        if (is_array($inColors = $request->input('style.colors'))) {
            $style['colors'] = array_merge($style['colors'] ?? [], array_filter($inColors, function ($v) {
                return is_string($v) && $v !== '';
            }));
        }

        if (is_array($inFont = $request->input('style.font'))) {
            $style['font'] = array_merge($style['font'] ?? [], array_filter($inFont, fn ($v) => is_string($v) && $v !== ''));
        }

        if (is_array($inPer = $request->input('style.font_per'))) {
            $style['font_per'] = array_merge($style['font_per'] ?? [], array_filter($inPer, fn ($v) => is_string($v) && $v !== ''));
        }

        if (is_array($inWeight = $request->input('style.weight_per'))) {
            $style['weight_per'] = array_merge($style['weight_per'] ?? [], array_filter($inWeight, fn ($v) => $v !== null && $v !== ''));
        }

        if (is_array($inSize = $request->input('style.size_per'))) {
            $style['size_per'] = array_merge($style['size_per'] ?? [], array_filter($inSize, fn ($v) => is_numeric($v)));
        }

        $style['duration_mode'] = $request->input('duration_mode', 'range');
        $style['duration_from'] = $request->input('duration_from') ?: null;
        $style['duration_to']   = $request->input('duration_to')   ?: null;
        $style['_print_flags']  = PrintFlags::fromRequest($request);

        // Merge custom positions
        if ($json = $request->input('custom_positions')) {
            $over = json_decode($json, true);
            if (is_array($over)) {
                foreach ($over as $k => $v) {
                    if (!is_array($v)) continue;
                    if (!isset($pos[$k]) || !is_array($pos[$k])) {
                        $pos[$k] = [];
                    }
                    $allowed = ['top','left','right','width','height','font','radius','border','border_color'];
                    $pos[$k] = array_merge($pos[$k], array_intersect_key($v, array_flip($allowed)));
                }
            }
        }

        // Handle photo
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
            $photoAbs = Storage::disk('local')->path($tmpRel);
            session(['teacher_last_photo' => $photoAbs]);
        } elseif (!$remove) {
            $photoAbs = session('teacher_last_photo');
        }

        // Generate image
        $imageRel = $imageCerts->generateSingle(
            $request->input('name_ar'),
            $request->input('name_en'),
            $pair['ar'],
            $pair['en'],
            $request->input('certificate_date'),
            $request->input('duration_from'),
            $pos,
            $bg,
            $style,
            $photoAbs
        );

        $url = URL::temporarySignedRoute('download', now()->addMinutes(60), [
            'p' => Crypt::encryptString($imageRel),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الصورة بنجاح.',
            'download_url' => $url,
        ]);
    }

    /**
     * Convert image to PDF
     * Uses mPDF library to wrap the image in a PDF document
     */
    private function convertImageToPdf(string $imageAbs): string
    {
        // Create mPDF instance with A4 landscape format and zero margins
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',  // A4 Landscape: 297mm (width) x 210mm (height)
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
            'dpi' => 300,
        ]);

        // Embed image in HTML with proper CSS to fill the entire page
        $html = sprintf(
            '<div style="margin:0;padding:0;width:100%%;height:100%%;position:absolute;top:0;left:0;">' .
            '<img src="%s" style="width:100%%;height:100%%;display:block;object-fit:fill;" />' .
            '</div>',
            $imageAbs
        );

        $mpdf->WriteHTML($html);

        // Save PDF to temp location
        $pdfPath = str_replace('.png', '.pdf', $imageAbs);
        $pdfPath = str_replace('tmp_previews_images', 'tmp_uploads', $pdfPath);
        $pdfPath = str_replace('certificates_images', 'tmp_uploads', $pdfPath);

        // Ensure directory exists
        $dir = dirname($pdfPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);

        return $pdfPath;
    }
}
