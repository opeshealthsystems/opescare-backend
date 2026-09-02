<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FileAsset extends Model
{
    use HasUuids;

    protected $fillable = [
        'original_name', 'stored_name', 'disk', 'path',
        'mime_type', 'size_bytes', 'checksum',
        'uploaded_by', 'facility_id', 'is_private',
    ];

    protected $casts = [
        'is_private'  => 'boolean',
        'size_bytes'  => 'integer',
    ];

    public function attachments()
    {
        return $this->hasMany(MedicalAttachment::class);
    }

    public function humanSize(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes < 1024)       return $bytes . ' B';
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    }

    public function url(): ?string
    {
        if ($this->disk === 'local') {
            return route('portals.staff.files.download', $this->id);
        }
        return Storage::disk($this->disk)->url($this->path);
    }
}
