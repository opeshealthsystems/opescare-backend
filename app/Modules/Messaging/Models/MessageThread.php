<?php

namespace App\Modules\Messaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageThread extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function participants()
    {
        return $this->hasMany(MessageThreadParticipant::class, 'thread_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'thread_id');
    }
}
