<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuSection extends Model
{
    protected $fillable = ['merchant_id', 'name', 'sort_order', 'is_active'];
    protected $casts = ['name' => 'array', 'is_active' => 'boolean'];

    public function merchant() { return $this->belongsTo(Merchant::class); }
    public function products() { return $this->hasMany(Product::class)->orderBy('sort_order'); }
}
