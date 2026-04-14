<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientTransaction extends Model
{
    //
    protected $table = 'ingredient_transactions';
    protected $fillable = [
        'ingredient_id',
        'user_id',
        'type',
        'quantity',
        'unit_price',   
        'total_price',  
        'stock_after',
        'note',
    ];
}
