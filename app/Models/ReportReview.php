<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ReportReview extends Model
{
    use HasUuids;

    protected $table = 'public_health_report_reviews';

    protected $fillable = [
        'report_id',
        'reviewer_id',
        'action',
        'comment',
        'reviewed_at'
    ];

    protected $casts = [
        'reviewed_at' => 'datetime'
    ];

    public function report()
    {
        return $this->belongsTo(PublicHealthReport::class, 'report_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
