<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon', 'slug', 'commission_percent', 'is_active', 'sort_order'];
    protected $casts = ['name' => 'array', 'icon' => 'array', 'is_active' => 'boolean'];

    public function merchants()
    {
        return $this->hasMany(Merchant::class);
    }

    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        return $this->name[$locale] ?? $this->name['fr'] ?? $this->name['ar'] ?? (string) reset($this->name);
    }
}
