<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Media */
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url(),
            'name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'markdown' => $this->markdown(),
        ];
    }
}
