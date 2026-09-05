<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR codes as data URIs, for pages and for dompdf.
 *
 * Two flavours on purpose: SVG scales cleanly in a browser, but dompdf's SVG
 * renderer ignores the stylesheet chillerlan emits, so documents get a PNG
 * rendered through GD at a scale that stays crisp at 300 dpi.
 */
final class QrSvg
{
    public static function svgDataUri(string $text, int $scale = 5): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64'    => true,
            'eccLevel'        => EccLevel::M,
            'addQuietzone'    => true,
            'quietzoneSize'   => 2,
            'scale'           => $scale,
        ]);

        return (new QRCode($options))->render($text);
    }

    public static function pngDataUri(string $text, int $scale = 8): string
    {
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'outputBase64'    => true,
            'eccLevel'        => EccLevel::M,
            'addQuietzone'    => true,
            'quietzoneSize'   => 2,
            'scale'           => $scale,
        ]);

        return (new QRCode($options))->render($text);
    }
}
