<?php

namespace App\Repositories;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ExternalDatabaseService;
use App\Models\Notification;
use App\Models\Visitor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

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

    //Studnets Start
    public function getStudents(array $filters): LengthAwarePaginator
    {

        $query = User::query()
            ->where('role_id', 2)
            ->withCount('devices')
            ->withMax('devices', 'last_seen_at');

        if (!empty($filters['search'])) {

            $search = $filters['search'];

            $query->where(function ($q) use ($search) {
                $q->where('stud_index', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['faculty_code'])) {
            $query->where(
                'faculty_code',
                $filters['faculty_code']
            );
        }

        if (!empty($filters['major_code'])) {
            $query->where(
                'major_code',
                $filters['major_code']
            );
        }

        if (!empty($filters['batch'])) {
            $query->where(
                'batch',
                $filters['batch']
            );
        }

        if (!empty($filters['semester'])) {
            $query->where(
                'semester',
                $filters['semester']
            );
        }

        if ($filters['status'] !== null && $filters['status'] !== '') {

            $active = (bool) $filters['status'];

            $query->whereExists(function ($q) use ($active) {

                $q->selectRaw('1')
                    ->from('system_settings')
                    ->whereColumn(
                        'system_settings.faculty_code',
                        'users.faculty_code'
                    )
                    ->whereColumn(
                        'system_settings.major_code',
                        'users.major_code'
                    )
                    ->whereColumn(
                        'system_settings.batch',
                        'users.batch'
                    )
                    ->whereColumn(
                        'system_settings.semester',
                        'users.semester'
                    )
                    ->where(
                        'system_settings.api_active',
                        $active
                    );
            });
        }

        $students = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $settings = SystemSetting::query()
            ->get()
            ->keyBy(function ($setting) {
                return implode('|', [
                    $setting->faculty_code,
                    $setting->major_code,
                    $setting->batch,
                    $setting->semester,
                ]);
            });


        $faculties = $this->externalDatabase->faculties()->keyBy('faculty_code');
        $majors = $this->externalDatabase->majors()->keyBy('major_code');

        $students->getCollection()->transform(function ($student) use ($faculties, $majors, $settings) {

            /*
            |--------------------------------------------------------------------------
            | Faculty
            |--------------------------------------------------------------------------
            */

            $faculty = $faculties->get($student->faculty_code);

            $student->faculty_desc_e = $faculty->faculty_desc_e
                ?? $student->faculty_code;


            /*
            |--------------------------------------------------------------------------
            | Major
            |--------------------------------------------------------------------------
            */

            $major = $majors->get($student->major_code);

            $student->major_desc_e = $major->major_desc_e
                ?? $student->major_code;


            /*
            |--------------------------------------------------------------------------
            | Application / Account Status
            |--------------------------------------------------------------------------
            */

            $settingKey = implode('|', [
                $student->faculty_code,
                $student->major_code,
                $student->batch,
                $student->semester,
            ]);

            $setting = $settings->get($settingKey);


            if (!$setting) {

                $student->account_active = false;
                $student->account_status = 'Disabled';

            } else {

                $student->account_active = (bool) $setting->api_active;

                $student->account_status =
                    $setting->api_active
                    ? 'Active'
                    : 'Disabled';
            }


            return $student;
        });

        return $students;
    }

    public function getStudentBatches(): Collection
    {
        return User::query()
            ->where('role_id', 2)
            ->whereNotNull('batch')
            ->where('batch', '!=', '')
            ->select('batch')
            ->distinct()
            ->orderBy('batch')
            ->pluck('batch');
    }

    public function getStudentDetails(User $student): User
    {
        $student->load([
            'devices' => function ($query) {
                $query->latest('last_seen_at');
            },
        ]);

        $student->loadCount('devices');

        $student->faculty_name =
            $this->externalDatabase->getFacultyName(
                $student->faculty_code
            );

        $student->major_name =
            $this->externalDatabase->getMajorName(
                $student->major_code
            );

        $student->notification_count =
            Notification::where('user_id', $student->id)->count();

        $student->unread_notification_count =
            Notification::where('user_id', $student->id)
                ->whereNull('read_at')
                ->count();

        return $student;
    }

}
