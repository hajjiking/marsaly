<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['replied_at' => 'datetime'];

    public function user()    { return $this->belongsTo(User::class); }
    public function order()   { return $this->belongsTo(Order::class); }
    public function assignee(){ return $this->belongsTo(User::class, 'assigned_to'); }
}
