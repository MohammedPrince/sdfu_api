<?php

namespace App\Helpers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use App\Models\Visitor;
use Illuminate\Support\Facades\Request;

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

    //API Helpers
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

        if (!$settings) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'Application settings not found',
            ];
        }

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

        if (!$settings) {
            return [
                'success' => false,
                'code' => 403,
                'message' => 'Application settings not found',
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

        return [
            'success' => true,
            'code' => 200,
            'message' => ucfirst($tab) . ' is active',
        ];
    }

}