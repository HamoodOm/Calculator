<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'header_color',
        'custom_hex_color',
        'badge_color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users belonging to this institution.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the tracks belonging to this institution.
     */
    public function tracks()
    {
        return $this->hasMany(Track::class);
    }

    /**
     * Check if using a custom hex color
     */
    public function hasHexColor(): bool
    {
        return !empty($this->custom_hex_color) && str_starts_with($this->custom_hex_color, '#');
    }

    /**
     * Get the hex color value
     */
    public function getHexColor(): ?string
    {
        return $this->hasHexColor() ? $this->custom_hex_color : null;
    }

    /**
     * Get the theme colors for this institution.
     */
    public function getThemeColors(): array
    {
        // If using custom hex color, return hex-based theme
        if ($this->hasHexColor()) {
            return [
                'bg' => '',
                'bg_hex' => $this->custom_hex_color,
                'hover' => 'hover:brightness-90',
                'text' => 'text-white',
                'accent' => 'text-gray-200',
                'badge' => '',
                'badge_hex' => $this->adjustBrightness($this->custom_hex_color, -30),
            ];
        }

        // Map header color to full theme
        $colorMap = [
            'bg-purple-700' => ['bg' => 'bg-purple-700', 'hover' => 'hover:bg-purple-800', 'text' => 'text-purple-100', 'accent' => 'text-purple-200', 'badge' => 'bg-purple-500'],
            'bg-indigo-700' => ['bg' => 'bg-indigo-700', 'hover' => 'hover:bg-indigo-800', 'text' => 'text-indigo-100', 'accent' => 'text-indigo-200', 'badge' => 'bg-indigo-500'],
            'bg-blue-700' => ['bg' => 'bg-blue-700', 'hover' => 'hover:bg-blue-800', 'text' => 'text-blue-100', 'accent' => 'text-blue-200', 'badge' => 'bg-blue-500'],
            'bg-teal-700' => ['bg' => 'bg-teal-700', 'hover' => 'hover:bg-teal-800', 'text' => 'text-teal-100', 'accent' => 'text-teal-200', 'badge' => 'bg-teal-500'],
            'bg-green-700' => ['bg' => 'bg-green-700', 'hover' => 'hover:bg-green-800', 'text' => 'text-green-100', 'accent' => 'text-green-200', 'badge' => 'bg-green-500'],
            'bg-yellow-700' => ['bg' => 'bg-yellow-700', 'hover' => 'hover:bg-yellow-800', 'text' => 'text-yellow-100', 'accent' => 'text-yellow-200', 'badge' => 'bg-yellow-500'],
            'bg-orange-700' => ['bg' => 'bg-orange-700', 'hover' => 'hover:bg-orange-800', 'text' => 'text-orange-100', 'accent' => 'text-orange-200', 'badge' => 'bg-orange-500'],
            'bg-red-700' => ['bg' => 'bg-red-700', 'hover' => 'hover:bg-red-800', 'text' => 'text-red-100', 'accent' => 'text-red-200', 'badge' => 'bg-red-500'],
            'bg-pink-700' => ['bg' => 'bg-pink-700', 'hover' => 'hover:bg-pink-800', 'text' => 'text-pink-100', 'accent' => 'text-pink-200', 'badge' => 'bg-pink-500'],
            'bg-gray-700' => ['bg' => 'bg-gray-700', 'hover' => 'hover:bg-gray-800', 'text' => 'text-gray-100', 'accent' => 'text-gray-200', 'badge' => 'bg-gray-500'],
            'bg-slate-700' => ['bg' => 'bg-slate-700', 'hover' => 'hover:bg-slate-800', 'text' => 'text-slate-100', 'accent' => 'text-slate-200', 'badge' => 'bg-slate-500'],
            'bg-cyan-700' => ['bg' => 'bg-cyan-700', 'hover' => 'hover:bg-cyan-800', 'text' => 'text-cyan-100', 'accent' => 'text-cyan-200', 'badge' => 'bg-cyan-500'],
            'bg-emerald-700' => ['bg' => 'bg-emerald-700', 'hover' => 'hover:bg-emerald-800', 'text' => 'text-emerald-100', 'accent' => 'text-emerald-200', 'badge' => 'bg-emerald-500'],
            'bg-rose-700' => ['bg' => 'bg-rose-700', 'hover' => 'hover:bg-rose-800', 'text' => 'text-rose-100', 'accent' => 'text-rose-200', 'badge' => 'bg-rose-500'],
            'bg-amber-700' => ['bg' => 'bg-amber-700', 'hover' => 'hover:bg-amber-800', 'text' => 'text-amber-100', 'accent' => 'text-amber-200', 'badge' => 'bg-amber-500'],
        ];

        return $colorMap[$this->header_color] ?? $colorMap['bg-indigo-700'];
    }

    /**
     * Adjust hex color brightness
     */
    protected function adjustBrightness(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + ($r * $percent / 100)));
        $g = max(0, min(255, $g + ($g * $percent / 100)));
        $b = max(0, min(255, $b + ($b * $percent / 100)));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Get available color options.
     */
    public static function getAvailableColors(): array
    {
        return [
            'bg-purple-700' => 'بنفسجي',
            'bg-indigo-700' => 'نيلي',
            'bg-blue-700' => 'أزرق',
            'bg-teal-700' => 'أخضر مزرق',
            'bg-green-700' => 'أخضر',
            'bg-yellow-700' => 'أصفر',
            'bg-orange-700' => 'برتقالي',
            'bg-red-700' => 'أحمر',
            'bg-pink-700' => 'وردي',
            'bg-gray-700' => 'رمادي',
            'bg-slate-700' => 'رمادي داكن',
            'bg-cyan-700' => 'سماوي',
            'bg-emerald-700' => 'زمردي',
            'bg-rose-700' => 'وردي غامق',
            'bg-amber-700' => 'كهرماني',
        ];
    }
}
