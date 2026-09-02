<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class LegalDocumentResource extends ApiResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'slug'                => $this->slug,
            'title'               => $this->title,
            'document_type'       => $this->document_type,
            'language'            => $this->language,
            'is_active'           => (bool) $this->is_active,
            'requires_acceptance' => (bool) $this->requires_acceptance,
            'created_by'          => $this->created_by,
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
