<?php

namespace App\Services\CareMap;

use App\Models\CareFacility;
use App\Modules\CareMap\Services\FacilityListingEditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * The facility's photo / logo on the settings page.
 *
 * ── Where it lives ───────────────────────────────────────────────────────
 * `care_facilities.logo_path` already existed in the schema (with
 * `cover_image_path`) and nothing wrote it; this service is now its only
 * writer. The file follows the SAME discipline as the patient and clinician
 * photos (MobilePatientPhotoController): the type is read out of the bytes,
 * not the multipart header; the bytes must decode; dimensions are bounded;
 * and what is STORED is never the upload itself but a fresh JPEG re-encoded
 * by GD (longest edge FIT within MAX_EDGE, aspect kept — a logo is not
 * centre-cropped like a face), which also discards EXIF/GPS. Files sit on
 * the private `local` disk (storage/app/private) and are served ONLY by a
 * short-lived signed URL (`facility.photo`, `signed` middleware), like the
 * patient photo. There is no public derivative: the public directory has no
 * image slot today, and when it gets one it asks urlFor() like this page.
 *
 * ── Replacement ──────────────────────────────────────────────────────────
 * A new photo replaces the path; the old file is deleted only AFTER the new
 * one is written and the row saved, so a failed write never leaves a facility
 * pointing at nothing.
 *
 * ── Audit ────────────────────────────────────────────────────────────────
 * One `facility_update_audits` row per upload (`field_changed = logo_path`,
 * `requires_review = false`): a picture does not change what the directory
 * says about where a facility is or what it is called.
 */
class FacilityPhotoService
{
    /** Where uploads live on the `local` disk. */
    public const DIRECTORY = 'facility-photos';

    /** Upload ceiling, kilobytes (2 MB). */
    public const MAX_KB = 2048;

    /** Each side of the upload must be at least this. */
    public const MIN_EDGE = 200;

    /** No upload side may exceed this (decoding a 20k×20k image exhausts memory). */
    public const MAX_UPLOAD_EDGE = 6000;

    /** The stored JPEG fits within this square. */
    public const MAX_EDGE = 1024;

    /** How long a link stays valid. */
    public const URL_TTL_MINUTES = 60;

    /** IMAGETYPE_* constants GD decodes here → the MIME we accept them as. */
    private const ALLOWED_IMAGE_TYPES = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_PNG  => 'image/png',
        IMAGETYPE_WEBP => 'image/webp',
    ];

    /** @return array<string,array<int,string>> */
    public static function rules(): array
    {
        // `mimetypes:` reads the type out of the file content (finfo);
        // `mimes:` would trust the extension. Dimensions are checked by
        // Laravel's `dimensions` rule AND again below from getimagesize().
        return [
            'photo' => [
                'required', 'file', 'max:' . self::MAX_KB,
                'mimetypes:image/jpeg,image/png,image/webp',
                'dimensions:min_width=' . self::MIN_EDGE . ',min_height=' . self::MIN_EDGE
                    . ',max_width=' . self::MAX_UPLOAD_EDGE . ',max_height=' . self::MAX_UPLOAD_EDGE,
            ],
        ];
    }

    /**
     * Store a validated upload for the listing. Returns false when GD could
     * not decode the bytes (the caller reports it as a validation error).
     */
    public function store(CareFacility $listing, UploadedFile $file, string $actorId): bool
    {
        $info = @getimagesize($file->getRealPath());
        if ($info === false || ! isset($info[2], self::ALLOWED_IMAGE_TYPES[$info[2]])) {
            return false;
        }

        [$width, $height] = $info;
        if ($width < self::MIN_EDGE || $height < self::MIN_EDGE
            || $width > self::MAX_UPLOAD_EDGE || $height > self::MAX_UPLOAD_EDGE) {
            return false;
        }

        $jpeg = $this->fitJpeg((string) file_get_contents($file->getRealPath()), $width, $height);
        if ($jpeg === null) {
            return false;
        }

        $path     = self::DIRECTORY . '/' . $listing->id . '/' . Str::uuid() . '.jpg';
        $previous = $listing->logo_path;

        Storage::disk(config('filesystems.private_disk', 'local'))->put($path, $jpeg);

        DB::transaction(function () use ($listing, $path, $previous, $actorId): void {
            $listing->forceFill(['logo_path' => $path, 'last_profile_update_at' => now()])->save();

            DB::table('facility_update_audits')->insert([
                'id'              => (string) Str::uuid(),
                'facility_id'     => $listing->id,
                'actor_id'        => $actorId,
                'actor_type'      => 'facility',
                'field_changed'   => 'logo_path',
                'old_value'       => $previous,
                'new_value'       => $path,
                'source'          => FacilityListingEditService::SOURCE,
                'requires_review' => false,
                'created_at'      => now(),
            ]);
        });

        // Only once the new file and the row are both in place.
        if ($previous && $previous !== $path && Storage::disk(config('filesystems.private_disk', 'local'))->exists($previous)) {
            Storage::disk(config('filesystems.private_disk', 'local'))->delete($previous);
        }

        return true;
    }

    /** A short-lived signed link to the listing's photo, or null when there is none. */
    public static function urlFor(?CareFacility $listing): ?string
    {
        if (! $listing || ! $listing->logo_path) {
            return null;
        }

        return URL::temporarySignedRoute(
            'facility.photo',
            now()->addMinutes(self::URL_TTL_MINUTES),
            // The version segment changes with every upload so a browser that
            // cached the old picture under the old link fetches the new one.
            ['listing' => $listing->id, 'v' => optional($listing->last_profile_update_at)->timestamp ?? 0],
        );
    }

    /**
     * Scale so the longest edge is at most MAX_EDGE (never upscaled), keep the
     * aspect ratio, write a plain JPEG with no metadata. Null if GD cannot
     * read the bytes.
     */
    private function fitJpeg(string $bytes, int $width, int $height): ?string
    {
        $source = @imagecreatefromstring($bytes);
        if ($source === false) {
            return null;
        }

        $scale = min(1, self::MAX_EDGE / max($width, $height));
        $outW  = max(1, (int) round($width * $scale));
        $outH  = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($outW, $outH);
        // Transparent PNG/WebP areas become white, not black.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $outW, $outH, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 86);
        $jpeg = (string) ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($source);

        return $jpeg !== '' ? $jpeg : null;
    }
}
