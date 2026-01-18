<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'bahan',
        'tingkat_ketebalan',
        'status_produk',
        'image_url',
        'category_id',
        'gender',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function totalStock(): int
    {
        return $this->variants()->sum('stock');
    }

    public function variants()
    {
        return $this->hasMany(ItemVariant::class);
    }

    public function flashSaleItems()
    {
        return $this->hasMany(FlashSaleItem::class);
    }

    public function isOnActiveFlashSale(): bool
    {
        return $this->flashSaleItems()
            ->whereHas('flashSale', function ($q) {
                $q->where('status', 'active');
            })
            ->exists();
    }

}