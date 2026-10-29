<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'label'         => $this->label,
            'contact_name'  => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'neighborhood'  => $this->neighborhood,
            'address_line'  => $this->address_line,
            'landmark'      => $this->landmark,
            'lat'           => $this->lat,
            'lng'           => $this->lng,
            'is_default'    => (bool) $this->is_default,
        ];
    }
}
