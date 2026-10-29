<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'phone', 'role_id', 'locale', 'is_active'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = [
        'password' => 'hashed',
        'phone_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function merchant(): HasOne
    {
        return $this->hasOne(Merchant::class);
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function hasRole(string ...$names): bool
    {
        return in_array($this->role?->name, $names, true);
    }

    public function isClient(): bool   { return $this->hasRole(Role::CLIENT); }
    public function isMerchant(): bool { return $this->hasRole(Role::MERCHANT); }
    public function isDriver(): bool   { return $this->hasRole(Role::DRIVER); }
    public function isAdmin(): bool    { return $this->hasRole(Role::ADMIN); }

    public function hasPermission(string $name): bool
    {
        if ($this->isAdmin()) {
            // admin role carries granular permissions; super_admin sees everything
            $perms = $this->role->permissions->pluck('name');
            return $perms->contains(Permission::SUPER_ADMIN) || $perms->contains($name);
        }
        return false;
    }
}
