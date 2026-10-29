<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'merchant_id'      => $this->merchant_id,
            'menu_section_id'  => $this->menu_section_id,
            'name'             => $this->name,
            'description'      => $this->description,
            'price'            => (float) $this->price,
            'compare_at_price' => $this->compare_at_price ? (float) $this->compare_at_price : null,
            'image_url'        => $this->image_path ? url('storage/' . $this->image_path) : null,
            'in_stock'         => (bool) $this->in_stock,
            'is_featured'      => (bool) $this->is_featured,
            'tags'             => $this->tags,
            'options'          => ProductOptionResource::collection($this->whenLoaded('options')),
        ];
    }
}
