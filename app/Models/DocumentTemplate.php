<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTemplate extends Model
{
    use \App\Traits\IsDemoRecord;
    use HasFactory;
    use HasUuids;

    protected $table = 'document_templates';

    protected $fillable = [
        'template_code',
        'document_type',
        'language',
        'version',
        'status',
        'html_template',
        'css_styles',
        'plain_text_template',
        'created_by',
        'approved_by',
        'published_at',
        'archived_at',
        'is_demo',
        'demo_seed_key',
        'demo_reset_group',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
        'is_demo' => 'boolean',
    ];
}
