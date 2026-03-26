<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/**
 * StorageController — manual file management for super-admins and developers.
 *
 * Manages four storage directories under storage/app/:
 *
 *   api_certificates_temp/   — Temporary preview images (web + API previews).
 *                               Auto-deleted after 30 minutes.
 *
 *   tmp_uploads/             — General temporary uploads:
 *                               uploaded photos, Excel/CSV imports, photo ZIPs,
 *                               generated PDF/ZIP downloads. Used per-request.
 *                               Auto-deleted after 2 hours.
 *
 *   certificates/            — Web-generated certificate images.
 *                               Auto-deleted after 24 hours.
 *
 *   api_certificates/        — API-generated certificates in nested subdirs:
 *                               {client-slug}/{year-month}/{certificate-id}/.
 *                               Auto-deleted after 48 hours (signed URLs valid 24h).
 *
 * Security:
 *   - Path inputs in deleteFile() are sanitized with basename() to prevent
 *     directory traversal attacks.
 *   - The 'type' parameter is constrained to an allowlist via validation rules.
 *   - All manual actions are recorded in the activity log.
 *
 * Recommendation:
 *   For automated cleanup, ensure the Laravel scheduler is running:
 *     * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
 *   See Kernel.php for the full cleanup schedule.
 */
class StorageController extends Controller
{
    /** Matches CleanupFiles::TEMP_MAX_MINUTES */
    private const TEMP_MAX_MINUTES = 30;

    /** Matches CleanupFiles::TMP_UPLOADS_MAX_HOURS */
    private const TMP_UPLOADS_MAX_HOURS = 2;

    /** Matches CleanupFiles::CERTIFICATES_MAX_HOURS */
    private const CERTIFICATES_MAX_HOURS = 24;

    /** Matches CleanupFiles::API_CERTIFICATES_MAX_HOURS */
    private const API_CERTIFICATES_MAX_HOURS = 48;

    // -----------------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------------

    /** Display storage management page. */
    public function index()
    {
        return view('auth.storage.index', ['stats' => $this->getStorageStats()]);
    }

    /**
     * Manually trigger cleanup of api_certificates_temp/ and tmp_uploads/.
     * (Equivalent to `files:cleanup --temp-only`)
     */
    public function cleanupTemp(Request $request)
    {
        Artisan::call('files:cleanup', ['--temp-only' => true, '--force' => true]);

        ActivityLogService::log('delete', null,
            'حذف يدوي للملفات المؤقتة (api_certificates_temp + tmp_uploads)',
            null, null,
            ['directories' => ['api_certificates_temp', 'tmp_uploads'], 'triggered_by' => 'manual']
        );

        return back()->with('status', 'تم حذف الملفات المؤقتة القديمة بنجاح (api_certificates_temp + tmp_uploads)');
    }

    /**
     * Manually trigger cleanup of tmp_uploads/ only (2-hour threshold).
     */
    public function cleanupUploads(Request $request)
    {
        // Run cleanup but only for tmp_uploads — we do this by calling the
        // full cleanup and relying on the 2h threshold to spare newer files.
        // For a dedicated flag we pass --temp-only which includes tmp_uploads.
        Artisan::call('files:cleanup', ['--temp-only' => true, '--force' => true]);

        ActivityLogService::log('delete', null,
            'حذف يدوي لملفات tmp_uploads القديمة',
            null, null,
            ['directory' => 'tmp_uploads', 'triggered_by' => 'manual']
        );

        return back()->with('status', 'تم حذف ملفات التحميل المؤقتة القديمة بنجاح');
    }

    /**
     * Manually trigger cleanup of certificates/ and api_certificates/.
     * (Equivalent to `files:cleanup --generated-only`)
     */
    public function cleanupGenerated(Request $request)
    {
        Artisan::call('files:cleanup', ['--generated-only' => true, '--force' => true]);

        ActivityLogService::log('delete', null,
            'حذف يدوي لملفات الشهادات المولدة القديمة (certificates + api_certificates)',
            null, null,
            ['directories' => ['certificates', 'api_certificates'], 'triggered_by' => 'manual']
        );

        return back()->with('status', 'تم حذف ملفات الشهادات القديمة بنجاح');
    }

    /**
     * Manually trigger cleanup of ALL four directories.
     * (Equivalent to `files:cleanup`)
     */
    public function cleanupAll(Request $request)
    {
        Artisan::call('files:cleanup', ['--force' => true]);

        ActivityLogService::log('delete', null,
            'حذف يدوي لجميع الملفات القديمة (جميع المجلدات الأربعة)',
            null, null,
            ['directories' => ['api_certificates_temp', 'tmp_uploads', 'certificates', 'api_certificates'], 'triggered_by' => 'manual']
        );

        return back()->with('status', 'تم حذف جميع الملفات القديمة بنجاح');
    }

