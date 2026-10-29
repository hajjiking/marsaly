<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $guarded = ['id'];
    protected $casts = [
        'value' => 'float', 'min_order_amount' => 'float', 'max_discount' => 'float',
        'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean',
    ];

    public function merchant() { return $this->belongsTo(Merchant::class); }

    public function isValidFor(?int $userId, float $subtotal): bool
    {
        if (!$this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return false;
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) return false;
        if ($subtotal < (float) $this->min_order_amount) return false;
        return true;
    }
}
