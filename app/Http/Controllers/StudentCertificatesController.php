<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentsRequest;
use App\Services\CertificateService;
use App\Services\ImageCertificateService;
use App\Services\ActivityLogService;
use App\Support\PrintFlags;
use App\Services\TemplateResolver;
use App\Services\FontRegistry;
use App\Support\StudentListReader;
use App\Models\Track;
use App\Models\StudentSetting;
use App\Models\StudentImageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;
use ZipArchive;

class StudentCertificatesController extends Controller
{
    /**
     * Simple students interface (for teachers)
     */
    public function index()
    {
        $user = auth()->user();

        // Get student tracks from database (exclude teacher tracks starting with 't_')
        // Filter by institution access
        $dbTracks = Track::where('active', true)
            ->accessibleBy($user)
            ->get()
            ->filter(function($track) {
                return strpos($track->key, 't_') !== 0;
            });

        // For super users, show tracks with institution prefix
        $isSuperUser = $user->isSuperUser();

        return view('students.index', [
            'tracks' => $dbTracks,
            'isSuperUser' => $isSuperUser,
        ]);
    }

    /**
     * Admin students interface (full editor)
     */
    public function adminIndex(TemplateResolver $resolver)
    {
        $fonts = (new FontRegistry())->families();
        $user = auth()->user();

        // Get student tracks from database (exclude teacher tracks starting with 't_')
        // Filter by institution access
        $dbTracks = Track::where('active', true)
            ->accessibleBy($user)
            ->get();
        $tracks = [];

        foreach ($dbTracks as $track) {
            // Only include student tracks - exclude teacher tracks (starting with 't_')
            if (strpos($track->key, 't_') !== 0) {
                $tracks[$track->key] = [
                    'ar' => $track->getDisplayName($user->isSuperUser()),
                    'en' => $track->name_en,
                    'id' => $track->id,
                    'institution_id' => $track->institution_id,
                ];
            }
        }

        // Merge config tracks (for backward compatibility) - only for super users
        // Regular institution users should only see their institution's database tracks
        if ($user->isSuperUser()) {
            $configTracks = config('certificates.tracks_student', []);
            foreach ($configTracks as $key => $names) {
                if (!isset($tracks[$key])) {
                    $tracks[$key] = $names;
                }
            }
        }

        // Build map for the editor (per track & gender)
        $tplMap = [];
        foreach (array_keys($tracks) as $trackKey) {
            foreach (['male','female'] as $gender) {
                try {
                    $tpl = $resolver->resolve('student', $trackKey, $gender);
                    $tplMap[$trackKey][$gender] = [
                        'bg_url'    => route('students.admin.bg', [$trackKey, $gender]),
                        'page_mm'   => [
                            'w' => (float) Arr::get($tpl, 'page_mm.w', 297),
                            'h' => (float) Arr::get($tpl, 'page_mm.h', 210),
                        ],
                        'positions' => $tpl['positions'] ?? [],
                    ];
                } catch (\Exception $e) {
                    // Track exists but has no settings - provide defaults
                    \Log::warning("Failed to resolve template for student track={$trackKey}, gender={$gender}: " . $e->getMessage());
                    $tplMap[$trackKey][$gender] = [
                        'bg_url'    => '', // Will show error in UI
                        'page_mm'   => ['w' => 297, 'h' => 210],
                        'positions' => [], // Empty positions - user must configure
                    ];
                }
            }
        }

        return view('students.admin', compact('tracks', 'tplMap', 'fonts'));
    }

    // Serve background file
    public function bg(string $track, string $gender, TemplateResolver $resolver)
    {
        $tpl = $resolver->resolve('student', $track, $gender);
        $abs = $tpl['bg_abs'];
        if (!is_file($abs)) abort(404);
        $mime = match (strtolower(pathinfo($abs, PATHINFO_EXTENSION))) {
            'png'  => 'image/png',
            'webp' => 'image/webp',
            default=> 'image/jpeg',
        };
        return response()->file($abs, ['Content-Type'=>$mime, 'Cache-Control'=>'public, max-age=604800']);
    }

