<?php

namespace App\Support;

use ZipArchive;
use SimpleXMLElement;
use RuntimeException;

class StudentListReader
{
    private static array $arKeys = ['name_ar','arabic_name','الاسم','الاسم_العربي','اسم_عربي'];
    private static array $enKeys = ['name_en','english_name','الاسم_الإنجليزي','الاسم_الانجليزي','اسم_إنجليزي','اسم_انجليزي'];
    private static array $idKeys = ['student_id','id','رقم','معرف','الرقم'];
    private static array $pfKeys = ['photo_filename','photo','image','filename','الصورة','اسم_الصورة'];

    public static function read(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['csv','txt'], true)) return self::readCsv($path);
        if ($ext === 'xlsx') return self::readXlsx($path);
        throw new RuntimeException('Unsupported file type.');
    }

    private static function mapHeader(array $header): array
    {
        $norm = array_map(fn($h)=>mb_strtolower(trim((string)$h)), $header);
        $find = function(array $cands) use ($norm): ?int {
            foreach ($cands as $c) { $i = array_search(mb_strtolower($c), $norm, true); if ($i !== false) return $i; }
            return null;
        };

        $ar = $find(self::$arKeys);
        $en = $find(self::$enKeys);
        if ($ar === null || $en === null) throw new RuntimeException('Header must contain Arabic and English name columns.');

        return ['ar'=>$ar, 'en'=>$en, 'id'=>$find(self::$idKeys), 'pf'=>$find(self::$pfKeys)];
    }

    private static function readCsv(string $path): array
    {
        $fh = @fopen($path, 'r');
        if (!$fh) throw new RuntimeException('Cannot open CSV.');
        $rows = []; $header = null; $map = null;

        while (($data = fgetcsv($fh)) !== false) {
            if ($header === null) { $header=$data; $map=self::mapHeader($header); continue; }
            if (!array_filter($data, fn($v)=>trim((string)$v) !== '')) continue;

            $ar = trim((string)($data[$map['ar']] ?? ''));
            $en = trim((string)($data[$map['en']] ?? ''));
            if ($ar === '' || $en === '') continue;

            $row = ['name_ar'=>$ar, 'name_en'=>$en];
            if ($map['id'] !== null) $row['student_id'] = trim((string)($data[$map['id']] ?? ''));
            if ($map['pf'] !== null) $row['photo_filename'] = trim((string)($data[$map['pf']] ?? ''));
            $rows[] = $row;
        }
        fclose($fh);
        if (empty($rows)) throw new RuntimeException('No valid names in file.');
        return $rows;
    }

    private static function readXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Invalid XLSX (zip open failed).');

        $shared = [];
        if (($ss = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $xml = new SimpleXMLElement($ss);
            foreach ($xml->si as $i=>$si) $shared[$i] = (string)($si->t ?? '');
        }
        $wbRels = new SimpleXMLElement($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $wb     = new SimpleXMLElement($zip->getFromName('xl/workbook.xml'));
        $rid    = (string)$wb->sheets->sheet[0]->attributes('r', true)['id'];
        $sheetRel = null;
        foreach ($wbRels->Relationship as $rel) {
            if ((string)$rel['Id'] === $rid) { $sheetRel = (string)$rel['Target']; break; }
        }
        if (!$sheetRel) { $zip->close(); throw new RuntimeException('Cannot resolve sheet rel.'); }

        $sheet = new SimpleXMLElement($zip->getFromName('xl/'.ltrim($sheetRel,'/')));
        $rows = []; $header = null; $map = null;

        foreach ($sheet->sheetData->row as $row) {
            $cells = [];
            $i = 0;
            foreach ($row->c as $c) {
                $t = (string)$c['t']; $v = '';
                if     ($t === 's')        { $idx = (int)$c->v; $v = $shared[$idx] ?? ''; }
                elseif ($t === 'inlineStr'){ $v = (string)$c->is->t; }
                else                       { $v = (string)$c->v; }
                $cells[$i++] = $v;
            }

            if ($header === null) { $header=$cells; $map=self::mapHeader($header); continue; }
            if (!array_filter($cells, fn($v)=>trim((string)$v) !== '')) continue;

            $ar = trim((string)($cells[$map['ar']] ?? ''));
            $en = trim((string)($cells[$map['en']] ?? ''));
            if ($ar === '' || $en === '') continue;

            $rowOut = ['name_ar'=>$ar, 'name_en'=>$en];
            if ($map['id'] !== null) $rowOut['student_id'] = trim((string)($cells[$map['id']] ?? ''));
            if ($map['pf'] !== null) $rowOut['photo_filename'] = trim((string)($cells[$map['pf']] ?? ''));
            $rows[] = $rowOut;
        }
        $zip->close();

        if (empty($rows)) throw new RuntimeException('No valid names in XLSX.');
        return $rows;
    }
}
