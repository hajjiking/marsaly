<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOptionValue extends Model
{
    protected $fillable = ['product_option_id', 'name', 'price_delta', 'is_default', 'sort_order'];
    protected $casts = ['name' => 'array', 'price_delta' => 'float', 'is_default' => 'boolean'];
}
