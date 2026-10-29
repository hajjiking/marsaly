<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'locale'=> $this->locale,
            'role'  => $this->role?->name,
            'is_active' => (bool) $this->is_active,
            'merchant' => new MerchantResource($this->whenLoaded('merchant')),
            'driver'   => $this->whenLoaded('driver', fn () => [
                'id' => $this->driver->id,
                'status' => $this->driver->status,
                'availability' => $this->driver->availability,
                'vehicle_type' => $this->driver->vehicle_type,
                'vehicle_model' => $this->driver->vehicle_model,
                'plate_number' => $this->driver->plate_number,
                'rating_avg' => round((float) $this->driver->rating_avg, 2),
                'cash_collected_total' => (float) $this->driver->cash_collected_total,
                'cash_owed_to_platform'=> (float) $this->driver->cash_owed_to_platform,
            ]),
        ];
    }
}
