<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\ProviderCredential;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Mobile Provider API — the clinician's OWN profile.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * This is not a staff directory, and it must never become one
 * ──────────────────────────────────────────────────────────────────────────
 * Every route here resolves its subject from `provider_user_id` on the request
 * attributes — stamped by AuthenticateMobileProvider from the bearer token. There
 * is NO id in any path, NO id accepted in any body, and NO listing. A clinician
 * asking this endpoint about a colleague has no way to phrase the question, so
 * "may I read this person" is a question that never has to be answered.
 * Adding a `{user}` segment or a `user_id` field here would turn a self-service
 * profile into a staff lookup; do not.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * What a clinician may assert about themselves — and what they may not
 * ──────────────────────────────────────────────────────────────────────────
 * PUT accepts exactly the eight keys of the contract's `ProviderProfileEdit`.
 * Three fields are returned and never writable here:
 *
 *   • `license_status` is DERIVED from `provider_credentials` — the rows an
 *     administrator verifies (ProfessionalProfileController / credential
 *     verification). A clinician who could write their own licence standing
 *     could publish themselves as licensed; the column does not exist for that
 *     reason. A revoked or suspended credential outranks an active one in the
 *     derivation: a licence in trouble must never read as clean.
 *   • `consultation_fee` / `currency` — the Finder product has no fee schedule.
 *     Both are reported null rather than invented.
 *
 * ──────────────────────────────────────────────────────────────────────────
 * The photo
 * ──────────────────────────────────────────────────────────────────────────
 * An upload is a file from a phone, and the phone's word for what it is counts
 * for nothing. The client's Content-Type and filename are never read. Three
 * independent checks must all pass:
 *
 *   1. `mimetypes:` — Laravel derives the type with finfo from the file's own
 *      bytes, not from the multipart header.
 *   2. `getimagesize()` — the bytes must actually decode as an image, and the
 *      IMAGETYPE it reports must be one of the three allowed.
 *   3. Dimensions must be sane, so a 60 000 × 60 000 "small" PNG cannot become
 *      a decompression bomb for anything that later opens it.
 *
 * The stored MIME and the stored extension are BOTH derived from (2) — nothing
 * the client sent is echoed back as the served content type.
 *
 * The file lands on the `local` disk (storage/app/private), outside the webroot,
 * with no public URL and no storage symlink. It is served only through a
 * temporary signed route, with the type the server determined and `nosniff`, so
 * a file that somehow got past the checks still cannot be served as script.
 */
class MobileDoctorProfileController extends Controller
{
    /** 4 MB. A headshot; not a scan of a diploma. */
    private const MAX_PHOTO_KB = 4096;

    /** Guards against a decompression bomb sitting behind a small file. */
    private const MAX_PHOTO_EDGE = 6000;

    /** How long a photo link stays good. Re-issued on every profile read. */
    private const PHOTO_URL_TTL_MINUTES = 60;

    /** The three types this API accepts, and the extension each is stored as. */
    private const ALLOWED_IMAGE_TYPES = [
        IMAGETYPE_JPEG => ['mime' => 'image/jpeg', 'ext' => 'jpg'],
        IMAGETYPE_PNG  => ['mime' => 'image/png',  'ext' => 'png'],
        IMAGETYPE_WEBP => ['mime' => 'image/webp', 'ext' => 'webp'],
    ];

    // ══════════════════════════════════════════════════════════════════════
    // GET /api/mobile/doctor/profile
    // ══════════════════════════════════════════════════════════════════════

