<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminPermission extends Model
{
     protected $fillable = [
        'user_id',
        'menu_key',
        'can_view',
    ];

    protected $casts = [
        'can_view' => 'boolean',
    ];
}
