<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'username' => 'Please login to access the administration desk.',
                ]);
        }

        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Admin / Supervisor
        |--------------------------------------------------------------------------
        |
        | 3 = Admin
        | 4 = Supervisor
        |
        */

        if (!in_array((int) $user->role_id, [1], true)) {

            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'username' => 'You are not authorized to access the administration desk.',
                ]);
        }

        return $next($request);
    }
}