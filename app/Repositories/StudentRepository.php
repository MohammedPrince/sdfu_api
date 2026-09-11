<?php

namespace App\Repositories;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\ExternalDatabaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Cache;

class StudentRepository
{
    protected $externalDatabase;

    public function __construct()
    {
        $this->externalDatabase = new ExternalDatabaseService();

    }

    public function studentCheck($data)
    {
        $studIndex = $data['stud_index'];
        $studPassword = $data['stud_password'];

        $rateLimitKey = 'student-login:' . strtolower($studIndex) . '|' . request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {

            $seconds = RateLimiter::availableIn($rateLimitKey);

            $minutes = ceil($seconds / 60);

            return response()->json([
                'success' => false,
                'code' => 429,
                'message' => 'Too many login attempts. Please try again in ' . $minutes . ' minute(s).',
                'retry_after' => $seconds,
            ], 429);
        }

        if (!empty($studIndex)) {
            return ['success' => true, 'code' => 200, 'message' => 'Student Index Exists', 'studIndex' => $studIndex, 'studPassword' => $studPassword];
        } else {
            return ['success' => false, 'code' => 404, 'message' => 'Student Index Not Exists'];
        }
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

        $student = $this->externalDatabase->getMoodleStudent($studIndex);

        if (!$student) {

            RateLimiter::hit($rateLimitKey, 300);

            return [
                'success' => false,
                'code' => 404,
                'message' => 'Student Index Not Exists',
            ];
        }

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

        $studentDetails = $this->externalDatabase->getStudentDetails($studIndex);

        if (!$studentDetails) {

            return [
                'success' => false,
                'code' => 404,
                'message' => 'Student Profile Not Found',
            ];
        }

        $stud_full_name =
            $studentDetails->stud_name . ' ' .
            $studentDetails->stud_surname . ' ' .
            $studentDetails->familyname . ' ' .
            $studentDetails->lastName;

        $faculty_desc_e = $this->externalDatabase->getFacultyName($studentDetails->faculty_code);
        $major_desc_e = $this->externalDatabase->getMajorName($studentDetails->major_code);
        $phone = $studentDetails->stud_tel_mobile ?? null;

        $user = User::where('stud_index', $studIndex)->first();

        if (!$user) {

            $user = User::create([
                'stud_index' => $studIndex,
                'name' => $stud_full_name,
                'email' => !empty($student->email)
                    ? $student->email
                    : null,
                'phone' => $phone,
                'faculty_code' => $studentDetails->faculty_code ?? null,
                'major_code' => $studentDetails->major_code ?? null,
                'batch' => $studentDetails->batch ?? null,
                'semester' => (int) $studentDetails->curr_sem,
                'password' => Hash::make(Str::random(64)),
            ]);

        } else {

            $user->update([
                'name' => $stud_full_name,
                'phone' => $phone,
                'email' => !empty($student->email)
                    ? $student->email
                    : $user->email,
                'faculty_code' => $studentDetails->faculty_code ?? null,
                'major_code' => $studentDetails->major_code ?? null,
                'batch' => $studentDetails->batch ?? null,
                'semester' => (int) $studentDetails->curr_sem,
            ]);
        }

        $expiresAt = Carbon::now()->addYear();

        $token = $user->createToken(
            'student-mobile-app',
            ['*'],
            $expiresAt
        );

        $LoginDetails = [
            'stud_index' => $studIndex,
            'stud_full_name' => $stud_full_name,
            'stud_email' => $studentDetails->stud_email ?? null,
            'stud_phone' => $phone,
            'faculty_code' => $studentDetails->faculty_code ?? null,
            'major_code' => $studentDetails->major_code ?? null,
            'faculty' => $faculty_desc_e ?? null,
            'major' => $major_desc_e ?? null,
            'batch' => $studentDetails->batch ?? null,
            'sem' => (int) $studentDetails->curr_sem,
            'token' => $token->plainTextToken,
            'token_expires_at' => $expiresAt->toISOString(),
        ];

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Login Successful',
            'studentDetails' => $LoginDetails,
        ];
    }

    public function getProfile()
    {

        if (!Auth::check() || !Auth::user()) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated'
            ];
        }

        $studentDetails = [];

        $user = Auth::user();

        $stud_id = $user->stud_index;
        $stud_full_name = $user->name;
        $faculty_code = $user->faculty_code;
        $major_code = $user->major_code;
        $batch = $user->batch;
        $semester = $user->semester;
        $email = $user->email ?? null;
        $phone = $user->phone ?? null;

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
        ];

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Student Details',
            'studentDetails' => $studentDetails,
        ];
    }

    //Without Cache
    // public function mainData()
    // {
    //     if (!Auth::check() || !Auth::user()) {
    //         return [
    //             'success' => false,
    //             'code' => 401,
    //             'message' => 'Student not authenticated'
    //         ];
    //     }

    //     $user = Auth::user();

    //     $stud_id = $user->stud_index;
    //     $faculty_code = $user->faculty_code;
    //     $major_code = $user->major_code;
    //     $batch = $user->batch;
    //     $semester = $user->semester;

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Fees Variables
    //     |--------------------------------------------------------------------------
    //     */

    //     $current_date = now()->format('Y-m-d');
    //     $registration_closed = false;
    //     $end_date = null;
    //     $viewData = null;
    //     $total_fee_bank = 0;
    //     $status = null;
    //     $fees_type = null;
    //     $today = Carbon::today();

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Student Details
    //     |--------------------------------------------------------------------------
    //     */

    //     $studentDetails = $this->externalDatabase->getStudentDetails($stud_id);

    //     if (!$studentDetails) {
    //         return [
    //             'success' => false,
    //             'code' => 404,
    //             'message' => 'Student Profile Not Found'
    //         ];
    //     }

    //     $stud_full_name = trim($studentDetails->stud_name . ' ' .$studentDetails->stud_surname . ' ' .$studentDetails->familyname . ' ' .$studentDetails->lastName);

    //     $faculty = $this->externalDatabase->getFacultyName($faculty_code);
    //     $major = $this->externalDatabase->getMajorName($major_code);

    //     $studentData = [
    //         'stud_id' => $stud_id,
    //         'student_name' => $stud_full_name,
    //         'email' => $studentDetails->stud_email ?? null,
    //         'phone' => $studentDetails->stud_tel_mobile ?? null,

    //         'faculty_code' => $faculty_code,
    //         'major_code' => $major_code,

    //         'faculty' => $faculty,
    //         'major' => $major,

    //         'batch' => $batch,
    //         'semester' => (int) $semester,
    //     ];

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Semester Result
    //     |--------------------------------------------------------------------------
    //     */

    //     $results = $this->externalDatabase->getStudentResult(
    //         $stud_id,
    //         $faculty_code,
    //         $major_code,
    //         $batch,
    //         $semester
    //     );

    //     $semesterResult = null;

    //     if (!empty($results)) {

    //         $first = $results[0];

    //         $courses = [];

    //         foreach ($results as $result) {

    //             $courses[] = [
    //                 'course_code' => $result['course_code'],
    //                 'course_name' => $result['course_name'],
    //                 'course_units' => $result['course_units'],
    //                 'grade' => $result['grade'],
    //                 'points' => round((float) $result['points']),
    //                 'remark' => $result['remark'],
    //                 'result_status' => $result['result_status'],
    //             ];
    //         }

    //         $semesterResult = [
    //             'semester' => $first['semester'],
    //             // Keep 2 decimal places
    //             'gpa' => round((float) $first['gpa'], 2),
    //             'cgpa' => round((float) $first['cgpa'], 2),
    //             'status' => $first['status'],
    //             'courses' => $courses,
    //         ];
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Fee Details
    //     |--------------------------------------------------------------------------
    //     */

    //     $feeDetails = $this->externalDatabase->getStudentFees(
    //         $stud_id,
    //         $faculty_code,
    //         $major_code,
    //         $batch,
    //         $semester
    //     );

    //     if ($feeDetails) {

    //         $start_date = $feeDetails->start_date;
    //         $end_date = $feeDetails->end_date;
    //         $viewData = $feeDetails->viewData;
    //         $total_fee_bank = $feeDetails->total_fee_bank;

    //         $registration_closed = $current_date > $end_date;

    //         $endDate = Carbon::parse($end_date)->startOfDay();

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Days Remaining
    //         |--------------------------------------------------------------------------
    //         */

    //         if ($endDate->isSameDay($today)) {
    //             $daysRemaining = 1;
    //         } elseif ($endDate->isFuture()) {
    //             $daysRemaining = $today->diffInDays($endDate);
    //         } else {
    //             $daysRemaining = 0;
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Registration Status
    //         |--------------------------------------------------------------------------
    //         */

    //         if ($registration_closed) {

    //             $status = 'Registration is closed.';

    //         } elseif ($total_fee_bank == 0 || $viewData == 0) {

    //             $status = 'Check with faculty registrar for fee details';

    //         } elseif ($viewData == 2) {

    //             $status = 'Paid';
    //         }

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Fees Type
    //         |--------------------------------------------------------------------------
    //         */

    //         $fees_type = in_array((int) $semester, [1, 3, 5, 7]) ? 'Year, Registration Fees' : 'Registration Fee';

    //         $feeDetails = [
    //             'total_fees' => $total_fee_bank,
    //             'fees_type' => $fees_type,
    //             'end_date' => $end_date,
    //             'days_remaining' => $daysRemaining,
    //             'registration_closed' => $registration_closed,
    //             'status' => $status,
    //         ];

    //     } else {

    //         // No fee record
    //         $feeDetails = null;
    //     }

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Timetable
    //     |--------------------------------------------------------------------------
    //     */

    //     $timetable = [];

    //     // Later:
    //     // $timetable = $this->externalDatabase->getStudentTimetable(
    //     //     $stud_id,
    //     //     $faculty_code,
    //     //     $major_code,
    //     //     $batch,
    //     //     $semester
    //     // );

    //     /*
    //     |--------------------------------------------------------------------------
    //     | Main Data Response
    //     |--------------------------------------------------------------------------
    //     */

    //     return [
    //         'success' => true,
    //         'code' => 200,
    //         'message' => 'Main Data Retrieved Successfully',

    //         'studentDetails' => $studentData,

    //         'semesterResult' => $semesterResult,

    //         'feeDetails' => $feeDetails,

    //         'timetable' => $timetable,
    //     ];
    // }


    public function mainData()
    {
        if (!Auth::check() || !Auth::user()) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated'
            ];
        }

        $user = Auth::user();

        $stud_id = $user->stud_index;
        $faculty_code = $user->faculty_code;
        $major_code = $user->major_code;
        $batch = $user->batch;
        $semester = $user->semester;

        $current_date = now()->format('Y-m-d');
        $today = Carbon::today();

        // Cache key base — unique per student per academic context
        $cacheKey = "student:{$stud_id}:{$faculty_code}:{$major_code}:{$batch}:{$semester}";

        /*
        |--------------------------------------------------------------------------
        | Student Details + Result (changes rarely — cache 1 hour)
        |--------------------------------------------------------------------------
        */
        $studentAndResult = Cache::remember("{$cacheKey}:profile_result", 3600, function () use ($stud_id, $faculty_code, $major_code, $batch, $semester) {
            $studentDetails = $this->externalDatabase->getStudentDetails($stud_id);

            if (!$studentDetails) {
                return null; // sentinel — student not found
            }

            $stud_full_name = trim(
                $studentDetails->stud_name . ' ' .
                $studentDetails->stud_surname . ' ' .
                $studentDetails->familyname . ' ' .
                $studentDetails->lastName
            );

            $faculty = $this->externalDatabase->getFacultyName($faculty_code);
            $major = $this->externalDatabase->getMajorName($major_code);

            $studentData = [
                'stud_id' => $stud_id,
                'student_name' => $stud_full_name,
                'email' => $studentDetails->stud_email ?? null,
                'phone' => $studentDetails->stud_tel_mobile ?? null,
                'faculty_code' => $faculty_code,
                'major_code' => $major_code,
                'faculty' => $faculty,
                'major' => $major,
                'batch' => $batch,
                'semester' => (int) $semester,
            ];

            $results = $this->externalDatabase->getStudentResult(
                $stud_id,
                $faculty_code,
                $major_code,
                $batch,
                $semester
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
                        'points' => round((float) $result['points']),
                        'remark' => $result['remark'],
                        'result_status' => $result['result_status'],
                    ];
                }

                $semesterResult = [
                    'semester' => $first['semester'],
                    'gpa' => round((float) $first['gpa'], 2),
                    'cgpa' => round((float) $first['cgpa'], 2),
                    'status' => $first['status'],
                    'courses' => $courses,
                ];
            }

            return [
                'studentData' => $studentData,
                'semesterResult' => $semesterResult,
            ];
        });

        if ($studentAndResult === null) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Student Profile Not Found'
            ];
        }

        $studentData = $studentAndResult['studentData'];
        $semesterResult = $studentAndResult['semesterResult'];

        /*
        |--------------------------------------------------------------------------
        | Fee Details (time-sensitive — short TTL, keyed by today's date)
        |--------------------------------------------------------------------------
        | Keyed with $current_date so days_remaining / registration_closed
        | naturally roll over at midnight without needing manual invalidation.
        */

        $feeDetails = Cache::remember(
            "{$cacheKey}:fees:{$current_date}",
            600, // 10 minutes — fee status can change (e.g. after a payment)
            function () use ($stud_id, $faculty_code, $major_code, $batch, $semester, $current_date, $today) {

                $raw = $this->externalDatabase->getStudentFees(
                    $stud_id,
                    $faculty_code,
                    $major_code,
                    $batch,
                    $semester
                );

                if (!$raw) {
                    return null;
                }

                $end_date = $raw->end_date;
                $viewData = $raw->viewData;
                $total_fee_bank = $raw->total_fee_bank;

                $registration_closed = $current_date > $end_date;
                $endDate = Carbon::parse($end_date)->startOfDay();

                if ($endDate->isSameDay($today)) {
                    $daysRemaining = 1;
                } elseif ($endDate->isFuture()) {
                    $daysRemaining = $today->diffInDays($endDate);
                } else {
                    $daysRemaining = 0;
                }

                $status = null;
                if ($registration_closed) {
                    $status = 'Registration is closed.';
                } elseif ($total_fee_bank == 0 || $viewData == 0) {
                    $status = 'Check with faculty registrar for fee details';
                } elseif ($viewData == 2) {
                    $status = 'Paid';
                }

                $fees_type = in_array((int) $semester, [1, 3, 5, 7])
                    ? 'Year, Registration Fees'
                    : 'Registration Fee';

                return [
                    'total_fees' => $total_fee_bank,
                    'fees_type' => $fees_type,
                    'end_date' => $end_date,
                    'days_remaining' => $daysRemaining,
                    'registration_closed' => $registration_closed,
                    'status' => $status,
                ];
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Timetable
        |--------------------------------------------------------------------------
        */
        $timetable = []; // Later: also cache once implemented

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Main Data Retrieved Successfully',
            'studentDetails' => $studentData,
            'semesterResult' => $semesterResult,
            'feeDetails' => $feeDetails,
            'timetable' => $timetable,
        ];
    }

    public function getResult()
    {
        if (!Auth::check() || !Auth::user()) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated'
            ];
        }

        $user = Auth::user();

        $stud_id = $user->stud_index;
        $faculty_code = $user->faculty_code;
        $major_code = $user->major_code;
        $batch = $user->batch;
        $semester = $user->semester;

        $cacheKey = "student:{$stud_id}:{$faculty_code}:{$major_code}:{$batch}:{$semester}:result";

        $payload = Cache::remember($cacheKey, 3600, function () use ($stud_id, $faculty_code, $major_code, $batch, $semester) {
            $results = $this->externalDatabase->getStudentResult(
                $stud_id,
                $faculty_code,
                $major_code,
                $batch,
                $semester
            );

            if (empty($results)) {
                return null; // sentinel — no result yet
            }

            $first = $results[0];

            $studentDetails = [
                'stud_id' => $first['stud_id'],
                'student_name' => $first['student_name'],
                'ministry_no' => $first['ministry_no'],
                'faculty' => $first['faculty'],
                'major' => $first['major'],
            ];

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

            return [
                'studentDetails' => $studentDetails,
                'semesterResult' => [
                    'semester' => $first['semester'],
                    'gpa' => round((float) $first['gpa'], 2),
                    'cgpa' => round((float) $first['cgpa']),
                    'status' => $first['status'],
                    'courses' => $courses,
                ],
            ];
        });

        if ($payload === null) {
            return [
                'success' => false,
                'code' => 404,
                'message' => 'Student Result Not Found'
            ];
        }

        return [
            'success' => true,
            'code' => 200,
            'message' => 'Student Result Retrieved Successfully',
            'studentDetails' => $payload['studentDetails'],
            'semesterResult' => $payload['semesterResult'],
        ];
    }
    public function getFees()
    {

        if (!Auth::check() || !Auth::user()) {
            return [
                'success' => false,
                'code' => 401,
                'message' => 'Student not authenticated'
            ];
        }

        $user = Auth::user();
        $feeDetails = [];
        $studentDetails = [];

        // $stud_id = '202257012'; // Hardcoded for testing purposes, replace with $user->stud_index in production
        // $faculty_code = 23;
        // $major_code = 57;
        // $batch = '2022';
        // $semester = 8;

        $stud_id = $user->stud_index;
        $faculty_code = $user->faculty_code;
        $major_code = $user->major_code;
        $batch = $user->batch;
        $semester = $user->semester;
        $stud_full_name = $user->name;
        $stud_phone = $user->phone ?? null;
        $stud_email = $user->email ?? null;

        $current_date = now()->format('Y-m-d');
        $registration_closed = false;
        $start_date = null;
        $end_date = null;
        $viewData = null;
        $total_fee_bank = 0;
        $status = null;
        $fees_type = null;
        $today = Carbon::today();

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
            } elseif ($viewData == 2) {
                $status = 'Paid';
            }

            $fees_type = in_array($semester, [1, 3, 5, 7]) ? 'Year, Registration Fees' : 'Registration Fee';

            $feeDetails = [
                // 'start_date' => $start_date,
                'total_fees' => $total_fee_bank,
                'fees_type' => $fees_type,
                'end_date' => $end_date,
                'days_remaining' => $daysRemaining,
                'registration_closed' => $registration_closed,
                'status' => $status,
            ];

            $studentDetails = [
                'stud_index' => $stud_id,
                'stud_full_name' => $stud_full_name,
                'stud_email' => $stud_email ?? null,
                'stud_phone' => $stud_phone,
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
    public function logout()
    {
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
}
