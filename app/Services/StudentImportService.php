<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class StudentImportService
{
    /** Save uploads into a unique batch directory */
    public function stageFiles($request): string
    {
        // If there’s an old staged batch, clear it first
        if (session()->has('student_batch_id')) {
            $old = (string) session('student_batch_id');
            if ($old) { $this->discardBatch($old); }
            session()->forget('student_batch_id');
        }

        $batchId = 'batch_'.now()->format('Ymd_His').'_'.Str::random(5);
        $dir = "student_imports/{$batchId}";
        Storage::disk('local')->makeDirectory($dir);

        $sheetRel = $request->file('sheet')->store($dir, 'local');
        $zipRel   = $request->hasFile('images') ? $request->file('images')->store($dir, 'local') : null;

        $meta = [
            'sheet'          => $sheetRel,
            'zip'            => $zipRel,
            'require_photos' => $request->boolean('require_photos'),
        ];
        Storage::disk('local')->put("{$dir}/meta.json", json_encode($meta));

        return $batchId;
    }

    /** Parse & validate and build a preview */
    public function buildPreview(string $batchId, bool $requirePhotos = false): array
    {
        $dir = "student_imports/{$batchId}";
        $meta = $this->readJson("{$dir}/meta.json");
        if ($requirePhotos) $meta['require_photos'] = true; // allow override from controller

        [$rows, $issues] = $this->parseSheet($meta['sheet']);

        // Index images if any ZIP
        $map = [];
        if (!empty($meta['zip'])) {
            $map = $this->indexZipFilenames(Storage::disk('local')->path($meta['zip']));
        }

        $photosDir = "{$dir}/photos";
        Storage::disk('local')->makeDirectory($photosDir);

        // Track duplicates
        $seen = [];

        foreach ($rows as $i => &$r) {
            $r['student_id'] = $this->cleanId($r['student_id'] ?? '');
            $r['name_ar']    = trim((string)($r['name_ar'] ?? ''));
            $r['name_en']    = trim((string)($r['name_en'] ?? ''));
            $pf = trim((string)($r['photo_filename'] ?? ''));

            if ($r['student_id'] === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $r['student_id'])) {
                $issues[] = "Row ".($i+2).": invalid student_id";
            }
            if ($r['name_ar'] === '' || $r['name_en'] === '') {
                $issues[] = "Row ".($i+2).": missing name_ar / name_en for ID {$r['student_id']}";
            }
            if (isset($seen[$r['student_id']])) {
                $issues[] = "Duplicate student_id {$r['student_id']} (rows {$seen[$r['student_id']]} and ".($i+2).")";
            } else {
                $seen[$r['student_id']] = $i+2;
            }

            // Resolve photo match (optional)
            $matched = null;
            if ($pf !== '') {
                $key = $this->normalizeKey(basename($pf));
                $matched = $map[$key] ?? null;
                if (!$matched) $issues[] = "Row ".($i+2).": photo_filename '{$pf}' not found in ZIP";
            } elseif (!empty($map)) {
                foreach (['jpg','jpeg','png','webp'] as $ext) {
                    $key = $this->normalizeKey($r['student_id'].'.'.$ext);
                    if (isset($map[$key])) { $matched = $map[$key]; break; }
                }
                if (!$matched && ($meta['require_photos'] ?? false)) {
                    $issues[] = "Row ".($i+2).": missing photo for ID {$r['student_id']}";
                }
            } else {
                if ($meta['require_photos'] ?? false) {
                    $issues[] = "Row ".($i+2).": photos required but no images ZIP was uploaded";
                }
            }

            if ($matched) {
                $ext = pathinfo($matched['name'], PATHINFO_EXTENSION);
                $r['photo_rel'] = "{$photosDir}/{$r['student_id']}.{$ext}";
                $r['photo_zip_index'] = $matched['index'];
            } else {
                $r['photo_rel'] = null;
            }
        }
        unset($r);

        Storage::disk('local')->put("{$dir}/rows.json", json_encode($rows, JSON_UNESCAPED_UNICODE));
        Storage::disk('local')->put("{$dir}/preview_meta.json", json_encode($meta));

        return [$rows, $issues, $meta];
    }

    /** Generate PDFs -> ZIP -> download; cleanup temp files */
    public function finalizeImport(string $batchId)
    {
        $dir = "student_imports/{$batchId}";
        $meta = $this->readJson("{$dir}/preview_meta.json") ?: $this->readJson("{$dir}/meta.json");
        $rows = $this->readJson("{$dir}/rows.json");

        abort_unless(is_array($rows), 400, 'No staged rows for this batch.');

        $zipFromUpload = $meta['zip'] ?? null;
        $zipPath = $zipFromUpload ? Storage::disk('local')->path($zipFromUpload) : null;

        // Extract matched photos to photosDir
        if ($zipPath && file_exists($zipPath)) {
            $za = new ZipArchive();
            $za->open($zipPath);
            foreach ($rows as $r) {
                if (!empty($r['photo_rel']) && isset($r['photo_zip_index'])) {
                    $stream = $za->getStream($za->getNameIndex($r['photo_zip_index']));
                    if ($stream) {
                        $data = stream_get_contents($stream);
                        fclose($stream);
                        Storage::disk('local')->put($r['photo_rel'], $data);
                    }
                }
            }
            $za->close();
        }

        // Generate PDFs
        $outDirRel = "{$dir}/pdfs";
        Storage::disk('local')->makeDirectory($outDirRel);

        $svc = app(\App\Services\CertificateService::class);
        foreach ($rows as $r) {
            $photoAbs = $r['photo_rel'] ? Storage::disk('local')->path($r['photo_rel']) : null;
            // IMPORTANT: your CertificateService::generateStudent must tolerate null $photoAbs and missing background
            $svc->generateStudent($r['student_id'], $r['name_ar'], $r['name_en'], $photoAbs, $outDirRel);
        }

        // Build ZIP OUTSIDE the batch dir so we can delete the batch before sending
        $exportDir = "student_exports";
        Storage::disk('local')->makeDirectory($exportDir);
        $zipRel = "{$exportDir}/certificates_{$batchId}.zip";
        $zipAbs = Storage::disk('local')->path($zipRel);

        $za = new ZipArchive();
        $za->open($zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach (Storage::disk('local')->files($outDirRel) as $pdfRel) {
            $za->addFile(Storage::disk('local')->path($pdfRel), basename($pdfRel));
        }
        $za->close();

        // Clean the entire staging batch directory NOW (keeps disk clean)
        $this->discardBatch($batchId);

        // Stream download and remove the ZIP after sending it
        return response()->download($zipAbs, basename($zipRel))->deleteFileAfterSend(true);
    }

    /** Delete all staged files for a batch (safe to call multiple times) */
    public function discardBatch(string $batchId): void
    {
        $dir = "student_imports/{$batchId}";
        if (Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->deleteDirectory($dir);
        }
    }

    // ------------- helpers -------------

    private function readJson(string $rel): ?array
    {
        if (!Storage::disk('local')->exists($rel)) return null;
        $s = Storage::disk('local')->get($rel);
        $j = json_decode($s, true);
        return is_array($j) ? $j : null;
    }

    private function parseSheet(string $sheetRel): array
    {
        $path = Storage::disk('local')->path($sheetRel);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $rows = []; $issues = [];

        if (in_array($ext, ['csv','txt'])) {
            $fp = fopen($path, 'r');
            $headers = fgetcsv($fp, 0, ',');
            $headers = array_map(fn($h)=>strtolower(trim((string)$h)), $headers);
            while (($line = fgetcsv($fp, 0, ',')) !== false) {
                if (count(array_filter($line, fn($v)=>trim((string)$v)!=='')) === 0) continue;
                $row = array_combine($headers, array_map(fn($v)=>(string)$v, $line));
                $rows[] = [
                    'student_id'     => $row['student_id']     ?? '',
                    'name_ar'        => $row['name_ar']        ?? '',
                    'name_en'        => $row['name_en']        ?? '',
                    'photo_filename' => $row['photo_filename'] ?? '',
                ];
            }
            fclose($fp);
        } else {
            $ss = IOFactory::load($path);
            $sheet = $ss->getSheetByName('Students') ?? $ss->getActiveSheet();
            $headers = [];
            foreach ($sheet->getRowIterator(1,1) as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) $cells[] = strtolower(trim((string)$cell->getValue()));
                $headers = $cells;
            }
            foreach ($sheet->getRowIterator(2) as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) $cells[] = (string)$cell->getValue();
                if (count(array_filter($cells, fn($v)=>trim($v)!=='')) === 0) continue;
                $assoc = array_combine($headers, $cells);
                $rows[] = [
                    'student_id'     => $assoc['student_id']     ?? '',
                    'name_ar'        => $assoc['name_ar']        ?? '',
                    'name_en'        => $assoc['name_en']        ?? '',
                    'photo_filename' => $assoc['photo_filename'] ?? '',
                ];
            }
        }
        return [$rows, $issues];
    }

    private function indexZipFilenames(string $zipAbs): array
    {
        $map = [];
        $za = new ZipArchive();
        if ($za->open($zipAbs) === true) {
            for ($i=0; $i < $za->numFiles; $i++) {
                $name = $za->getNameIndex($i);
                if (preg_match('/\.(jpe?g|png|webp)$/i', $name)) {
                    $map[$this->normalizeKey(basename($name))] = ['index'=>$i, 'name'=>$name];
                }
            }
            $za->close();
        }
        return $map;
    }

    private function normalizeKey(string $s): string
    {
        return strtolower(trim($s));
    }

    private function cleanId(string $id): string
    {
        $id = trim($id);
        return preg_replace('/[^A-Za-z0-9_-]/', '', $id);
    }
}
