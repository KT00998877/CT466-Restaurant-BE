<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'description',
    ];

    // Quan hệ: Role có nhiều permissions
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    // Quan hệ: Role có nhiều users
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
