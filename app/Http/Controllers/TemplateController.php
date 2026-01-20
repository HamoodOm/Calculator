<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TemplateResolver;
use App\Models\Track;

class TemplateController extends Controller
{
    public function info(Request $request, TemplateResolver $resolver)
    {
        $request->validate([
            'role'      => ['required','in:teacher,student'],
            'track_key' => ['required','string'],
            'gender'    => ['required','in:male,female'],
        ]);

        $role     = $request->query('role');
        $trackKey = $request->query('track_key');
        $gender   = $request->query('gender');

        // For both teachers and students, check both config and database tracks
        if ($role === 'teacher') {
            $configTracks = array_keys(config('certificates.tracks_teacher', []));
            $dbTracks = Track::where('active', true)
                ->where('key', 'like', 't_%')
                ->pluck('key')
                ->toArray();
            $allowed = array_merge($configTracks, $dbTracks);
        } else {
            $configTracks = array_keys(config('certificates.tracks_student', []));
            $dbTracks = Track::where('active', true)
                ->where(function($q) {
                    $q->where('key', 'like', 's_%')
                      ->orWhere('key', 'not like', 't_%');
                })
                ->pluck('key')
                ->toArray();
            $allowed = array_merge($configTracks, $dbTracks);
        }

        abort_unless(in_array($trackKey, $allowed, true), 422, 'Invalid track_key for role.');

        $tpl   = $resolver->resolve($role, $trackKey, $gender);
        $bgRel = $tpl['bg_rel']; // نُرجع المسار النسبي
        $pos   = $tpl['positions'];
        $style = $tpl['style'] ?? [];
        $pageMm = $tpl['page_mm'] ?? ['w' => 297, 'h' => 210];

        // URL عامة للخلفية
        $bgUrl = asset($bgRel);

        // Get date_type from database if available (for student role)
        $dateType = null;
        if ($role === 'student') {
            $track = Track::where('key', $trackKey)->first();
            if ($track) {
                $setting = \App\Models\StudentSetting::where('track_id', $track->id)
                    ->where('gender', $gender)
                    ->first();
                if ($setting) {
                    $dateType = $setting->date_type ?? 'duration';
                }
            }
        }

        return response()->json([
            'success'        => true,
            'background_url' => $bgUrl,
            'positions'      => $pos,
            'style'          => $style,
            'page_mm'        => $pageMm,
            'date_type'      => $dateType,
        ]);
    }
}
