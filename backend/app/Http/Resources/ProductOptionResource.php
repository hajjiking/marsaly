<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'type'          => $this->type, // single | multiple
            'required'      => (bool) $this->is_required,
            'min_values'    => $this->min_values,
            'max_values'    => $this->max_values,
            'values'        => ProductOptionValueResource::collection($this->whenLoaded('values')),
        ];
    }
}
