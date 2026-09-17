<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $fillable = [
        'faculty_code',
        'major_code',
        'batch',
        'semester',

        'api_active',

        'fee_active',
        'result_active',
        'timetable_active',

        'show_result',
        'show_timetable',
    ];

    protected $casts = [
        'api_active' => 'boolean',

        'fee_active' => 'boolean',
        'result_active' => 'boolean',
        'timetable_active' => 'boolean',

        'show_result' => 'boolean',
        'show_timetable' => 'boolean',

        'semester' => 'integer',
    ];
}