    public function preview(StudentsRequest $request, ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        // Use image generation with PDF conversion for consistency with /studentimg
        [$students, $tpl, $pair, $posMm, $photoAbs, $zipAbsUp, $style] = $this->prepareAll($request, $resolver, true, false); // false = use StudentSetting

        $first = null;
        foreach ($students as $st) {
            if (trim((string)($st['name_ar'] ?? '')) !== '' && trim((string)($st['name_en'] ?? '')) !== '') { $first = $st; break; }
        }
        if (!$first) return response()->json(['message' => 'لا توجد سجلات صالحة في الملف.'], 422);

        // Generate image first
        $imageRel = $imageCerts->generateSingle(
            $first['name_ar'], $first['name_en'],
            (string)$pair['ar'], (string)$pair['en'],
            $request->input('certificate_date'),
            $request->input('duration_from'),
            $posMm, $tpl['bg_abs'], $style, $photoAbs
        );

        if ($zipAbsUp) @unlink($zipAbsUp);

        $imageAbs = Storage::disk('local')->path($imageRel);

        // Convert image to PDF
        $pdfAbs = $this->convertImageToPdf($imageAbs);

        // Clean up the temporary image
        @unlink($imageAbs);

        return response()->file($pdfAbs, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function store(StudentsRequest $request, ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        [$students, $tpl, $pair, $posMm, $_photoAbs, $zipAbsUp, $style] = $this->prepareAll($request, $resolver, false, false); // false, false = !forPreview, use StudentSetting

        $generatedAbs = [];
        $imgMap = [];
        if ($zipAbsUp) $imgMap = $this->indexZipImages($zipAbsUp);

        foreach ($students as $st) {
            $ar = trim((string)($st['name_ar'] ?? ''));
            $en = trim((string)($st['name_en'] ?? ''));
            if ($ar === '' || $en === '') continue;

            $photoAbs = null;
            if ($zipAbsUp) $photoAbs = $this->matchPhotoFromZip($imgMap, $zipAbsUp, $st);

            // Generate image first
            $imageRel = $imageCerts->generateSingle(
                $ar, $en,
                (string)$pair['ar'], (string)$pair['en'],
                $request->input('certificate_date'),
                $request->input('duration_from'),
                $posMm, $tpl['bg_abs'], $style, $photoAbs
            );

            $imageAbs = Storage::disk('local')->path($imageRel);

            // Convert image to PDF
            $pdfAbs = $this->convertImageToPdf($imageAbs);

            // Clean up temporary image
            @unlink($imageAbs);

            $generatedAbs[] = $pdfAbs;
        }

        // Name ZIP as: track_name_timestamp.zip
        $trackSlug = \Str::slug($pair['en'] ?: $pair['ar'], '_');
        $zipRel = 'tmp_uploads/'.$trackSlug.'_'.date('Ymd_His').'.zip';
        $zipAbs = Storage::disk('local')->path($zipRel);
        @mkdir(dirname($zipAbs), 0775, true);

        $zip = new ZipArchive();
        if ($zip->open($zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            if ($zipAbsUp) @unlink($zipAbsUp);
            foreach ($generatedAbs as $a) @unlink($a);
            return back()->withErrors(['zip' => 'تعذر إنشاء ملف ZIP.']);
        }
        foreach ($generatedAbs as $a) $zip->addFile($a, basename($a));
        $zip->close();

        foreach ($generatedAbs as $a) @unlink($a);
        if ($zipAbsUp) @unlink($zipAbsUp);

        $url = URL::temporarySignedRoute('download', now()->addMinutes(60), [
            'p' => Crypt::encryptString($zipRel)
        ]);

        // Log certificate generation
        ActivityLogService::logGenerate($pair['ar'] ?? $pair['en'], count($generatedAbs), 'pdf');

        return back()->with(['success'=>'تم إنشاء شهادات الطلاب وضغطها بنجاح.', 'download_url'=>$url]);
    }

    public function template(string $type)
    {
        $rows = [
            ['student_id','name_ar','name_en','photo_filename'],
            ['S-0001','أحمد سالم','Ahmed Salem','S-0001.jpg'],
            ['S-0002','فاطمة علي','Fatima Ali',''],
            ['S-0003','سعيد حمد','Saeed Hamed','S-0003.png'],
        ];

        if ($type === 'csv') {
            $cb = function () use ($rows) {
                echo "\xEF\xBB\xBF";
                $out = fopen('php://output', 'w');
                foreach ($rows as $r) fputcsv($out, $r);
                fclose($out);
            };
            return response()->streamDownload($cb, 'students_template.csv', ['Content-Type'=>'text/csv; charset=UTF-8']);
        }

        $cb = function () use ($rows) { echo "\xEF\xBB\xBF"; $out=fopen('php://output','w'); foreach($rows as $r) fputcsv($out,$r,"\t"); fclose($out); };
        return response()->streamDownload($cb, 'students_template.xlsx', ['Content-Type'=>'application/vnd.ms-excel']);
    }

    public function clear()
    {
        $disk = Storage::disk('local');
        if ($disk->exists('tmp_uploads')) $disk->deleteDirectory('tmp_uploads');
        $disk->makeDirectory('tmp_uploads');
        return back()->with('status', 'تم مسح الملفات المؤقتة بنجاح.');
    }

    /**
     * Save student certificate defaults.
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
            $trackConfig = config("certificates.tracks_student.{$trackKey}");
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

        // Extract positions from custom_positions
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

        // Per-field alignment
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

        // Determine certificate_bg
        $certificateBg = '';
        $existingSetting = StudentSetting::where('track_id', $track->id)
            ->where('gender', $gender)
            ->first();

        if ($existingSetting && $existingSetting->certificate_bg) {
            $certificateBg = $existingSetting->certificate_bg;
        } else {
            $tpl = config("certificates.templates.student.{$trackKey}.{$gender}");
            $certificateBg = $tpl['bg'] ?? '';
        }

        // Date type
        $dateType = $request->input('duration_mode', 'duration') === 'range' ? 'duration' : 'end';

        // Upsert the setting
        StudentSetting::updateOrCreate(
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

        // Log settings save
        ActivityLogService::logSaveSettings('إعدادات شهادات الطلاب', $track, null, [
            'track_key' => $trackKey,
            'track_name' => $track->name_ar,
            'gender' => $gender,
        ]);

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'fetch') {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الإعدادات الافتراضية بنجاح.'
            ]);
        }

        return back()->with('success', 'تم حفظ الإعدادات الافتراضية بنجاح.');
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

        // Generate unique track key (start with 's_' for student tracks)
        $baseKey = 's_' . \Str::slug($request->input('name_en'), '_');
        $trackKey = $baseKey;
        $counter = 1;

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

        // Log track creation
        ActivityLogService::logCreate($track, 'إضافة مسار طلاب جديد', [
            'track_type' => 'student',
        ]);

        // Upload certificate templates
        $uploadDir = public_path('images/templates/student');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Male certificate
        $maleFile = $request->file('male_certificate');
        $maleExt = $maleFile->getClientOriginalExtension();
        $maleName = $trackKey . '-male.' . $maleExt;
        $maleFile->move($uploadDir, $maleName);
        $malePath = 'images/templates/student/' . $maleName;

        // Female certificate
        $femaleFile = $request->file('female_certificate');
        $femaleExt = $femaleFile->getClientOriginalExtension();
        $femaleName = $trackKey . '-female.' . $femaleExt;
        $femaleFile->move($uploadDir, $femaleName);
        $femalePath = 'images/templates/student/' . $femaleName;

        // Default positions
        $defaultPositions = [
            'cert_date' => ['top' => 23, 'right' => 56, 'width' => 78, 'font' => 5],
            'ar_name' => ['top' => 78, 'right' => 12, 'width' => 90, 'font' => 7],
            'en_name' => ['top' => 78, 'left' => 13, 'width' => 120, 'font' => 6],
            'ar_track' => ['top' => 98, 'right' => 12, 'width' => 90, 'font' => 6],
            'en_track' => ['top' => 98, 'left' => 13, 'width' => 120, 'font' => 5.5],
            'ar_from' => ['top' => 118, 'right' => 45, 'width' => 45, 'font' => 6],
            'en_from' => ['top' => 114, 'left' => 50, 'width' => 60, 'font' => 5.2],
            'photo' => ['top' => 35, 'left' => 30, 'width' => 30, 'height' => 30, 'radius' => 6, 'border' => 0.6, 'border_color' => '#1f2937'],
        ];

        // Default style
        $defaultStyle = [
            'font' => ['ar' => 'Amiri', 'en' => 'DejaVu Sans'],
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

        // Create student settings for male and female
        foreach (['male' => $malePath, 'female' => $femalePath] as $gender => $bgPath) {
            StudentSetting::create([
                'track_id' => $track->id,
                'gender' => $gender,
                'certificate_bg' => $bgPath,
                'positions' => $defaultPositions,
                'style' => $defaultStyle,
                'print_defaults' => $defaultPrintFlags,
                'date_type' => 'duration',
            ]);
        }

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

        // Store old values for logging
        $oldValues = $track->toArray();

        // Update track name
        $track->update([
            'name_ar' => $request->input('name_ar'),
            'name_en' => $request->input('name_en'),
        ]);

        // Update certificate templates if provided
        $uploadDir = public_path('images/templates/student');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Male certificate
        if ($request->hasFile('male_certificate')) {
            $maleFile = $request->file('male_certificate');
            $maleExt = $maleFile->getClientOriginalExtension();
            $maleName = $track->key . '-male.' . $maleExt;
            $maleFile->move($uploadDir, $maleName);
            $malePath = 'images/templates/student/' . $maleName;

            StudentSetting::where('track_id', $track->id)
                ->where('gender', 'male')
                ->update(['certificate_bg' => $malePath]);
        }

        // Female certificate
        if ($request->hasFile('female_certificate')) {
            $femaleFile = $request->file('female_certificate');
            $femaleExt = $femaleFile->getClientOriginalExtension();
            $femaleName = $track->key . '-female.' . $femaleExt;
            $femaleFile->move($uploadDir, $femaleName);
            $femalePath = 'images/templates/student/' . $femaleName;

            StudentSetting::where('track_id', $track->id)
                ->where('gender', 'female')
                ->update(['certificate_bg' => $femalePath]);
        }

        // Log track update
        ActivityLogService::logUpdate($track, $oldValues, 'تعديل مسار طلاب', [
            'track_type' => 'student',
            'certificates_updated' => [
                'male' => $request->hasFile('male_certificate'),
                'female' => $request->hasFile('female_certificate'),
            ],
        ]);

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
        $settings = StudentSetting::where('track_id', $track->id)->get();

        foreach ($settings as $setting) {
            $certPath = public_path($setting->certificate_bg);
            if (is_file($certPath)) {
                @unlink($certPath);
            }
        }

        // Log track deletion before deleting
        ActivityLogService::logDelete($track, 'حذف مسار طلاب', [
            'track_type' => 'student',
        ]);

        // Delete track (cascade will delete student_settings)
        $track->delete();

        return back()->with('success', 'تم حذف المسار بنجاح.');
    }

    /* ===== Private Methods ===== */

    private function prepareAll(StudentsRequest $request, TemplateResolver $resolver, bool $forPreview, bool $useImageSettings = false): array
    {
        $tmpRel = $request->file('students_file')->store('tmp_uploads', 'local');
        $tmpAbs = Storage::disk('local')->path($tmpRel);
        try { $students = StudentListReader::read($tmpAbs); } finally { @unlink($tmpAbs); }

        $trackKey = $request->input('track_key');
        $gender   = $request->input('gender','male');
        $pair     = $this->getTrackNames($trackKey);

        $tpl   = $resolver->resolve('student', $trackKey, $gender, $useImageSettings);
        $wmm   = (float)Arr::get($tpl, 'page_mm.w', 297);
        $hmm   = (float)Arr::get($tpl, 'page_mm.h', 210);

        // For preview with custom positions, use config defaults instead of database
        // This ensures UI position changes are applied immediately without database interference
        $customPositions = $request->input('custom_positions');
        if ($forPreview && !empty($customPositions)) {
            // Use config template as base (not database) so UI changes take full effect
            $configTpl = config("certificates.templates.student.{$trackKey}.{$gender}");
            $base = $configTpl['pos'] ?? $tpl['positions'] ?? [];
        } else {
            // Use database positions for generation or when no custom positions
            $base = $tpl['positions'] ?? [];
        }

        $style = $tpl['style'] ?? [];

        // Merge advanced style overrides
        if (is_array($inColors = $request->input('style.colors'))) {
            $style['colors'] = array_merge($style['colors'] ?? [], array_filter($inColors, function ($v) {
                return is_string($v) && $v !== '';
            }));
        }

        // Language fonts
        if (is_array($inFont = $request->input('style.font'))) {
            $style['font'] = array_merge($style['font'] ?? [], array_filter($inFont, fn ($v) => is_string($v) && $v !== ''));
        }

        // Per-field font
        if (is_array($inPer = $request->input('style.font_per'))) {
            $style['font_per'] = array_merge($style['font_per'] ?? [], array_filter($inPer, fn ($v) => is_string($v) && $v !== ''));
        }

        // Per-field weight
        if (is_array($inWeight = $request->input('style.weight_per'))) {
            $style['weight_per'] = array_merge($style['weight_per'] ?? [], array_filter($inWeight, fn ($v) => $v !== null && $v !== ''));
        }

        // Per-field sizes
        $incomingStyle = (array) $request->input('style', []);
        if (!empty($incomingStyle['size_per']) && is_array($incomingStyle['size_per'])) {
            $style['size_per'] = array_merge(
                $style['size_per'] ?? [],
                array_filter($incomingStyle['size_per'], fn($v) => $v !== null && $v !== '' && is_numeric($v))
            );
        }

        // Per-field alignment
        if (!empty($incomingStyle['align_per']) && is_array($incomingStyle['align_per'])) {
            $style['align_per'] = array_merge($style['align_per'] ?? [], array_filter($incomingStyle['align_per'], fn ($v) => is_string($v) && $v !== ''));
        }

        // Duration mode and dates
        $style['duration_mode'] = $request->input('duration_mode', 'range');
        $style['duration_from'] = $request->input('duration_from') ?: null;
        $style['duration_to']   = $request->input('duration_to')   ?: null;
        $style['duration_from_ar'] = $request->input('duration_from_ar') ?: $request->input('duration_from');
        $style['duration_from_en'] = $request->input('duration_from_en') ?: $request->input('duration_from');
        $style['_print_flags']     = PrintFlags::fromRequest($request);

        // Merge custom positions
        $custom = json_decode((string)$request->input('custom_positions','{}'), true) ?: [];
        $posMm  = $this->mergePercentIntoMm($base, $custom, $wmm, $hmm);

        $zipAbsUp = null;
        if ($request->hasFile('images_zip')) {
            $zipRelUp = $request->file('images_zip')->store('tmp_uploads', 'local');
            $zipAbsUp = Storage::disk('local')->path($zipRelUp);
        }

        $photoAbs = null;
        if ($forPreview && $zipAbsUp && !empty($students)) {
            $imgMap = $this->indexZipImages($zipAbsUp);
            $photoAbs = $this->matchPhotoFromZip($imgMap, $zipAbsUp, $students[0]);
        }

        return [$students, $tpl, $pair, $posMm, $photoAbs, $zipAbsUp, $style];
    }

    private function mergePercentIntoMm(array $base, array $custom, float $wmm, float $hmm): array
    {
        $out = $base;
        foreach ($custom as $key => $dims) {
            if (!is_array($dims)) continue;

            $lp = isset($dims['left_pct'])  ? (float)$dims['left_pct']  : null;
            $tp = isset($dims['top_pct'])   ? (float)$dims['top_pct']   : null;
            $wp = isset($dims['width_pct']) ? (float)$dims['width_pct'] : null;
            $hp = isset($dims['height_pct'])? (float)$dims['height_pct']: null;

            $mm = [];
            if ($lp !== null) $mm['left']   = round($lp * $wmm,  2);
            if ($tp !== null) $mm['top']    = round($tp * $hmm,  2);
            if ($wp !== null) $mm['width']  = round($wp * $wmm,  2);
            if ($hp !== null) $mm['height'] = round($hp * $hmm,  2);

            if (!isset($out[$key])) $out[$key] = [];
            $out[$key] = array_merge($out[$key], $mm);
        }
        return $out;
    }

    /**
     * Convert image to PDF
     * Uses mPDF library to wrap the image in a PDF document
     * This approach ensures consistency: all certificates are generated as images first,
     * then converted to PDF, ensuring bold/weight/size fixes work correctly
     *
     * The PDF is A4 landscape (297mm x 210mm) and the certificate image fills the entire page
     */
    private function convertImageToPdf(string $imageAbs): string
    {
        // Create mPDF instance with A4 landscape format and zero margins
        // Use 'A4-L' format string for explicit landscape orientation (297mm x 210mm)
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',  // A4 Landscape: 297mm (width) x 210mm (height)
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
            'dpi' => 300, // High DPI for better quality
        ]);

        // Embed image in HTML with proper CSS to fill the entire page
        // The image should fill the full A4 landscape page without any gaps
        $html = sprintf(
            '<div style="margin:0;padding:0;width:100%%;height:100%%;position:absolute;top:0;left:0;">' .
            '<img src="%s" style="width:100%%;height:100%%;display:block;object-fit:fill;" />' .
            '</div>',
            $imageAbs
        );

        $mpdf->WriteHTML($html);

        // Save PDF to temp location
        $pdfPath = str_replace('.png', '.pdf', $imageAbs);
        $pdfPath = str_replace('api_certificates_temp', 'tmp_uploads', $pdfPath);
        $pdfPath = str_replace('certificates', 'tmp_uploads', $pdfPath);

        // Ensure directory exists
        $dir = dirname($pdfPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);

        return $pdfPath;
    }

    private function indexZipImages(string $zipAbs): array
    {
        $map = [];
        $zip = new ZipArchive();
        if ($zip->open($zipAbs) !== true) return $map;
        for ($i=0; $i<$zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('/\.(jpe?g|png|webp)$/i', $name))
                $map[strtolower(basename($name))] = ['i'=>$i,'name'=>$name];
        }
        $zip->close();
        return $map;
    }

