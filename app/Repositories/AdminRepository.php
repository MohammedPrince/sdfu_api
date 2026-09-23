<?php

namespace App\Repositories;

use App\Helpers\Helper;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Visitor;
use App\Services\ExternalDatabaseService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

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
            $ttid
        );
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

                // No setting means the student is active by default.
                $student->account_active = true;
                $student->account_status = 'Active';

            } else {

                $student->account_active = (bool) $setting->api_active;
                $student->account_status = $setting->api_active
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
        abort_unless($student->role_id === 2, 404);

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

        // Student Desk account status
        $student->account_active = Helper::getStudentAccountStatus($student);

        return $student;
    }


    public function updateStudentStatus(User $student, bool $isActive): void
    {
        abort_unless($student->role_id === 2, 404);

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
            ->where('role_id', 2);


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

                $query->where('role_id', 2);

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

                $query->where('role_id', 2);

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


    public function syncTimetableData(
        string $facultyCode,
        string $majorCode,
        string $batch,
        int $semester,
        int $ttid
    ): array {

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






}