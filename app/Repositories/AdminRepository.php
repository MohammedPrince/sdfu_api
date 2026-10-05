<?php

namespace App\Repositories;

use App\Helpers\Helper;
use App\Models\AppVersion;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Visitor;
use App\Services\ExternalDatabaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use function CuyZ\Valinor\Compiler\return_;

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
        return User::where('role_id', Helper::STUDENT_ROLE)->count();
    }

    public function getAppVersions()
    {
        return AppVersion::query()
            ->orderByRaw("
            CASE platform
                WHEN 'ios' THEN 1
                WHEN 'android' THEN 2
                ELSE 3
            END
        ")->get();
    }

    public function getAppVersion(int $id)
    {
        return AppVersion::find($id);
    }

    public function storeAppVersion(array $data)
    {
        $appVersion = AppVersion::updateOrCreate(
            [
                'platform' => strtolower($data['platform']),
            ],
            [
                'minimum_version' => $data['minimum_version'],
                'app_url' => $data['app_url'],
                'force_update' => $data['force_update'],
                'created_by' => auth()->id(),
            ]
        );

        return $appVersion;
    }

    public function updateAppVersion(array $data)
    {
        $appVersion = AppVersion::find($data['id']);

        if (!$appVersion) {
            return false;
        }

        $appVersion->platform = $data['platform'];
        $appVersion->minimum_version = $data['minimum_version'];
        $appVersion->app_url = $data['app_url'];
        $appVersion->force_update = (bool) ($data['force_update'] ?? false);

        return $appVersion->save();
    }

    public function deleteAppVersion(int $id)
    {
        $appVersion = AppVersion::findOrFail($id);

        $platform = strtoupper($appVersion->platform);

        $appVersion->delete();

        return [
            'success' => true,
            'platform' => $platform,
        ];
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
        return $this->externalDatabase->countCourses();
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
            ->paginate(5, ['*'], 'notifications_page');
    }

    public function getApplicationStatus(): array
    {

        $settings = SystemSetting::query()->orderBy('id')->get();

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

    public function getTimetable($facultyCode, $majorCode, $batch, $semester, $ttid)
    {
        // stud_id is not actually used in the getStudentTimetable method for the queries,
        // but we need to pass something. We'll pass 0 as a placeholder.
        return $this->externalDatabase->getStudentTimetable(
            0, // stud_id - placeholder, not used in queries
            $facultyCode,
            $majorCode,
            $batch,
            $semester,

        );
    }

    //Studnets Start
    // Students Start

    public function getStudents(array $filters): LengthAwarePaginator
    {

        $query = User::query()->where('role_id', Helper::STUDENT_ROLE)->withCount('devices')->withMax('devices', 'last_seen_at');

        /*
        |--------------------------------------------------------------------------
        | Search: Student Index / Name / Email
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['search'])) {

            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {

                $q->where('stud_index', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");

            });
        }

        /*
        |--------------------------------------------------------------------------
        | Faculty
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['faculty_code'])) {

            $query->where(
                'faculty_code',
                $filters['faculty_code']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Major
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['major_code'])) {

            $query->where(
                'major_code',
                $filters['major_code']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Batch
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['batch'])) {

            $query->where(
                'batch',
                $filters['batch']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Semester
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['semester'])) {

            $query->where(
                'semester',
                $filters['semester']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Account Status
        |--------------------------------------------------------------------------
        |
        | 1 = Active
        | 0 = Inactive
        |
        | Uses users.is_active, NOT system_settings.api_active.
        |
        */

        if (
            isset($filters['status']) &&
            $filters['status'] !== ''
        ) {

            $query->where(
                'is_active',
                (bool) $filters['status']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Students
        |--------------------------------------------------------------------------
        */

        $students = $query
            ->orderBy('name')
            ->paginate(
                10,
                ['*'],
                'students_page'
            )
            ->appends(request()->query());

        /*
        |--------------------------------------------------------------------------
        | Faculty / Major Data
        |--------------------------------------------------------------------------
        */

        $faculties = $this->externalDatabase
            ->faculties()
            ->keyBy('faculty_code');

        $majors = $this->externalDatabase
            ->majors()
            ->keyBy('major_code');

        /*
        |--------------------------------------------------------------------------
        | Add Display Information
        |--------------------------------------------------------------------------
        */

        foreach ($students->items() as $student) {

            /*
            |--------------------------------------------------------------------------
            | Faculty
            |--------------------------------------------------------------------------
            */

            $faculty = $faculties->get(
                $student->faculty_code
            );

            $student->faculty_desc_e = $faculty->faculty_desc_e
                ?? $student->faculty_code;


            /*
            |--------------------------------------------------------------------------
            | Major
            |--------------------------------------------------------------------------
            */

            $major = $majors->get(
                $student->major_code
            );

            $student->major_desc_e = $major->major_desc_e
                ?? $student->major_code;


            /*
            |--------------------------------------------------------------------------
            | Account Status
            |--------------------------------------------------------------------------
            */

            $student->account_active = (bool) $student->is_active;

            $student->account_status = $student->is_active ? 'Active' : 'Inactive';
        }

        return $students;
    }

    // Students End

    public function getStudentBatches(): Collection
    {
        return User::query()
            ->where('role_id', Helper::STUDENT_ROLE)
            ->whereNotNull('batch')
            ->where('batch', '!=', '')
            ->select('batch')
            ->distinct()
            ->orderBy('batch')
            ->pluck('batch');
    }

    public function getStudentDetails(User $student): User
    {
        abort_unless($student->role_id === Helper::STUDENT_ROLE, 404);


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

        // Student Desk account status
        $student->account_active = Helper::getStudentAccountStatus($student);


        return $student;
    }

    public function updateStudentStatus(User $student, bool $isActive): void
    {
        abort_unless($student->role_id === Helper::STUDENT_ROLE, 404);

        SystemSetting::updateOrCreate(
            [
                'faculty_code' => $student->faculty_code,
                'major_code' => $student->major_code,
                'batch' => $student->batch,
                'semester' => $student->semester,
            ],
            [
                'api_active' => $isActive,
                'fee_active' => $isActive,
                'result_active' => $isActive,
                'timetable_active' => $isActive,
            ]
        );
    }

    public function toggleStudentAccountStatus(int $studentId): array
    {

        $user = User::where('id', $studentId)->where('role_id', Helper::STUDENT_ROLE)->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Student account not found.',
            ];
        }

        $newStatus = !$user->is_active;

        $user->is_active = $newStatus;
        $user->save();

        // Delete ALL Sanctum tokens = force logout from all devices
        $user->tokens()->delete();

        return [
            'success' => true,
            'is_active' => $newStatus,
            'message' => $newStatus
                ? 'Student account enabled successfully.'
                : 'Student account disabled successfully.',
        ];
    }

    public function forceLogout(int $studentId)
    {
        return DB::transaction(function () use ($studentId) {

            $user = User::where('id', $studentId)->where('role_id', Helper::STUDENT_ROLE)->first();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Student account not found.',
                ];
            }

            // Disable the student account
            // $user->is_active = false;
            // $user->save();

            // Delete ALL Sanctum tokens = force logout from all devices
            $user->tokens()->delete();

            return [
                'success' => true,
                'is_active' => false,
                'message' => 'Student account disabled and logged out from all devices successfully.',
            ];
        });
    }

    public function forceLogoutAll()
    {
        return DB::transaction(function () {

            $students = User::where('role_id', Helper::STUDENT_ROLE)->get();

            if ($students->isEmpty()) {
                return [
                    'success' => false,
                    'message' => 'No student accounts found.',
                ];
            }

            $studentIds = $students->pluck('id');

            /*
            |--------------------------------------------------------------------------
            | Disable all student accounts
            |--------------------------------------------------------------------------
            */

            // User::whereIn('id', $studentIds)
            //     ->update([
            //         'is_active' => false,
            //     ]);

            /*
            |--------------------------------------------------------------------------
            | Delete all Sanctum tokens
            |--------------------------------------------------------------------------
            */

            $tokenCount = DB::table('personal_access_tokens')->where('tokenable_type', User::class)->whereIn('tokenable_id', $studentIds)->delete();

            return [
                'success' => true,
                'message' => $students->count()
                    . ' student account(s) disabled and '
                    . $tokenCount
                    . ' login token(s) deleted successfully.',
            ];
        });

    }
    public function getReports(array $filters = []): array
    {
        /*
        |--------------------------------------------------------------------------
        | Date Range
        |--------------------------------------------------------------------------
        */

        $dateFrom = !empty($filters['date_from'])
            ? Carbon::parse($filters['date_from'])->startOfDay()
            : now()->startOfMonth();

        $dateTo = !empty($filters['date_to'])
            ? Carbon::parse($filters['date_to'])->endOfDay()
            : now()->endOfDay();


        /*
        |--------------------------------------------------------------------------
        | Student Query
        |--------------------------------------------------------------------------
        */

        $studentQuery = User::query()
            ->where('role_id', Helper::STUDENT_ROLE);


        if (!empty($filters['faculty_code'])) {
            $studentQuery->where(
                'faculty_code',
                $filters['faculty_code']
            );
        }


        if (!empty($filters['major_code'])) {
            $studentQuery->where(
                'major_code',
                $filters['major_code']
            );
        }


        if (!empty($filters['batch'])) {
            $studentQuery->where(
                'batch',
                $filters['batch']
            );
        }


        if (!empty($filters['semester'])) {
            $studentQuery->where(
                'semester',
                $filters['semester']
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Basic Student Statistics
        |--------------------------------------------------------------------------
        */

        $totalStudents = (clone $studentQuery)->count();


        /*
        |--------------------------------------------------------------------------
        | System Settings
        |--------------------------------------------------------------------------
        */

        $settingsQuery = SystemSetting::query();


        if (!empty($filters['faculty_code'])) {
            $settingsQuery->where(
                'faculty_code',
                $filters['faculty_code']
            );
        }


        if (!empty($filters['major_code'])) {
            $settingsQuery->where(
                'major_code',
                $filters['major_code']
            );
        }


        if (!empty($filters['batch'])) {
            $settingsQuery->where(
                'batch',
                $filters['batch']
            );
        }


        if (!empty($filters['semester'])) {
            $settingsQuery->where(
                'semester',
                $filters['semester']
            );
        }


        $settings = $settingsQuery->get();


        /*
        |--------------------------------------------------------------------------
        | Application Status
        |--------------------------------------------------------------------------
        */

        $activeSettings = $settings
            ->where('api_active', true)
            ->count();

        $inactiveSettings = $settings
            ->where('api_active', false)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Student Account Status
        |
        | Status is determined from SystemSetting.api_active
        |--------------------------------------------------------------------------
        */

        $activeStudents = 0;
        $disabledStudents = 0;

        $settingsMap = $settings->keyBy(function ($setting) {

            return implode('|', [
                $setting->faculty_code,
                $setting->major_code,
                $setting->batch,
                $setting->semester,
            ]);
        });


        foreach ((clone $studentQuery)->get() as $student) {

            $key = implode('|', [
                $student->faculty_code,
                $student->major_code,
                $student->batch,
                $student->semester,
            ]);

            $setting = $settingsMap->get($key);

            if ($setting && (bool) $setting->api_active) {
                $activeStudents++;
            } else {
                $disabledStudents++;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Faculty Distribution
        |--------------------------------------------------------------------------
        */

        $facultyDistribution = (clone $studentQuery)
            ->selectRaw('faculty_code, COUNT(*) as total')
            ->groupBy('faculty_code')
            ->orderByDesc('total')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Major Distribution
        |--------------------------------------------------------------------------
        */

        $majorDistribution = (clone $studentQuery)
            ->selectRaw('major_code, COUNT(*) as total')
            ->groupBy('major_code')
            ->orderByDesc('total')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Batch Distribution
        |--------------------------------------------------------------------------
        */

        $batchDistribution = (clone $studentQuery)
            ->selectRaw('batch, COUNT(*) as total')
            ->groupBy('batch')
            ->orderBy('batch')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Semester Distribution
        |--------------------------------------------------------------------------
        */

        $semesterDistribution = (clone $studentQuery)
            ->selectRaw('semester, COUNT(*) as total')
            ->groupBy('semester')
            ->orderBy('semester')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Devices
        |--------------------------------------------------------------------------
        */

        $deviceQuery = UserDevice::query()
            ->whereHas('user', function ($query) use ($filters) {

                $query->where('role_id', Helper::STUDENT_ROLE);

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
            });

        $totalDevices = (clone $deviceQuery)->count();

        $activeDevices = (clone $deviceQuery)
            ->where('is_active', true)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Device Types
        |--------------------------------------------------------------------------
        */

        $deviceTypes = (clone $deviceQuery)
            ->selectRaw(
                "COALESCE(device_type, 'Unknown') as device_type, COUNT(*) as total"
            )
            ->groupBy('device_type')
            ->orderByDesc('total')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        $notificationQuery = Notification::query()
            ->whereBetween('created_at', [
                $dateFrom,
                $dateTo,
            ]);


        $totalNotifications = (clone $notificationQuery)->count();

        $readNotifications = (clone $notificationQuery)
            ->whereNotNull('read_at')
            ->count();

        $unreadNotifications = (clone $notificationQuery)
            ->whereNull('read_at')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Notification Types
        |--------------------------------------------------------------------------
        */

        $notificationTypes = (clone $notificationQuery)
            ->selectRaw(
                "COALESCE(type, 'general') as type, COUNT(*) as total"
            )
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Visitors
        |--------------------------------------------------------------------------
        */

        $visitorsToday = Visitor::whereDate(
            'created_at',
            today()
        )->count();

        $visitorsMonth = Visitor::whereYear(
            'created_at',
            now()->year
        )
            ->whereMonth(
                'created_at',
                now()->month
            )
            ->count();

        $visitorsTotal = Visitor::count();


        /*
        |--------------------------------------------------------------------------
        | Visitor Activity
        |--------------------------------------------------------------------------
        */

        $visitorActivity = collect();

        for ($i = 6; $i >= 0; $i--) {

            $date = now()->subDays($i);

            $visitorActivity->push([
                'date' => $date->format('d M'),
                'total' => Visitor::whereDate(
                    'created_at',
                    $date->toDateString()
                )->count(),
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Recent Student Activity
        |--------------------------------------------------------------------------
        */

        $recentActivity = UserDevice::query()
            ->with([
                'user:id,name,stud_index,faculty_code,major_code,batch,semester',
            ])
            ->whereNotNull('last_seen_at')
            ->whereHas('user', function ($query) use ($filters) {

                $query->where('role_id', Helper::STUDENT_ROLE);

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
            })->orderByDesc('last_seen_at')->paginate(8, ['*'], 'activity_page')->appends(request()->except('activity_page'));


        /*
        |--------------------------------------------------------------------------
        | Return Report
        |--------------------------------------------------------------------------
        */

        return [
            'students' => [
                'total' => $totalStudents,
                'active' => $activeStudents,
                'disabled' => $disabledStudents,
            ],

            'application' => [
                'total' => $settings->count(),
                'active' => $activeSettings,
                'inactive' => $inactiveSettings,

                'fee_active' => $settings
                    ->where('fee_active', true)
                    ->count(),

                'result_active' => $settings
                    ->where('result_active', true)
                    ->count(),

                'timetable_active' => $settings
                    ->where('timetable_active', true)
                    ->count(),
            ],

            'faculty_distribution' => $facultyDistribution,
            'major_distribution' => $majorDistribution,
            'batch_distribution' => $batchDistribution,
            'semester_distribution' => $semesterDistribution,

            'devices' => [
                'total' => $totalDevices,
                'active' => $activeDevices,
            ],

            'device_types' => $deviceTypes,

            'notifications' => [
                'total' => $totalNotifications,
                'read' => $readNotifications,
                'unread' => $unreadNotifications,
            ],

            'notification_types' => $notificationTypes,

            'visitors' => [
                'today' => $visitorsToday,
                'month' => $visitorsMonth,
                'total' => $visitorsTotal,
            ],

            'visitor_activity' => $visitorActivity,

            'recent_activity' => $recentActivity,
        ];
    }
    public function getPushedNotifications()
    {
        return Notification::with('user')
            ->latest('created_at')
            ->paginate(10);
    }

    public function getMoodlePasswordByUsername(string $username)
    {
        $moodleStudent = $this->externalDatabase->getMoodleStudent($username);

        return $moodleStudent ? $moodleStudent->password : null;
    }

    public function updateUserPasswordByStudIndex(string $studIndex, string $password): int
    {
        return User::where('stud_index', $studIndex)->update(['password' => $password]);
    }

    //Timetable Start

    public function getTimetableCourses(int $facultyCode, int $majorCode, string $batch, int $semester)
    {
        return $this->externalDatabase->getTimetableCourses($facultyCode, $majorCode, $batch, $semester);
    }

    public function getTimetableInstructors()
    {
        return $this->externalDatabase->getTimetableInstructors();
    }

    public function getTimetableClassrooms()
    {
        return $this->externalDatabase->getTimetableClassrooms();
    }

    public function getTimetableTimes()
    {
        return $this->externalDatabase->getTimetableTimes();
    }

    public function createTimeTable(array $data): array
    {
        return $this->externalDatabase->createTimeTable($data);
    }

    public function syncTimetableData(string $facultyCode, string $majorCode, string $batch, int $semester, int $ttid): array
    {

        /*
        |--------------------------------------------------------------------------
        | Runtime Settings
        |--------------------------------------------------------------------------
        */

        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $startTime = microtime(true);

        $currentStep = 'Initialization';


        /*
        |--------------------------------------------------------------------------
        | Server Configuration
        |--------------------------------------------------------------------------
        */

        $configFile = storage_path(
            'app/config/server_config.json'
        );

        $serverIp = 'unknown';

        if (File::exists($configFile)) {

            $configData = json_decode(
                File::get($configFile),
                true
            );

            if (
                is_array($configData) &&
                !empty($configData['server_ip'])
            ) {
                $serverIp = trim($configData['server_ip']);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Server Configuration
        |--------------------------------------------------------------------------
        */

        if ($serverIp === 'unknown') {

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Local timetable server is not configured.',
                'server_ip' => $serverIp,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Local Timetable API
        |--------------------------------------------------------------------------
        */

        $url = 'http://' . $serverIp . '/ott/api/index.php';

        try {

            $response = Http::timeout(30)
                ->get($url, [
                    'faculty_code' => $facultyCode,
                    'major_code' => $majorCode,
                    'batch' => $batch,
                    'semester' => $semester,
                    'ttid' => $ttid,
                ]);




        } catch (Throwable $e) {

            Log::error('Local Timetable Server Connection Failed', [
                'message' => $e->getMessage(),
                'server_ip' => $serverIp,
                'url' => $url,
                'faculty_code' => $facultyCode,
                'major_code' => $majorCode,
                'batch' => $batch,
                'semester' => $semester,
                'ttid' => $ttid,
            ]);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Unable to connect to the local timetable server.',
                'server_ip' => $serverIp,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Validate HTTP Response
        |--------------------------------------------------------------------------
        */

        if (!$response->successful()) {

            Log::error('Local Timetable Server Returned HTTP Error', [
                'status' => $response->status(),
                'server_ip' => $serverIp,
                'url' => $url,
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Local timetable server returned HTTP status '
                    . $response->status() . '.',
                'server_ip' => $serverIp,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Decode JSON Response
        |--------------------------------------------------------------------------
        */

        $apiResponse = $response->json();


        // dd($apiResponse);

        if (!is_array($apiResponse)) {

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Invalid response received from local timetable server.',
                'server_ip' => $serverIp,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Check API Status
        |--------------------------------------------------------------------------
        */

        if (
            ($apiResponse['status'] ?? null) !== 'success' ||
            (int) ($apiResponse['code'] ?? 0) !== 200
        ) {

            Log::warning('Local Timetable API Returned Error', [
                'server_ip' => $serverIp,
                'response' => $apiResponse,
            ]);

            return [
                'success' => false,
                'code' => (int) ($apiResponse['code'] ?? 500),
                'message' => $apiResponse['message']
                    ?? 'Local timetable server returned an error.',
                'server_ip' => $serverIp,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Extract Local Server Data
        |--------------------------------------------------------------------------
        */

        $localData = $apiResponse['LocalServerData'] ?? null;

        if (!is_array($localData)) {

            return [
                'success' => false,
                'code' => 500,
                'message' => 'Local timetable server returned no timetable data.',
                'server_ip' => $serverIp,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Start mysql_ott Transaction
        |--------------------------------------------------------------------------
        */

        try {

            $this->externalDatabase->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | 1. Lab Timetable
            |--------------------------------------------------------------------------
            */

            $currentStep = 'Lab Timetable';

            $labTimetableData =
                $localData['labTimetableDetails'] ?? [];

            $labCount = $this->externalDatabase->upsertTable(
                'lab_timetable',
                $labTimetableData,
                ['Id']
            );

            Log::info('Lab Timetable Sync Completed', [
                'count' => $labCount,
                'faculty_code' => $facultyCode,
                'major_code' => $majorCode,
                'batch' => $batch,
                'semester' => $semester,
                'ttid' => $ttid,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 2. Classrooms
            |--------------------------------------------------------------------------
            */

            $currentStep = 'Classrooms';

            $classroomsData =
                $localData['classRoomDetails'] ?? [];

            $classroomsCount = $this->externalDatabase->upsertTable(
                'tbl_classrooms',
                $classroomsData,
                ['Class_ID']
            );

            Log::info('Classrooms Sync Completed', [
                'count' => $classroomsCount,
                'faculty_code' => $facultyCode,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 3. Courses
            |--------------------------------------------------------------------------
            */

            $currentStep = 'Courses';

            $coursesData =
                $localData['courseDetails'] ?? [];

            $coursesCount = $this->externalDatabase->upsertTable(
                'tbl_courses',
                $coursesData,
                ['Id']
            );

            Log::info('Courses Sync Completed', [
                'count' => $coursesCount,
                'faculty_code' => $facultyCode,
                'major_code' => $majorCode,
                'batch' => $batch,
                'semester' => $semester,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 4. Instructors
            |--------------------------------------------------------------------------
            */

            $currentStep = 'Instructors';

            $instructorsData =
                $localData['instructorDetails'] ?? [];

            $instructorsCount = $this->externalDatabase->upsertTable(
                'tbl_instructors',
                $instructorsData,
                ['Instructor_ID']
            );

            Log::info('Instructors Sync Completed', [
                'count' => $instructorsCount,
                'faculty_code' => $facultyCode,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 5. Setting Timetable
            |--------------------------------------------------------------------------
            */

            $currentStep = 'Setting Timetable';

            $settingTimetableData =
                $localData['timetableDetails'] ?? [];

            $settingCount = $this->externalDatabase->upsertTable(
                'tbl_setting_timetable',
                $settingTimetableData,
                ['Id']
            );

            Log::info('Setting Timetable Sync Completed', [
                'count' => $settingCount,
                'faculty_code' => $facultyCode,
                'major_code' => $majorCode,
                'batch' => $batch,
                'ttid' => $ttid,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 6. Tim
            |--------------------------------------------------------------------------
            */

            $currentStep = 'Tim';

            $timData =
                $localData['timeDetails'] ?? [];

            $timCount = $this->externalDatabase->upsertTable(
                'tim',
                $timData,
                ['id']
            );

            Log::info('Tim Sync Completed', [
                'count' => $timCount,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 7. Timetables / Seasons
            |--------------------------------------------------------------------------
            */

            $currentStep = 'Timetables';

            $timetablesData =
                $localData['seasonDetails'] ?? [];

            $timetablesCount = $this->externalDatabase->upsertTable(
                'timetables',
                $timetablesData,
                ['TTID']
            );

            Log::info('Timetables Sync Completed', [
                'count' => $timetablesCount,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Commit Transaction
            |--------------------------------------------------------------------------
            */

            $this->externalDatabase->commit();


            /*
            |--------------------------------------------------------------------------
            | Completed
            |--------------------------------------------------------------------------
            */

            $duration = round(
                microtime(true) - $startTime,
                2
            );

            Log::info('Timetable Synchronization Completed', [
                'duration' => $duration . ' sec',
                'server_ip' => $serverIp,
                'faculty_code' => $facultyCode,
                'major_code' => $majorCode,
                'batch' => $batch,
                'semester' => $semester,
                'ttid' => $ttid,
            ]);


            return [
                'success' => true,
                'code' => 200,
                'message' => 'Timetable synchronization completed successfully.',
                'duration' => $duration . ' sec',
                'server_ip' => $serverIp,

                'records' => [
                    'lab_timetable' => $labCount,
                    'tbl_classrooms' => $classroomsCount,
                    'tbl_courses' => $coursesCount,
                    'tbl_instructors' => $instructorsCount,
                    'tbl_setting_timetable' => $settingCount,
                    'tim' => $timCount,
                    'timetables' => $timetablesCount,
                ],

                'timetableData' => [
                    'timetableDetails' => $localData['timetableDetails'] ?? [],
                    'classRoomDetails' => $localData['classRoomDetails'] ?? [],
                    'courseDetails' => $localData['courseDetails'] ?? [],
                    'instructorDetails' => $localData['instructorDetails'] ?? [],
                    'labTimetableDetails' => $localData['labTimetableDetails'] ?? [],
                    'timeDetails' => $localData['timeDetails'] ?? [],
                    'seasonDetails' => $localData['seasonDetails'] ?? [],
                ],
            ];

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Rollback
            |--------------------------------------------------------------------------
            */

            if ($this->externalDatabase->transactionLevel() > 0) {
                $this->externalDatabase->rollBack();
            }


            /*
            |--------------------------------------------------------------------------
            | Log Error
            |--------------------------------------------------------------------------
            */

            Log::error('Timetable Synchronization Failed', [
                'step' => $currentStep,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'server_ip' => $serverIp,
                'faculty_code' => $facultyCode,
                'major_code' => $majorCode,
                'batch' => $batch,
                'semester' => $semester,
                'ttid' => $ttid,
            ]);


            return [
                'success' => false,
                'code' => 500,
                'failed_step' => $currentStep,
                'message' => $e->getMessage(),
                'server_ip' => $serverIp,
            ];
        }
    }

    public function getTimetableData(string $facultyCode, string $majorCode, string $batch, int $semester, int $ttid): array
    {
        return $this->externalDatabase->getTimetableData($facultyCode, $majorCode, $batch, $semester, $ttid);
    }

    public function getSavedTimetableConfigurations()
    {
        return $this->externalDatabase->getSavedTimetableConfigurations();
    }

    public function getSavedTimetableRows(int $facultyCode, int $majorCode, string $batch, int $ttid)
    {
        return $this->externalDatabase->getSavedTimetableRows($facultyCode, $majorCode, $batch, $ttid);
    }

    public function getTimetableSemester(int $facultyCode, int $majorCode, string $batch, int $ttid): ?int
    {
        return $this->externalDatabase->getTimetableSemester($facultyCode, $majorCode, $batch, $ttid);
    }

    public function replaceTimetable(int $facultyCode, int $majorCode, string $batch, int $ttid, array $rows): int
    {
        return $this->externalDatabase->replaceTimetable($facultyCode, $majorCode, $batch, $ttid, $rows);
    }

    public function deleteTimetable(int $facultyCode, int $majorCode, string $batch, int $ttid): int
    {
        return $this->externalDatabase->deleteTimetable($facultyCode, $majorCode, $batch, $ttid);
    }

    //Timetable End
}