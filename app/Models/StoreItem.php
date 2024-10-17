<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreItem extends Model
{
    use HasFactory;
    protected $table = 'store_items';
    protected $fillable = [
        'user_id',
        'item_name',
        'item_unit',
        'item_quantity'
    ];
}
