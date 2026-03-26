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
        'stock_after',
        'note',
    ];
}
