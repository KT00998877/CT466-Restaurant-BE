<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'user_id',
        'table_id',
        'customer_name',
        'customer_phone',
        'guests',
        'reserved_at',
        'note',
        'status',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(TableList::class, 'table_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
