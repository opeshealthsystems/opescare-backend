<?php

namespace App\Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NotificationEvent extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function deliveries()
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function voiceJobs()
    {
        return $this->hasMany(VoiceNotificationJob::class);
    }

    public function escalationChain()
    {
        return $this->belongsTo(EscalationChain::class);
    }
}
