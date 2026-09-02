<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignalReview extends Model
{
    protected $table = 'public_health_signal_reviews';

    protected $fillable = [
        'signal_id',
        'reviewer_id',
        'action',
        'comment',
        'reviewed_at'
    ];

    protected $casts = [
        'reviewed_at' => 'datetime'
    ];

    public function signal()
    {
        return $this->belongsTo(PublicHealthSignal::class, 'signal_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
