<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ArPHP\I18N\Arabic;

class ImageCertificateService
{
    private const PAGE_W = 297.0; // mm (A4 landscape width)
    private const PAGE_H = 210.0; // mm (A4 landscape height)
    private const DPI = 300; // High quality for printing

    // Expected image dimensions at 300 DPI for A4 landscape
    private const EXPECTED_WIDTH = 3508;  // 297mm at 300 DPI
    private const EXPECTED_HEIGHT = 2480; // 210mm at 300 DPI

    protected string $root = 'certificates';
    protected string $previewRoot = 'api_certificates_temp';
    protected ?Arabic $arabic = null;

    // Dynamic scale factors for current image
    protected float $scaleX = 1.0;
    protected float $scaleY = 1.0;

    public function __construct()
    {
        // Initialize AR-PHP for Arabic text processing
        $this->arabic = new Arabic();
    }

    /**
     * Calculate scale factors based on actual image dimensions
     * This ensures text is positioned correctly regardless of template image size
     *
     * @param int $actualWidth Actual image width in pixels
     * @param int $actualHeight Actual image height in pixels
     */
    private function calculateScaleFactors(int $actualWidth, int $actualHeight): void
    {
        // Calculate scale factors relative to expected A4 at 300 DPI
        $this->scaleX = $actualWidth / self::EXPECTED_WIDTH;
        $this->scaleY = $actualHeight / self::EXPECTED_HEIGHT;

        \Log::debug("Image scale factors calculated", [
            'actual' => "{$actualWidth}x{$actualHeight}",
            'expected' => self::EXPECTED_WIDTH . 'x' . self::EXPECTED_HEIGHT,
            'scaleX' => $this->scaleX,
            'scaleY' => $this->scaleY
        ]);
    }

    /**
     * Reset scale factors to default
     */
    private function resetScaleFactors(): void
    {
        $this->scaleX = 1.0;
        $this->scaleY = 1.0;
    }

