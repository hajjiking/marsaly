<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'status', 'availability', 'vehicle_type', 'vehicle_model',
        'plate_number', 'id_document_path', 'vehicle_document_path',
        'lat', 'lng', 'last_location_at',
    ];

    protected $casts = [
        'lat' => 'float', 'lng' => 'float', 'last_location_at' => 'datetime',
        'cash_collected_total' => 'float', 'cash_owed_to_platform' => 'float',
        'rating_avg' => 'float',
    ];

    public function user()      { return $this->belongsTo(User::class); }
    public function locations() { return $this->hasMany(DriverLocation::class); }
    public function deliveries(){ return $this->hasMany(Delivery::class); }
    public function orders()    { return $this->hasMany(Order::class); }
    public function reviews()   { return $this->hasMany(Review::class)->where('target', 'driver'); }

    public function isAvailable(): bool
    {
        return $this->status === 'approved' && $this->availability === 'online';
    }
}
