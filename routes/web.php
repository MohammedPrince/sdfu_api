<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\MainController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\AdminPermissionController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

//Clear All route
Route::get('/clear', function () {

    Artisan::call('optimize');
    Artisan::call('route:clear');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');

    return 'Caching, routes, and configuration cleared successfully.';
})->name('clear');

//Home
Route::get('/', [MainController::class, 'index']);
Route::get('/privacy-policy', [MainController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/test_connection', [MainController::class, 'testConnection']);

Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::get('/', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Admin Area
    |--------------------------------------------------------------------------
    */

    Route::middleware('admin')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | User Permissions
        |--------------------------------------------------------------------------
        | Only role_id = 1 AND username = prince
        |--------------------------------------------------------------------------
        */

        Route::middleware('admin.permissions')->group(function () {

            Route::get('/permissions', [AdminPermissionController::class, 'index'])
                ->name('permissions');

            Route::post('/permissions/{userId}', [AdminPermissionController::class, 'update'])
                ->name('permissions.update');

        });


        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::middleware('menu:dashboard')->group(function () {

            Route::get('/dashboard', [MainController::class, 'dashboard'])
                ->name('dashboard');

        });


        /*
        |--------------------------------------------------------------------------
        | Manage Application
        |--------------------------------------------------------------------------
        */

        Route::middleware('menu:manage_application')->group(function () {

            Route::get('/manage', [MainController::class, 'manageApplication'])
                ->name('manage');

            Route::post('/manage', [MainController::class, 'updateApplication'])
                ->name('manage.update');

            Route::get('/manage/majors/{faculty_code}', [MainController::class, 'getMajors'])
                ->name('manage.majors');

        });


        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */

        Route::middleware('menu:notifications')->group(function () {

            Route::get('/notifications', [NotificationController::class, 'index'])
                ->name('notifications');

            Route::post('/notifications/push', [NotificationController::class, 'pushToGroup'])
                ->name('notifications.push');

            Route::post('/notifications/push-one', [NotificationController::class, 'pushToOne'])
                ->name('notifications.push.one');

            Route::get('/notifications/test', [NotificationController::class, 'testFirebase']);

        });


        /*
        |--------------------------------------------------------------------------
        | Students
        |--------------------------------------------------------------------------
        */

        Route::middleware('menu:students')->group(function () {

            Route::get('/students', [MainController::class, 'students'])
                ->name('students');

            Route::get('/students/{studentId}', [MainController::class, 'showStudents'])
                ->name('students.show');

            Route::patch('/students/{studentId}/status', [MainController::class, 'updateStudentAccountStatus'])
                ->name('students.status');

            Route::post('/students/{studentId}/reset-password', [MainController::class, 'resetPassword'])
                ->name('students.reset-password');

            Route::patch('/students/{studentId}/logout', [MainController::class, 'forceLogout'])
                ->name('students.logout');

            Route::patch('/students/logout-all', [MainController::class, 'forceLogoutAll'])
                ->name('students.forceLogoutAll');

        });


        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */

        Route::middleware('menu:reports')->group(function () {

            Route::get('/reports', [MainController::class, 'reports'])
                ->name('reports');

        });


        /*
        |--------------------------------------------------------------------------
        | Pull Timetable
        |--------------------------------------------------------------------------
        */

        Route::middleware('menu:pull_timetable')->group(function () {

            Route::get('/timetable', [MainController::class, 'manageTimeTable'])
                ->name('timetable');

            Route::post('/timetable/fetch', [MainController::class, 'fetchTimetable'])
                ->name('timetable.fetch');

            Route::post('/timetable/server/config', [MainController::class, 'saveServerConfig'])
                ->name('timetable.server.config');

        });


        /*
        |--------------------------------------------------------------------------
        | Create / Edit / View Timetable
        |--------------------------------------------------------------------------
        */

        Route::middleware('menu:create_timetable')->group(function () {

            Route::get('/timetable/create', [MainController::class, 'createTimeTable'])
                ->name('timetable.create');

            Route::post('/timetable/create', [MainController::class, 'storeTimeTable'])
                ->name('timetable.store');

            Route::get('/timetable/display', [MainController::class, 'displayTimeTable'])
                ->name('timetable.display');

            Route::get(
                '/timetable/edit/{faculty_code}/{major_code}/{batch}/{ttid}/{type}',
                [MainController::class, 'editTimeTable']
            )->name('timetable.edit');

            Route::put(
                '/timetable/edit/{faculty_code}/{major_code}/{batch}/{ttid}',
                [MainController::class, 'updateTimeTable']
            )->name('timetable.update');

            Route::delete(
                '/timetable/{faculty_code}/{major_code}/{batch}/{ttid}',
                [MainController::class, 'deleteTimeTable']
            )->name('timetable.delete');

            Route::get(
                '/timetable/print/{faculty_code}/{major_code}/{batch}/{ttid}',
                [MainController::class, 'printTimeTable']
            )->name('timetable.print');

            Route::get(
                '/timetable/courses',
                [MainController::class, 'getTimetableCourses']
            )->name('timetable.courses');

        });


        /*
        |--------------------------------------------------------------------------
        | Logout
        |--------------------------------------------------------------------------
        */

        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('logout');

    });

});