<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Merchant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category_id', 'delivery_zone_id', 'business_name', 'description',
        'phone', 'address', 'neighborhood', 'lat', 'lng', 'logo_path', 'cover_path',
        'status', 'licence_number', 'id_document_path', 'licence_document_path',
        'commission_percent', 'is_busy', 'is_temporarily_closed', 'prep_time_minutes',
    ];

    protected $casts = [
        'lat' => 'float', 'lng' => 'float', 'commission_percent' => 'float',
        'is_busy' => 'boolean', 'is_temporarily_closed' => 'boolean',
        'rating_avg' => 'float', 'approved_at' => 'datetime',
    ];

    public function user()   { return $this->belongsTo(User::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function zone()   { return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id'); }
    public function hours()  { return $this->hasMany(MerchantHour::class); }
    public function menuSections() { return $this->hasMany(MenuSection::class)->orderBy('sort_order'); }
    public function products() { return $this->hasMany(Product::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function reviews() { return $this->hasMany(Review::class)->where('target', 'merchant'); }

    public function isOpenNow(?Carbon $at = null): bool
    {
        if ($this->is_temporarily_closed || $this->status !== 'approved') {
            return false;
        }
        $at ??= now();
        $dow = (int) $at->dayOfWeek;
        $today = $this->hours->firstWhere('day_of_week', $dow);
        if (!$today || $today->is_closed || !$today->opens_at) {
            return false;
        }
        $now = $at->format('H:i:s');
        $open = substr($today->opens_at, 0, 8);
        $close = substr($today->closes_at, 0, 8);
        return $close >= $open ? ($now >= $open && $now <= $close) : ($now >= $open || $now <= $close);
    }

    public function effectiveCommissionPercent(): float
    {
        return (float) ($this->commission_percent
            ?? $this->category?->commission_percent
            ?? config('marsaly.fees.default_commission'));
    }
}
