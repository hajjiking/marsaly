<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['title' => 'array', 'body' => 'array', 'is_active' => 'boolean', 'publish_at' => 'datetime'];
}