    private function matchPhotoFromZip(array $imgMap, string $zipAbs, array $student): ?string
    {
        $cand = trim((string)($student['photo_filename'] ?? ''));
        if ($cand !== '') {
            $key = strtolower(basename($cand));
            if (isset($imgMap[$key])) return $this->extractOneFromZip($zipAbs, $imgMap[$key]);
        }
        $id = trim((string)($student['student_id'] ?? ''));
        if ($id !== '') {
            foreach (['jpg','jpeg','png','webp'] as $ext) {
                $key = strtolower($id.'.'.$ext);
                if (isset($imgMap[$key])) return $this->extractOneFromZip($zipAbs, $imgMap[$key]);
            }
        }
        return null;
    }

    private function extractOneFromZip(string $zipAbs, array $match): ?string
    {
        $zip = new ZipArchive();
        if ($zip->open($zipAbs) !== true) return null;
        $stream = $zip->getStream($match['name']);
        if (!$stream) { $zip->close(); return null; }
        $data = stream_get_contents($stream);
        fclose($stream);
        $zip->close();

        $tmpRel = 'tmp_uploads/'.uniqid('photo_', true).'_'.basename($match['name']);
        Storage::disk('local')->put($tmpRel, $data);
        return Storage::disk('local')->path($tmpRel);
    }

