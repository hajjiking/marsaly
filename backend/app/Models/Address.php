<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id', 'label', 'contact_name', 'contact_phone', 'neighborhood',
        'address_line', 'landmark', 'lat', 'lng', 'is_default',
    ];
    protected $casts = ['lat' => 'float', 'lng' => 'float', 'is_default' => 'boolean'];

    public function user() { return $this->belongsTo(User::class); }
}
