<?php

namespace App\Repositories;

use App\Helpers\Helper;
use App\Models\AppVersion;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserDevice;
use App\Repositories\AdminRepository;
use App\Services\ExternalDatabaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;

use function CuyZ\Valinor\Compiler\return_;

class StudentRepository
{
    protected $externalDatabase;
    protected $adminRepository;

    public function __construct()
    {
        $this->externalDatabase = new ExternalDatabaseService();
        $this->adminRepository = new AdminRepository();
    }

    public function login($data)
    {

        $studIndex = trim($data['stud_index'] ?? '');
        $studPassword = $data['stud_password'] ?? '';

        $rateLimitKey = 'student-login:' . strtolower($studIndex) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {

            $seconds = RateLimiter::availableIn($rateLimitKey);
            $minutes = ceil($seconds / 60);

            return [
                'success' => false,
                'code' => 429,
                'message' => 'Too many login attempts. Please try again in ' . $minutes . ' minute(s).',
                'retry_after' => $seconds,
            ];
        }

        if (empty($studIndex) || empty($studPassword)) {

            RateLimiter::hit($rateLimitKey, 300);

            return [
                'success' => false,
                'code' => 400,
                'message' => 'Student Index and Password are required',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | 1. Check local User table first
        |--------------------------------------------------------------------------
        */

        $user = User::where('stud_index', $studIndex)->where('role_id', Helper::STUDENT_ROLE)->first();

        if ($user) {

            /*
       |--------------------------------------------------------------------------
       | Student exists locally
       |--------------------------------------------------------------------------
       */

            if (!$user->is_active) {

                return [
                    'success' => false,
                    'code' => 403,
                    'message' => 'Account has been deleted',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Student exists locally.
            | Authenticate ONLY against local password.
            |--------------------------------------------------------------------------
            */

            if (!Hash::check($studPassword, $user->password)) {

                RateLimiter::hit($rateLimitKey, 300);

                $attempts = RateLimiter::attempts($rateLimitKey);
                $remaining = max(0, 3 - $attempts);

                return [
                    'success' => false,
                    'code' => 401,
                    'message' => 'Invalid Student Index or Password',
                    'attempts_remaining' => $remaining,
                ];
            }

            // Local authentication successful.
            Auth::login($user);

            RateLimiter::clear($rateLimitKey);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Student does not exist locally -> check Moodle
        |--------------------------------------------------------------------------
        */ else {

            $student = $this->externalDatabase->getMoodleStudent($studIndex);

            if (!$student) {

                RateLimiter::hit($rateLimitKey, 300);

                return [
                    'success' => false,
                    'code' => 404,
                    'message' => 'Student Index Not Exists',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Verify Moodle password
            |--------------------------------------------------------------------------
            */

            if (!password_verify($studPassword, $student->password)) {

                RateLimiter::hit($rateLimitKey, 300);

                $attempts = RateLimiter::attempts($rateLimitKey);
                $remaining = max(0, 3 - $attempts);

                return [
                    'success' => false,
                    'code' => 401,
                    'message' => 'Invalid Student Index or Password',
                    'attempts_remaining' => $remaining,
                ];
            }

            RateLimiter::clear($rateLimitKey);

            /*
            |--------------------------------------------------------------------------
            | Get student profile from SIS
            |--------------------------------------------------------------------------
            */

            $studentDetails = $this->externalDatabase->getStudentDetails($studIndex);

            if (!$studentDetails) {

                return [
                    'success' => false,
                    'code' => 404,
                    'message' => 'Student Profile Not Found',
                ];
            }

            $stud_full_name = trim(
                $studentDetails->stud_name . ' ' .
                $studentDetails->stud_surname . ' ' .
                $studentDetails->familyname . ' ' .
                $studentDetails->lastName
            );

            $faculty_code = $studentDetails->faculty_code ?? null;
            $major_code = $studentDetails->major_code ?? null;
            $batch = $studentDetails->batch ?? null;
            $semester = (int) ($studentDetails->curr_sem ?? 0);

            $phone = $studentDetails->stud_tel_mobile ?? null;

            $gender = match ((int) ($studentDetails->sex_code ?? 0)) {
                1 => 'Male',
                2 => 'Female',
                3 => 'Other',
                default => null,
            };

            /*
            |--------------------------------------------------------------------------
            | Create local User
            |--------------------------------------------------------------------------
            */

            $user = User::create([
                'stud_index' => $studIndex,
                'name' => $stud_full_name,

                'email' => !empty($student->email)
                    ? $student->email
                    : null,

                'phone' => $phone,

                'faculty_code' => $faculty_code,
                'major_code' => $major_code,
                'batch' => $batch,
                'semester' => $semester,

                // Store Moodle's already-hashed password.
                'password' => $student->password,

                'gender' => $gender,

                'role_id' => Helper::STUDENT_ROLE,
            ]);

            Auth::login($user);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Application status
        |--------------------------------------------------------------------------
        */

        $applicationStatus = Helper::checkApplicationStatus();

        if (!$applicationStatus['success']) {
            return $applicationStatus;
        }

        $settings = $applicationStatus['settings'];

        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | 4. Student information
        |--------------------------------------------------------------------------
        */

        $stud_full_name = $user->name;
        $faculty_code = $user->faculty_code;
        $major_code = $user->major_code;
        $batch = $user->batch;
        $semester = $user->semester;
        $phone = $user->phone;
        $email = $user->email;
        $gender = $user->gender;

        $faculty_desc_e = $this->externalDatabase->getFacultyName($faculty_code);
        $major_desc_e = $this->externalDatabase->getMajorName($major_code);

        /*
        |--------------------------------------------------------------------------
        | 5. Create API token
        |--------------------------------------------------------------------------
        */

        $user->tokens()->delete();

        $expiresAt = Carbon::now()->addYear();

        $token = $user->createToken(
            'student-mobile-app',
            ['*'],
            $expiresAt
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Application status
        |--------------------------------------------------------------------------
        */

        $resultMaintenanceMode =
            $this->externalDatabase->resultMaintenanceMode();

        $notificationToggled = UserDevice::where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        $appStatus = [

            'active' => $settings
                ? (bool) $settings->api_active
                : true,

            'tabs_status' => [

                'fee' => $settings
                    ? (bool) $settings->fee_active
                    : true,

                'result' => (
                    $settings
                    ? (bool) $settings->result_active
                    : true
                ) && !$resultMaintenanceMode,

                'timetable' => $settings
                    ? (bool) $settings->timetable_active
                    : true,
            ],

            'notificationToggled' => $notificationToggled,
        ];

        /*
        |--------------------------------------------------------------------------
        | 7. Login response
        |--------------------------------------------------------------------------
        */

        $LoginDetails = [

            'stud_index' => $user->stud_index,
            'stud_full_name' => $stud_full_name,
            'stud_email' => $email,
            'stud_phone' => $phone,
            'faculty_code' => $faculty_code,
            'major_code' => $major_code,
            'faculty' => $faculty_desc_e,
            'major' => $major_desc_e,
            'batch' => $batch,
            'sem' => (int) $semester,
            'gender' => $gender,
            'token' => $token->plainTextToken,
            'token_expires_at' => $expiresAt->toISOString(),
            'app_status' => $appStatus,
        ];

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Login Successful',
            'studentDetails' => $LoginDetails,
        ];
    }

    public function mainData()
    {

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        $auth = Helper::authenticatedStudent();

        if (!$auth['success']) {
            return $auth;
        }

        /*
        |--------------------------------------------------------------------------
        | Initialize data
        |--------------------------------------------------------------------------
        */

        $studentData = [];
        $semesterResult = [];
        $feeDetails = [];
        $timetable = [];
        $appStatus = [];

        /*
        |--------------------------------------------------------------------------
        | Header data
        |--------------------------------------------------------------------------
        */

        $platform = strtolower(trim((string) request()->header('X-Platform', '')));
        $currentAppVersion = trim((string) request()->header('X-App-Version', ''));
        $appVersion = null;

        /*
        |--------------------------------------------------------------------------
        | Student information
        |--------------------------------------------------------------------------
        */

        $studentHelper = Helper::studentData();

        $user_id = $studentHelper['id'];
        $stud_id = $studentHelper['stud_id'];
        $stud_full_name = $studentHelper['stud_full_name'];

        $faculty_code = $studentHelper['faculty_code'];
        $major_code = $studentHelper['major_code'];

        $batch = $studentHelper['batch'];
        $semester = $studentHelper['semester'];

        $phone = $studentHelper['phone'];
        $email = $studentHelper['email'];
        $gender = $studentHelper['gender'];

        /*
        |--------------------------------------------------------------------------
        | Check application status
        |--------------------------------------------------------------------------
        */

        // Check application status in real-time as settings need to be checked for active/inactive status
        $applicationStatus = Helper::checkApplicationStatus();

        if (!$applicationStatus['success']) {
            return $applicationStatus;
        }

        $settings = $applicationStatus['settings'];

        /*
        |--------------------------------------------------------------------------
        | Date information
        |--------------------------------------------------------------------------
        */

        $current_date = now()->format('Y-m-d');
        $today = Carbon::today();

        /*
        |--------------------------------------------------------------------------
        | Student Profile
        |--------------------------------------------------------------------------
        |
        | Cache faculty and major information for 24 hours as they rarely change.
        |--------------------------------------------------------------------------
        */

        // Cache faculty name
        $faculty = Cache::remember(
            'faculty_name_' . $faculty_code,
            1440, // 24 hours
            function () use ($faculty_code) {
                return $this->externalDatabase->getFacultyName($faculty_code);
            }
        );

        // Cache major name
        $major = Cache::remember(
            'major_name_' . $major_code,
            1440, // 24 hours
            function () use ($major_code) {
                return $this->externalDatabase->getMajorName($major_code);
            }
        );

        $studentData = [
            'stud_id' => $stud_id,

            'student_name' => $stud_full_name,

            'email' => $email ?? null,

            'phone' => $phone ?? null,

            'faculty_code' => $faculty_code,

            'major_code' => $major_code,

            'faculty' => $faculty,

            'major' => $major,

            'batch' => $batch,

            'semester' => (int) $semester,

            'gender' => $gender ?? null,
        ];

        /*
        |--------------------------------------------------------------------------
        | Student Result
        |--------------------------------------------------------------------------
        |
        | Cache student results for 1 hour as they update per semester.
        |--------------------------------------------------------------------------
        */

        // Cache student results
        $results = Cache::remember(
            'student_results_' . $stud_id . '_' . $faculty_code . '_' . $major_code . '_' . $batch . '_' . $semester,
            360, // 1 hour
            function () use ($stud_id, $faculty_code, $major_code, $batch, $semester) {
                return $this->externalDatabase->getStudentResult(
                    $stud_id,
                    $faculty_code,
                    $major_code,
                    $batch,
                    $semester
                );
            }
        );

        $semesterResult = null;

        if (!empty($results)) {

            $first = $results[0];

            $courses = [];

            foreach ($results as $result) {

                $courses[] = [
                    'course_code' => $result['course_code'],
                    'course_name' => $result['course_name'],
                    'course_units' => $result['course_units'],
                    'grade' => $result['grade'],
                    'points' => $result['points'],
                    'remark' => $result['remark'],
                    'result_status' => $result['result_status'],
                ];
            }

            $semesterResult = [
                'semester' => $first['semester'],

                'gpa' => $first['gpa'],

                'cgpa' => $first['cgpa'],

                'status' => $first['status'],

                'courses' => $courses,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Fee Details
        |--------------------------------------------------------------------------
        |
        | Cache fee details for 30 minutes as they update with payments.
        |--------------------------------------------------------------------------
        */

        // Cache fee details
        $raw = Cache::remember(
            'student_fees_' . $stud_id . '_' . $faculty_code . '_' . $major_code . '_' . $batch . '_' . $semester,
            30, // 30 minutes
            function () use ($stud_id, $faculty_code, $major_code, $batch, $semester) {
                return $this->externalDatabase->getStudentFees(
                    $stud_id,
                    $faculty_code,
                    $major_code,
                    $batch,
                    $semester
                );
            }
        );

        if (!$raw) {

            $feeDetails = [
                'total_fees' => 0,

                'fees_type' => null,

                'end_date' => null,

                'days_remaining' => 0,

                'registration_closed' => false,

                'status' => null,

                'paid' => null,
            ];

        } else {

            $paymentStatus = false;

            $end_date = $raw->end_date;

            $viewData = $raw->viewData;

            $total_fee_bank = $raw->total_fee_bank;

            /*
            |--------------------------------------------------------------------------
            | Registration status
            |--------------------------------------------------------------------------
            */

            $registration_closed = $current_date > $end_date;

            $endDate = Carbon::parse($end_date)->startOfDay();

            if ($endDate->isSameDay($today)) {

                $daysRemaining = 1;

            } elseif ($endDate->isFuture()) {

                $daysRemaining = $today->diffInDays($endDate);

            } else {

                $daysRemaining = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | Fee status
            |--------------------------------------------------------------------------
            */

            $status = null;

            if ($registration_closed) {

                $status = 'Registration is closed.';

            } elseif ($total_fee_bank == 0 || $viewData == 0) {

                $status = 'Check with faculty registrar for fee details';
            }

            /*
            |--------------------------------------------------------------------------
            | Payment status
            |--------------------------------------------------------------------------
            */

            if ($viewData == 2) {
                $paymentStatus = true;
            }

            /*
            |--------------------------------------------------------------------------
            | Fee type
            |--------------------------------------------------------------------------
            */

            $fees_type = in_array(
                (int) $semester,
                [1, 3, 5, 7]
            )
                ? 'Year, Registration Fees'
                : 'Registration Fee';

            /*
            |--------------------------------------------------------------------------
            | Build fee details
            |--------------------------------------------------------------------------
            */

            $feeDetails = [

                'total_fees' => $total_fee_bank,

                'fees_type' => $fees_type,

                'end_date' => $end_date,

                'days_remaining' => $daysRemaining,

                'registration_closed' => $registration_closed,

                'status' => $status,

                'paid' => $paymentStatus,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Timetable
        |--------------------------------------------------------------------------
        |
        | Cache removed from timetable.
        |--------------------------------------------------------------------------
        */

        // Cache removed from timetable
        $timetable = $this->externalDatabase->getStudentTimetable(
            $stud_id,
            $faculty_code,
            $major_code,
            $batch,
            $semester
        );

        /*
        |--------------------------------------------------------------------------
        | Timetable status
        |--------------------------------------------------------------------------
        */

        if (empty($timetable['days'])) {

            $timetable = null;

        }


        /*
        |--------------------------------------------------------------------------
        | Result maintenance mode
        |--------------------------------------------------------------------------
        |
        | Cache maintenance mode for 15 minutes as it changes during maintenance windows.
        |--------------------------------------------------------------------------
        */

        // Cache result maintenance mode
        $resultMaintenanceMode = Cache::remember(
            'result_maintenance_mode',
            15, // 15 minutes
            function () {
                return $this->externalDatabase->resultMaintenanceMode();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Notification status
        |--------------------------------------------------------------------------
        |
        | Cache notification status for 10 minutes as it changes when user toggles settings.
        |--------------------------------------------------------------------------
        */

        // Cache notification status
        $notificationToggled = Cache::remember(
            'user_notification_toggled_' . $user_id,
            10, // 10 minutes
            function () use ($user_id) {
                return UserDevice::where('user_id', $user_id)
                    ->where('is_active', true)
                    ->exists();
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Application Version
        |--------------------------------------------------------------------------
        */

        if (
            in_array($platform, ['ios', 'android'], true) &&
            $currentAppVersion !== ''
        ) {

            $versionConfig = AppVersion::query()
                ->where('platform', $platform)
                ->first();

            if ($versionConfig) {

                /*
                |--------------------------------------------------------------------------
                | Force Update
                |--------------------------------------------------------------------------
                |
                | Force update only applies when the installed application
                | version is lower than the configured minimum version.
                |
                */

                $forceUpdate = version_compare($currentAppVersion, $versionConfig->minimum_version, '<') && (bool) $versionConfig->force_update;

                $appVersion = [
                    'platform' => $versionConfig->platform,
                    'minimum_version' => $versionConfig->minimum_version,
                    'app_url' => $versionConfig->app_url,
                    'force_update' => $forceUpdate,
                    'desc' => 'A new version of Student Desk app is available. Please update the app to continue using the latest features and improvements.',

                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Application Status
        |--------------------------------------------------------------------------
        */

        $appStatus = [

            'active' => $settings
                ? (bool) $settings->api_active
                : true,

            'tabs_status' => [

                'fee' => $settings
                    ? (bool) $settings->fee_active
                    : true,

                'result' => (
                    $settings
                    ? (bool) $settings->result_active
                    : true
                ) && !$resultMaintenanceMode,

                'timetable' => $settings
                    ? (bool) $settings->timetable_active
                    : true,
            ],

            'notificationToggled' => $notificationToggled,
        ];

        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */

        return [

            'success' => true,

            'code' => 200,

            'message' => 'Main Data Retrieved Successfully',

            'studentDetails' => $studentData,

            'semesterResult' => $semesterResult,

            'feeDetails' => $feeDetails,

            'timetable' => $timetable,

            'appStatus' => $appStatus,

            'appVersion' => $appVersion
        ];
    }

    public function getProfile()
    {

        $studentDetails = [];

        $auth = Helper::authenticatedStudent();
        if (!$auth['success']) {
            return $auth;
        }

        $applicationStatus = Helper::checkApplicationStatus();
        if (!$applicationStatus['success']) {
            return $applicationStatus;
        }

        $studentHelper = Helper::studentData();

        $stud_id = $studentHelper['stud_id'];
        $stud_full_name = $studentHelper['stud_full_name'];
        $faculty_code = $studentHelper['faculty_code'];
        $major_code = $studentHelper['major_code'];
        $batch = $studentHelper['batch'];
        $semester = $studentHelper['semester'];

        $phone = $studentHelper['phone'];
        $email = $studentHelper['email'];
        $gender = $studentHelper['gender'];

        $faculty_desc_e = $this->externalDatabase->getFacultyName($faculty_code);
        $major_desc_e = $this->externalDatabase->getMajorName($major_code);

        $studentDetails = [
            'stud_index' => $stud_id,
            'stud_full_name' => $stud_full_name,
            'stud_email' => $email ?? null,
            'stud_phone' => $phone ?? null,
            'faculty_code' => $faculty_code ?? null,
            'major_code' => $major_code ?? null,
            'faculty' => $faculty_desc_e ?? null,
            'major' => $major_desc_e ?? null,
            'batch' => $batch ?? null,
            'sem' => (int) $semester,
            'gender' => $gender,
        ];

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Student Details',
            'studentDetails' => $studentDetails,
        ];
    }

    public function getResult()
    {
        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        $auth = Helper::authenticatedStudent();

        if (!$auth['success']) {
            return $auth;
        }

        /*
        |--------------------------------------------------------------------------
        | Check result tab status
        |--------------------------------------------------------------------------
        */

        $status = Helper::checkTabStatus('result');

        if (!$status['success']) {
            return $status;
        }

        /*
        |--------------------------------------------------------------------------
        | Student information
        |--------------------------------------------------------------------------
        */

        $studentHelper = Helper::studentData();

        $stud_id = $studentHelper['stud_id'];
        $faculty_code = $studentHelper['faculty_code'];
        $major_code = $studentHelper['major_code'];
        $batch = $studentHelper['batch'];
        $semester = $studentHelper['semester'];

        /*
        |--------------------------------------------------------------------------
        | Result cache - temporarily disabled
        |--------------------------------------------------------------------------
        */

        /*
        $cacheKey = "student:{$stud_id}:{$faculty_code}:{$major_code}:{$batch}:{$semester}:result";

        $payload = Cache::remember(
            $cacheKey,
            3600,
            function () use (
                $stud_id,
                $faculty_code,
                $major_code,
                $batch,
                $semester
            ) {
                $results = $this->externalDatabase->getStudentResult(
                    $stud_id,
                    $faculty_code,
                    $major_code,
                    $batch,
                    $semester
                );

                // ...
            }
        );
        */

        /*
        |--------------------------------------------------------------------------
        | Get result directly from external database
        |--------------------------------------------------------------------------
        */

        $results = $this->externalDatabase->getStudentResult(
            $stud_id,
            $faculty_code,
            $major_code,
            $batch,
            $semester
        );

        /*
        |--------------------------------------------------------------------------
        | Result not found
        |--------------------------------------------------------------------------
        */

        if (empty($results)) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Student Result Not Found',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Student details
        |--------------------------------------------------------------------------
        */

        $first = $results[0];

        $studentDetails = [
            'stud_id' => $first['stud_id'],
            'student_name' => $first['student_name'],
            'ministry_no' => $first['ministry_no'],
            'faculty' => $first['faculty'],
            'major' => $first['major'],
        ];

        /*
        |--------------------------------------------------------------------------
        | Courses
        |--------------------------------------------------------------------------
        */

        $courses = [];

        foreach ($results as $result) {
            $courses[] = [
                'course_code' => $result['course_code'],
                'course_name' => $result['course_name'],
                'course_units' => $result['course_units'],
                'grade' => $result['grade'],
                'points' => $result['points'],
                'remark' => $result['remark'],
                'result_status' => $result['result_status'],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Semester result
        |--------------------------------------------------------------------------
        */

        $semesterResult = [
            'semester' => $first['semester'],
            'gpa' => number_format((float) $first['gpa'], 2, '.', ''),
            'cgpa' => number_format((float) $first['cgpa'], 2, '.', ''),
            'status' => $first['status'],
            'courses' => $courses,
        ];

        if ($semesterResult['status'] === 'Incomplete') {
            $semesterResult['gpa'] = '0.00';
            $semesterResult['cgpa'] = '0.00';
        }

        /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Student Result Retrieved Successfully',
            'studentDetails' => $studentDetails,
            'semesterResult' => $semesterResult,
        ];
    }

    public function getFees()
    {

        $feeDetails = [];
        $studentDetails = [];

        $auth = Helper::authenticatedStudent();
        if (!$auth['success']) {
            return $auth;
        }

        //Check tab status
        $status = Helper::checkTabStatus('fee');
        if (!$status['success']) {
            return $status;
        }

        $studentHelper = Helper::studentData();

        $stud_id = $studentHelper['stud_id'];
        $stud_full_name = $studentHelper['stud_full_name'];
        $faculty_code = $studentHelper['faculty_code'];
        $major_code = $studentHelper['major_code'];
        $batch = $studentHelper['batch'];
        $semester = $studentHelper['semester'];
        $phone = $studentHelper['phone'];

        $current_date = now()->format('Y-m-d');
        $registration_closed = false;

        $end_date = null;
        $viewData = null;
        $total_fee_bank = 0;
        $status = null;
        $fees_type = null;
        $today = Carbon::today();
        $paymentStatus = false;

        $getFeeDetails = $this->externalDatabase->getStudentFees($stud_id, $faculty_code, $major_code, $batch, $semester);

        if ($getFeeDetails) {

            $start_date = $getFeeDetails->start_date;
            $end_date = $getFeeDetails->end_date;
            $viewData = $getFeeDetails->viewData;
            $total_fee_bank = $getFeeDetails->total_fee_bank;

            $registration_closed = $current_date > $end_date;

            $endDate = Carbon::parse($end_date)->startOfDay();

            if ($endDate->isSameDay($today)) {
                $daysRemaining = 1;
            } elseif ($endDate->isFuture()) {
                $daysRemaining = $today->diffInDays($endDate);
            } else {
                $daysRemaining = 0;
            }

            if ($registration_closed) {
                $status = 'Registration is closed.';
            } elseif ($total_fee_bank == 0 || $viewData == 0) {
                $status = 'Check with faculty registrar for fee details';
            }

            if ($viewData == 2) {
                $paymentStatus = true;
            }

            $fees_type = in_array($semester, [1, 3, 5, 7]) ? 'Year, Registration Fees' : 'Registration Fee';

            $feeDetails = [
                'total_fees' => $total_fee_bank,
                'fees_type' => $fees_type,
                'end_date' => $end_date,
                'days_remaining' => $daysRemaining,
                'registration_closed' => $registration_closed,
                'status' => $status,
                'paid' => $paymentStatus,
            ];

            $studentDetails = [
                'stud_index' => $stud_id,
                'stud_full_name' => $stud_full_name,
                'stud_email' => $email ?? null,
                'stud_phone' => $phone,
                'batch' => $batch ?? null,
                'sem' => (int) $semester,
            ];

            return [
                'success' => true,
                'code' => 200,
                'message' => 'Fee Details Retrieved Successfully',
                'studentDetails' => $studentDetails,
                'feeDetails' => $feeDetails,
            ];

        } else {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Fee Details Not Found'
            ];
        }
    }

    public function getTimetable(): array
    {

        $auth = Helper::authenticatedStudent();

        if (!$auth['success']) {
            return $auth;
        }

        //Check tab status
        $status = Helper::checkTabStatus('timetable');
        if (!$status['success']) {
            return $status;
        }

        $studentHelper = Helper::studentData();

        $stud_id = $studentHelper['stud_id'];
        $faculty_code = $studentHelper['faculty_code'];
        $major_code = $studentHelper['major_code'];
        $batch = $studentHelper['batch'];
        $semester = $studentHelper['semester'];

        $timetable = $this->externalDatabase->getStudentTimetable(
            $stud_id,
            $faculty_code,
            $major_code,
            $batch,
            $semester
        );

        if (!empty($timetable['days'])) {
            return [
                'success' => true,
                'code' => 200,
                'message' => 'Timetable Retrieved Successfully',
                'timetableDetails' => $timetable,
            ];
        } else {
            //return [$timetable = null]

            return [
                'success' => false,
                'code' => 404,
                'message' => 'Timetable Not Found'
            ];
        }
    }

    public function updatePassword($data)
    {
        $currentPassword = (string) ($data['current_password'] ?? '');
        $newPassword = (string) ($data['new_password'] ?? '');
        $newPasswordConfirm = (string) ($data['new_password_confirm'] ?? '');

        /*
        |--------------------------------------------------------------------------
        | Check application status
        |--------------------------------------------------------------------------
        */

        $applicationStatus = Helper::checkApplicationStatus();

        if (!$applicationStatus['success']) {
            return $applicationStatus;
        }

        /*
        |--------------------------------------------------------------------------
        | Check authentication
        |--------------------------------------------------------------------------
        */

        if (!Auth::check() || !Auth::user()) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated',
            ];
        }

        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Make sure local student account exists
        |--------------------------------------------------------------------------
        */

        if (
            !$user->id ||
            !$user->stud_index ||
            (int) $user->role_id !== Helper::STUDENT_ROLE
        ) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Student account not found',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Make sure account is active
        |--------------------------------------------------------------------------
        */

        if (!$user->is_active) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'Account has been deleted',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Validate current password
        |--------------------------------------------------------------------------
        */

        if ($currentPassword === '') {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Current password is required',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Verify current password against LOCAL User password
        |--------------------------------------------------------------------------
        */

        if (
            empty($user->password) ||
            !Hash::check($currentPassword, $user->password)
        ) {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Current password not correct, try again',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Validate new password
        |--------------------------------------------------------------------------
        */

        if ($newPassword === '') {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'New password is required',
            ];
        }

        if ($newPassword !== $newPasswordConfirm) {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Password does not match',
            ];
        }

        if (strlen($newPassword) < 6) {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'Password must be at least 6 characters',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent using the same password
        |--------------------------------------------------------------------------
        */

        if (Hash::check($newPassword, $user->password)) {
            return [
                'success' => false,
                'code' => 422,
                'message' => 'New password must be different from your current password',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Update LOCAL SDFU password ONLY
        |--------------------------------------------------------------------------
        */

        $user->password = Hash::make($newPassword);

        // Explicitly preserve the student role.
        $user->role_id = Helper::STUDENT_ROLE;

        $user->save();

        /*
        |--------------------------------------------------------------------------
        | Revoke current Sanctum token
        |--------------------------------------------------------------------------
        */

        $currentToken = $user->currentAccessToken();

        if ($currentToken instanceof PersonalAccessToken) {
            $currentToken->delete();
        }

        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Password updated successfully. Please login again.',
        ];
    }

    //Notifications
    public function getNotifications()
    {
        // Check application status
        $applicationStatus = Helper::checkApplicationStatus();

        if (!$applicationStatus['success']) {
            return $applicationStatus;
        }

        // Get authenticated student
        $studentHelper = Helper::studentData();

        if (!$studentHelper) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated',
            ];
        }

        // Helper::studentData() returns stud_id
        $user_id = $studentHelper['id'];

        // Get notifications for this student
        $notifications = Notification::query()->where('user_id', $user_id)->latest('created_at')->paginate(20);

        // Unread notification count
        $unreadCount = Notification::query()->where('user_id', $user_id)->whereNull('read_at')->count();

        // Format notification details
        $notificationsDetails = collect($notifications->items())
            ->map(function ($notification) {

                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'type' => $notification->type,

                    'is_read' => !is_null($notification->read_at),
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at?->toISOString(),
                ];

            })->values()->toArray();

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Notifications Retrieved Successfully',

            'notificationsDetails' => [
                'notifications' => $notificationsDetails,
                'unread_count' => $unreadCount,

                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                    'has_more' => $notifications->hasMorePages(),
                ],
            ],
        ];
    }

    public function markNotificationAsRead($notificationId)
    {
        // Check application status
        $applicationStatus = Helper::checkApplicationStatus();

        if (!$applicationStatus['success']) {
            return $applicationStatus;
        }

        // Get authenticated student
        $studentHelper = Helper::studentData();

        if (!$studentHelper || !isset($studentHelper['user'])) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated',
            ];
        }

        $user_id = $studentHelper['user']->id;

        // Find notification belonging to this student
        $notification = Notification::query()->where('id', $notificationId)->where('user_id', $user_id)->first();

        if (!$notification) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Notification not found',
            ];
        }

        // Mark as read
        if (is_null($notification->read_at)) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        // Get remaining unread count
        $unreadCount = Notification::query()->where('user_id', $user_id)->whereNull('read_at')->count();

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Notification marked as read successfully',
            'notificationDetails' => [
                'id' => $notification->id,
                'is_read' => true,
                'read_at' => $notification->fresh()->read_at?->toISOString(),
            ],
            'unread_count' => $unreadCount,
        ];
    }

    public function markAllNotificationsAsRead()
    {
        // Check application status
        $applicationStatus = Helper::checkApplicationStatus();

        if (!$applicationStatus['success']) {
            return $applicationStatus;
        }

        // Get authenticated student
        $studentHelper = Helper::studentData();

        if (!$studentHelper || !isset($studentHelper['user'])) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated',
            ];
        }

        $user_id = $studentHelper['user']->id;

        // Mark all unread notifications as read
        $updatedCount = Notification::query()->where('user_id', $user_id)->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);

        return [
            'success' => true,
            'code' => 200,
            'message' => 'All notifications marked as read successfully',
            'notificationDetails' => [
                'updated_count' => $updatedCount,
                'unread_count' => 0,
            ],
        ];
    }

    public function registerToken($request)
    {

        //Check application status
        $applicationStatus = Helper::checkApplicationStatus();
        if (!$applicationStatus['success']) {
            return $applicationStatus;
        }

        $user = $request->user();

        if (!$user) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated',
            ];
        }

        UserDevice::updateOrCreate(
            [
                'fcm_token' => $request->input('token'),
            ],
            [
                'user_id' => $user->id,
                'device_type' => $request->input('device_type'),
                'device_name' => $request->input('device_name'),
                'app_version' => $request->input('app_version'),
                'last_seen_at' => now(),
                'is_active' => true,
            ]
        );

        return [
            'success' => true,
            'code' => 200,
            'message' => 'FCM token registered successfully',
        ];
    }

    public function unregisterToken($request): array
    {

        //Check application status
        $applicationStatus = Helper::checkApplicationStatus();
        if (!$applicationStatus['success']) {
            return $applicationStatus;
        }

        $user = $request->user();

        if (!$user) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated',
            ];
        }

        $token = UserDevice::where('user_id', $user->id)->where('fcm_token', $request->input('token'))->first();

        if (!$token) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'FCM token not found for this student',
            ];
        }

        $token->update([
            'is_active' => false,
        ]);

        return [
            'success' => true,
            'code' => 200,
            'message' => 'FCM token removed successfully',
        ];
    }

