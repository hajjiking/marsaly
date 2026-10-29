<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOption extends Model
{
    protected $fillable = ['product_id', 'name', 'type', 'required', 'sort_order'];
    protected $casts = ['name' => 'array', 'required' => 'boolean'];

    public function product() { return $this->belongsTo(Product::class); }
    public function values()  { return $this->hasMany(ProductOptionValue::class)->orderBy('sort_order'); }
}
