<?php

namespace App\Services\Blog;

use RuntimeException;

/**
 * Branded 1200x630 hero / Open Graph card for a blog post, drawn with GD.
 *
 * Brand gradient (#0F4C81 -> deep navy), the OpesCare mark on a white disc,
 * the category label, the title wrapped in a bold sans, and the Health ID seal
 * bleeding off the right edge. Written to public/blog-images/{slug}.png.
 *
 * Font: GD needs a TTF and the self-hosted Inter ships as woff2 only, so the
 * card uses DejaVu Sans Bold, which is already in the repo (vendor/ is tracked)
 * via dompdf. Override with blog.image_font (BLOG_IMAGE_FONT) to point at an Inter TTF later.
 */
class BlogImageGenerator
{
    public const WIDTH = 1200;
    public const HEIGHT = 630;

    public function generate(array $post, bool $force = false): string
    {
        $relative = (string) $post['image'];
        $target = public_path($relative);

        if (! $force && is_file($target)) {
            return $target;
        }

        if (! function_exists('imagettftext')) {
            throw new RuntimeException('GD with FreeType is required to draw blog images');
        }

        $dir = dirname($target);
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new RuntimeException("cannot create {$dir}");
        }

        $w = self::WIDTH;
        $h = self::HEIGHT;
        $img = imagecreatetruecolor($w, $h);
        imagealphablending($img, true);
        imagesavealpha($img, true);

        // Diagonal brand gradient.
        $from = [0x0F, 0x4C, 0x81];
        $to = [0x08, 0x2A, 0x4A];
        for ($i = 0; $i < $w + $h; $i++) {
            $t = $i / ($w + $h);
            $c = imagecolorallocate($img,
                (int) round($from[0] + ($to[0] - $from[0]) * $t),
                (int) round($from[1] + ($to[1] - $from[1]) * $t),
                (int) round($from[2] + ($to[2] - $from[2]) * $t));
            imageline($img, $i, 0, $i - $h, $h, $c);
            imageline($img, $i + 1, 0, $i + 1 - $h, $h, $c);
        }

        // Soft light circles for depth.
        $glow = imagecolorallocatealpha($img, 255, 255, 255, 120);
        imagefilledellipse($img, 1050, 120, 520, 520, $glow);
        imagefilledellipse($img, 980, 560, 380, 380, $glow);

        // The seal, bleeding off the right edge.
        $seal = public_path('images/health-id/seal.png');
        if (is_file($seal) && ($s = @imagecreatefrompng($seal))) {
            $size = 380;
            imagecopyresampled($img, $s, $w - $size + 70, ($h - $size) / 2, 0, 0, $size, $size, imagesx($s), imagesy($s));
            imagedestroy($s);
        }

        // Logo on a white disc, top-left.
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefilledellipse($img, 110, 110, 104, 104, $white);
        $logo = public_path('brand/opescare-logo-512.png');
        if (is_file($logo) && ($l = @imagecreatefrompng($logo))) {
            imagecopyresampled($img, $l, 110 - 44, 110 - 44, 0, 0, 88, 88, imagesx($l), imagesy($l));
            imagedestroy($l);
        }

        $font = $this->font();
        $muted = imagecolorallocate($img, 0xCF, 0xE0, 0xF0);
        imagettftext($img, 28, 0, 182, 122, $white, $font, 'OpesCare');

        // Category pill.
        $category = mb_strtoupper(trim((string) ($post['category'] ?? '')));
        $y = 250;
        if ($category !== '') {
            $box = imagettfbbox(15, 0, $font, $category);
            $tw = abs($box[2] - $box[0]);
            $pill = imagecolorallocatealpha($img, 255, 255, 255, 95);
            $this->roundedRect($img, 64, 196, 64 + $tw + 36, 236, 20, $pill);
            imagettftext($img, 15, 0, 82, 223, $white, $font, $category);
            $y = 300;
        }

        // Title: shrink until it fits in at most four lines within 760px.
        $title = trim((string) $post['title']);
        $maxWidth = 760;
        foreach ([50, 46, 42, 38, 34, 30] as $size) {
            $lines = $this->wrap($title, $font, $size, $maxWidth);
            if (count($lines) <= 4) {
                break;
            }
        }
        $lines = array_slice($lines, 0, 4);
        $lineHeight = (int) round($size * 1.32);
        foreach ($lines as $i => $line) {
            imagettftext($img, $size, 0, 64, $y + $i * $lineHeight, $white, $font, $line);
        }

        imagettftext($img, 17, 0, 64, $h - 52, $muted, $font, 'opescare.com/blog');

        // Bottom accent bar.
        $accent = imagecolorallocate($img, 0x38, 0xBD, 0xF8);
        imagefilledrectangle($img, 0, $h - 8, $w, $h, $accent);

        $tmp = $target . '.tmp';
        imagepng($img, $tmp, 8);
        // A WebP twin (~80% smaller) served first via <picture>; the PNG stays
        // as the fallback and the og:image (social fetchers want PNG/JPEG).
        if (function_exists('imagewebp')) {
            $webp = preg_replace('/\.png$/', '.webp', $target);
            if ($webp !== $target && @imagewebp($img, $webp . '.tmp', 82)) {
                rename($webp . '.tmp', $webp);
            }
        }
        imagedestroy($img);
        rename($tmp, $target);

        return $target;
    }

    private function font(): string
    {
        $candidates = array_filter([
            config('blog.image_font'),
            public_path('fonts/inter/Inter-Bold.ttf'),
            base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf'),
        ]);
        foreach ($candidates as $f) {
            if (is_file($f)) {
                return $f;
            }
        }

        throw new RuntimeException('no TTF font available for blog images');
    }

    /** @return list<string> */
    private function wrap(string $text, string $font, int $size, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', $text) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $try = $current === '' ? $word : $current . ' ' . $word;
            $box = imagettfbbox($size, 0, $font, $try);
            if (abs($box[2] - $box[0]) > $maxWidth && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $try;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    private function roundedRect($img, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x1 + $r - 1, $y2 - $r, $color);
        imagefilledrectangle($img, $x2 - $r + 1, $y1 + $r, $x2, $y2 - $r, $color);
        imagefilledarc($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, 180, 270, $color, IMG_ARC_PIE);
        imagefilledarc($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, 270, 360, $color, IMG_ARC_PIE);
        imagefilledarc($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, 90, 180, $color, IMG_ARC_PIE);
        imagefilledarc($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, 0, 90, $color, IMG_ARC_PIE);
    }
}
