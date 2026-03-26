<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

/**
 * CleanupFiles — Artisan command for storage maintenance.
 *
 * Manages four storage directories under storage/app/:
 *
 *   api_certificates_temp/   — Temporary preview images (web + API previews).
 *                               Files are generated per-request for certificate
 *                               preview, then abandoned. Deleted after 30 min.
 *
 *   tmp_uploads/             — General temporary uploads:
 *                               uploaded photos, Excel/CSV imports, photo ZIPs,
 *                               and generated PDF/ZIP downloads served inline.
 *                               All files are used within a single HTTP request.
 *                               Deleted after TMP_UPLOADS_MAX_HOURS (2 h).
 *
 *   certificates/            — Web-generated certificate images (PNG/PDF).
 *                               Deleted after CERTIFICATES_MAX_HOURS (24 h).
 *
 *   api_certificates/        — API-generated certificate files stored in nested
 *                               subdirectories: {client-slug}/{year-month}/{id}/.
 *                               Signed download URLs are valid for 24 h, so files
 *                               are kept for API_CERTIFICATES_MAX_HOURS (48 h)
 *                               to give a safe grace period.
 *
 * ┌─────────────────────────┬──────────────────┬────────────────────────────┐
 * │ Directory               │ Retention        │ Included in                │
 * ├─────────────────────────┼──────────────────┼────────────────────────────┤
 * │ api_certificates_temp   │ 30 min           │ --temp-only                │
 * │ tmp_uploads             │ 2 h              │ --temp-only                │
 * │ certificates            │ 24 h             │ --generated-only           │
 * │ api_certificates        │ 48 h             │ --generated-only           │
 * └─────────────────────────┴──────────────────┴────────────────────────────┘
 *
 * Usage:
 *   php artisan files:cleanup                  — clean all four directories
 *   php artisan files:cleanup --temp-only      — api_certificates_temp + tmp_uploads
 *   php artisan files:cleanup --generated-only — certificates + api_certificates
 *   php artisan files:cleanup --dry-run        — preview without deleting
 *
 * Scheduler (Kernel.php):
 *   --temp-only      runs every 30 min   (catches expired 30-min + 2-h files)
 *   --generated-only runs daily at 02:00
 */