    /**
     * Get track names from database or config.
     */
    protected function getTrackNames(string $trackKey): array
    {
        // Clean the track key (trim whitespace)
        $trackKey = trim($trackKey);

        // Try database first
        $track = Track::where('key', $trackKey)->first();
        if ($track) {
            // Check if names are not empty - if empty, fall back to config
            $nameAr = trim((string)$track->name_ar);
            $nameEn = trim((string)$track->name_en);

            if (!empty($nameAr) && !empty($nameEn)) {
                return [
                    'ar' => $nameAr,
                    'en' => $nameEn,
                ];
            }

            // Track exists but names are empty - try config as fallback
            \Log::warning("Track '{$trackKey}' found in database but names are empty, falling back to config");
        }

        // Fallback to config
        $config = config("certificates.tracks_student.{$trackKey}");
        if ($config && isset($config['ar']) && isset($config['en'])) {
            return [
                'ar' => $config['ar'],
                'en' => $config['en'],
            ];
        }

        // Provide detailed error information for debugging
        $allTracks = Track::pluck('key')->toArray();
        $message = "Unknown track for student: {$trackKey}. ";
        $message .= "Available tracks in database: " . implode(', ', $allTracks);

        \Log::error('Track not found or has empty names', [
            'requested_key' => $trackKey,
            'key_length' => strlen($trackKey),
            'available_tracks' => $allTracks,
            'track_in_db' => $track ? 'yes (but names empty)' : 'no'
        ]);

        throw new \InvalidArgumentException($message);
    }

