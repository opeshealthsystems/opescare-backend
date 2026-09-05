<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Support\PatientPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * The patient's own photo — set from the app, shown on the Health ID card.
 *
 *   POST   /mobile/me/photo                 upload or replace (multipart `photo`)
 *   DELETE /mobile/me/photo                 remove
 *   GET    /mobile/patients/{patient}/photo serve — SIGNED, no bearer token
 *
 * Same discipline as the clinician photo (MobileDoctorProfileController):
 * the type is read out of the bytes, not the multipart header; the bytes must
 * decode as an image; dimensions are bounded; and what is STORED is never the
 * upload itself but a fresh 512×512 JPEG re-encoded by GD — which also throws
 * away EXIF, GPS and anything else a phone tucks into a picture. Files live on
 * the private `local` disk and are served only through a short-lived signed
 * URL, because an <img> tag cannot carry a bearer token.
 */
class MobilePatientPhotoController extends Controller
{
    /** Upload ceiling (kilobytes). */
    private const MAX_PHOTO_KB = 5120;

    /** No side longer than this on the upload; the stored file is 512 square. */
    private const MAX_PHOTO_EDGE = 6000;

    /** IMAGETYPE_* constants GD can decode here → the MIME we accept them as. */
    private const ALLOWED_IMAGE_TYPES = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_PNG  => 'image/png',
        IMAGETYPE_WEBP => 'image/webp',
    ];

    /**
     * POST /mobile/me/photo
     */
    public function upload(Request $request): JsonResponse
    {
        $patient = $this->patient($request);
        if (! $patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        // Check 1 — the type finfo reads out of the FILE, not the multipart
        // header. `mimetypes:` uses UploadedFile::getMimeType(), which guesses
        // from content; `mimes:` and getClientMimeType() do not.
        $request->validate([
            'photo' => ['required', 'file', 'max:' . self::MAX_PHOTO_KB, 'mimetypes:image/jpeg,image/png,image/webp'],
        ]);

        /** @var UploadedFile $file */
        $file = $request->file('photo');

        // Check 2 — the bytes must decode as an image at all.
        $info = @getimagesize($file->getRealPath());
        if ($info === false || ! isset($info[2], self::ALLOWED_IMAGE_TYPES[$info[2]])) {
            return response()->json([
                'error_code' => 'PHOTO_NOT_AN_IMAGE',
                'message'    => __('doctor_messaging.photo_not_an_image'),
            ], 422);
        }

        // Check 3 — sane dimensions.
        [$width, $height] = $info;
        if ($width < 1 || $height < 1 || $width > self::MAX_PHOTO_EDGE || $height > self::MAX_PHOTO_EDGE) {
            return response()->json([
                'error_code' => 'PHOTO_DIMENSIONS_REJECTED',
                'message'    => __('doctor_messaging.photo_dimensions', ['max' => self::MAX_PHOTO_EDGE]),
            ], 422);
        }

        // Re-encode: centre-crop to a square, scale to 512, write a plain JPEG.
        $jpeg = $this->squareJpeg((string) file_get_contents($file->getRealPath()), $width, $height);
        if ($jpeg === null) {
            return response()->json([
                'error_code' => 'PHOTO_NOT_AN_IMAGE',
                'message'    => __('doctor_messaging.photo_not_an_image'),
            ], 422);
        }

        $path     = PatientPhoto::DIRECTORY . '/' . $patient->id . '/' . Str::uuid() . '.jpg';
        $previous = $patient->photo_path;

        Storage::disk('local')->put($path, $jpeg);

        $patient->forceFill([
            'photo_path'       => $path,
            'photo_mime'       => 'image/jpeg',
            'photo_updated_at' => now(),
        ])->save();

        if ($previous && $previous !== $path && Storage::disk('local')->exists($previous)) {
            Storage::disk('local')->delete($previous);
        }

        return response()->json(['photo_url' => PatientPhoto::urlFor($patient)]);
    }

    /**
     * DELETE /mobile/me/photo
     */
    public function destroy(Request $request): JsonResponse
    {
        $patient = $this->patient($request);
        if (! $patient) {
            return response()->json(['message' => __('api.patient_not_found')], 404);
        }

        if ($patient->photo_path && Storage::disk('local')->exists($patient->photo_path)) {
            Storage::disk('local')->delete($patient->photo_path);
        }

        $patient->forceFill(['photo_path' => null, 'photo_mime' => null, 'photo_updated_at' => now()])->save();

        return response()->json(['photo_url' => null]);
    }

    /**
     * GET /mobile/patients/{patient}/photo — behind the `signed` middleware;
     * the signature IS the authorisation, and it expires.
     *
     * The Content-Type is the one the SERVER wrote at upload time, and
     * `nosniff` stops a browser second-guessing it. The response is marked
     * private so a shared proxy never keeps a copy of a patient's face.
     */
    public function show(string $patient): BinaryFileResponse|JsonResponse
    {
        if (! Str::isUuid($patient)) {
            return response()->json(['message' => __('api.not_found')], 404);
        }

        $row = Patient::query()->withoutGlobalScopes()->find($patient);

        if (! $row || ! $row->photo_path || ! Storage::disk('local')->exists($row->photo_path)) {
            return response()->json(['message' => __('api.not_found')], 404);
        }

        $response = response()->file(Storage::disk('local')->path($row->photo_path), [
            'Content-Type'           => $row->photo_mime ?: 'image/jpeg',
            'Content-Disposition'    => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setPrivate();
        $response->setMaxAge(300);

        return $response;
    }

    // ── Internals ────────────────────────────────────────────────────────────

    private function patient(Request $request): ?Patient
    {
        $patientId = $request->attributes->get('patient_id');

        return $patientId ? Patient::find($patientId) : null;
    }

    /**
     * Centre-crop to a square and scale to PatientPhoto::EDGE, as a JPEG with
     * no metadata. Returns null if GD cannot read the bytes.
     */
    private function squareJpeg(string $bytes, int $width, int $height): ?string
    {
        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return null;
        }

        $edge = min($width, $height);
        $srcX = (int) floor(($width - $edge) / 2);
        $srcY = (int) floor(($height - $edge) / 2);
        $out  = PatientPhoto::EDGE;

        $canvas = imagecreatetruecolor($out, $out);
        // Transparent PNG/WebP corners become white, not black.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, $srcX, $srcY, $out, $out, $edge, $edge);

        ob_start();
        imagejpeg($canvas, null, 88);
        $jpeg = (string) ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        return $jpeg !== '' ? $jpeg : null;
    }
}
