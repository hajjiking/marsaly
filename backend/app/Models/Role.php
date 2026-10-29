<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    public const CLIENT = 'client';
    public const MERCHANT = 'merchant';
    public const DRIVER = 'driver';
    public const ADMIN = 'admin';

    protected $fillable = ['name', 'display_name'];

    public function users(): \Illuminate\Database\Eloquent\HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