    /**
     * Preview a single certificate as an image
     */
    public function previewImage(StudentsRequest $request, ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        // Set reasonable timeout for preview
        set_time_limit(60);

        [$students, $tpl, $pair, $posMm, $photoAbs, $zipAbsUp, $style] = $this->prepareAll($request, $resolver, true);

        $first = null;
        foreach ($students as $st) {
            if (trim((string)($st['name_ar'] ?? '')) !== '' && trim((string)($st['name_en'] ?? '')) !== '') { $first = $st; break; }
        }
        if (!$first) return response()->json(['message' => 'لا توجد سجلات صالحة في الملف.'], 422);

        $absPath = $imageCerts->generatePreview(
            $first['name_ar'], $first['name_en'],
            (string)$pair['ar'], (string)$pair['en'],
            $request->input('certificate_date'),
            $request->input('duration_from'),
            $posMm, $tpl['bg_abs'], $style, $photoAbs
        );

        if ($zipAbsUp) @unlink($zipAbsUp);

        return response()->file($absPath, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'inline; filename="preview.png"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Generate certificate images and download as ZIP
     */
    public function storeImages(StudentsRequest $request, ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        // Increase execution time and memory for large batches
        set_time_limit(300); // 5 minutes max
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M'); // Increase memory for image processing

        [$students, $tpl, $pair, $posMm, $_photoAbs, $zipAbsUp, $style] = $this->prepareAll($request, $resolver, false);

        $generatedAbs = [];
        $imgMap = [];
        if ($zipAbsUp) $imgMap = $this->indexZipImages($zipAbsUp);

        $total = count($students);
        $count = 0;

        foreach ($students as $st) {
            $ar = trim((string)($st['name_ar'] ?? ''));
            $en = trim((string)($st['name_en'] ?? ''));
            if ($ar === '' || $en === '') continue;

            $photoAbs = null;
            if ($zipAbsUp) $photoAbs = $this->matchPhotoFromZip($imgMap, $zipAbsUp, $st);

            $rel = $imageCerts->generateSingle(
                $ar, $en,
                (string)$pair['ar'], (string)$pair['en'],
                $request->input('certificate_date'),
                $request->input('duration_from'),
                $posMm, $tpl['bg_abs'], $style, $photoAbs
            );

            $generatedAbs[] = Storage::disk('local')->path($rel);

            // Log progress every 10 students
            $count++;
            if ($count % 10 === 0) {
                \Log::info("Image generation progress: {$count}/{$total} certificates generated");
            }

            // Free memory periodically
            if ($count % 20 === 0) {
                gc_collect_cycles();
            }
        }

        \Log::info("Image generation completed: {$count} certificates generated successfully");

        // Name ZIP as: track_name_timestamp.zip
        $trackSlug = \Str::slug($pair['en'] ?: $pair['ar'], '_');
        $zipRel = 'tmp_uploads/'.$trackSlug.'_images_'.date('Ymd_His').'.zip';
        $zipAbs = Storage::disk('local')->path($zipRel);
        @mkdir(dirname($zipAbs), 0775, true);

        $zip = new ZipArchive();
        if ($zip->open($zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            if ($zipAbsUp) @unlink($zipAbsUp);
            foreach ($generatedAbs as $a) @unlink($a);

            // Return JSON for AJAX requests
            if ($request->wantsJson() || $request->header('X-Requested-With') === 'fetch') {
                return response()->json(['message' => 'تعذر إنشاء ملف ZIP.'], 500);
            }
            return back()->withErrors(['zip' => 'تعذر إنشاء ملف ZIP.']);
        }
        foreach ($generatedAbs as $a) $zip->addFile($a, basename($a));
        $zip->close();

        foreach ($generatedAbs as $a) @unlink($a);
        if ($zipAbsUp) @unlink($zipAbsUp);

        $url = URL::temporarySignedRoute('download', now()->addMinutes(60), [
            'p' => Crypt::encryptString($zipRel)
        ]);

        // Log certificate generation
        ActivityLogService::logGenerate($pair['ar'] ?? $pair['en'], $count, 'image');

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'fetch') {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء صور الشهادات وضغطها بنجاح.',
                'download_url' => $url
            ]);
        }

        return back()->with(['success'=>'تم إنشاء صور الشهادات وضغطها بنجاح.', 'download_url'=>$url]);
    }

