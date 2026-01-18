<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ItemVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'color',
        'size',
        'stock',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

}