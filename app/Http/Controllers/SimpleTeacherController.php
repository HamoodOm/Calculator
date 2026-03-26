<?php

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\TeacherSetting;
use App\Services\CertificateService;
use App\Services\TemplateResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use App\Services\ActivityLogService;

class SimpleTeacherController extends Controller
{
    /**
     * Display the simplified teacher form.
     */
    public function index()
    {
        // Clear photo session on page load (fresh start)
        session()->forget('simple_teacher_photo');

        $user = auth()->user();

        // Load teacher tracks from DB (only tracks NOT starting with 's_')
        // Apply institution-based access control
        $allTracks = Track::where('active', true)
            ->accessibleBy($user)
            ->get();
        $tracks = $allTracks->filter(function ($track) {
            return strpos($track->key, 's_') !== 0; // Exclude student tracks
        });

        // Fallback to config tracks only for super users
        // Regular institution users should only see their institution's database tracks
        if ($tracks->isEmpty() && $user->isSuperUser()) {
            $tracks = collect(config('certificates.tracks_teacher'))->map(function ($v, $k) {
                return (object) ['key' => $k, 'name_ar' => $v['ar'], 'name_en' => $v['en']];
            });
        }

        // Get all track settings for JavaScript (teacher tracks only)
        $trackSettings = [];
        foreach ($tracks as $track) {
            foreach (['male', 'female'] as $gender) {
                // Only query database if track has an ID (from database)
                $setting = null;
                if (isset($track->id)) {
                    $setting = TeacherSetting::where('track_id', $track->id)
                        ->where('gender', $gender)
                        ->first();
                }

                $trackSettings[$track->key][$gender] = [
                    'date_type' => $setting ? $setting->date_type : 'duration',
                ];
            }
        }

        return view('teacher.simple', [
            'tracks' => $tracks,
            'trackSettings' => $trackSettings,
        ]);
    }

