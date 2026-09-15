<?php

namespace App\Repositories;

use App\Models\SystemSetting;
use App\Models\User;

class AdminRepository
{
    public function __construct()
    {
        //
    }

    public function getSystemSettings(
        $facultyCode = null,
        $majorCode = null,
        $batch = null,
        $semester = null
    ) {
        return SystemSetting::where(function ($query) use ($facultyCode, $majorCode, $batch, $semester) {

            $query
                ->where(function ($q) use ($facultyCode, $majorCode, $batch, $semester) {
                    $q->where('faculty_code', $facultyCode)
                        ->where('major_code', $majorCode)
                        ->where('batch', $batch)
                        ->where('semester', $semester);
                })

                ->orWhere(function ($q) {
                    $q->whereNull('faculty_code')
                        ->whereNull('major_code')
                        ->whereNull('batch')
                        ->whereNull('semester');
                });

        })
            ->orderByRaw(
                'CASE WHEN faculty_code IS NULL THEN 0 ELSE 1 END DESC'
            )
            ->first();
    }


    public function updateSystemSettings(array $data)
    {

        return SystemSetting::updateOrCreate(
            [
                'faculty_code' => $data['faculty_code'] ?? null,
                'major_code' => $data['major_code'] ?? null,
                'batch' => $data['batch'] ?? null,
                'semester' => $data['semester'] ?? null,
            ],
            [
                'api_active' => $data['api_active'] ?? true,

                'fee_active' => $data['fee_active'] ?? true,
                'result_active' => $data['result_active'] ?? true,
                'timetable_active' => $data['timetable_active'] ?? true,

                'show_result' => $data['show_result'] ?? true,
                'show_timetable' => $data['show_timetable'] ?? true,
            ]
        );
    }

    public function countStudents()
    {
        return User::where('role_id', 2)->count();
    }

}
