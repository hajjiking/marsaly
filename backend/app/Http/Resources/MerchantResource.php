<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'business_name'        => $this->business_name,
            'description'          => $this->description,
            'phone'                => $this->phone,
            'address'              => $this->address,
            'neighborhood'         => $this->neighborhood,
            'lat'                  => $this->lat,
            'lng'                  => $this->lng,
            'logo_url'             => $this->logo_path ? url('storage/' . $this->logo_path) : null,
            'cover_url'            => $this->cover_path ? url('storage/' . $this->cover_path) : null,
            'status'               => $this->status,
            'is_open_now'          => $this->isOpenNow(),
            'is_busy'              => (bool) $this->is_busy,
            'prep_time_minutes'    => $this->prep_time_minutes,
            'rating_avg'           => round((float) $this->rating_avg, 2),
            'category'             => new CategoryResource($this->whenLoaded('category')),
            'zone'                => $this->whenLoaded('zone', fn () => [
                'id' => $this->zone->id, 'name' => $this->zone->name,
                'base_fee' => $this->zone->base_fee, 'per_km_fee' => $this->zone->per_km_fee,
                'minimum_order' => $this->zone->minimum_order,
            ]),
            'hours'                => $this->whenLoaded('hours', fn () => $this->hours->map(fn ($h) => [
                'day_of_week' => $h->day_of_week,
                'opens_at'   => optional($h->opens_at)->format('H:i'),
                'closes_at'  => optional($h->closes_at)->format('H:i'),
                'is_closed'   => (bool) $h->is_closed,
            ])),
        ];
    }
}
