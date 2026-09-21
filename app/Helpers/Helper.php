<?php

namespace App\Helpers;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Visitor;
use App\Services\ExternalDatabaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class Helper
{

    //Application Helpers
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

        return $setting
            ? (bool) $setting->api_active
            : false;
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