<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A clinician's own professional profile (see the create migration for why this
 * is not part of StaffProfile).
 *
 * `$fillable` lists exactly the eight keys `PUT /api/mobile/doctor/profile`
 * accepts. `photo_path` / `photo_mime` are deliberately absent: the photo is
 * written only by the upload endpoint, after the file's content has been
 * inspected, and must never be settable through a profile PUT.
 */
class ProviderProfile extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'title',
        'specialty',
        'subspecialty',
        'bio',
        'years_experience',
        'languages',
        'qualifications',
        'room',
    ];

    protected $casts = [
        'languages'        => 'array',
        'qualifications'   => 'array',
        'years_experience' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
