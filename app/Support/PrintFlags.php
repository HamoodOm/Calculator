<?php

namespace App\Support;

use Illuminate\Http\Request;

class PrintFlags
{
    public static function fromRequest(Request $request): array
    {
        // defaults: everything prints
        $flags = [
            'ar_name' => true,
            'en_name' => true,
            'ar_track' => true,
            'en_track' => true,
            'ar_duration' => true,
            'en_duration' => true,
        ];

        $incoming = (array) $request->input('print', []);
        foreach ($flags as $k => $v) {
            $val = $incoming[$k] ?? '1';
            $flags[$k] = ($val === '1' || $val === 1 || $val === true);
        }

        $arabicOnly  = self::toBool($request->input('arabic_only', null));
        $englishOnly = self::toBool($request->input('english_only', null));

        if ($arabicOnly && !$englishOnly) {
            $flags['en_name'] = $flags['en_track'] = $flags['en_duration'] = false;
            $flags['ar_name'] = $flags['ar_track'] = $flags['ar_duration'] = true;
        } elseif ($englishOnly && !$arabicOnly) {
            $flags['ar_name'] = $flags['ar_track'] = $flags['ar_duration'] = false;
            $flags['en_name'] = $flags['en_track'] = $flags['en_duration'] = true;
        }

        return $flags;
    }

    private static function toBool($v): bool
    {
        if ($v === null) return false;
        if (is_bool($v)) return $v;
        if (is_numeric($v)) return ((int)$v) === 1;
        if (is_string($v)) {
            $v = strtolower(trim($v));
            return in_array($v, ['1', 'true', 'yes', 'on'], true);
        }
        return false;
    }
}
