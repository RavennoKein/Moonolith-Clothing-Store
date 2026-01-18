<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    protected $fillable = ['name', 'description', 'start_time', 'end_time', 'status', 'is_permanent'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_permanent' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(FlashSaleItem::class);
    }
}