    /**
     * The signed-in clinician's profile.
     *
     * `null` is a real answer — a clinician who has never filled anything in —
     * and the contract distinguishes it from a failure to load. So a missing row
     * is 200 with `data: null`, not a 404, which the client reads as
     * "this server does not serve the clinician surface".
     */
    public function show(Request $request): JsonResponse
    {
        $user = $this->me($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $profile = ProviderProfile::where('user_id', $user->id)->first();

        return response()->json([
            'data' => $profile ? $this->present($request, $user, $profile) : null,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // PUT /api/mobile/doctor/profile
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Save the eight editable keys and answer with what was STORED — never the
     * draft that was sent, so a field the server trimmed or rejected shows up
     * on the phone as the server's version.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $this->me($request);

        if (! $user) {
            return $this->unauthenticated();
        }

        $validated = $request->validate([
            'title'            => ['nullable', 'string', 'max:120'],
            'specialty'        => ['nullable', 'string', 'max:120'],
            'subspecialty'     => ['nullable', 'string', 'max:120'],
            'bio'              => ['nullable', 'string', 'max:2000'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:70'],
            // Entries are `nullable`, not `required`: Laravel's
            // ConvertEmptyStringsToNull turns a blank list entry into null
            // before validation runs, and a stray blank in a comma-separated
            // box is something to DROP (cleanList does) — not a 422 thrown at
            // a clinician who typed one comma too many.
            'languages'        => ['nullable', 'array', 'max:12'],
            'languages.*'      => ['nullable', 'string', 'max:60'],
            'qualifications'   => ['nullable', 'array', 'max:20'],
            'qualifications.*' => ['nullable', 'string', 'max:120'],
            'room'             => ['nullable', 'string', 'max:120'],
        ]);

        $profile = ProviderProfile::firstOrNew(['user_id' => $user->id]);

        $profile->fill([
            'title'            => $this->trimOrNull($validated['title'] ?? null),
            'specialty'        => $this->trimOrNull($validated['specialty'] ?? null),
            'subspecialty'     => $this->trimOrNull($validated['subspecialty'] ?? null),
            'bio'              => $this->trimOrNull($validated['bio'] ?? null),
            'years_experience' => $validated['years_experience'] ?? null,
            'languages'        => $this->cleanList($validated['languages'] ?? []),
            'qualifications'   => $this->cleanList($validated['qualifications'] ?? []),
            'room'             => $this->trimOrNull($validated['room'] ?? null),
        ]);

        // user_id is set here and only here — it is not in the validated set, so
        // no request could ever aim this write at another clinician's row.
        $profile->user_id = $user->id;
        $profile->save();

        return response()->json([
            'data' => $this->present($request, $user, $profile->refresh()),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // POST /api/mobile/doctor/profile/photo
    // ══════════════════════════════════════════════════════════════════════

    /** Upload a headshot. See the class docblock for what "is an image" means here. */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $user = $this->me($request);

        if (! $user) {
            return $this->unauthenticated();
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

        $type = self::ALLOWED_IMAGE_TYPES[$info[2]];

        $profile = ProviderProfile::firstOrNew(['user_id' => $user->id]);
        $profile->user_id = $user->id;

        $previous = $profile->photo_path;

        // The name is the server's own — a client filename is never part of a
        // path this server writes to.
        $path = sprintf('doctor-photos/%s/%s.%s', $user->id, (string) Str::uuid(), $type['ext']);

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        $profile->photo_path = $path;
        $profile->photo_mime = $type['mime'];
        $profile->save();

        // Only after the new one is committed — a failed save must not leave the
        // clinician with no photo at all.
        if ($previous && $previous !== $path) {
            Storage::disk('local')->delete($previous);
        }

        return response()->json([
            'data' => ['photo_url' => $this->photoUrl($request, $profile)],
        ], 201);
    }

    // ══════════════════════════════════════════════════════════════════════
    // GET /api/mobile/doctor/profile/photo/{profile} (signed, no bearer)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Serve a stored headshot.
     *
     * Unauthenticated by design: an <Image> tag cannot send an Authorization
     * header, so the capability lives in the signature instead — a short-lived
     * URL that only a holder of the profile response could have been given. The
     * signature is verified by ValidateSignature on the route; reaching this
     * method at all means it checked out.
     *
     * The Content-Type is the one the SERVER determined at upload time, and
     * `nosniff` stops a browser second-guessing it.
     */
    public function showPhoto(string $profileId): BinaryFileResponse|JsonResponse
    {
        if (! Str::isUuid($profileId)) {
            return $this->photoMissing();
        }

        $profile = ProviderProfile::find($profileId);

        if (! $profile || ! $profile->photo_path || ! Storage::disk('local')->exists($profile->photo_path)) {
            return $this->photoMissing();
        }

        $response = response()->file(Storage::disk('local')->path($profile->photo_path), [
            'Content-Type'           => $profile->photo_mime ?: 'application/octet-stream',
            'Content-Disposition'    => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        // BinaryFileResponse marks itself PUBLIC by default, which would let a
        // shared proxy keep a copy of a clinician's photo and hand it to the
        // next person down the wire. setPrivate() after construction is the
        // only thing that sticks — a Cache-Control passed in $headers is
        // overwritten by that default.
        $response->setPrivate();
        $response->setMaxAge(300);

        return $response;
    }

    // ══════════════════════════════════════════════════════════════════════
    // Internals
    // ══════════════════════════════════════════════════════════════════════

    /**
     * The signed-in clinician. The ONLY subject any method here operates on.
     */
    private function me(Request $request): ?User
    {
        $id = $request->attributes->get('provider_user_id');

        return $id ? User::find($id) : null;
    }

    /** The facility on the token — attributes only, never body/query/header. */
    private function facilityId(Request $request): ?string
    {
        $id = $request->attributes->get('provider_facility_id');

        return $id ? (string) $id : null;
    }

    /**
     * The contract's `ProviderProfile`.
     *
     * @return array<string,mixed>
     */
    private function present(Request $request, User $user, ProviderProfile $profile): array
    {
        $facilityId = $this->facilityId($request);

        return [
            'display_name'     => $user->name,
            'title'            => $profile->title,
            'specialty'        => $profile->specialty,
            'subspecialty'     => $profile->subspecialty,
            'bio'              => $profile->bio,
            'photo_url'        => $this->photoUrl($request, $profile),
            'years_experience' => $profile->years_experience,
            'languages'        => array_values($profile->languages ?? []),
            'qualifications'   => array_values($profile->qualifications ?? []),
            'room'             => $profile->room,

            // Read-only, and null rather than invented — see the class docblock.
            'consultation_fee' => null,
            'currency'         => null,
            'license_status'   => $this->licenseStatus($user),

            // Not part of ProviderProfile on the wire contract, but the app's
            // identity header needs a facility name and `/mobile/doctor/me` is
            // owned elsewhere. Extra keys are ignored by a typed client.
            'facility_id'      => $facilityId,
            'facility_name'    => $facilityId ? Facility::whereKey($facilityId)->value('name') : null,
        ];
    }

    /** A short-lived signed link, or null when there is no photo. */
    private function photoUrl(Request $request, ProviderProfile $profile): ?string
    {
        if (! $profile->photo_path || ! $profile->id) {
            return null;
        }

        return URL::temporarySignedRoute(
            'mobile.doctor.profile.photo',
            now()->addMinutes(self::PHOTO_URL_TTL_MINUTES),
            ['profile' => $profile->id],
        );
    }

    /**
     * Licence standing, derived from the credentials an administrator verified.
     *
     * Order matters and is deliberately pessimistic:
     *   suspended/revoked  →  'suspended'   (trouble outranks everything)
     *   a live verified one →  'active'
     *   verified but expired → 'expired'
     *   anything unverified  → 'pending'
     *   nothing on file      →  null        (no claim at all, not "fine")
     */
    private function licenseStatus(User $user): ?string
    {
        $credentials = ProviderCredential::where('provider_id', $user->id)->get();

        if ($credentials->isEmpty()) {
            return null;
        }

        if ($credentials->contains(fn (ProviderCredential $c) => in_array($c->status, ['suspended', 'revoked'], true))) {
            return 'suspended';
        }

        $verified = $credentials->filter(fn (ProviderCredential $c) => $c->verified_at !== null);

        if ($verified->isEmpty()) {
            return 'pending';
        }

        $live = $verified->contains(
            fn (ProviderCredential $c) => $c->status === 'active' && ! $c->isExpired()
        );

        return $live ? 'active' : 'expired';
    }

    private function trimOrNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<int,mixed>  $values
     * @return array<int,string>
     */
    private function cleanList(array $values): array
    {
        return array_values(array_unique(array_filter(
            array_map(fn ($v) => trim((string) $v), $values),
            fn (string $v) => $v !== '',
        )));
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json(['message' => __('doctor_messaging.unauthenticated')], 401);
    }

    private function photoMissing(): JsonResponse
    {
        return response()->json([
            'error_code' => 'NOT_FOUND',
            'message'    => __('doctor_messaging.photo_not_found'),
        ], 404);
    }
}
