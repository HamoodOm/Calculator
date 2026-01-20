<?php

namespace App\Services;

use App\Models\Track;
use App\Models\TeacherSetting;
use App\Models\StudentSetting;
use InvalidArgumentException;

class TemplateResolver
{
    /**
     * resolve: يُرجع الخلفية + الإحداثيات + التنسيقات
     * @param string $role Role type (teacher/student)
     * @param string $trackKey Track key
     * @param string $gender Gender (male/female)
     * @return array{bg_abs:string,bg_rel:string,positions:array,style:array}
     */
    public function resolve(string $role, string $trackKey, string $gender): array
    {
        $all = config('certificates.templates');

        $role = in_array($role, ['teacher','student'], true) ? $role : 'teacher';
        if (!isset($all[$role])) {
            throw new InvalidArgumentException("No templates for role: {$role}");
        }

        $byRole = $all[$role];

        // Check if track exists in config
        $trackInConfig = isset($byRole[$trackKey]);

        // If not in config, check database based on role
        if (!$trackInConfig) {
            if ($role === 'teacher') {
                return $this->resolveFromDatabase($trackKey, $gender);
            } elseif ($role === 'student') {
                return $this->resolveFromStudentDatabase($trackKey, $gender);
            }
            throw new InvalidArgumentException("Unknown track for {$role}: {$trackKey}");
        }

        $byTrack = $byRole[$trackKey];
        $gender = in_array($gender, ['male','female'], true) ? $gender : 'male';
        if (!isset($byTrack[$gender])) {
            throw new InvalidArgumentException("No template for gender={$gender} (role={$role}, track={$trackKey})");
        }

        $tpl = $byTrack[$gender];
        $bgRel = $tpl['bg'] ?? null;           // مثال: images/templates/teacher/t_laravel_fundamentals-male.jpg
        $pos   = $tpl['pos'] ?? null;

        // If the template defines 'photo' at the root, merge it into positions (from previous fix)
        if (isset($tpl['photo']) && is_array($tpl['photo'])) {
            $pos['photo'] = $tpl['photo'];
        }

        // --- Normalize background keys ---
        $background_rel = $tpl['background_rel'] ?? ($tpl['background'] ?? null);
        $background_abs = $tpl['background_abs'] ?? null;
        if (!$background_abs && $background_rel) {
            $rel = ltrim($background_rel, '/');
            if (\Storage::disk('local')->exists($rel)) {
                $background_abs = \Storage::disk('local')->path($rel);
                $background_rel = $rel;
            } elseif (\Storage::disk('public')->exists($rel)) {
                $background_abs = \Storage::disk('public')->path($rel);
                $background_rel = $rel;
            } else {
                $p = public_path($rel);
                if (is_file($p)) { $background_abs = $p; }
                $r = resource_path($rel);
                if (!$background_abs && is_file($r)) { $background_abs = $r; }
            }
        }

        // Build the final payload
        $tpl['pos'] = $pos;
        $tpl['background_rel'] = $background_rel;
        $tpl['background_abs'] = $background_abs;


        // If the template defines 'photo' at the root, merge it into positions
        if (isset($tpl['photo']) && is_array($tpl['photo'])) {
            $pos['photo'] = $tpl['photo'];
        }
        $style = $tpl['style'] ?? [];

        if (!$bgRel || !$pos || !is_array($pos)) {
            throw new InvalidArgumentException("Template incomplete for {$role}/{$trackKey}/{$gender}.");
        }

        $bgAbs = public_path($bgRel);
        if (!is_file($bgAbs)) {
            throw new InvalidArgumentException("Background not found at {$bgAbs}");
        }

        // === DB Override Logic (for both teacher and student roles) ===
        if ($role === 'teacher') {
            $dbOverrides = $this->getTeacherOverrides($trackKey, $gender);
            if ($dbOverrides) {
                // Override background if DB has one
                if (!empty($dbOverrides['bg_abs'])) {
                    $bgAbs = $dbOverrides['bg_abs'];
                    $bgRel = $dbOverrides['bg_rel'];
                }

                // Merge positions (DB overrides config)
                if (!empty($dbOverrides['positions']) && is_array($dbOverrides['positions'])) {
                    $pos = array_merge($pos, $dbOverrides['positions']);
                }

                // Merge style (DB overrides config)
                if (!empty($dbOverrides['style']) && is_array($dbOverrides['style'])) {
                    $style = array_merge($style, $dbOverrides['style']);
                }
            }
        } elseif ($role === 'student') {
            // Always use StudentSetting for students
            $dbOverrides = $this->getStudentOverrides($trackKey, $gender);

            if ($dbOverrides) {
                // Override background if DB has one
                if (!empty($dbOverrides['bg_abs'])) {
                    $bgAbs = $dbOverrides['bg_abs'];
                    $bgRel = $dbOverrides['bg_rel'];
                }

                // Smart merge positions (DB overrides config, but only non-null/non-empty-string values)
                if (!empty($dbOverrides['positions']) && is_array($dbOverrides['positions'])) {
                    foreach ($dbOverrides['positions'] as $key => $dbPos) {
                        if (is_array($dbPos) && !empty($dbPos)) {
                            // Merge field-by-field, keeping config values if DB value is null or empty string
                            // Note: We allow 0 as a valid value (for positions like left:0, top:0)
                            if (!isset($pos[$key])) {
                                $pos[$key] = $dbPos;
                            } else {
                                $pos[$key] = array_merge($pos[$key], array_filter($dbPos, function($v) {
                                    return $v !== null && $v !== '';
                                }));
                            }
                        }
                    }
                }

                // Merge style (DB overrides config)
                if (!empty($dbOverrides['style']) && is_array($dbOverrides['style'])) {
                    $style = array_merge($style, $dbOverrides['style']);
                }
            }
        }

        // Get page_mm from template or use defaults
        $pageMm = $tpl['page_mm'] ?? ['w' => 297, 'h' => 210];

        return [
            'bg_abs'    => $bgAbs,
            'bg_rel'    => $bgRel,
            'positions' => $pos,
            'style'     => $style,
            'page_mm'   => $pageMm,
        ];
    }

