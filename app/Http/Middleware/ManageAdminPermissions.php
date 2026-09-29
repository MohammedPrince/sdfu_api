<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManageAdminPermissions
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Only Prince can manage administrator permissions
        |--------------------------------------------------------------------------
        */
        if (
            (int) $user->role_id !== 1 ||
            strtolower(trim($user->username ?? '')) !== 'prince'
        ) {
            abort(
                403,
                'You do not have permission to manage administrator permissions.'
            );
        }

        return $next($request);
    }
}