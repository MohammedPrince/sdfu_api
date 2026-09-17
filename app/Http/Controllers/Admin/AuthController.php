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

            //role_id:1 = Admin
            if (!in_array((int) $user->role_id, [1], true)) {

                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withInput($request->only('username'))->withErrors(['username' => 'You are not authorized to access the administration desk.',]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'))->with('success', 'Welcome back, ' . ($user->name ?? $user->username) . '.');
        }

        return back()->withInput($request->only('username'))->withErrors(['username' => 'The username or password is incorrect.',]);
    }


    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'You have been logged out successfully.');
        
    }
}