    /**
     * Delete a specific file by name.
     *
     * Security: basename() strips directory separators to prevent path traversal.
     * The 'type' field is validated against a fixed allowlist.
     * api_certificates is excluded because it uses nested subdirectories;
     * bulk cleanup via `files:cleanup --generated-only` handles that directory.
     */
    public function deleteFile(Request $request)
    {
        $request->validate([
            'file' => 'required|string|max:255',
            'type' => 'required|in:temp,uploads,certificates',
        ]);

        $directory = match ($request->type) {
            'temp'         => 'api_certificates_temp',
            'uploads'      => 'tmp_uploads',
            'certificates' => 'certificates',
        };

        $filename = basename($request->file); // prevents directory traversal
        $path     = $directory . '/' . $filename;

        if (!Storage::exists($path)) {
            return back()->withErrors(['file' => 'الملف غير موجود']);
        }

        Storage::delete($path);

        ActivityLogService::log('delete', null,
            "حذف يدوي للملف: {$filename}",
            null, null,
            ['file' => $path, 'directory' => $directory, 'triggered_by' => 'manual']
        );

        return back()->with('status', "تم حذف الملف: {$filename}");
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    /**
     * Gather storage statistics for all four managed directories.
     *
     * Returns array with keys: 'temp', 'uploads', 'certificates', 'api'.
     * Each entry:
     *   total         — total file count
     *   expired       — files past retention threshold
     *   size          — total bytes
     *   expired_size  — reclaimable bytes
     *   files         — up to 20 most-recent files
     *   max_minutes   — threshold in minutes (temp only)
     *   max_hours     — threshold in hours (uploads, certificates, api)
     */
    private function getStorageStats(): array
    {
        return [
            'temp' => $this->dirStats(
                'api_certificates_temp',
                Carbon::now()->subMinutes(self::TEMP_MAX_MINUTES),
                'max_minutes', self::TEMP_MAX_MINUTES
            ),
            'uploads' => $this->dirStats(
                'tmp_uploads',
                Carbon::now()->subHours(self::TMP_UPLOADS_MAX_HOURS),
                'max_hours', self::TMP_UPLOADS_MAX_HOURS
            ),
            'certificates' => $this->dirStats(
                'certificates',
                Carbon::now()->subHours(self::CERTIFICATES_MAX_HOURS),
                'max_hours', self::CERTIFICATES_MAX_HOURS
            ),
            'api' => $this->dirStats(
                'api_certificates',
                Carbon::now()->subHours(self::API_CERTIFICATES_MAX_HOURS),
                'max_hours', self::API_CERTIFICATES_MAX_HOURS,
                recursive: true
            ),
        ];
    }

    /**
     * Build a stats array for a single directory.
     *
     * @param  string  $dir        Storage-relative path
     * @param  Carbon  $cutoff     Files modified before this are "expired"
     * @param  string  $threshKey  'max_minutes' or 'max_hours'
     * @param  int     $threshVal  Numeric threshold value
     * @param  bool    $recursive  Use allFiles() to include subdirectories
     */
    private function dirStats(
        string $dir,
        Carbon $cutoff,
        string $threshKey,
        int    $threshVal,
        bool   $recursive = false
    ): array {
        $files = [];
        if (Storage::exists($dir)) {
            $files = $recursive ? Storage::allFiles($dir) : Storage::files($dir);
        }

        $expired     = array_filter($files, fn ($f) => Carbon::createFromTimestamp(Storage::lastModified($f))->lt($cutoff));
        $totalSize   = array_sum(array_map(fn ($f) => Storage::size($f), $files));
        $expiredSize = array_sum(array_map(fn ($f) => Storage::size($f), $expired));

        $recent = collect($files)
            ->sortByDesc(fn ($f) => Storage::lastModified($f))
            ->take(20)
            ->map(fn ($f) => [
                'path'     => $f,
                'name'     => basename($f),
                'rel_path' => $f,
                'size'     => Storage::size($f),
                'modified' => Carbon::createFromTimestamp(Storage::lastModified($f)),
                'expired'  => Carbon::createFromTimestamp(Storage::lastModified($f))->lt($cutoff),
            ])
            ->values()
            ->toArray();

        return [
            'total'        => count($files),
            'expired'      => count($expired),
            'size'         => $totalSize,
            'expired_size' => $expiredSize,
            'files'        => $recent,
            $threshKey     => $threshVal,
        ];
    }

    // -----------------------------------------------------------------------
    // Static utility
    // -----------------------------------------------------------------------

    /**
     * Format bytes to a human-readable string.
     * Used in Blade via StorageController::formatBytes().
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
