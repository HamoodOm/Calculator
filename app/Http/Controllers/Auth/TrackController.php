<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\StudentImageSetting;
use App\Models\StudentSetting;
use App\Models\TeacherImageSetting;
use App\Models\TeacherSetting;
use App\Models\Track;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TrackController extends Controller
{
    /**
     * Display a listing of tracks.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Get filter parameters
        $search = $request->get('search');
        $type = $request->get('type'); // 'teacher', 'student', or null for all
        $institutionId = $request->get('institution');
        $status = $request->get('status'); // 'active', 'inactive', or null for all
        $sortColumn = $request->get('sort', 'created_at');
        $sortDirection = $request->get('dir', 'desc');

        // Validate sort column
        $allowedSorts = ['name_ar', 'name_en', 'key', 'created_at', 'active'];
        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'created_at';
        }

        // Build query
        $query = Track::with('institution')
            ->accessibleBy($user);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%")
                  ->orWhere('key', 'like', "%{$search}%");
            });
        }

        // Apply type filter (teacher vs student tracks)
        if ($type === 'teacher') {
            $query->where('key', 'not like', 's_%');
        } elseif ($type === 'student') {
            $query->where('key', 'like', 's_%');
        }

        // Apply institution filter (super users only)
        if ($user->isSuperUser() && $institutionId) {
            if ($institutionId === 'global') {
                $query->whereNull('institution_id');
            } else {
                $query->where('institution_id', $institutionId);
            }
        }

        // Apply status filter
        if ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        }

        // Apply sorting
        $query->orderBy($sortColumn, $sortDirection);

        // Get paginated results
        $tracks = $query->paginate(20)->withQueryString();

        // Get track statistics
        $stats = [
            'total' => Track::accessibleBy($user)->count(),
            'teacher' => Track::accessibleBy($user)->where('key', 'not like', 's_%')->count(),
            'student' => Track::accessibleBy($user)->where('key', 'like', 's_%')->count(),
            'active' => Track::accessibleBy($user)->where('active', true)->count(),
            'inactive' => Track::accessibleBy($user)->where('active', false)->count(),
        ];

        // Get institutions for filter (super users only)
        $institutions = $user->isSuperUser() ? Institution::orderBy('name')->get() : collect();

        // Current filters for view
        $currentFilters = [
            'search' => $search,
            'type' => $type,
            'institution' => $institutionId,
            'status' => $status,
            'sort' => $sortColumn,
            'dir' => $sortDirection,
        ];

        return view('auth.tracks.index', compact('tracks', 'stats', 'institutions', 'currentFilters'));
    }

    /**
     * Show the form for creating a new track.
     */
    public function create()
    {
        $user = auth()->user();
        $institutions = $user->isSuperUser() ? Institution::orderBy('name')->get() : collect();

        return view('auth.tracks.create', compact('institutions'));
    }

    /**
     * Store a newly created track.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'type' => 'required|in:teacher,student',
            'institution_id' => 'nullable|exists:institutions,id',
            'male_certificate' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'female_certificate' => 'required|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        // Generate unique track key
        $prefix = $request->input('type') === 'student' ? 's_' : 't_';
        $baseKey = $prefix . \Str::slug($request->input('name_en'), '_');
        $trackKey = $baseKey;
        $counter = 1;

        while (Track::where('key', $trackKey)->exists()) {
            $trackKey = $baseKey . '_' . $counter;
            $counter++;
        }

        // Determine institution
        $institutionId = $user->isSuperUser()
            ? $request->input('institution_id')
            : $user->institution_id;

        // Create track
        $track = Track::create([
            'key' => $trackKey,
            'name_ar' => $request->input('name_ar'),
            'name_en' => $request->input('name_en'),
            'active' => true,
            'institution_id' => $institutionId,
        ]);

        // Upload certificate templates
        $type = $request->input('type');
        $uploadDir = public_path("images/templates/{$type}");
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Male certificate
        $maleFile = $request->file('male_certificate');
        $maleExt = $maleFile->getClientOriginalExtension();
        $maleName = $trackKey . '-male.' . $maleExt;
        $maleFile->move($uploadDir, $maleName);
        $malePath = "images/templates/{$type}/" . $maleName;

        // Female certificate
        $femaleFile = $request->file('female_certificate');
        $femaleExt = $femaleFile->getClientOriginalExtension();
        $femaleName = $trackKey . '-female.' . $femaleExt;
        $femaleFile->move($uploadDir, $femaleName);
        $femalePath = "images/templates/{$type}/" . $femaleName;

        // Default positions and style
        $defaultPositions = $this->getDefaultPositions();
        $defaultStyle = $this->getDefaultStyle();
        $defaultPrintFlags = $this->getDefaultPrintFlags();

        // Create settings based on type
        $settingsClass = $type === 'student' ? StudentSetting::class : TeacherSetting::class;

        $settingsClass::create([
            'track_id' => $track->id,
            'gender' => 'male',
            'certificate_bg' => $malePath,
            'positions' => $defaultPositions,
            'style' => $defaultStyle,
            'print_defaults' => $defaultPrintFlags,
            'date_type' => 'duration',
        ]);

        $settingsClass::create([
            'track_id' => $track->id,
            'gender' => 'female',
            'certificate_bg' => $femalePath,
            'positions' => $defaultPositions,
            'style' => $defaultStyle,
            'print_defaults' => $defaultPrintFlags,
            'date_type' => 'duration',
        ]);

        // Log track creation
        ActivityLogService::logCreate($track, 'إنشاء مسار جديد من صفحة إدارة المسارات', [
            'track_type' => $type,
        ]);

        return redirect()->route('tracks.index')
            ->with('status', 'تم إنشاء المسار بنجاح!');
    }

    /**
     * Show the form for editing a track.
     */
    public function edit(Track $track)
    {
        $user = auth()->user();

        // Check authorization
        if (!$user->isSuperUser() && $track->institution_id !== $user->institution_id) {
            abort(403, 'غير مصرح لك بتعديل هذا المسار');
        }

        $institutions = $user->isSuperUser() ? Institution::orderBy('name')->get() : collect();

        // Determine track type
        $type = str_starts_with($track->key, 's_') ? 'student' : 'teacher';

        // Get settings
        $settingsClass = $type === 'student' ? StudentSetting::class : TeacherSetting::class;
        $maleSettings = $settingsClass::where('track_id', $track->id)->where('gender', 'male')->first();
        $femaleSettings = $settingsClass::where('track_id', $track->id)->where('gender', 'female')->first();

        return view('auth.tracks.edit', compact('track', 'institutions', 'type', 'maleSettings', 'femaleSettings'));
    }

    /**
     * Update the specified track.
     */
    public function update(Request $request, Track $track)
    {
        $user = auth()->user();

        // Check authorization
        if (!$user->isSuperUser() && $track->institution_id !== $user->institution_id) {
            abort(403, 'غير مصرح لك بتعديل هذا المسار');
        }

        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'institution_id' => 'nullable|exists:institutions,id',
            'male_certificate' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
            'female_certificate' => 'nullable|image|mimes:jpeg,jpg,png|max:10240',
        ]);

        // Store old values for logging
        $oldValues = $track->toArray();

        // Update track
        $updateData = [
            'name_ar' => $request->input('name_ar'),
            'name_en' => $request->input('name_en'),
        ];

        // Only super users can change institution
        if ($user->isSuperUser()) {
            $updateData['institution_id'] = $request->input('institution_id');
        }

        $track->update($updateData);

        // Determine track type
        $type = str_starts_with($track->key, 's_') ? 'student' : 'teacher';
        $settingsClass = $type === 'student' ? StudentSetting::class : TeacherSetting::class;

        // Update certificate templates if provided
        $uploadDir = public_path("images/templates/{$type}");
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Male certificate
        if ($request->hasFile('male_certificate')) {
            $maleFile = $request->file('male_certificate');
            $maleExt = $maleFile->getClientOriginalExtension();
            $maleName = $track->key . '-male.' . $maleExt;
            $maleFile->move($uploadDir, $maleName);
            $malePath = "images/templates/{$type}/" . $maleName;

            $settingsClass::where('track_id', $track->id)
                ->where('gender', 'male')
                ->update(['certificate_bg' => $malePath]);
        }

        // Female certificate
        if ($request->hasFile('female_certificate')) {
            $femaleFile = $request->file('female_certificate');
            $femaleExt = $femaleFile->getClientOriginalExtension();
            $femaleName = $track->key . '-female.' . $femaleExt;
            $femaleFile->move($uploadDir, $femaleName);
            $femalePath = "images/templates/{$type}/" . $femaleName;

            $settingsClass::where('track_id', $track->id)
                ->where('gender', 'female')
                ->update(['certificate_bg' => $femalePath]);
        }

        // Log track update
        ActivityLogService::logUpdate($track, $oldValues, 'تعديل مسار من صفحة إدارة المسارات', [
            'track_type' => $type,
            'certificates_updated' => [
                'male' => $request->hasFile('male_certificate'),
                'female' => $request->hasFile('female_certificate'),
            ],
        ]);

        return redirect()->route('tracks.index')
            ->with('status', 'تم تحديث المسار بنجاح!');
    }

    /**
     * Toggle track active status.
     */
    public function toggle(Track $track)
    {
        $user = auth()->user();

        // Check authorization
        if (!$user->isSuperUser() && $track->institution_id !== $user->institution_id) {
            abort(403, 'غير مصرح لك بتعديل هذا المسار');
        }

        $track->update(['active' => !$track->active]);

        // Log toggle
        ActivityLogService::logToggle($track, $track->active, null, [
            'track_type' => str_starts_with($track->key, 's_') ? 'student' : 'teacher',
        ]);

        return back()->with('status', $track->active ? 'تم تفعيل المسار بنجاح!' : 'تم تعطيل المسار بنجاح!');
    }

    /**
     * Remove the specified track.
     */
    public function destroy(Track $track)
    {
        $user = auth()->user();

        // Check authorization
        if (!$user->isSuperUser() && $track->institution_id !== $user->institution_id) {
            abort(403, 'غير مصرح لك بحذف هذا المسار');
        }

        // Determine track type
        $type = str_starts_with($track->key, 's_') ? 'student' : 'teacher';

        // Get and delete settings
        $settingsClasses = $type === 'student'
            ? [StudentSetting::class, StudentImageSetting::class]
            : [TeacherSetting::class, TeacherImageSetting::class];

        foreach ($settingsClasses as $settingsClass) {
            $settings = $settingsClass::where('track_id', $track->id)->get();
            foreach ($settings as $setting) {
                // Delete certificate file
                if ($setting->certificate_bg) {
                    $certPath = public_path($setting->certificate_bg);
                    if (is_file($certPath)) {
                        @unlink($certPath);
                    }
                }
                $setting->delete();
            }
        }

        // Log before deleting
        ActivityLogService::logDelete($track, 'حذف مسار من صفحة إدارة المسارات', [
            'track_type' => $type,
        ]);

        $track->delete();

        return redirect()->route('tracks.index')
            ->with('status', 'تم حذف المسار بنجاح!');
    }

    /**
     * Export tracks to CSV.
     */
    public function export(Request $request)
    {
        $user = auth()->user();
        $tracks = Track::with('institution')->accessibleBy($user)->get();

        $filename = 'tracks_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($tracks) {
            $file = fopen('php://output', 'w');
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($file, [
                'المعرف',
                'المفتاح',
                'الاسم بالعربية',
                'الاسم بالإنجليزية',
                'النوع',
                'المؤسسة',
                'الحالة',
                'تاريخ الإنشاء',
            ]);

            foreach ($tracks as $track) {
                $type = str_starts_with($track->key, 's_') ? 'طلاب' : 'معلمين';
                fputcsv($file, [
                    $track->id,
                    $track->key,
                    $track->name_ar,
                    $track->name_en,
                    $type,
                    $track->institution?->name ?? 'عام',
                    $track->active ? 'مفعل' : 'معطل',
                    $track->created_at->format('Y-m-d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get default positions for new tracks.
     */
    private function getDefaultPositions(): array
    {
        return [
            'cert_date' => ['top' => 23, 'right' => 56, 'width' => 78, 'font' => 5],
            'ar_name' => ['top' => 78, 'right' => 12, 'width' => 90, 'font' => 7],
            'en_name' => ['top' => 78, 'left' => 13, 'width' => 120, 'font' => 6],
            'ar_track' => ['top' => 98, 'right' => 12, 'width' => 90, 'font' => 6],
            'en_track' => ['top' => 98, 'left' => 13, 'width' => 120, 'font' => 5.5],
            'ar_from' => ['top' => 118, 'right' => 45, 'width' => 45, 'font' => 6],
            'en_from' => ['top' => 114, 'left' => 50, 'width' => 60, 'font' => 5.2],
            'photo' => ['top' => 35, 'left' => 30, 'width' => 30, 'height' => 30, 'radius' => 6, 'border' => 0.6, 'border_color' => '#1f2937'],
        ];
    }

    /**
     * Get default style for new tracks.
     */
    private function getDefaultStyle(): array
    {
        return [
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
    }

    /**
     * Get default print flags for new tracks.
     */
    private function getDefaultPrintFlags(): array
    {
        return [
            'arabic_only' => false,
            'english_only' => false,
            'ar_name' => true,
            'en_name' => true,
            'ar_track' => true,
            'en_track' => true,
            'ar_from' => true,
            'en_from' => true,
        ];
    }
}
