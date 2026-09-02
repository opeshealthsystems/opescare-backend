<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignalAlert extends Model
{
    protected $table = 'public_health_signal_alerts';

    protected $fillable = [
        'signal_id',
        'recipient_type',
        'recipient_id',
        'channel',
        'status',
        'sent_at',
        'acknowledged_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime'
    ];

    public function signal()
    {
        return $this->belongsTo(PublicHealthSignal::class, 'signal_id');
    }
}
