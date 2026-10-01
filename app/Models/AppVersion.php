<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppVersion extends Model
{
    protected $fillable = [
        'platform',
        'minimum_version',
        'app_url',
        'force_update',
        'created_by',
    ];

    protected $casts = [
        'force_update' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}