<?php

namespace App\Modules\Messaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageAttachment extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }
}
