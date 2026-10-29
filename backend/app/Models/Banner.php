<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['title' => 'array', 'is_active' => 'boolean', 'ends_at' => 'datetime'];
}
