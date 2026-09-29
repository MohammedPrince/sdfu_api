<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\AdminPermission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminPermissionController extends Controller
{

    public function index(Request $request)
    {

        $users = User::where('role_id', 1)->orderBy('username')->get();

        $selectedUser = null;
        $permissions = [];

        if ($request->filled('user_id')) {

            $selectedUser = $users->firstWhere(
                'id',
                $request->user_id
            );

            if ($selectedUser) {

                $permissions = AdminPermission::where(
                    'user_id',
                    $selectedUser->id
                )
                    ->where('can_view', true)
                    ->pluck('menu_key')
                    ->toArray();
            }
        }

        return view(
            'admin.manage_permissions',
            [
                'users' => $users,
                'selectedUser' => $selectedUser,
                'permissions' => $permissions,
                'menus' => config('admin_menus'),
            ]
        );
    }


    public function update(
        Request $request,
        int $userId
    ) {

        $user = User::where('role_id', 1)
            ->findOrFail($userId);


        $allowedMenus = array_keys(
            config('admin_menus')
        );


        $selectedMenus = $request->input(
            'permissions',
            []
        );


        $selectedMenus = array_values(
            array_intersect(
                $selectedMenus,
                $allowedMenus
            )
        );


        AdminPermission::where(
            'user_id',
            $user->id
        )->delete();


        foreach ($selectedMenus as $menu) {

            AdminPermission::create([
                'user_id' => $user->id,
                'menu_key' => $menu,
                'can_view' => true,
            ]);
        }


        return redirect()
            ->route('admin.permissions', [
                'user_id' => $user->id,
            ])
            ->with(
                'success',
                'Permissions updated successfully.'
            );
    }
}