<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    public const SUPER_ADMIN = 'super_admin';
    public const SUPPORT = 'support';
    public const FINANCE = 'finance';

    protected $fillable = ['name', 'display_name'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
