<?php

namespace App\Helpers;

use App\Models\AppVersion;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Visitor;
use App\Services\ExternalDatabaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class Helper
{
    //Roles
    public const ADMIN_ROLE = 1;
    public const STUDENT_ROLE = 2;

    public const STUD_IDS = [
        //Test indexs
        '202503001',
        '202503002',
    ];

    //Admin Panel Helpers
    public static function recordVisitor(): void
    {
        $sessionId = Request::session()->getId();

        $ip = Request::ip();

        $ipHash = $ip
            ? hash('sha256', $ip)
            : null;

        $exists = Visitor::where('session_id', $sessionId)
            ->whereDate('created_at', today())
            ->exists();

        if ($exists) {
            return;
        }

        Visitor::create([
            'session_id' => $sessionId,
            'ip_hash' => $ipHash,
            'user_agent' => substr(
                Request::userAgent() ?? '',
                0,
                500
            ),
            'page' => Request::path(),
        ]);
    }

    public static function visitorCount(): array
    {
        return cache()->remember(
            'visitor_counts',
            now()->addMinutes(5),
            function () {
                return [
                    'today' => Visitor::whereDate(
                        'created_at',
                        today()
                    )->count(),

                    'month' => Visitor::whereYear(
                        'created_at',
                        now()->year
                    )
                        ->whereMonth(
                            'created_at',
                            now()->month
                        )
                        ->count(),

                    'total' => Visitor::count(),
                ];
            }
        );
    }

    public static function getStudentAccountStatus(User $student): bool
    {
        $setting = SystemSetting::where('faculty_code', $student->faculty_code)
            ->where('major_code', $student->major_code)
            ->where('batch', $student->batch)
            ->where('semester', $student->semester)
            ->first();

        // No setting = Active by default
        return $setting
            ? (bool) $setting->api_active
            : true;
    }

    public static function studentToken(User $student): string
    {
        do {
            $token = Str::upper(Str::random(3));
        } while (
            cache()->has("student_token:{$token}")
        );

        cache()->put(
            "student_token:{$token}",
            $student->id,
            now()->addMinutes(30)
        );

        return $token;
    }

    public static function formatTimetableForDisplay(array $timetableData): string
    {
        /*
        |--------------------------------------------------------------------------
        | Extract data
        |--------------------------------------------------------------------------
        */

        $timetableDetails = $timetableData['timetableDetails'] ?? [];
        $timeDetails = $timetableData['timeDetails'] ?? [];
        $courseDetails = $timetableData['courseDetails'] ?? [];
        $instructorDetails = $timetableData['instructorDetails'] ?? [];
        $classRoomDetails = $timetableData['classRoomDetails'] ?? [];

        if (empty($timetableDetails)) {
            return '
            <div class="alert alert-info">
                No timetable data available for the selected criteria.
            </div>
        ';
        }

        /*
        |--------------------------------------------------------------------------
        | Build Course Lookup
        |--------------------------------------------------------------------------
        |
        | Course_Code => Course_Name
        |
        */

        $courses = [];

        foreach ($courseDetails as $course) {

            if (!is_array($course)) {
                continue;
            }

            $courseCode = trim((string) ($course['Course_Code'] ?? ''));

            if ($courseCode === '') {
                continue;
            }

            $courses[$courseCode] = trim(
                (string) ($course['Course_Name'] ?? '')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Build Instructor Lookup
        |--------------------------------------------------------------------------
        |
        | instructor_id => instructor_name
        |
        */

        $instructors = [];

        foreach ($instructorDetails as $instructor) {

            if (!is_array($instructor)) {
                continue;
            }

            $instructorId = (string) ($instructor['instructor_id'] ?? '');

            if ($instructorId === '') {
                continue;
            }

            $instructors[$instructorId] = trim(
                (string) ($instructor['instructor_name'] ?? '')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Build Classroom Lookup
        |--------------------------------------------------------------------------
        |
        | class_id => class_name
        |
        */

        $classrooms = [];

        foreach ($classRoomDetails as $room) {

            if (!is_array($room)) {
                continue;
            }

            $classId = (string) ($room['class_id'] ?? '');

            if ($classId === '') {
                continue;
            }

            $classrooms[$classId] = trim(
                (string) ($room['class_name'] ?? '')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Build Time Lookup
        |--------------------------------------------------------------------------
        |
        | tim.id => day + time
        |
        */

        $timeLookup = [];

        foreach ($timeDetails as $time) {

            if (!is_array($time)) {
                continue;
            }

            $timeId = (string) ($time['id'] ?? '');

            if ($timeId === '') {
                continue;
            }

            $dayName = trim((string) ($time['day_name'] ?? ''));
            $timeName = trim((string) ($time['time'] ?? ''));

            if (
                $dayName === '' ||
                $dayName === 'None' ||
                $timeName === '' ||
                $timeName === 'None'
            ) {
                continue;
            }

            $timeLookup[$timeId] = [
                'day' => $dayName,
                'time' => $timeName,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Day Order
        |--------------------------------------------------------------------------
        */

        $dayOrder = [
            'Saturday',
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
        ];

        /*
        |--------------------------------------------------------------------------
        | Build Grid
        |--------------------------------------------------------------------------
        */

        $grid = [];

        foreach ($dayOrder as $day) {
            $grid[$day] = [];

            foreach ($timeLookup as $slot) {

                if ($slot['day'] !== $day) {
                    continue;
                }

                $grid[$day][$slot['time']] = [];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Process Timetable Records
        |--------------------------------------------------------------------------
        */

        foreach ($timetableDetails as $class) {

            if (!is_array($class)) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Period = tim.id
            |--------------------------------------------------------------------------
            */

            $period = (string) ($class['Period'] ?? '');

            if (
                $period === '' ||
                !isset($timeLookup[$period])
            ) {
                continue;
            }

            $timeInfo = $timeLookup[$period];

            $day = $timeInfo['day'];
            $time = $timeInfo['time'];

            /*
            |--------------------------------------------------------------------------
            | Course
            |--------------------------------------------------------------------------
            */

            $courseCode = trim(
                (string) ($class['Course_Code'] ?? '')
            );

            $courseName = $courses[$courseCode] ?? '';

            /*
            |--------------------------------------------------------------------------
            | Instructor
            |--------------------------------------------------------------------------
            */

            $instructorId = (string) (
                $class['Instructor_ID'] ?? ''
            );

            $instructorName = $instructors[$instructorId] ?? '';

            /*
            |--------------------------------------------------------------------------
            | Classroom
            |--------------------------------------------------------------------------
            */

            $classId = (string) (
                $class['ClassID'] ?? ''
            );

            $className = $classrooms[$classId] ?? '';

            /*
            |--------------------------------------------------------------------------
            | Student Group
            |--------------------------------------------------------------------------
            */

            $group = match ((int) ($class['Stud_Group'] ?? 0)) {
                1 => 'A',
                2 => 'B',
                3 => 'C',
                default => '',
            };

            /*
            |--------------------------------------------------------------------------
            | Class Information
            |--------------------------------------------------------------------------
            */

            $classInfo = [
                'course_code' => $courseCode,
                'course_name' => $courseName,

                'instructor' => $instructorName,
                'instructor_id' => $instructorId,

                'room' => $className,
                'room_id' => $classId,

                'group' => $group,

                'theory_hrs' => $class['TheoryHrs'] ?? null,
                'tutorial_hrs' => $class['TutorialHrs'] ?? null,
                'practical_hrs' => $class['PracticalHrs'] ?? null,
            ];

            /*
            |--------------------------------------------------------------------------
            | Add Main Period
            |--------------------------------------------------------------------------
            */

            if (!isset($grid[$day][$time])) {
                $grid[$day][$time] = [];
            }

            $grid[$day][$time][] = $classInfo;

            /*
            |--------------------------------------------------------------------------
            | Second Period
            |--------------------------------------------------------------------------
            */

            $period2 = (string) ($class['Period2'] ?? '');

            if (
                $period2 !== '' &&
                isset($timeLookup[$period2])
            ) {

                $timeInfo2 = $timeLookup[$period2];

                $day2 = $timeInfo2['day'];
                $time2 = $timeInfo2['time'];

                if (!isset($grid[$day2][$time2])) {
                    $grid[$day2][$time2] = [];
                }

                $grid[$day2][$time2][] = $classInfo;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Generate HTML
        |--------------------------------------------------------------------------
        */

        $html = '<div class="timetable-table-responsive">';

        $html .= '<table class="table table-bordered timetable-grid">';

        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $html .= '<thead>';
        $html .= '<tr>';

        $html .= '
        <th class="time-slot-header">
            Time / Day
        </th>
    ';

        foreach ($dayOrder as $day) {

            $html .= '<th class="day-header">'
                . e($day)
                . '</th>';
        }

        $html .= '</tr>';
        $html .= '</thead>';

        /*
        |--------------------------------------------------------------------------
        | Body
        |--------------------------------------------------------------------------
        */

        $html .= '<tbody>';

        /*
        | Get unique time slots in database order
        */

        $displayTimes = [];

        foreach ($timeDetails as $time) {

            if (!is_array($time)) {
                continue;
            }

            $dayName = trim((string) ($time['day_name'] ?? ''));
            $timeName = trim((string) ($time['time'] ?? ''));

            if (
                $dayName === '' ||
                $dayName === 'None' ||
                $timeName === '' ||
                $timeName === 'None'
            ) {
                continue;
            }

            if (!in_array($timeName, $displayTimes, true)) {
                $displayTimes[] = $timeName;
            }
        }

        foreach ($displayTimes as $timeSlot) {

            $html .= '<tr>';

            $html .= '
            <td class="time-slot fw-bold">
                ' . e($timeSlot) . '
            </td>
        ';

            foreach ($dayOrder as $day) {

                $classes = $grid[$day][$timeSlot] ?? [];

                $html .= '<td class="timetable-cell">';

                if (!empty($classes)) {

                    foreach ($classes as $class) {

                        $html .= '<div class="timetable-class">';

                        /*
                        | Course Code
                        */

                        $html .= '<div class="timetable-course">';
                        $html .= e($class['course_code']);
                        $html .= '</div>';

                        /*
                        | Course Name
                        */

                        if ($class['course_name'] !== '') {

                            $html .= '<div class="timetable-course-name">';
                            $html .= e($class['course_name']);
                            $html .= '</div>';
                        }

                        /*
                        | Group
                        */

                        if ($class['group'] !== '') {

                            $html .= '<div class="timetable-detail">';
                            $html .= '<strong>Group:</strong> '
                                . e($class['group']);
                            $html .= '</div>';
                        }

                        /*
                        | Instructor
                        */

                        if ($class['instructor'] !== '') {

                            $html .= '<div class="timetable-detail">';
                            $html .= '<strong>Instructor:</strong> '
                                . e($class['instructor']);
                            $html .= '</div>';
                        }

                        /*
                        | Classroom
                        */

                        if ($class['room'] !== '') {

                            $html .= '<div class="timetable-detail">';
                            $html .= '<strong>Classroom:</strong> '
                                . e($class['room']);
                            $html .= '</div>';
                        }

                        $html .= '</div>';
                    }

                } else {

                    $html .= '<span class="empty-slot">—</span>';
                }

                $html .= '</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        return $html;
    }

    public static function canAccessAdminMenu(string $menu): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Only role 1 is allowed to use admin menu permissions
        |--------------------------------------------------------------------------
        */
        if ((int) $user->role_id !== 1) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Check permissions for THIS user
        |--------------------------------------------------------------------------
        */
        return $user->adminPermissions()
            ->where('menu_key', $menu)
            ->where('can_view', true)
            ->exists();
    }

    //API Helpers
    public static function authenticatedStudent(): array
    {
        if (!Auth::check() || !Auth::user()) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated',
            ];
        }

        return [
            'success' => true,
            'code' => 200,
            'user' => Auth::user(),
        ];
    }

    public static function studentDumpTestData()
    {

        $semesterResult = [
            'semester' => 8,
            'gpa' => '1.98',
            'cgpa' => '2.78',
            'status' => 'Normal',
            'courses' => [
                [
                    'course_code' => 'BABF103',
                    'course_name' => 'Advanced Financial Management',
                    'course_units' => 3,
                    'grade' => 'B',
                    'points' => '3.00',
                    'remark' => '',
                    'result_status' => 0,
                ],
                [
                    'course_code' => 'BABM404',
                    'course_name' => 'Business Ethics and Social Responsibility',
                    'course_units' => 3,
                    'grade' => 'D+',
                    'points' => '1.60',
                    'remark' => '',
                    'result_status' => 0,
                ],
                [
                    'course_code' => 'BABM412',
                    'course_name' => 'Scientific Research Methodology',
                    'course_units' => 3,
                    'grade' => 'Z',
                    'points' => '0.00',
                    'remark' => '',
                    'result_status' => 0,
                ],
                [
                    'course_code' => 'BABM414',
                    'course_name' => 'Strategic management',
                    'course_units' => 3,
                    'grade' => 'B+',
                    'points' => '3.33',
                    'remark' => '',
                    'result_status' => 0,
                ],
            ],
        ];

        $timetable = [
            'days' => [
                'Saturday' => [
                    [
                        'type' => 'lecture',
                        'course_code' => 'BSSM401X',
                        'course_name' => 'Mathematics I',
                        'stud_group' => 'A',
                        'time' => '03:00 PM - 05:00 PM',
                        'day' => 0,
                        'room' => '017 (Drawing Tables)',
                        'instructor_name' => 'Ahmed Abdo Shareef M.',
                        'time_tut' => null,
                        'day_tut' => null,
                        'room_tut' => '',
                        'instructor_name_tut' => '',
                        'period' => 4,
                        'period2' => null,
                    ],
                    [
                        'type' => 'lecture',
                        'course_code' => 'BABA301X',
                        'course_name' => 'Financial Accounting I',
                        'stud_group' => 'A',
                        'time' => '07:00 AM - 09:00 AM',
                        'day' => 0,
                        'room' => 'FOE Dean Office',
                        'instructor_name' => 'Afraa Mohmed Elraher Adam',
                        'time_tut' => null,
                        'day_tut' => null,
                        'room_tut' => '',
                        'instructor_name_tut' => '',
                        'period' => 1,
                        'period2' => null,
                    ],
                    [
                        'type' => 'lecture',
                        'course_code' => 'BABE501X',
                        'course_name' => 'Introduction to microeconomics',
                        'stud_group' => 'A',
                        'time' => '10:00 AM - 12:00 PM',
                        'day' => 0,
                        'room' => '017 (Drawing Tables)',
                        'instructor_name' => 'Dr. Abdelaziz Mahmoud Abdelmageed',
                        'time_tut' => null,
                        'day_tut' => null,
                        'room_tut' => '',
                        'instructor_name_tut' => '',
                        'period' => 2,
                        'period2' => null,
                    ],
                ],

                'Sunday' => [
                    [
                        'type' => 'lecture',
                        'course_code' => 'CSSW101X',
                        'course_name' => 'Computer Science I (Introduction to Computer Studies 1)',
                        'stud_group' => 'A',
                        'time' => '03:00 PM - 05:00 PM',
                        'day' => 1,
                        'room' => '114',
                        'instructor_name' => 'Ahmed Abdo Shareef M.',
                        'time_tut' => null,
                        'day_tut' => null,
                        'room_tut' => '',
                        'instructor_name_tut' => '',
                        'period' => 8,
                        'period2' => null,
                    ],
                    [
                        'type' => 'lecture',
                        'course_code' => 'BSHA100X',
                        'course_name' => 'Arabic Language I',
                        'stud_group' => 'A',
                        'time' => '10:00 AM - 12:00 PM',
                        'day' => 1,
                        'room' => '119',
                        'instructor_name' => 'Dr. Abdalrahman Badwai',
                        'time_tut' => null,
                        'day_tut' => null,
                        'room_tut' => '',
                        'instructor_name_tut' => '',
                        'period' => 6,
                        'period2' => null,
                    ],
                ],

                'Monday' => [
                    [
                        'type' => 'lecture',
                        'course_code' => 'BSHA100X',
                        'course_name' => 'Arabic Language I',
                        'stud_group' => 'A',
                        'time' => '07:00 AM - 09:00 AM',
                        'day' => 2,
                        'room' => '206',
                        'instructor_name' => 'ASIS.NAWAL IBRAHIM IDRIS',
                        'time_tut' => null,
                        'day_tut' => null,
                        'room_tut' => '',
                        'instructor_name_tut' => '',
                        'period' => 9,
                        'period2' => null,
                    ],
                    [
                        'type' => 'lecture',
                        'course_code' => 'BABA301X',
                        'course_name' => 'Financial Accounting I',
                        'stud_group' => 'A',
                        'time' => '12:30 PM - 02:30 PM',
                        'day' => 2,
                        'room' => '121',
                        'instructor_name' => 'Dr. Fadalla Berir',
                        'time_tut' => null,
                        'day_tut' => null,
                        'room_tut' => '',
                        'instructor_name_tut' => '',
                        'period' => 11,
                        'period2' => null,
                    ],
                ],

                'Thursday' => [
                    [
                        'type' => 'lecture',
                        'course_code' => 'BABE501X',
                        'course_name' => 'Introduction to microeconomics',
                        'stud_group' => 'A',
                        'time' => '07:00 AM - 09:00 AM',
                        'day' => 5,
                        'room' => '122',
                        'instructor_name' => 'Dr. Marwan Mustafa',
                        'time_tut' => null,
                        'day_tut' => null,
                        'room_tut' => '',
                        'instructor_name_tut' => '',
                        'period' => 21,
                        'period2' => null,
                    ],
                ],
            ],
        ];

        $feeDetails = [
            'total_fees' => 1550000,
            'fees_type' => 'Registration Fee',
            'end_date' => '2026-08-14',
            'days_remaining' => 0,
            'registration_closed' => true,
            'status' => 'Registration is closed.',
            'paid' => true,
        ];

        return [
            'semesterResult' => $semesterResult,
            'feeDetails' => $feeDetails,
            'timetable' => $timetable,
        ];
    }

    public static function buildTestResponse(
        $dumpData,
        $studentHelper,
        ExternalDatabaseService $externalDatabase,
        $platform = null,
        $currentAppVersion = null,
        $apiType
    ) {
        $appVersion = [];

        $studentData = [
            'stud_id' => $studentHelper['stud_id'],
            'student_name' => $studentHelper['stud_full_name'],
            'email' => $studentHelper['email'] ?? null,
            'phone' => $studentHelper['phone'] ?? null,
            'faculty_code' => $studentHelper['faculty_code'],
            'major_code' => $studentHelper['major_code'],
            'faculty' => $externalDatabase->getFacultyName($studentHelper['faculty_code']),
            'major' => $externalDatabase->getMajorName($studentHelper['major_code']),
            'batch' => $studentHelper['batch'],
            'semester' => (int) $studentHelper['semester'],
            'gender' => $studentHelper['gender'] ?? null,
        ];

        if ($apiType === 'mainData') {

            if (
                in_array($platform, ['ios', 'android'], true) &&
                $currentAppVersion !== ''
            ) {
                $versionConfig = AppVersion::where(
                    'platform',
                    $platform
                )->first();

                if ($versionConfig) {
                    $forceUpdate = version_compare(
                        $currentAppVersion,
                        $versionConfig->minimum_version,
                        '<'
                    ) && (bool) $versionConfig->force_update;

                    $appVersion = [
                        'platform' => $versionConfig->platform,
                        'minimum_version' => $versionConfig->minimum_version,
                        'app_url' => $versionConfig->app_url,
                        'force_update' => $forceUpdate,
                        'desc' => 'A new version of Student Desk app is available. Please update the app to continue using the latest features and improvements.',
                    ];
                }
            }
        }

        return match ($apiType) {

            'profile' => [
                'success' => true,
                'code' => 200,
                'message' => 'Profile Retrieved Successfully',
                'studentDetails' => $studentData,
            ],

            'timetable' => [
                'success' => true,
                'code' => 200,
                'message' => 'Timetable Retrieved Successfully',
                'timetableDetails' => $dumpData['timetable'],
            ],

            'fee' => [
                'success' => true,
                'code' => 200,
                'message' => 'Fee Details Retrieved Successfully',
                'feeDetails' => $dumpData['feeDetails'],
            ],

            'result' => [
                'success' => true,
                'code' => 200,
                'message' => 'Semester Result Retrieved Successfully',
                'semesterResult' => $dumpData['semesterResult'],
            ],

            'mainData' => [
                'success' => true,
                'code' => 200,
                'message' => 'Main Data Retrieved Successfully',
                'studentDetails' => $studentData,
                'semesterResult' => $dumpData['semesterResult'],
                'feeDetails' => $dumpData['feeDetails'],
                'timetable' => $dumpData['timetable'],
                'appStatus' => [
                    'active' => true,
                    'tabs_status' => [
                        'fee' => true,
                        'result' => true,
                        'timetable' => true,
                    ],
                    'notificationToggled' => UserDevice::where(
                        'user_id',
                        $studentHelper['id']
                    )->where('is_active', true)->exists(),
                ],
                'appVersion' => $appVersion,
            ],

            default => [
                'success' => false,
                'code' => 400,
                'message' => 'Invalid API type',
            ],
        };
    }

    public static function getFacultyAndMajorNames(int|string $faculty_code, int|string $major_code, ExternalDatabaseService $externalDatabase): array
    {

        // Cache faculty name for 24 hours
        $faculty = Cache::remember(
            'faculty_name_' . $faculty_code,
            1440,
            function () use ($faculty_code, $externalDatabase) {
                return $externalDatabase->getFacultyName($faculty_code);
            }
        );

        // Cache major name for 24 hours
        $major = Cache::remember(
            'major_name_' . $major_code,
            1440,
            function () use ($major_code, $externalDatabase) {
                return $externalDatabase->getMajorName($major_code);
            }
        );

        return [
            'faculty' => $faculty,
            'major' => $major,
        ];
    }

    public static function studentData(): ?array
    {
        if (!Auth::check() || !Auth::user()) {
            return null;
        }

        $user = Auth::user();

        return [
            'user' => $user,
            'id' => $user->id,
            'stud_id' => $user->stud_index,
            'stud_full_name' => $user->name,
            'password' => $user->password,

            'faculty_code' => $user->faculty_code,
            'major_code' => $user->major_code,

            'batch' => $user->batch,
            'semester' => $user->semester,
            //Personal
            'phone' => $user->phone ?? null,
            'email' => $user->email ?? null,
            'gender' => $user->gender ?? null,
            //Role
            'role_id' => $user->role_id ?? null,
            'is_active' => $user->is_active ?? null,
        ];
    }
    public static function authenticatedUser()
    {
        return Auth::check() ? Auth::user() : null;
    }

    public static function isAuthenticated(): bool
    {
        return Auth::check() && Auth::user() !== null;
    }

    public static function checkApplicationStatus(): array
    {

        if (!self::isAuthenticated()) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated',
            ];
        }

        $user = Auth::user();

        $settings = SystemSetting::where('faculty_code', $user->faculty_code)
            ->where('major_code', $user->major_code)
            ->where('batch', $user->batch)
            ->where('semester', $user->semester)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | No settings found
        |--------------------------------------------------------------------------
        | Application is active by default.
        |
        | Create an in-memory model only.
        | Nothing is saved to the database.
        |--------------------------------------------------------------------------
        */

        if (!$settings) {
            $settings = new SystemSetting([
                'api_active' => true,
                'fee_active' => true,
                'result_active' => true,
                'timetable_active' => true,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Application disabled
        |--------------------------------------------------------------------------
        */

        if (!(bool) $settings->api_active) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'Application is currently unavailable',
            ];
        }

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Application is active',
            'settings' => $settings,
        ];
    }

    public static function checkTabStatus(string $tab): array
    {

        if (!self::isAuthenticated()) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated',
            ];
        }

        $user = Auth::user();

        $settings = SystemSetting::where('faculty_code', $user->faculty_code)
            ->where('major_code', $user->major_code)
            ->where('batch', $user->batch)
            ->where('semester', $user->semester)
            ->first();

        // No settings found = application is active by default
        if (!$settings) {
            return [
                'success' => true,
                'code' => 200,
                'message' => 'Application is active',
                'settings' => [
                    'api_active' => true,
                    'fee_active' => true,
                    'result_active' => true,
                    'timetable_active' => true,
                ],
            ];
        }


        if (!(bool) $settings->api_active) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'Application is currently unavailable',
            ];
        }


        $tabs = [
            'fee' => (bool) $settings->fee_active,
            'result' => (bool) $settings->result_active,
            'timetable' => (bool) $settings->timetable_active,
        ];

        if (!array_key_exists($tab, $tabs)) {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'Invalid tab',
            ];
        }

        if (!$tabs[$tab]) {
            return [
                'success' => false,
                'code' => 403,
                'message' => ucfirst($tab) . ' is currently unavailable',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Result Maintenance Mode
        |--------------------------------------------------------------------------
        |
        | Even if result_active = 1 in system_settings,
        | maintenance_mode.maintenance_status = 1 will disable Result.
        |
        */

        if ($tab === 'result') {

            $externalDatabase = app(ExternalDatabaseService::class);

            if ($externalDatabase->resultMaintenanceMode()) {
                return [
                    'success' => false,
                    'code' => 403,
                    'message' => 'result is currently unavailable',
                ];
            }
        }

        return [
            'success' => true,
            'code' => 200,
            'message' => ucfirst($tab) . ' is active',
        ];
    }

}