    /**
     * Convert Arabic text to proper glyphs for GD/ImageMagick rendering
     * CRITICAL: This fixes RTL text display issues in image generation
     *
     * This method converts Arabic logical text (RTL) to visual order (LTR)
     * for rendering engines like GD and ImageMagick that don't handle RTL natively.
     *
     * Example: "بسم الله الرحمن الرحيم" → proper connected glyphs in visual order
     *
     * @param string $text Arabic text in logical order
     * @return string Arabic text in visual order with proper glyph joining
     */
    private function arabicGlyphs(string $text): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        try {
            // utf8Glyphs() performs:
            // 1. Arabic letter joining/connection
            // 2. Converts from logical (RTL) to visual (LTR) order
            // 3. Returns UTF-8 that renders correctly in GD/ImageMagick
            return $this->arabic->utf8Glyphs($text);
        } catch (\Exception $e) {
            \Log::warning("AR-PHP glyphs conversion failed: " . $e->getMessage());
            return $text;
        }
    }

    /**
     * Convert number to Arabic text
     * Example: 1975 → "ألف وتسعمائة وخمسة وسبعون"
     *
     * @param int $number Number to convert
     * @param string|null $mode Conversion mode: null (default), 'Feminine', 'Currency', 'Short'
     * @return string Arabic text representation
     */
    private function numberToArabic(int $number, string $mode = null): string
    {
        try {
            if ($mode) {
                return $this->arabic->int2str($number, $mode);
            }
            return $this->arabic->int2str($number);
        } catch (\Exception $e) {
            \Log::warning("AR-PHP number conversion failed: " . $e->getMessage());
            return (string)$number;
        }
    }

    /**
     * Format date in Arabic with proper calendar support
     *
     * @param string $format Date format (PHP date format)
     * @param int $timestamp Unix timestamp
     * @param int $mode Calendar mode: 0=Gregorian (default), 2=Hijri, 8=Umm Al-Qura
     * @return string Formatted date in Arabic
     */
    private function formatArabicDate(string $format, $timestamp, int $mode = 0): string
    {
        try {
            // Set calendar mode (0=Gregorian, 2=Hijri, 8=Umm Al-Qura)
            $this->arabic->setDateMode($mode);

            // Calculate correction for Hijri calendar
            $correction = $this->arabic->dateCorrection($timestamp);

            // Format date in Arabic
            return $this->arabic->date($format, $timestamp, $correction);
        } catch (\Exception $e) {
            \Log::warning("AR-PHP date formatting failed: " . $e->getMessage());
            return date($format, $timestamp);
        }
    }

    /**
     * Convert Western numerals to Eastern Arabic numerals
     * Example: "123" → "١٢٣"
     *
     * @param string $text Text containing Western numerals
     * @return string Text with Eastern Arabic numerals
     */
    private function toEasternNumerals(string $text): string
    {
        // Map Western numerals to Eastern Arabic numerals
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        return str_replace($western, $eastern, $text);
    }

    /**
     * Format Gregorian date in Arabic with Western numerals
     * Example: "14 December 2024" → "14 ديسمبر 2024"
     *
     * Uses Western/Latin numerals (0-9) instead of Eastern Arabic numerals (٠-٩)
     * because Western numerals maintain correct LTR order in RTL text,
     * while Eastern Arabic numerals get reversed by the RTL processor.
     *
     * @param Carbon $date The date to format
     * @return string Formatted date with Western numerals and Gregorian month names in Arabic
     */
    private function formatGregorianArabicDate(Carbon $date): string
    {
        // Gregorian months in Arabic
        $arabicMonths = [
            1  => 'يناير',    // January
            2  => 'فبراير',   // February
            3  => 'مارس',     // March
            4  => 'أبريل',    // April
            5  => 'مايو',     // May
            6  => 'يونيو',    // June
            7  => 'يوليو',    // July
            8  => 'أغسطس',    // August
            9  => 'سبتمبر',   // September
            10 => 'أكتوبر',   // October
            11 => 'نوفمبر',   // November
            12 => 'ديسمبر',   // December
        ];

        $day = $date->day;
        $month = $arabicMonths[$date->month];
        $year = $date->year;

        // Use Western numerals directly - they maintain LTR order in RTL context
        // Format: "14 ديسمبر 2024" (not "١٤ ديسمبر ٢٠٢٤")
        return "{$day} {$month} {$year}";
    }

    private function asDate(?string $val): Carbon
    {
        try { return $val ? Carbon::parse($val) : now(); }
        catch (\Throwable $e) { return now(); }
    }

    private function safeFilename(string $name, string $fallback = 'شهادة'): string
    {
        $name = trim($name) ?: $fallback;
        $name = preg_replace('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|\\r\\n]+/u', ' ', $name);
        $name = preg_replace('/\\s+/u', ' ', $name);
        $name = mb_substr($name, 0, 120, 'UTF-8');
        return $name ?: $fallback;
    }

    /**
     * Convert mm to pixels at given DPI (for measurements that don't need scaling, like font sizes)
     */
    private function mmToPx(float $mm): int
    {
        return (int)round(($mm / 25.4) * self::DPI);
    }

    /**
     * Convert mm to pixels with X scaling applied (for horizontal positions/widths)
     */
    private function mmToPxX(float $mm): int
    {
        return (int)round((($mm / 25.4) * self::DPI) * $this->scaleX);
    }

    /**
     * Convert mm to pixels with Y scaling applied (for vertical positions/heights)
     */
    private function mmToPxY(float $mm): int
    {
        return (int)round((($mm / 25.4) * self::DPI) * $this->scaleY);
    }

    /**
     * Convert mm to pixels with uniform scaling (average of X and Y for font sizes)
     * This prevents text from being stretched/squished when aspect ratio differs
     */
    private function mmToPxScaled(float $mm): int
    {
        $avgScale = ($this->scaleX + $this->scaleY) / 2;
        return (int)round((($mm / 25.4) * self::DPI) * $avgScale);
    }

    /**
     * Compute left position in mm (handles right-based positioning)
     */
    private function computeLeft(array $item, float $fallbackLeft): float
    {
        $left  = array_key_exists('left',  $item) ? (float)$item['left']  : null;
        $right = array_key_exists('right', $item) ? (float)$item['right'] : null;
        $width = array_key_exists('width', $item) ? (float)$item['width'] : null;

        if ($left !== null)  return $left;
        if ($right !== null) {
            $w = $width ?? 60.0;
            return self::PAGE_W - ($right + $w);
        }
        return $fallbackLeft;
    }

    /**
     * Get color from style array
     */
    private function colorOf(array $style, string $key, string $fallback): string
    {
        $c = $style['colors'][$key] ?? null;
        return is_string($c) && $c !== '' ? $c : $fallback;
    }

    /**
     * Get font for specific field
     */
    private function fontFor(array $style, string $key, string $fallback = 'Arial'): string
    {
        if (!empty($style['font_per'][$key]) && is_string($style['font_per'][$key])) {
            return $style['font_per'][$key];
        }
        $isEn = str_starts_with($key, 'en_');
        if ($isEn) {
            return !empty($style['font']['en']) ? (string)$style['font']['en'] : 'Arial';
        }
        return !empty($style['font']['ar']) ? (string)$style['font']['ar'] : $fallback;
    }

    /**
     * Get font weight
     */
    private function weightFor(array $style, string $key, int $fallback = 400): int
    {
        $w = $style['weight_per'][$key] ?? null;
        if (is_string($w)) {
            $w = strtolower($w);
            if ($w === 'bold')   return 700;
            if ($w === 'normal') return 400;
            if (ctype_digit($w)) return (int) $w;
        } elseif (is_int($w)) {
            return $w;
        }
        return $fallback;
    }

    /**
     * Get font size in mm
     * Checks style['size_per'] first for advanced options override, then falls back to positions
     */
    private function sizeFor(array $style, array $pos, string $key, float $fallbackMm): float
    {
        // First check if there's a size override in advanced options
        $sizeOverride = $style['size_per'][$key] ?? null;
        if ($sizeOverride !== null && is_numeric($sizeOverride)) {
            $mm = (float)$sizeOverride;
        } else {
            // Fall back to position-based size
            $raw = $pos[$key]['font'] ?? $fallbackMm;
            $mm  = is_numeric($raw) ? (float)$raw : $fallbackMm;
        }

        // Clamp to reasonable bounds
        if ($mm < 3.0)  $mm = 3.0;
        if ($mm > 60.0) $mm = 60.0;
        return $mm;
    }

    /**
     * Convert hex color to RGB array
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Get system font path for Arabic/English fonts
     * Works on both Windows and Linux
     * Supports both TTF and OTF fonts
     */
    /**
     * Get font file path, selecting appropriate variant based on weight
     */
    private function getFontPath(string $fontName, int $weight = 400): string
    {
        // If weight is bold (>=700), try to get bold variant first
        if ($weight >= 700) {
            $boldFontName = $this->getBoldFontName($fontName);
            $boldPath = $this->findFontFile($boldFontName);
            if ($boldPath) {
                return $boldPath;
            }
        } elseif ($weight <= 300) {
            // Try light variant
            $lightFontName = $this->getLightFontName($fontName);
            $lightPath = $this->findFontFile($lightFontName);
            if ($lightPath) {
                return $lightPath;
            }
        }

        // Fall back to regular weight
        return $this->findFontFile($fontName) ?? $this->getFallbackFont();
    }

    /**
     * Get bold variant name for a font
     */
    private function getBoldFontName(string $fontName): string
    {
        // Already includes "Bold"
        if (stripos($fontName, 'bold') !== false) {
            return $fontName;
        }

        // Special cases
        $boldVariants = [
            'Amiri' => 'Amiri Bold',
            'Lateef' => 'Lateef Bold',
            'DejaVu Sans' => 'DejaVu Sans Bold',
            'Arial' => 'Arial Bold',
            'IBMPlexSansArabic' => 'IBMPlexSansArabic-Bold',
            'Cairo' => 'Cairo-Bold',
            'Tajawal' => 'Tajawal-Bold',
        ];

        return $boldVariants[$fontName] ?? $fontName . ' Bold';
    }

    /**
     * Get light variant name for a font
     */
    private function getLightFontName(string $fontName): string
    {
        if (stripos($fontName, 'light') !== false) {
            return $fontName;
        }

        $lightVariants = [
            'IBMPlexSansArabic' => 'IBMPlexSansArabic-Light',
            'Cairo' => 'Cairo-Light',
            'Tajawal' => 'Tajawal-Light',
        ];

        return $lightVariants[$fontName] ?? $fontName . ' Light';
    }

    /**
     * Find font file path by name
     */
    private function findFontFile(string $fontName): ?string
    {
        // Priority 1: Check public/fonts directory first (custom fonts)
        $publicFonts = [
            // Arabic fonts
            'Amiri' => public_path('fonts/Amiri-Regular.ttf'),
            'Lateef' => public_path('fonts/Lateef-Regular.ttf'),
            'Cairo' => public_path('fonts/Cairo-Regular.ttf'),
            'Tajawal' => public_path('fonts/Tajawal-Regular.ttf'),
            'IBMPlexSansArabic' => public_path('fonts/IBMPlexSansArabic-Regular.ttf'),
            'NotoSansArabic' => public_path('fonts/static/NotoSansArabic-Regular.ttf'),
            'NotoSansArabic-VariableFont_wdth,wght' => public_path('fonts/NotoSansArabic-VariableFont_wdth,wght.ttf'),
            'ReemKufi-VariableFont_wght' => public_path('fonts/ReemKufi-VariableFont_wght.ttf'), // If exists
            'Tasees' => public_path('fonts/Tasees-Bold.ttf'),

            // Variations
            'Amiri Bold' => public_path('fonts/Amiri-Bold.ttf'),
            'Lateef Bold' => public_path('fonts/Lateef-Bold.ttf'),
            'IBMPlexSansArabic-Bold' => public_path('fonts/IBMPlexSansArabic-Bold.ttf'),
            'IBMPlexSansArabic-Medium' => public_path('fonts/IBMPlexSansArabic-Medium.ttf'),
            'IBMPlexSansArabic-Light' => public_path('fonts/IBMPlexSansArabic-Light.ttf'),
        ];

        if (isset($publicFonts[$fontName]) && file_exists($publicFonts[$fontName])) {
            return $publicFonts[$fontName];
        }

        // Try to find font by partial name match in public/fonts
        $fontsDir = public_path('fonts');
        if (is_dir($fontsDir)) {
            // Search for TTF files
            $pattern = $fontsDir . '/' . $fontName . '*.ttf';
            $matches = glob($pattern);
            if (!empty($matches) && file_exists($matches[0])) {
                return $matches[0];
            }

            // Search for OTF files
            $pattern = $fontsDir . '/' . $fontName . '*.otf';
            $matches = glob($pattern);
            if (!empty($matches) && file_exists($matches[0])) {
                return $matches[0];
            }

            // Search in subdirectories
            $pattern = $fontsDir . '/**/' . $fontName . '*.{ttf,otf}';
            $matches = glob($pattern, GLOB_BRACE);
            if (!empty($matches) && file_exists($matches[0])) {
                return $matches[0];
            }
        }

        // Priority 2: Try Windows system fonts
        $windowsFonts = [
            'Arial' => 'C:/Windows/Fonts/arial.ttf',
            'DejaVu Sans' => 'C:/Windows/Fonts/arial.ttf',
            'DejaVu Sans Bold' => 'C:/Windows/Fonts/arialbd.ttf',
            'Times New Roman' => 'C:/Windows/Fonts/times.ttf',
        ];

        if (isset($windowsFonts[$fontName]) && file_exists($windowsFonts[$fontName])) {
            return $windowsFonts[$fontName];
        }

        // Priority 3: Try Linux system fonts
        $linuxFonts = [
            'Arial' => '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            'DejaVu Sans' => '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            'DejaVu Sans Bold' => '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        ];

        if (isset($linuxFonts[$fontName]) && file_exists($linuxFonts[$fontName])) {
            return $linuxFonts[$fontName];
        }

        // Priority 4: Fallback to any available system font
        $fallbackPaths = [
            // Windows fallbacks
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/times.ttf',
            // Linux fallbacks
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            // Public fonts fallback (any available)
            public_path('fonts/IBMPlexSansArabic-Regular.ttf'),
            public_path('fonts/Amiri-Regular.ttf'),
            public_path('fonts/Lateef-Regular.ttf'),
            public_path('fonts/static/NotoSansArabic-Regular.ttf'),
        ];

        foreach ($fallbackPaths as $path) {
            if (file_exists($path)) {
                \Log::warning("Font '{$fontName}' not found, using fallback: {$path}");
                return $path;
            }
        }

        // Not found
        return null;
    }

    /**
     * Get fallback font path (throws exception if no fonts available)
     */
    private function getFallbackFont(): string
    {
        $fallbackPaths = [
            // Windows fallbacks
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/times.ttf',
            // Linux fallbacks
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            // Public fonts fallback (any available)
            public_path('fonts/IBMPlexSansArabic-Regular.ttf'),
            public_path('fonts/Amiri-Regular.ttf'),
            public_path('fonts/Lateef-Regular.ttf'),
            public_path('fonts/static/NotoSansArabic-Regular.ttf'),
        ];

        foreach ($fallbackPaths as $path) {
            if (file_exists($path)) {
                \Log::warning("Using fallback font: {$path}");
                return $path;
            }
        }

        // Priority 5: Critical fallback - throw exception with helpful message
        $message = "No font files found. Please ensure font files are available in:\n";
        $message .= "- Windows: C:/Windows/Fonts/arial.ttf\n";
        $message .= "- Linux: /usr/share/fonts/truetype/dejavu/DejaVuSans.ttf\n";
        $message .= "- Custom: " . public_path('fonts/') . "\n";

        throw new \RuntimeException($message);
    }

    /**
     * Draw text on image with proper alignment, font, and word wrapping
     * Handles Arabic text with proper RTL rendering using AR-PHP
     * Supports text wrapping when text exceeds the box width (like MS Word text boxes)
     */
    private function drawText($image, string $text, float $topMm, float $leftMm, float $widthMm, float $fontSizeMm, string $color, string $font, string $align = 'right', int $weight = 400): void
    {
        if (trim($text) === '') return;

        // Use scaled coordinates based on actual image size
        $topPx = $this->mmToPxY($topMm);
        $leftPx = $this->mmToPxX($leftMm);
        $widthPx = $this->mmToPxX($widthMm);
        $fontSize = $this->mmToPxScaled($fontSizeMm * 0.35); // Use scaled font size

        try {
            // Pass weight to getFontPath to select appropriate font variant (bold/regular/light)
            $fontPath = $this->getFontPath($font, $weight);
        } catch (\RuntimeException $e) {
            \Log::error("Font loading failed for '{$font}' with weight {$weight}: " . $e->getMessage());
            throw new \RuntimeException("Failed to load font '{$font}'. Please ensure font files are installed. " . $e->getMessage());
        }

        // Validate font file exists
        if (!file_exists($fontPath)) {
            throw new \RuntimeException("Font file not found: {$fontPath}");
        }

        // CRITICAL: Process Arabic text for proper RTL display using AR-PHP
        // This converts logical (RTL) order to visual (LTR) order with proper glyph joining
        // Without this, Arabic text will appear disconnected and reversed
        // IMPORTANT: Only process text that actually contains Arabic characters
        // Pure numbers/English text should not be reversed, even if right-aligned
        $hasArabic = preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text);

        $rgb = $this->hexToRgb($color);
        $textColor = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);

        // Word wrap text to fit within width
        $lines = $this->wrapText($text, $fontPath, $fontSize, $widthPx, $hasArabic);

        // Calculate line height (1.4x font size for comfortable reading)
        $lineHeight = (int)($fontSize * 1.4);

        // Draw each line
        $currentY = $topPx + $fontSize;
        foreach ($lines as $line) {
            if (trim($line) === '') continue;

            // Process Arabic text for proper display
            $processedLine = ($hasArabic && ($align === 'right' || $align === 'rtl'))
                ? $this->arabicGlyphs($line)
                : $line;

            // Calculate text bounding box for this line
            $bbox = @imagettfbbox($fontSize, 0, $fontPath, $processedLine);
            if ($bbox === false) {
                continue; // Skip this line if bbox fails
            }
            $textWidth = abs($bbox[4] - $bbox[0]);

            // Calculate X position based on alignment
            if ($align === 'right' || $align === 'rtl') {
                $x = $leftPx + $widthPx - $textWidth;
            } elseif ($align === 'center') {
                $x = $leftPx + ($widthPx - $textWidth) / 2;
            } else { // left
                $x = $leftPx;
            }

            // Draw the text line
            @imagettftext($image, $fontSize, 0, (int)$x, (int)$currentY, $textColor, $fontPath, $processedLine);

            // Move to next line
            $currentY += $lineHeight;
        }
    }

    /**
     * Word-wrap text to fit within a specified width (like MS Word text boxes)
     * Handles both English (space-separated) and Arabic text
     *
     * @param string $text Original text
     * @param string $fontPath Path to font file
     * @param int $fontSize Font size in pixels
     * @param int $maxWidth Maximum width in pixels
     * @param bool $isArabic Whether text contains Arabic
     * @return array Array of wrapped lines
     */
    private function wrapText(string $text, string $fontPath, int $fontSize, int $maxWidth, bool $isArabic): array
    {
        // If text fits on one line, return as-is
        $bbox = @imagettfbbox($fontSize, 0, $fontPath, $text);
        if ($bbox !== false) {
            $textWidth = abs($bbox[4] - $bbox[0]);
            if ($textWidth <= $maxWidth) {
                return [$text];
            }
        }

        // Split into words
        $words = preg_split('/\s+/u', $text);
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            // Test if adding this word would exceed width
            $testLine = $currentLine === '' ? $word : $currentLine . ' ' . $word;
            $bbox = @imagettfbbox($fontSize, 0, $fontPath, $testLine);

            if ($bbox === false) {
                // If bbox fails, just add the word
                $currentLine = $testLine;
                continue;
            }

            $testWidth = abs($bbox[4] - $bbox[0]);

            if ($testWidth <= $maxWidth) {
                // Word fits, add to current line
                $currentLine = $testLine;
            } else {
                // Word doesn't fit, start new line
                if ($currentLine !== '') {
                    $lines[] = $currentLine;
                }
                $currentLine = $word;
            }
        }

        // Add last line
        if ($currentLine !== '') {
            $lines[] = $currentLine;
        }

        return $lines;
    }

    /**
     * Draw photo on certificate with scaled coordinates
     * Uses object-fit: contain behavior - scales the entire image to fit within the box
     * without cropping, maintaining aspect ratio (may result in letterboxing)
     */
    private function drawPhoto($image, array $pos, string $photoAbs): void
    {
        if (!is_file($photoAbs)) return;

        $top = (float)($pos['top'] ?? 35);
        $left = (float)($pos['left'] ?? 30);
        $w = max(5.0, (float)($pos['width'] ?? 30));
        $h = max(5.0, (float)($pos['height'] ?? 30));

        // Use scaled coordinates based on actual image size
        $topPx = $this->mmToPxY($top);
        $leftPx = $this->mmToPxX($left);
        $widthPx = $this->mmToPxX($w);
        $heightPx = $this->mmToPxY($h);

        // Load photo based on extension
        $ext = strtolower(pathinfo($photoAbs, PATHINFO_EXTENSION));
        $photo = match($ext) {
            'png' => imagecreatefrompng($photoAbs),
            'jpg', 'jpeg' => imagecreatefromjpeg($photoAbs),
            'gif' => imagecreatefromgif($photoAbs),
            'webp' => imagecreatefromwebp($photoAbs),
            default => null,
        };

        if (!$photo) return;

        // Get source dimensions
        $srcWidth = imagesx($photo);
        $srcHeight = imagesy($photo);
        $srcRatio = $srcWidth / $srcHeight;
        $destRatio = $widthPx / $heightPx;

        // SCALE (object-fit: contain) - fit entire image within box, maintaining aspect ratio
        // Calculate destination dimensions that fit within the box
        if ($srcRatio > $destRatio) {
            // Source is wider - fit to width, center vertically
            $scaledWidth = $widthPx;
            $scaledHeight = (int)($widthPx / $srcRatio);
            $destX = $leftPx;
            $destY = $topPx + (int)(($heightPx - $scaledHeight) / 2);
        } else {
            // Source is taller - fit to height, center horizontally
            $scaledHeight = $heightPx;
            $scaledWidth = (int)($heightPx * $srcRatio);
            $destX = $leftPx + (int)(($widthPx - $scaledWidth) / 2);
            $destY = $topPx;
        }

        // Copy the entire source image, scaled to fit within the destination box
        imagecopyresampled(
            $image, $photo,
            $destX, $destY,           // Destination position (centered within box)
            0, 0,                      // Source position (entire image)
            $scaledWidth, $scaledHeight, // Destination size (scaled to fit)
            $srcWidth, $srcHeight      // Source size (entire image)
        );

        imagedestroy($photo);
    }

    /**
     * Render certificate on image resource
     */
    public function renderCertificate($image, array $data, array $pos, array $style, ?string $photoAbs = null): void
    {
        /* ---- FLAGS (safe defaults) ---- */
        $__flags = $style['_print_flags'] ?? [];
        if (!is_array($__flags)) { $__flags = []; }
        $__flags = array_merge([
            'ar_name'     => true,
            'en_name'     => true,
            'ar_track'    => true,
            'en_track'    => true,
            'ar_duration' => true,
            'en_duration' => true,
        ], $__flags);

        // Handle arabic_only / english_only master flags
        if (!empty($__flags['arabic_only'])) {
            $__flags['en_name'] = false;
            $__flags['en_track'] = false;
            $__flags['en_duration'] = false;
        }
        if (!empty($__flags['english_only'])) {
            $__flags['ar_name'] = false;
            $__flags['ar_track'] = false;
            $__flags['ar_duration'] = false;
        }

        // Photo (optional)
        if ($photoAbs) {
            $photoPos = $pos['photo'] ?? ['top'=>35,'left'=>30,'width'=>30,'height'=>30];
            $this->drawPhoto($image, $photoPos, $photoAbs);
        }

        // Pull positions
        $cd = $pos['cert_date'] ?? [];
        $ar = $pos['ar_name']   ?? [];
        $tr = $pos['ar_track']  ?? [];
        $af = $pos['ar_from']   ?? [];
        $en = $pos['en_name']   ?? [];
        $et = $pos['en_track']  ?? [];
        $ef = $pos['en_from']   ?? [];

        // Calculate positions and sizes
        $cdTop = (float)($cd['top'] ?? 27.0);   $cdW = (float)($cd['width'] ?? 78.0);  $cdF = $this->sizeFor($style, $pos, 'cert_date', 5.0);   $cdL = $this->computeLeft($cd, 200.0);
        $arTop = (float)($ar['top'] ?? 78.0);   $arW = (float)($ar['width'] ?? 90.0);  $arF = $this->sizeFor($style, $pos, 'ar_name',   7.0);   $arL = $this->computeLeft($ar, 180.0);
        $trTop = (float)($tr['top'] ?? 95.0);   $trW = (float)($tr['width'] ?? 90.0);  $trF = $this->sizeFor($style, $pos, 'ar_track',  6.0);   $trL = $this->computeLeft($tr, 180.0);
        $afTop = (float)($af['top'] ?? 112.0);  $afW = (float)($af['width'] ?? 45.0);  $afF = $this->sizeFor($style, $pos, 'ar_from',   6.0);   $afL = $this->computeLeft($af, 225.0);
        $enTop = (float)($en['top'] ?? 78.0);   $enW = (float)($en['width'] ?? 120.0); $enF = $this->sizeFor($style, $pos, 'en_name',   6.0);   $enL = $this->computeLeft($en, 30.0);
        $etTop = (float)($et['top'] ?? 95.0);   $etW = (float)($et['width'] ?? 120.0); $etF = $this->sizeFor($style, $pos, 'en_track',  5.5);   $etL = $this->computeLeft($et, 30.0);
        $efTop = (float)($ef['top'] ?? 112.0);  $efW = (float)($ef['width'] ?? 60.0);  $efF = $this->sizeFor($style, $pos, 'en_from',   5.5);   $efL = $this->computeLeft($ef, 30.0);

        // Colors
        $color_cd = $this->colorOf($style, 'cert_date', '#0f172a');
        $color_ar = $this->colorOf($style, 'ar_name',   '#334155');
        $color_tr = $this->colorOf($style, 'ar_track',  '#0891b2');
        $color_af = $this->colorOf($style, 'ar_from',   '#0891b2');
        $color_en = $this->colorOf($style, 'en_name',   '#0f172a');
        $color_et = $this->colorOf($style, 'en_track',  '#0891b2');
        $color_ef = $this->colorOf($style, 'en_from',   '#0f172a');

        // Fonts
        $font_cd = $this->fontFor($style, 'cert_date', 'Arial');
        $font_ar_name = $this->fontFor($style, 'ar_name', 'Amiri');
        $font_ar_track= $this->fontFor($style, 'ar_track', 'Amiri');
        $font_ar_from = $this->fontFor($style, 'ar_from', 'Amiri');
        $font_en_name = $this->fontFor($style, 'en_name', 'DejaVu Sans');
        $font_en_track= $this->fontFor($style, 'en_track', 'DejaVu Sans');
        $font_en_from = $this->fontFor($style, 'en_from', 'DejaVu Sans');

        // Weights
        $weight_cd = $this->weightFor($style, 'cert_date', 400);
        $weight_ar_name = $this->weightFor($style, 'ar_name', 700);
        $weight_ar_track = $this->weightFor($style, 'ar_track', 700);
        $weight_ar_from = $this->weightFor($style, 'ar_from', 700);
        $weight_en_name = $this->weightFor($style, 'en_name', 700);
        $weight_en_track = $this->weightFor($style, 'en_track', 700);
        $weight_en_from = $this->weightFor($style, 'en_from', 400);

        // Data
        $nameAr   = (string)($data['name_ar']   ?? '');
        $nameEn   = (string)($data['name_en']   ?? '');
        $trackAr  = (string)($data['track_ar']  ?? '');
        $trackEn  = (string)($data['track_en']  ?? '');

        // Certificate date - format as j/n/Y (e.g., 18/8/2024 - no leading zeros)
        $cdDate = !empty($data['certificate_date'])
            ? $this->asDate($data['certificate_date'])
            : Carbon::today();
        $cdText = $cdDate ? $cdDate->format('j/n/Y') : '';

        // Duration text
        $durationMode = $style['duration_mode'] ?? 'range';
        $fromRaw      = $style['duration_from'] ?? null;
        $toRaw        = $style['duration_to']   ?? null;
        $today        = Carbon::today();

        if ($durationMode === 'end') {
            $fromDate = null;
            $toDate   = $toRaw ? $this->asDate($toRaw) : $today;
        } else {
            $fromDate = $fromRaw ? $this->asDate($fromRaw) : $today;
            $toDate   = $toRaw   ? $this->asDate($toRaw)   : $today;
        }

        // Format for English: "September 14, 2024"
        $fmtEn = 'F j, Y';

        // Format for Arabic: "١٤ ديسمبر ٢٠٢٤" (Gregorian date with Arabic numerals)
        $durTextAr = null;
        $durTextEn = null;
        if ($durationMode === 'end') {
            if ($toDate) {
                $durTextEn = 'until ' . $toDate->format($fmtEn);
                $durTextAr = 'حتى ' . $this->formatGregorianArabicDate($toDate);
            }
        } else {
            if ($fromDate && $toDate) {
                $durTextEn = 'from ' . $fromDate->format($fmtEn) . ' to ' . $toDate->format($fmtEn);
                $fromAr = $this->formatGregorianArabicDate($fromDate);
                $toAr = $this->formatGregorianArabicDate($toDate);
                $durTextAr = 'خلال الفترة من ' . $fromAr . ' إلى ' . $toAr;
            }
        }

        // Draw all text fields
        if ($cdText !== '') {
            $this->drawText($image, $cdText, $cdTop, $cdL, $cdW, $cdF, $color_cd, $font_cd, 'right', $weight_cd);
        }

        if (($__flags['ar_name'] ?? true) && $nameAr !== '') {
            $this->drawText($image, $nameAr, $arTop, $arL, $arW, $arF, $color_ar, $font_ar_name, 'right', $weight_ar_name);
        }

        if (($__flags['ar_track'] ?? true) && $trackAr !== '') {
            $this->drawText($image, $trackAr, $trTop, $trL, $trW, $trF, $color_tr, $font_ar_track, 'right', $weight_ar_track);
        }

        if (($__flags['ar_duration'] ?? true) && $durTextAr) {
            $this->drawText($image, $durTextAr, $afTop, $afL, $afW, $afF, $color_af, $font_ar_from, 'right', $weight_ar_from);
        }

        if (($__flags['en_name'] ?? true) && $nameEn !== '') {
            $this->drawText($image, $nameEn, $enTop, $enL, $enW, $enF, $color_en, $font_en_name, 'left', $weight_en_name);
        }

        if (($__flags['en_track'] ?? true) && $trackEn !== '') {
            $this->drawText($image, $trackEn, $etTop, $etL, $etW, $etF, $color_et, $font_en_track, 'left', $weight_en_track);
        }

        if (($__flags['en_duration'] ?? true) && $durTextEn) {
            $this->drawText($image, $durTextEn, $efTop, $efL, $efW, $efF, $color_ef, $font_en_from, 'left', $weight_en_from);
        }
    }

    /**
     * Generate a single certificate image
     * Filename format: name_track_name.png (e.g., Ahmed_Mohammed_Full_Stack_Development.png)
     */
    public function generateSingle(
        string $nameAr,
        string $nameEn,
        string $trackAr,
        string $trackEn,
        ?string $certificateDate,
        ?string $durationFrom,
        array $positions,
        string $backgroundAbs,
        array $style,
        ?string $photoAbs = null
    ): string
    {
        $folderSlug = Str::slug($nameEn ?: $nameAr, '_');
        // Include track name in filename: name_track.png
        $namePart  = $this->safeFilename($nameAr ?: $nameEn, 'شهادة');
        $trackPart = $this->safeFilename($trackAr ?: $trackEn, 'track');
        $fileBase  = $namePart . '_' . $trackPart;
        $filename  = $fileBase . '.png';

        $dir      = "{$this->root}/{$folderSlug}";
        $relative = "{$dir}/{$filename}";

        Storage::disk('local')->makeDirectory($dir);

        // Create image from background
        $ext = strtolower(pathinfo($backgroundAbs, PATHINFO_EXTENSION));
        $image = match($ext) {
            'png' => imagecreatefrompng($backgroundAbs),
            'jpg', 'jpeg' => imagecreatefromjpeg($backgroundAbs),
            'gif' => imagecreatefromgif($backgroundAbs),
            'webp' => imagecreatefromwebp($backgroundAbs),
            default => imagecreatetruecolor($this->mmToPx(self::PAGE_W), $this->mmToPx(self::PAGE_H)),
        };

        if (!$image) {
            throw new \RuntimeException('Failed to create image from background');
        }

        // Calculate scale factors based on actual image dimensions
        // This ensures text positions correctly regardless of template size
        $actualWidth = imagesx($image);
        $actualHeight = imagesy($image);
        $this->calculateScaleFactors($actualWidth, $actualHeight);

        // Enable alpha blending for proper text rendering (critical for JPG backgrounds)
        imagealphablending($image, true);
        imagesavealpha($image, true);

        // Render certificate on image
        $this->renderCertificate($image, [
            'name_ar'          => $nameAr,
            'name_en'          => $nameEn,
            'track_ar'         => $trackAr,
            'track_en'         => $trackEn,
            'certificate_date' => $certificateDate,
            'duration_from'    => $durationFrom,
        ], $positions, $style, $photoAbs);

        // Reset scale factors for next image
        $this->resetScaleFactors();

        // Save image
        $absPath = Storage::disk('local')->path($relative);
        // Use compression level 5 for good balance between quality and speed
        // Level 9 (max) is very slow, level 5 is ~3x faster with minimal quality difference
        imagepng($image, $absPath, 5);
        imagedestroy($image);

        return $relative;
    }

    /**
     * Generate a preview image
     */
    public function generatePreview(
        string $nameAr,
        string $nameEn,
        string $trackAr,
        string $trackEn,
        ?string $certificateDate,
        ?string $durationFrom,
        array $positions,
        string $backgroundAbs,
        array $style,
        ?string $photoAbs = null
    ): string
    {
        $this->cleanupOldPreviews();

        $uuid = Str::uuid()->toString();
        $relative = "{$this->previewRoot}/{$uuid}.png";
        Storage::disk('local')->makeDirectory($this->previewRoot);

        // Create image from background
        $ext = strtolower(pathinfo($backgroundAbs, PATHINFO_EXTENSION));
        $image = match($ext) {
            'png' => imagecreatefrompng($backgroundAbs),
            'jpg', 'jpeg' => imagecreatefromjpeg($backgroundAbs),
            'gif' => imagecreatefromgif($backgroundAbs),
            'webp' => imagecreatefromwebp($backgroundAbs),
            default => imagecreatetruecolor($this->mmToPx(self::PAGE_W), $this->mmToPx(self::PAGE_H)),
        };

        if (!$image) {
            throw new \RuntimeException('Failed to create image from background');
        }

        // Calculate scale factors based on actual image dimensions
        // This ensures text positions correctly regardless of template size
        $actualWidth = imagesx($image);
        $actualHeight = imagesy($image);
        $this->calculateScaleFactors($actualWidth, $actualHeight);

        // Enable alpha blending for proper text rendering (critical for JPG backgrounds)
        imagealphablending($image, true);
        imagesavealpha($image, true);

        // Render certificate
        $this->renderCertificate($image, [
            'name_ar'          => $nameAr,
            'name_en'          => $nameEn,
            'track_ar'         => $trackAr,
            'track_en'         => $trackEn,
            'certificate_date' => $certificateDate,
            'duration_from'    => $durationFrom,
        ], $positions, $style, $photoAbs);

        // Reset scale factors for next image
        $this->resetScaleFactors();

        // Save image
        $absPath = Storage::disk('local')->path($relative);
        imagepng($image, $absPath, 6); // Good compression for preview
        imagedestroy($image);

        return $absPath;
    }

    /**
     * Cleanup old preview files
     */
    private function cleanupOldPreviews(): void
    {
        $disk = Storage::disk('local');
        if (!$disk->exists($this->previewRoot)) return;

        foreach ($disk->files($this->previewRoot) as $file) {
            $full = $disk->path($file);
            if (file_exists($full) && (time() - filemtime($full) > 3600)) {
                @unlink($full);
            }
        }
    }
}
