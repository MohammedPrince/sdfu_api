<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    public function showLogin()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'string',
            ],
        ]);

        $remember = $request->boolean('remember');

        if (
            Auth::attempt([
                'username' => $credentials['username'],
                'password' => $credentials['password'],
            ], $remember)
        ) {

            $user = Auth::user();

            /*
            |--------------------------------------------------------------------------
            | Role 1 = Administrator
            |--------------------------------------------------------------------------
            */
            if ((int) $user->role_id !== 1) {

                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withInput($request->only('username'))
                    ->withErrors([
                        'username' => 'You are not authorized to access the administration desk.',
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Regenerate session after successful login
            |--------------------------------------------------------------------------
            */
            $request->session()->regenerate();

            /*
            |--------------------------------------------------------------------------
            | Find the first menu this administrator is allowed to access
            |--------------------------------------------------------------------------
            |
            | Keep this order as the preferred landing-page order.
            |
            */
            $allowedMenus = [
                'dashboard' => 'admin.dashboard',
                'manage_application' => 'admin.manage',
                'notifications' => 'admin.notifications',
                'students' => 'admin.students',
                'pull_timetable' => 'admin.timetable',
                'create_timetable' => 'admin.timetable.display',
                'reports' => 'admin.reports',
            ];

            foreach ($allowedMenus as $menuKey => $routeName) {

                if (
                    \App\Helpers\Helper::canAccessAdminMenu($menuKey)
                ) {
                    return redirect()
                        ->route($routeName)
                        ->with(
                            'success',
                            'Welcome back, ' .
                            ($user->name ?? $user->username) .
                            '.'
                        );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Administrator has no assigned menu permissions
            |--------------------------------------------------------------------------
            */
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('username'))
                ->withErrors([
                    'username' =>
                        'Your administrator account does not have access to any administration menu. Please contact the system administrator.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Invalid username / password
        |--------------------------------------------------------------------------
        */
        return back()
            ->withInput($request->only('username'))
            ->withErrors([
                'username' => 'The username or password is incorrect.',
            ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'You have been logged out successfully.');

    }
}