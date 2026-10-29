<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'title'     => $this->title,
            'image_url' => $this->image_path ? url('storage/' . $this->image_path) : null,
            'link'      => $this->link ?? null,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at'   => $this->ends_at?->toIso8601String(),
        ];
    }
}
