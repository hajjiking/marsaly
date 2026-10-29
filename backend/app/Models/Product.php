<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'merchant_id', 'menu_section_id', 'name', 'description', 'price',
        'compare_at_price', 'image_path', 'in_stock', 'is_featured', 'sort_order', 'tags',
    ];

    protected $casts = [
        'name' => 'array', 'tags' => 'array', 'price' => 'float',
        'compare_at_price' => 'float', 'in_stock' => 'boolean', 'is_featured' => 'boolean',
    ];

    public function merchant()      { return $this->belongsTo(Merchant::class); }
    public function section()       { return $this->belongsTo(MenuSection::class, 'menu_section_id'); }
    public function options()       { return $this->hasMany(ProductOption::class)->orderBy('sort_order'); }

    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        return $this->name[$locale] ?? $this->name['fr'] ?? $this->name['ar'] ?? (string) reset($this->name);
    }
}
