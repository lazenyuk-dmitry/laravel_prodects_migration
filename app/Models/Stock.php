<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'last_change_date' => 'datetime',
        'quantity' => 'integer',
        'quantity_full' => 'integer',
        'price' => 'float',
        'is_supply' => 'boolean',
        'is_realization' => 'boolean',
    ];
}
