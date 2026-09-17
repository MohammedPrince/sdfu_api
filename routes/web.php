<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\MainController;
use App\Http\Controllers\Admin\NotificationController;
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

        Route::get('/dashboard', [MainController::class, 'dashboard'])->name('dashboard');
        Route::get('/manage', [MainController::class, 'manageApplication'])->name('manage');
        Route::post('/manage', [MainController::class, 'updateApplication'])->name('manage.update');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/push', [NotificationController::class, 'pushToGroup'])->name('notifications.push');
        Route::post('/notifications/push-one', [NotificationController::class, 'pushToOne'])->name('notifications.push.one');

        Route::get('/notifications/test', [NotificationController::class, 'testFirebase']);

        //Students
        Route::get('/students', [MainController::class, 'students'])->name('students');
        Route::get('/students/{student}', [MainController::class, 'showStudents'])->name('students.show');
        Route::patch('/students/{student}/status', [MainController::class, 'updateStudentStatus'])->name('students.status');

        //Get majors based of faculty_code. JS
        Route::get('/manage/majors/{faculty_code}', [MainController::class, 'getMajors'])->name('manage.majors');

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    });

});