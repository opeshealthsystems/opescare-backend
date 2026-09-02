<?php

namespace App\Modules\Messaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageThreadParticipant extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function thread()
    {
        return $this->belongsTo(MessageThread::class, 'thread_id');
    }
}
