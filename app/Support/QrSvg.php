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

    /**
     * The OpesCare-branded code: navy modules, high error correction and a
     * cleared centre for the seal, which the page lays over the code. The
     * code stays scannable because ECC level H tolerates the covered area.
     */
    public static function brandedSvg(string $text): string
    {
        $options = new QROptions([
            'outputInterface'  => QRMarkupSVG::class,
            'outputBase64'     => false,
            'eccLevel'         => EccLevel::H,
            'addQuietzone'     => false,
            'addLogoSpace'     => true,
            'logoSpaceWidth'   => 9,
            'drawLightModules' => false,
            'connectPaths'     => true,
            'svgAddXmlHeader'  => false,
            'cssClass'         => 'oc-qr',
        ]);

        $svg = (new QRCode($options))->render($text);

        // chillerlan emits a <style> block; colour the modules inline instead
        // so the markup carries no stylesheet (CSP: style-src needs no nonce).
        $svg = preg_replace('#<style>.*?</style>#s', '', $svg) ?? $svg;

        $svg = preg_replace('#(<path[^>]*?)\s+fill="[^"]*"#', '$1', $svg) ?? $svg;

        return preg_replace('#<path #', '<path fill="#0B2A4A" ', $svg) ?? $svg;
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