    /**
     * Preview certificate with saved defaults.
     * Uses image->PDF conversion approach for consistency with student side.
     */
    public function preview(Request $request, \App\Services\ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        $validated = $request->validate([
            'track_key'  => 'required|string',
            'gender'     => 'required|in:male,female',
            'name_ar'    => 'required|string|max:255',
            'name_en'    => 'required|string|max:255',
            'date_mode'  => 'required|in:range,end',
            'duration_from' => 'nullable|date',
            'duration_to'   => 'nullable|date',
        ]);

        $trackKey = $validated['track_key'];
        $gender   = $validated['gender'];

        // Get track names (from DB or config)
        try {
            $trackNames = $this->getTrackNames($trackKey);
        } catch (\Exception $e) {
            return back()->withErrors(['track_key' => 'Invalid track']);
        }

        // Resolve template with DB overrides (use image settings for consistency)
        $tpl = $resolver->resolve('teacher', $trackKey, $gender);
        $pos = $tpl['positions'];
        $bg  = $tpl['bg_abs'];
        $style = $tpl['style'];

        // Apply saved defaults from DB
        $this->applyDefaults($trackKey, $gender, $style, $validated);

        // Handle photo
        $photoAbs = null;
        $remove = $request->boolean('remove_photo');

        if ($remove) {
            if (session()->has('simple_teacher_photo')) {
                $old = session('simple_teacher_photo');
                if (is_string($old) && @is_file($old)) { @unlink($old); }
            }
            session()->forget('simple_teacher_photo');
        }

        if ($request->hasFile('photo')) {
            $tmpRel  = $request->file('photo')->store('tmp_uploads', 'local');
            $photoAbs = Storage::disk('local')->path($tmpRel);
            session(['simple_teacher_photo' => $photoAbs]);
        } elseif (!$remove) {
            $photoAbs = session('simple_teacher_photo');
        }

        // Generate image first
        $imageRel = $imageCerts->generateSingle(
            $validated['name_ar'],
            $validated['name_en'],
            $trackNames['ar'],
            $trackNames['en'],
            $request->input('certificate_date'),
            $validated['duration_from'],
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
     * Generate and download certificate with saved defaults.
     * Uses image->PDF conversion approach for consistency with student side.
     */
    public function store(Request $request, \App\Services\ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        $validated = $request->validate([
            'track_key'  => 'required|string',
            'gender'     => 'required|in:male,female',
            'name_ar'    => 'required|string|max:255',
            'name_en'    => 'required|string|max:255',
            'date_mode'  => 'required|in:range,end',
            'duration_from' => 'nullable|date',
            'duration_to'   => 'nullable|date',
        ]);

        $trackKey = $validated['track_key'];
        $gender   = $validated['gender'];

        // Get track names (from DB or config)
        try {
            $trackNames = $this->getTrackNames($trackKey);
        } catch (\Exception $e) {
            return back()->withErrors(['track_key' => 'Invalid track']);
        }

        // Resolve template with DB overrides (use image settings for consistency)
        $tpl = $resolver->resolve('teacher', $trackKey, $gender);
        $pos = $tpl['positions'];
        $bg  = $tpl['bg_abs'];
        $style = $tpl['style'];

        // Apply saved defaults from DB
        $this->applyDefaults($trackKey, $gender, $style, $validated);

        // Handle photo
        $photoAbs = null;
        $remove = $request->boolean('remove_photo');

        if ($remove) {
            if (session()->has('simple_teacher_photo')) {
                $old = session('simple_teacher_photo');
                if (is_string($old) && @is_file($old)) { @unlink($old); }
            }
            session()->forget('simple_teacher_photo');
        }

        if ($request->hasFile('photo')) {
            $tmpRel  = $request->file('photo')->store('tmp_uploads', 'local');
            $photoAbs = Storage::disk('local')->path($tmpRel);
            session(['simple_teacher_photo' => $photoAbs]);
        } elseif (!$remove) {
            $photoAbs = session('simple_teacher_photo');
        }

        // Generate image first
        $imageRel = $imageCerts->generateSingle(
            $validated['name_ar'],
            $validated['name_en'],
            $trackNames['ar'],
            $trackNames['en'],
            $request->input('certificate_date'),
            $validated['duration_from'],
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
        // Name format: user_name_track_name.pdf (preserves Arabic characters)
        $namePart = $this->safeFilename($validated['name_ar'] ?: $validated['name_en'], 'شهادة');
        $trackPart = $this->safeFilename($trackNames['ar'] ?: $trackNames['en'], 'مسار');
        $pdfName = $namePart . '_' . $trackPart . '.pdf';
        $pdfRel = 'certificates/' . $pdfName;
        $pdfDestAbs = Storage::disk('local')->path($pdfRel);

        // Ensure directory exists
        $pdfDir = dirname($pdfDestAbs);
        if (!is_dir($pdfDir)) {
            @mkdir($pdfDir, 0775, true);
        }

        rename($pdfAbs, $pdfDestAbs);

        // Log certificate generation
        ActivityLogService::logGenerate($trackNames['ar'] ?? $trackNames['en'], 1, 'pdf', [
            'recipient_name' => $validated['name_ar'] ?: $validated['name_en'],
            'track_key' => $trackKey,
            'gender' => $gender,
            'type' => 'teacher',
            'interface' => 'simple',
        ]);

        $url = URL::temporarySignedRoute('download', now()->addMinutes(60), [
            'p' => Crypt::encryptString($pdfRel),
        ]);

        // Clear photo session after successful generation
        if (session()->has('simple_teacher_photo')) {
            $old = session('simple_teacher_photo');
            if (is_string($old) && @is_file($old)) { @unlink($old); }
        }
        session()->forget('simple_teacher_photo');

        return back()->with(['success'=>'تم إنشاء الشهادة بنجاح.', 'download_url'=>$url]);
    }

    /**
     * Apply saved defaults from database to style array.
     */
    protected function applyDefaults(string $trackKey, string $gender, array &$style, array $validated)
    {
        $track = Track::where('key', $trackKey)->first();
        if (!$track) {
            return;
        }

        $setting = TeacherSetting::where('track_id', $track->id)
            ->where('gender', $gender)
            ->first();

        if (!$setting) {
            return;
        }

        // Apply duration mode from form
        $style['duration_mode'] = $validated['date_mode'];
        $style['duration_from'] = $validated['duration_from'] ?? null;
        $style['duration_to']   = $validated['duration_to'] ?? null;

        // Apply print flags from DB
        if (!empty($setting->print_defaults)) {
            $style['_print_flags'] = $setting->print_defaults;
        }

        // Merge advanced style options from database (colors, fonts, sizes, weights)
        if (!empty($setting->style) && is_array($setting->style)) {
            // Merge colors
            if (!empty($setting->style['colors']) && is_array($setting->style['colors'])) {
                $style['colors'] = array_merge($style['colors'] ?? [], $setting->style['colors']);
            }

            // Merge per-field fonts
            if (!empty($setting->style['font_per']) && is_array($setting->style['font_per'])) {
                $style['font_per'] = array_merge($style['font_per'] ?? [], $setting->style['font_per']);
            }

            // Merge per-field sizes (THIS IS THE KEY FIX FOR TEXT SIZE)
            if (!empty($setting->style['size_per']) && is_array($setting->style['size_per'])) {
                $style['size_per'] = array_merge($style['size_per'] ?? [], $setting->style['size_per']);
            }

            // Merge per-field weights
            if (!empty($setting->style['weight_per']) && is_array($setting->style['weight_per'])) {
                $style['weight_per'] = array_merge($style['weight_per'] ?? [], $setting->style['weight_per']);
            }

            // Merge language-level font defaults
            if (!empty($setting->style['font']) && is_array($setting->style['font'])) {
                $style['font'] = array_merge($style['font'] ?? [], $setting->style['font']);
            }
        }
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
     * Preview certificate as image.
     */
    public function previewImage(Request $request, \App\Services\ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        $validated = $request->validate([
            'track_key'  => 'required|string',
            'gender'     => 'required|in:male,female',
            'name_ar'    => 'required|string|max:255',
            'name_en'    => 'required|string|max:255',
            'date_mode'  => 'required|in:range,end',
            'duration_from' => 'nullable|date',
            'duration_to'   => 'nullable|date',
        ]);

        $trackKey = $validated['track_key'];
        $gender   = $validated['gender'];

        try {
            $trackNames = $this->getTrackNames($trackKey);
        } catch (\Exception $e) {
            return back()->withErrors(['track_key' => 'Invalid track']);
        }

        // Resolve template with DB overrides (use image settings)
        $tpl = $resolver->resolve('teacher', $trackKey, $gender);
        $pos = $tpl['positions'];
        $bg  = $tpl['bg_abs'];
        $style = $tpl['style'];

        // Apply saved defaults from DB
        $this->applyDefaults($trackKey, $gender, $style, $validated);

        // Handle photo
        $photoAbs = null;
        $remove = $request->boolean('remove_photo');

        if ($remove) {
            if (session()->has('simple_teacher_photo')) {
                $old = session('simple_teacher_photo');
                if (is_string($old) && @is_file($old)) { @unlink($old); }
            }
            session()->forget('simple_teacher_photo');
        }

        if ($request->hasFile('photo')) {
            $tmpRel  = $request->file('photo')->store('tmp_uploads', 'local');
            $photoAbs = Storage::disk('local')->path($tmpRel);
            session(['simple_teacher_photo' => $photoAbs]);
        } elseif (!$remove) {
            $photoAbs = session('simple_teacher_photo');
        }

        // Generate image
        $imageRel = $imageCerts->generateSingle(
            $validated['name_ar'],
            $validated['name_en'],
            $trackNames['ar'],
            $trackNames['en'],
            $request->input('certificate_date'),
            $validated['duration_from'],
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
     * Generate and download certificate as image.
     */
    public function storeImage(Request $request, \App\Services\ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        $validated = $request->validate([
            'track_key'  => 'required|string',
            'gender'     => 'required|in:male,female',
            'name_ar'    => 'required|string|max:255',
            'name_en'    => 'required|string|max:255',
            'date_mode'  => 'required|in:range,end',
            'duration_from' => 'nullable|date',
            'duration_to'   => 'nullable|date',
        ]);

        $trackKey = $validated['track_key'];
        $gender   = $validated['gender'];

        try {
            $trackNames = $this->getTrackNames($trackKey);
        } catch (\Exception $e) {
            return back()->withErrors(['track_key' => 'Invalid track']);
        }

        // Resolve template with DB overrides (use image settings)
        $tpl = $resolver->resolve('teacher', $trackKey, $gender);
        $pos = $tpl['positions'];
        $bg  = $tpl['bg_abs'];
        $style = $tpl['style'];

        // Apply saved defaults from DB
        $this->applyDefaults($trackKey, $gender, $style, $validated);

        // Handle photo
        $photoAbs = null;
        $remove = $request->boolean('remove_photo');

        if ($remove) {
            if (session()->has('simple_teacher_photo')) {
                $old = session('simple_teacher_photo');
                if (is_string($old) && @is_file($old)) { @unlink($old); }
            }
            session()->forget('simple_teacher_photo');
        }

        if ($request->hasFile('photo')) {
            $tmpRel  = $request->file('photo')->store('tmp_uploads', 'local');
            $photoAbs = Storage::disk('local')->path($tmpRel);
            session(['simple_teacher_photo' => $photoAbs]);
        } elseif (!$remove) {
            $photoAbs = session('simple_teacher_photo');
        }

        // Generate image
        $imageRel = $imageCerts->generateSingle(
            $validated['name_ar'],
            $validated['name_en'],
            $trackNames['ar'],
            $trackNames['en'],
            $request->input('certificate_date'),
            $validated['duration_from'],
            $pos,
            $bg,
            $style,
            $photoAbs
        );

        // Log certificate generation
        ActivityLogService::logGenerate($trackNames['ar'] ?? $trackNames['en'], 1, 'image', [
            'recipient_name' => $validated['name_ar'] ?: $validated['name_en'],
            'track_key' => $trackKey,
            'gender' => $gender,
            'type' => 'teacher',
            'interface' => 'simple',
        ]);

        $url = URL::temporarySignedRoute('download', now()->addMinutes(60), [
            'p' => Crypt::encryptString($imageRel),
        ]);

        // Clear photo session after successful generation
        if (session()->has('simple_teacher_photo')) {
            $old = session('simple_teacher_photo');
            if (is_string($old) && @is_file($old)) { @unlink($old); }
        }
        session()->forget('simple_teacher_photo');

        return back()->with(['success'=>'تم إنشاء الصورة بنجاح.', 'download_url'=>$url]);
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

    /**
     * Create a safe filename that preserves Arabic characters.
     * Only removes characters that are invalid in filenames.
     *
     * @param string $name The name to sanitize
     * @param string $fallback Fallback if name is empty
     * @return string Safe filename
     */
    private function safeFilename(string $name, string $fallback = 'شهادة'): string
    {
        $name = trim($name) ?: $fallback;
        // Remove invalid filename characters: \ / : * ? " < > | and newlines
        $name = preg_replace('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|\\r\\n]+/u', ' ', $name);
        // Collapse multiple spaces
        $name = preg_replace('/\\s+/u', ' ', $name);
        // Limit length
        $name = mb_substr($name, 0, 80, 'UTF-8');
        return trim($name);
    }
}
