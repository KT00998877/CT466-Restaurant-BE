<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'menu_item_id',
        'quantity',
        'price',
    ];

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