    /**
     * Get teacher overrides from database if they exist.
     *
     * @param string $trackKey
     * @param string $gender
     * @return array|null
     */
    protected function getTeacherOverrides(string $trackKey, string $gender): ?array
    {
        $track = Track::where('key', $trackKey)->first();
        if (!$track) {
            return null;
        }

        $setting = TeacherSetting::where('track_id', $track->id)
            ->where('gender', $gender)
            ->first();

        if (!$setting) {
            return null;
        }

        // Resolve background path
        $bgRel = $setting->certificate_bg;
        $bgAbs = null;

        if ($bgRel) {
            $rel = ltrim($bgRel, '/');
            if (\Storage::disk('local')->exists($rel)) {
                $bgAbs = \Storage::disk('local')->path($rel);
            } elseif (\Storage::disk('public')->exists($rel)) {
                $bgAbs = \Storage::disk('public')->path($rel);
            } else {
                $p = public_path($rel);
                if (is_file($p)) {
                    $bgAbs = $p;
                }
            }
        }

        return [
            'bg_abs' => $bgAbs,
            'bg_rel' => $bgRel,
            'positions' => $setting->positions,
            'style' => $setting->style,
        ];
    }

    /**
     * Get student overrides from database if they exist.
     *
     * @param string $trackKey
     * @param string $gender
     * @return array|null
     */
    protected function getStudentOverrides(string $trackKey, string $gender): ?array
    {
        $track = Track::where('key', $trackKey)->first();
        if (!$track) {
            return null;
        }

        $setting = StudentSetting::where('track_id', $track->id)
            ->where('gender', $gender)
            ->first();

        if (!$setting) {
            return null;
        }

        // Resolve background path
        $bgRel = $setting->certificate_bg;
        $bgAbs = null;

        if ($bgRel) {
            $rel = ltrim($bgRel, '/');
            if (\Storage::disk('local')->exists($rel)) {
                $bgAbs = \Storage::disk('local')->path($rel);
            } elseif (\Storage::disk('public')->exists($rel)) {
                $bgAbs = \Storage::disk('public')->path($rel);
            } else {
                $p = public_path($rel);
                if (is_file($p)) {
                    $bgAbs = $p;
                }
            }
        }

        return [
            'bg_abs' => $bgAbs,
            'bg_rel' => $bgRel,
            'positions' => $setting->positions,
            'style' => $setting->style,
        ];
    }

