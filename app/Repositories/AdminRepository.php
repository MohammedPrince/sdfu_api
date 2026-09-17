<?php

namespace App\Repositories;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ExternalDatabaseService;
use App\Models\Notification;
use App\Models\Visitor;

class AdminRepository
{
    protected $externalDatabase;

    public function __construct()
    {
        $this->externalDatabase = new ExternalDatabaseService();
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

    public function getSavedSettings()
    {

        $savedSettings = SystemSetting::orderBy('faculty_code')
            ->orderBy('major_code')
            ->orderBy('batch')
            ->orderBy('id')
            ->get();

        $faculties = $this->externalDatabase
            ->faculties()
            ->keyBy('faculty_code');

        $majors = $this->externalDatabase
            ->majors()
            ->keyBy('major_code');

        return $savedSettings->map(function ($setting) use ($faculties, $majors) {

            $faculty = $faculties->get($setting->faculty_code);
            $major = $majors->get($setting->major_code);

            return [
                'id' => $setting->id,
                'faculty_code' => $setting->faculty_code,
                'faculty_desc_e' => $faculty->faculty_desc_e ?? $setting->faculty_code,

                'major_code' => $setting->major_code,
                'major_desc_e' => $major->major_desc_e ?? $setting->major_code,

                'batch' => $setting->batch,
                'semester' => $setting->semester,

                'api_active' => (bool) $setting->api_active,
                'fee_active' => (bool) $setting->fee_active,
                'result_active' => (bool) $setting->result_active,
                'timetable_active' => (bool) $setting->timetable_active,
            ];
        });
    }

    public function countCourses(): int
    {
        // Update this later when your course source/table is finalized.
        return 0;
    }

    public function countNotifications(): int
    {
        return Notification::count();
    }

    public function getVisitorCounts(): array
    {
        return [
            'today' => Visitor::whereDate('created_at', today())->count(),

            'month' => Visitor::whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),

            'total' => Visitor::count(),
        ];
    }

    public function getRecentNotifications()
    {
        return Notification::latest()
            ->take(5)
            ->get();
    }

    public function getApplicationStatus(): array
    {
        $settings = SystemSetting::query()
            ->orderBy('id')
            ->get();

        return [
            'active' => $settings->contains(
                fn($setting) => (bool) $setting->api_active
            ),

            'result_active' => $settings->contains(
                fn($setting) => (bool) $setting->result_active
            ),

            'timetable_active' => $settings->contains(
                fn($setting) => (bool) $setting->timetable_active
            ),

            'fee_active' => $settings->contains(
                fn($setting) => (bool) $setting->fee_active
            ),
        ];
    }

    public function getApplicationOverview(): array
    {
        $settings = SystemSetting::query()->get();

        return [
            'total' => $settings->count(),

            'active' => $settings
                ->where('api_active', true)
                ->count(),

            'inactive' => $settings
                ->where('api_active', false)
                ->count(),

            'result_active' => $settings
                ->where('result_active', true)
                ->count(),

            'timetable_active' => $settings
                ->where('timetable_active', true)
                ->count(),

            'fee_active' => $settings
                ->where('fee_active', true)
                ->count(),
        ];
    }

    public function getFaculties()
    {
        return $this->externalDatabase->faculties();

    }

    public function getMajors()
    {
        return $this->externalDatabase->majors();

    }

    public function getBatches()
    {
        return $this->externalDatabase->batches();

    }

    public function majorsByFaculty($facultyCode)
    {
        $majors = $this->externalDatabase->majorsByFaculty($facultyCode);

        return $majors;
    }

}
