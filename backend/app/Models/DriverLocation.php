<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverLocation extends Model
{
    protected $fillable = ['driver_id', 'lat', 'lng', 'accuracy', 'recorded_at'];
    protected $casts = ['lat' => 'float', 'lng' => 'float', 'accuracy' => 'float', 'recorded_at' => 'datetime'];

    public function driver() { return $this->belongsTo(Driver::class); }
}
