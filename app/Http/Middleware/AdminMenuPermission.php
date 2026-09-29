<?php

namespace App\Http\Middleware;

use App\Helpers\Helper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMenuPermission
{
    public function handle(
        Request $request,
        Closure $next,
        string $menu
    ): Response {

        /*
        |--------------------------------------------------------------------------
        | Must be authenticated
        |--------------------------------------------------------------------------
        */
        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        $user = auth()->user();

        /*
        |--------------------------------------------------------------------------
        | Only role 1 can access the admin panel
        |--------------------------------------------------------------------------
        */
        if ((int) $user->role_id !== 1) {
            abort(403, 'You do not have permission to access this page.');
        }

        /*
        |--------------------------------------------------------------------------
        | Check menu permission
        |--------------------------------------------------------------------------
        */
        if (!Helper::canAccessAdminMenu($menu)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}