<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class LegalDocumentVersionResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'legal_document_id'     => $this->legal_document_id,
            'version'               => $this->version,
            'content_html'          => $this->content_html,
            'content_markdown'      => $this->content_markdown,
            'is_current'            => (bool) $this->is_current,
            'requires_reacceptance' => (bool) $this->requires_reacceptance,
            'change_summary'        => $this->change_summary,
            'published_by'          => $this->published_by,
            'published_at'          => $this->published_at?->toISOString(),
            'effective_at'          => $this->effective_at?->toISOString(),
            'created_at'            => $this->created_at?->toISOString(),
            'updated_at'            => $this->updated_at?->toISOString(),
        ];
    }
}