class CleanupFiles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'files:cleanup
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--temp-only : Only clean temp dirs: api_certificates_temp + tmp_uploads}
                            {--generated-only : Only clean cert dirs: certificates + api_certificates}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up temporary and generated certificate storage (all 4 managed directories)';

    // -----------------------------------------------------------------------
    // Retention thresholds
    // -----------------------------------------------------------------------

    /** api_certificates_temp/: preview files used per-request. */
    private const TEMP_MAX_MINUTES = 30;

    /**
     * tmp_uploads/: uploaded photos, imports, generated PDF/ZIP downloads.
     * All are used within a single HTTP request; 2 h is a generous safety margin.
     */
    private const TMP_UPLOADS_MAX_HOURS = 2;

    /** certificates/: web-generated certificate images. */
    private const CERTIFICATES_MAX_HOURS = 24;

    /**
     * api_certificates/: API-generated certificates.
     * Signed URLs are valid 24 h, so keep files 48 h as a grace period.
     */
    private const API_CERTIFICATES_MAX_HOURS = 48;

    // -----------------------------------------------------------------------
    // Entry point
    // -----------------------------------------------------------------------

    public function handle(): int
    {
        $dryRun        = $this->option('dry-run');
        $tempOnly      = $this->option('temp-only');
        $generatedOnly = $this->option('generated-only');

        if ($dryRun) {
            $this->warn('[DRY RUN] No files will actually be deleted.');
        }

        $totalDeleted = 0;
        $totalSize    = 0;

        // ── Temp group ────────────────────────────────────────────────────
        if (!$generatedOnly) {
            [$d, $s] = $this->cleanTempPreviews($dryRun);
            $totalDeleted += $d;
            $totalSize    += $s;

            [$d, $s] = $this->cleanTmpUploads($dryRun);
            $totalDeleted += $d;
            $totalSize    += $s;
        }

        // ── Generated group ───────────────────────────────────────────────
        if (!$tempOnly) {
            [$d, $s] = $this->cleanCertificates($dryRun);
            $totalDeleted += $d;
            $totalSize    += $s;

            [$d, $s] = $this->cleanApiCertificates($dryRun);
            $totalDeleted += $d;
            $totalSize    += $s;
        }

        $sizeLabel = $this->formatBytes($totalSize);

        if ($dryRun) {
            $this->info("Would delete {$totalDeleted} file(s) ({$sizeLabel}).");
        } else {
            $this->info("Deleted {$totalDeleted} file(s), freed {$sizeLabel}.");
        }

        return Command::SUCCESS;
    }

    // -----------------------------------------------------------------------
    // Per-directory cleanup methods
    // -----------------------------------------------------------------------

    /**
     * Clean api_certificates_temp/ — preview images older than TEMP_MAX_MINUTES.
     */
    private function cleanTempPreviews(bool $dryRun): array
    {
        return $this->cleanDirectory(
            'api_certificates_temp',
            Carbon::now()->subMinutes(self::TEMP_MAX_MINUTES),
            'معاينة مؤقتة (api_certificates_temp)',
            $dryRun
        );
    }

    /**
     * Clean tmp_uploads/ — uploaded files older than TMP_UPLOADS_MAX_HOURS.
     *
     * Contains: uploaded photos, Excel/CSV imports, photo ZIP imports,
     * generated PDF/ZIP download files. All are single-request artifacts.
     */
    private function cleanTmpUploads(bool $dryRun): array
    {
        return $this->cleanDirectory(
            'tmp_uploads',
            Carbon::now()->subHours(self::TMP_UPLOADS_MAX_HOURS),
            'رفوعات مؤقتة (tmp_uploads)',
            $dryRun
        );
    }

    /**
     * Clean certificates/ — web-generated certificates older than CERTIFICATES_MAX_HOURS.
     */
    private function cleanCertificates(bool $dryRun): array
    {
        return $this->cleanDirectory(
            'certificates',
            Carbon::now()->subHours(self::CERTIFICATES_MAX_HOURS),
            'شهادات الويب (certificates)',
            $dryRun
        );
    }

    /**
     * Clean api_certificates/ — API-generated certificates older than API_CERTIFICATES_MAX_HOURS.
     *
     * Uses recursive file listing because this directory has nested subdirectories:
     *   api_certificates/{client-slug}/{year-month}/{certificate-id}/
     */
    private function cleanApiCertificates(bool $dryRun): array
    {
        return $this->cleanDirectory(
            'api_certificates',
            Carbon::now()->subHours(self::API_CERTIFICATES_MAX_HOURS),
            'شهادات API (api_certificates)',
            $dryRun,
            recursive: true
        );
    }

    // -----------------------------------------------------------------------
    // Core helpers
    // -----------------------------------------------------------------------

    /**
     * Scan $directory and delete files older than $cutoff.
     *
     * @param  bool   $recursive  When true, uses allFiles() to descend into subdirs.
     * @return array{int, int}    [deleted_count, freed_bytes]
     */
    private function cleanDirectory(
        string $directory,
        Carbon $cutoff,
        string $label,
        bool   $dryRun,
        bool   $recursive = false
    ): array {
        $deleted    = 0;
        $freedBytes = 0;

        if (!Storage::exists($directory)) {
            $this->line("  Directory [{$directory}] does not exist, skipping.");
            return [0, 0];
        }

        $files = $recursive ? Storage::allFiles($directory) : Storage::files($directory);

        if (empty($files)) {
            $this->line("  No files in [{$directory}].");
            return [0, 0];
        }

        $this->line("Checking {$label} in [{$directory}]...");

        foreach ($files as $file) {
            $modifiedAt = Carbon::createFromTimestamp(Storage::lastModified($file));

            if ($modifiedAt->lt($cutoff)) {
                $size     = Storage::size($file);
                $ageLabel = $modifiedAt->diffForHumans();

                if ($dryRun) {
                    $this->line("  [WOULD DELETE] {$file} (modified {$ageLabel}, " . $this->formatBytes($size) . ')');
                } else {
                    Storage::delete($file);
                    $this->line("  [DELETED] {$file} (modified {$ageLabel})");
                }

                $deleted++;
                $freedBytes += $size;
            }
        }

        // Remove empty subdirectories after file deletion
        if (!$dryRun) {
            $this->cleanEmptySubdirectories($directory);
        }

        $suffix = $dryRun ? 'to be deleted' : 'deleted';
        $this->line("  Done: {$deleted} file(s) {$suffix}.");

        return [$deleted, $freedBytes];
    }

    /**
     * Remove all empty subdirectories within $directory (bottom-up via allDirectories).
     */
    private function cleanEmptySubdirectories(string $directory): void
    {
        foreach (Storage::allDirectories($directory) as $subdir) {
            if (empty(Storage::allFiles($subdir))) {
                Storage::deleteDirectory($subdir);
                $this->line("  [REMOVED] Empty directory: {$subdir}");
            }
        }
    }

    /**
     * Format bytes to human-readable size string.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
