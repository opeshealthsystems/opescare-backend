<?php

namespace App\Services\Documents;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;

class QrCodeGenerationService
{
    /**
     * Generate a real, scannable inline SVG QR code that encodes the document verification URL.
     *
     * The returned SVG contains the verification URL both as scannable QR data (machine-readable)
     * and as a data-url attribute (machine/test readable). It is suitable for inline embedding
     * in HTML documents.
     */
    public function generateSvg(string $token): string
    {
        $url = route('document.verify', ['token' => $token], true);

        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'eccLevel'        => EccLevel::M,
            'addQuietzone'    => true,
            'quietzoneSize'   => 4,
            'cssClass'        => 'opescare-qr',
            'svgAddXmlHeader' => false,
            'outputBase64'    => false,
        ]);

        $svg = (new QRCode($options))->render($url);

        // Inject the verification URL as a data-url attribute on the <svg> element
        // so views and tests can extract it without decoding the QR matrix.
        $encodedUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $svg = str_replace('<svg ', '<svg data-url="' . $encodedUrl . '" ', $svg);

        return $svg;
    }
}