    /**
     * Resolve template entirely from database (for database-only tracks).
     *
     * @param string $trackKey
     * @param string $gender
     * @return array
     */
    protected function resolveFromDatabase(string $trackKey, string $gender): array
    {
        $track = Track::where('key', $trackKey)->first();
        if (!$track) {
            throw new InvalidArgumentException("Track not found: {$trackKey}");
        }

        $gender = in_array($gender, ['male','female'], true) ? $gender : 'male';

        $setting = TeacherSetting::where('track_id', $track->id)
            ->where('gender', $gender)
            ->first();

        if (!$setting) {
            throw new InvalidArgumentException("No settings found for track={$trackKey}, gender={$gender}");
        }

        // Resolve background path
        $bgRel = $setting->certificate_bg;
        $bgAbs = null;

        if ($bgRel) {
            $rel = ltrim($bgRel, '/');
            if (\Storage::disk('local')->exists($rel)) {
                $bgAbs = \Storage::disk('local')->path($rel);
            } elseif (\Storage::disk('public')->exists($rel)) {
                $bgAbs = \Storage::disk('public')->path($rel);
            } else {
                $p = public_path($rel);
                if (is_file($p)) {
                    $bgAbs = $p;
                }
            }
        }

        if (!$bgAbs || !is_file($bgAbs)) {
            throw new InvalidArgumentException("Background file not found for track={$trackKey}, gender={$gender}");
        }

        return [
            'bg_abs'    => $bgAbs,
            'bg_rel'    => $bgRel,
            'positions' => $setting->positions,
            'style'     => $setting->style,
            'page_mm'   => ['w' => 297, 'h' => 210], // Default A4 landscape
        ];
    }

    /**
     * Resolve template entirely from database for student tracks.
     *
     * @param string $trackKey
     * @param string $gender
     * @return array
     */
    protected function resolveFromStudentDatabase(string $trackKey, string $gender): array
    {
        $track = Track::where('key', $trackKey)->first();
        if (!$track) {
            throw new InvalidArgumentException("Student track not found in database: {$trackKey}");
        }

        $gender = in_array($gender, ['male','female'], true) ? $gender : 'male';

        // Always use StudentSetting
        $setting = StudentSetting::where('track_id', $track->id)
            ->where('gender', $gender)
            ->first();

        if (!$setting) {
            throw new InvalidArgumentException("No student settings found in student_settings for track={$trackKey}, gender={$gender}. Please recreate the track or add settings via 'حفظ الإعدادات الافتراضية'.");
        }

        // Resolve background path
        $bgRel = $setting->certificate_bg;
        $bgAbs = null;

        if ($bgRel) {
            $rel = ltrim($bgRel, '/');
            if (\Storage::disk('local')->exists($rel)) {
                $bgAbs = \Storage::disk('local')->path($rel);
            } elseif (\Storage::disk('public')->exists($rel)) {
                $bgAbs = \Storage::disk('public')->path($rel);
            } else {
                $p = public_path($rel);
                if (is_file($p)) {
                    $bgAbs = $p;
                }
            }
        }

        if (!$bgAbs || !is_file($bgAbs)) {
            throw new InvalidArgumentException("Background file not found for student track={$trackKey}, gender={$gender}. Expected path: {$bgRel}");
        }

        return [
            'bg_abs'    => $bgAbs,
            'bg_rel'    => $bgRel,
            'positions' => $setting->positions ?? $this->getDefaultPositions(),
            'style'     => $setting->style ?? [],
            'page_mm'   => ['w' => 297, 'h' => 210], // Default A4 landscape
        ];
    }

    /**
     * Get default positions for a template.
     *
     * @return array
     */
    protected function getDefaultPositions(): array
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
}