    /* ========================================
       IMAGE-ONLY PAGE METHODS (/studentimg)
       ======================================== */

    /**
     * Image-only page index
     */
    public function indexImageOnly(TemplateResolver $resolver)
    {
        $fonts = (new FontRegistry())->families();
        $user = auth()->user();

        // Get student tracks from database filtered by institution
        $dbTracks = Track::where('active', true)
            ->accessibleBy($user)
            ->get();
        $tracks = [];

        foreach ($dbTracks as $track) {
            if (strpos($track->key, 't_') !== 0) {
                $tracks[$track->key] = [
                    'ar' => $track->getDisplayName($user->isSuperUser()),
                    'en' => $track->name_en,
                    'id' => $track->id,
                    'institution_id' => $track->institution_id,
                ];
            }
        }

        // Merge config tracks - only for super users
        // Regular institution users should only see their institution's database tracks
        if ($user->isSuperUser()) {
            $configTracks = config('certificates.tracks_student', []);
            foreach ($configTracks as $key => $names) {
                if (!isset($tracks[$key])) {
                    $tracks[$key] = $names;
                }
            }
        }

        // Build template map
        $tplMap = [];
        foreach (array_keys($tracks) as $trackKey) {
            foreach (['male','female'] as $gender) {
                try {
                    $tpl = $resolver->resolve('student', $trackKey, $gender); // true = use StudentImageSetting
                    $tplMap[$trackKey][$gender] = [
                        'bg_url'    => route('studentimg.bg', [$trackKey, $gender]),
                        'page_mm'   => [
                            'w' => (float) Arr::get($tpl, 'page_mm.w', 297),
                            'h' => (float) Arr::get($tpl, 'page_mm.h', 210),
                        ],
                        'positions' => $tpl['positions'] ?? [],
                    ];
                } catch (\Exception $e) {
                    \Log::warning("Failed to resolve template for studentimg track={$trackKey}, gender={$gender}: " . $e->getMessage());
                    $tplMap[$trackKey][$gender] = [
                        'bg_url'    => '',
                        'page_mm'   => ['w' => 297, 'h' => 210],
                        'positions' => [],
                    ];
                }
            }
        }

        return view('studentimg.index', compact('tracks', 'tplMap', 'fonts'));
    }

