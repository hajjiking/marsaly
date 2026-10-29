<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'city', 'base_fee', 'per_km_fee', 'minimum_order',
        'service_radius_km', 'center_lat', 'center_lng', 'is_active',
    ];
    protected $casts = [
        'name' => 'array', 'base_fee' => 'float', 'per_km_fee' => 'float',
        'minimum_order' => 'float', 'service_radius_km' => 'float',
        'center_lat' => 'float', 'center_lng' => 'float', 'is_active' => 'boolean',
    ];

    public function merchants()
    {
        return $this->hasMany(Merchant::class);
    }
}