    public function logout()
    {
        //Check application status
        $applicationStatus = Helper::checkApplicationStatus();
        if (!$applicationStatus['success']) {
            return $applicationStatus;
        }

        if (Auth::check()) {
            if (Auth::user()->tokens()->delete()) {
                return ['success' => true, 'code' => 200, 'message' => 'logout success'];
            } else {
                return [
                    'success' => false,
                    'code' => 500,
                    'message' => 'logout failed'
                ];
            }
        } else {
            return ['success' => false, 'code' => 401, 'message' => 'Student not found'];
        }
    }

    //DELETE ACCOUNT
    public function deleteAccount()
    {

        $auth = Helper::authenticatedStudent();

        if (!$auth['success']) {
            return $auth;
        }

        //Check application status
        $applicationStatus = Helper::checkApplicationStatus();
        if (!$applicationStatus['success']) {
            return $applicationStatus;
        }

        $studentHelper = Helper::studentData();

        $stud_id = $studentHelper['stud_id'];
        $role_id = $studentHelper['role_id'];
        $is_active = $studentHelper['is_active'];

        if ((int) $role_id !== Helper::STUDENT_ROLE) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'Student account required',
            ];
        }

        if (!$is_active) {
            return [
                'success' => false,
                'code' => 400,
                'message' => 'Account already deleted',
            ];
        }

        $user = Auth::user();
        $user->is_active = false;
        $user->save();

        $user->tokens()->delete();

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Account deleted successfully',
        ];
    }

}