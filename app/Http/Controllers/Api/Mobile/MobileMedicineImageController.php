<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * GET /api/mobile/medicines/{medicine}/image — a catalogue medicine's picture,
 * behind the `signed` middleware (minted by CatalogImageUrls::medicineImage()).
 * The signature is the authorisation, because an <Image> cannot send a bearer.
 *
 * `medicines.image_path` is a column with no writer today, so in practice this
 * 404s and the app shows the category icon. It exists so that the day a
 * catalogue image is stored, the app shows it with no client release.
 */
class MobileMedicineImageController extends Controller
{
    private const MIME_BY_EXT = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];

    public function show(string $medicine): HttpResponse
    {
        if (! Str::isUuid($medicine)) {
            return response()->json(['message' => __('api.not_found')], 404);
        }

        $row  = Medicine::query()->find($medicine);
        $disk = Storage::disk(config('filesystems.private_disk', 'local'));
        $mime = $row?->image_path ? (self::MIME_BY_EXT[strtolower(pathinfo($row->image_path, PATHINFO_EXTENSION))] ?? null) : null;

        if (! $row || ! $mime || ! $disk->exists($row->image_path)) {
            return response()->json(['message' => __('api.not_found')], 404);
        }

        $response = $disk->response($row->image_path, null, [
            'Content-Type'           => $mime,
            'Content-Disposition'    => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->setMaxAge(3600);

        return $response;
    }
}
