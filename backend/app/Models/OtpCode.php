<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = ['phone', 'code', 'purpose', 'attempts', 'expires_at', 'verified_at'];
    protected $casts = ['expires_at' => 'datetime', 'verified_at' => 'datetime'];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
