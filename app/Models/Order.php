<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $guarded = [];

     protected $casts = [
        'date' => 'datetime',
        'last_change_date' => 'date',
        'cancel_dt' => 'datetime',
        'is_cancel' => 'boolean',
        'total_price' => 'decimal:2',
        'discount_percent' => 'integer',
    ];
}
