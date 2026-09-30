<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\AdminPermission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

    //Manage Users
    public function manageUser(Request $request)
    {
        $users = User::query()
            ->where('role_id', 1)
            ->with('adminPermissions')
            ->orderBy('username')
            ->get();

        $editUser = null;
        $permissions = [];

        if ($request->filled('edit')) {

            $editUser = $users->firstWhere(
                'id',
                (int) $request->input('edit')
            );

            if ($editUser) {
                $permissions = $editUser->adminPermissions
                    ->where('can_view', true)
                    ->pluck('menu_key')
                    ->values()
                    ->toArray();
            }
        }

        return view('admin.manage_user', [
            'users' => $users,
            'editUser' => $editUser,
            'permissions' => $permissions,
            'menus' => config('admin_menus'),
        ]);
    }


    /**
     * Create a new role 1 administrator.
     */
    public function storeUser(Request $request)
    {

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:100',
                'unique:users,username',
            ],

            'password' => [
                'required',
                'string',
                'min:6',
            ],

            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'string',
            ],
        ]);

        $allowedMenus = array_keys(config('admin_menus'));

        $password = $validated['username'] . '@CESD';

        $selectedMenus = array_values(
            array_intersect(
                $validated['permissions'] ?? [],
                $allowedMenus
            )
        );

        DB::transaction(function () use ($validated, $selectedMenus, $password) {

            $user = new User();

            $user->username = trim($validated['username']);
            $user->name = trim($validated['username']);
            $user->email = trim($validated['username'] . '@test.com');
            $user->password = Hash::make($validated['password']);
            $user->role_id = 1;

            $user->save();

            foreach ($selectedMenus as $menuKey) {

                AdminPermission::create([
                    'user_id' => $user->id,
                    'menu_key' => $menuKey,
                    'can_view' => true,
                ]);
            }
        });

        return redirect()
            ->route('admin.users')
            ->with('success', 'Administrator user created successfully.');
    }


    /**
     * Update an existing role 1 administrator.
     */
    public function updateUser(Request $request, int $userId)
    {
        $user = User::query()
            ->where('role_id', 1)
            ->findOrFail($userId);

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($user->id),
            ],

            'password' => [
                'nullable',
                'string',
                'min:6',
            ],

            'permissions' => [
                'nullable',
                'array',
            ],

            'permissions.*' => [
                'string',
            ],
        ]);

        $allowedMenus = array_keys(config('admin_menus'));

        $selectedMenus = array_values(
            array_intersect(
                $validated['permissions'] ?? [],
                $allowedMenus
            )
        );

        DB::transaction(function () use ($user, $validated, $selectedMenus) {

            $user->username = trim($validated['username']);

            if (
                isset($validated['password']) &&
                $validated['password'] !== ''
            ) {
                $user->password = Hash::make(
                    $validated['password']
                );
            }

            $user->role_id = 1;
            $user->save();

            AdminPermission::where(
                'user_id',
                $user->id
            )->delete();

            foreach ($selectedMenus as $menuKey) {

                AdminPermission::create([
                    'user_id' => $user->id,
                    'menu_key' => $menuKey,
                    'can_view' => true,
                ]);
            }
        });

        return redirect()
            ->route('admin.users', [
                'edit' => $user->id,
            ])
            ->with('success', 'Administrator user updated successfully.');
    }

    /**
     * Delete an administrator.
     */
    public function deleteUser(int $userId)
    {
        $user = User::query()
            ->where('role_id', 1)
            ->findOrFail($userId);

        if (
            strtolower(trim($user->username ?? '')) === 'prince'
        ) {
            return back()->withErrors([
                'username' =>
                    'The master administrator account cannot be deleted.',
            ]);
        }

        if ((int) auth()->id() === (int) $user->id) {
            return back()->withErrors([
                'username' =>
                    'You cannot delete your own administrator account.',
            ]);
        }

        DB::transaction(function () use ($user) {

            if (method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }

            AdminPermission::where(
                'user_id',
                $user->id
            )->delete();

            $user->delete();
        });

        return redirect()
            ->route('admin.users')
            ->with('success', 'Administrator user deleted successfully.');
    }
}