    /**
     * Preview image for image-only page
     */
    public function previewImageOnly(StudentsRequest $request, ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        set_time_limit(60);

        [$students, $tpl, $pair, $posMm, $photoAbs, $zipAbsUp, $style] = $this->prepareAll($request, $resolver, true, true); // true, true = forPreview, useImageSettings

        $first = null;
        foreach ($students as $st) {
            if (trim((string)($st['name_ar'] ?? '')) !== '' && trim((string)($st['name_en'] ?? '')) !== '') { $first = $st; break; }
        }
        if (!$first) return response()->json(['message' => 'لا توجد سجلات صالحة في الملف.'], 422);

        $absPath = $imageCerts->generatePreview(
            $first['name_ar'], $first['name_en'],
            (string)$pair['ar'], (string)$pair['en'],
            $request->input('certificate_date'),
            $request->input('duration_from'),
            $posMm, $tpl['bg_abs'], $style, $photoAbs
        );

        if ($zipAbsUp) @unlink($zipAbsUp);

        return response()->file($absPath, [
            'Content-Type'        => 'image/png',
            'Content-Disposition' => 'inline; filename="preview.png"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /**
     * Generate images for image-only page
     */
    public function storeImagesOnly(StudentsRequest $request, ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        set_time_limit(300);
        ini_set('max_execution_time', '300');
        ini_set('memory_limit', '512M');

        [$students, $tpl, $pair, $posMm, $_photoAbs, $zipAbsUp, $style] = $this->prepareAll($request, $resolver, false, true); // false, true = !forPreview, useImageSettings

        $generatedAbs = [];
        $imgMap = [];
        if ($zipAbsUp) $imgMap = $this->indexZipImages($zipAbsUp);

        $total = count($students);
        $count = 0;

        foreach ($students as $st) {
            $ar = trim((string)($st['name_ar'] ?? ''));
            $en = trim((string)($st['name_en'] ?? ''));
            if ($ar === '' || $en === '') continue;

            $photoAbs = null;
            if ($zipAbsUp) $photoAbs = $this->matchPhotoFromZip($imgMap, $zipAbsUp, $st);

            $rel = $imageCerts->generateSingle(
                $ar, $en,
                (string)$pair['ar'], (string)$pair['en'],
                $request->input('certificate_date'),
                $request->input('duration_from'),
                $posMm, $tpl['bg_abs'], $style, $photoAbs
            );

            $generatedAbs[] = Storage::disk('local')->path($rel);

            $count++;
            if ($count % 10 === 0) {
                \Log::info("Image generation progress (studentimg): {$count}/{$total} certificates generated");
            }

            if ($count % 20 === 0) {
                gc_collect_cycles();
            }
        }

        \Log::info("Image generation completed (studentimg): {$count} certificates generated successfully");

        // Name ZIP as: track_name_timestamp.zip
        $trackSlug = \Str::slug($pair['en'] ?: $pair['ar'], '_');
        $zipRel = 'tmp_uploads/'.$trackSlug.'_images_'.date('Ymd_His').'.zip';
        $zipAbs = Storage::disk('local')->path($zipRel);
        @mkdir(dirname($zipAbs), 0775, true);

        $zip = new ZipArchive();
        if ($zip->open($zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            if ($zipAbsUp) @unlink($zipAbsUp);
            foreach ($generatedAbs as $a) @unlink($a);

            if ($request->wantsJson() || $request->header('X-Requested-With') === 'fetch') {
                return response()->json(['message' => 'تعذر إنشاء ملف ZIP.'], 500);
            }
            return back()->withErrors(['zip' => 'تعذر إنشاء ملف ZIP.']);
        }
        foreach ($generatedAbs as $a) $zip->addFile($a, basename($a));
        $zip->close();

        foreach ($generatedAbs as $a) @unlink($a);
        if ($zipAbsUp) @unlink($zipAbsUp);

        $url = URL::temporarySignedRoute('download', now()->addMinutes(60), [
            'p' => Crypt::encryptString($zipRel)
        ]);

        // Log certificate generation
        ActivityLogService::logGenerate($pair['ar'] ?? $pair['en'], $count, 'image');

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'fetch') {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء صور الشهادات وضغطها بنجاح.',
                'download_url' => $url
            ]);
        }

        return back()->with(['success'=>'تم إنشاء صور الشهادات وضغطها بنجاح.', 'download_url'=>$url]);
    }

    /**
     * Save image-only settings
     */
    public function saveImageOptions(Request $request)
    {
        $request->validate([
            'track_key' => 'required|string',
            'gender'    => 'required|in:male,female',
        ]);

        $trackKey = $request->input('track_key');
        $gender   = $request->input('gender');

        $track = Track::where('key', $trackKey)->first();

        if (!$track) {
            $trackConfig = config("certificates.tracks_student.{$trackKey}");
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

        // Extract positions
        $positions = [];
        if ($json = $request->input('custom_positions')) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $positions = $decoded;
            }
        }

        // Build style array
        $style = [];

        if (is_array($inFont = $request->input('style.font'))) {
            $style['font'] = array_filter($inFont, fn ($v) => is_string($v) && $v !== '');
        }

        if (is_array($inFontPer = $request->input('style.font_per'))) {
            $style['font_per'] = array_filter($inFontPer, fn ($v) => is_string($v) && $v !== '');
        }

        if (is_array($inWeight = $request->input('style.weight_per'))) {
            $style['weight_per'] = array_filter($inWeight, fn ($v) => $v !== null && $v !== '');
        }

        if (is_array($inSize = $request->input('style.size_per'))) {
            $style['size_per'] = array_filter($inSize, fn ($v) => is_numeric($v));
        }

        if (is_array($inAlign = $request->input('style.align_per'))) {
            $style['align_per'] = array_filter($inAlign, fn ($v) => is_string($v) && $v !== '');
        }

        if (is_array($inColors = $request->input('style.colors'))) {
            $style['colors'] = array_filter($inColors, fn ($v) => is_string($v) && $v !== '');
        }

        // Build print_defaults
        $printDefaults = [
            'arabic_only'  => $request->boolean('arabic_only'),
            'english_only' => $request->boolean('english_only'),
        ];

        $printFields = (array) $request->input('print', []);
        foreach ($printFields as $k => $v) {
            $printDefaults[$k] = ($v === '1' || $v === 1 || $v === true);
        }

        // Certificate background - try multiple sources
        $certificateBg = '';
        $existingSetting = StudentImageSetting::where('track_id', $track->id)
            ->where('gender', $gender)
            ->first();

        if ($existingSetting && $existingSetting->certificate_bg) {
            // Use existing StudentImageSetting background
            $certificateBg = $existingSetting->certificate_bg;
        } else {
            // Fall back to StudentSetting (for tracks added via "Add Track")
            $studentSetting = StudentSetting::where('track_id', $track->id)
                ->where('gender', $gender)
                ->first();

            if ($studentSetting && $studentSetting->certificate_bg) {
                $certificateBg = $studentSetting->certificate_bg;
            } else {
                // Fall back to config
                $tpl = config("certificates.templates.student.{$trackKey}.{$gender}");
                $certificateBg = $tpl['bg'] ?? '';
            }
        }

        $dateType = $request->input('duration_mode', 'duration') === 'range' ? 'duration' : 'end';

        // Upsert
        StudentImageSetting::updateOrCreate(
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

        // Log settings save
        ActivityLogService::logSaveSettings('إعدادات صور شهادات الطلاب', $track, null, [
            'track_key' => $trackKey,
            'track_name' => $track->name_ar,
            'gender' => $gender,
        ]);

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'fetch') {
            return response()->json([
                'success' => true,
                'message' => 'تم حفظ إعدادات الصور بنجاح.'
            ]);
        }

        return back()->with('success', 'تم حفظ إعدادات الصور بنجاح.');
    }

    /**
     * Simple preview (uses saved settings)
     */
    public function simplePreview(StudentsRequest $request, ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        // Use the regular preview method - it reads from StudentSetting automatically
        return $this->preview($request, $imageCerts, $resolver);
    }

    /**
     * Simple store (uses saved settings)
     */
    public function simpleStore(StudentsRequest $request, ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        // Use the regular store method - it reads from StudentSetting automatically
        return $this->store($request, $imageCerts, $resolver);
    }

    /**
     * Simple preview image (uses saved settings)
     */
    public function simplePreviewImage(StudentsRequest $request, ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        // Use the regular previewImage method - it reads from StudentImageSetting automatically
        return $this->previewImage($request, $imageCerts, $resolver);
    }

    /**
     * Simple store images (uses saved settings)
     */
    public function simpleStoreImages(StudentsRequest $request, ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        // Use the regular storeImages method - it reads from StudentImageSetting automatically
        return $this->storeImages($request, $imageCerts, $resolver);
    }
}

