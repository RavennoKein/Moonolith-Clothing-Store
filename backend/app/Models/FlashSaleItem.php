<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSaleItem extends Model
{
    protected $fillable = [
        'flash_sale_id',
        'item_id',
        'discount_price',
        'discount_percentage',
        'flash_sale_stock',
        'sold_count'
    ];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
