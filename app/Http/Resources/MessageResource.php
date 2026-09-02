<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MessageResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'uuid'                  => $this->uuid,
            'thread_id'             => $this->thread_id,
            'sender_id'             => $this->sender_id,
            'message_type'          => $this->message_type,
            'body'                  => $this->body,
            'status'                => $this->status,
            'edited_at'             => $this->edited_at?->toISOString(),
            'deleted_for_sender_at' => $this->deleted_for_sender_at?->toISOString(),
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
