<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class KnowledgeBaseArticleResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'slug'         => $this->slug,
            'audience'     => $this->audience,
            'status'       => $this->status,
            'body'         => $this->body,
            'view_count'   => $this->view_count,
            'created_by'   => $this->created_by,
            'published_at' => $this->published_at?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
