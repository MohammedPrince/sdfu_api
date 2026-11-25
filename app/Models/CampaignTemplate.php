<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignTemplate extends Model
{
    public const TYPES = [
        'general' => 'Announcement',
        'timetable' => 'Timetable',
        'result' => 'Result',
        'fees' => 'Fees',
    ];

    protected $fillable = [
        'template_group',
        'sequence',
        'type',
        'title',
        'body',
    ];

    protected $casts = [
        'sequence' => 'integer',
    ];
}
