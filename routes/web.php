<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\MainController;
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

Route::get('/', function () {
    return view('home');
});


// Route::get('/admin', function () {
//     return view('admin.auth.login');
// });

// Route::get('/admin/dashboard', function () {
//     return view('admin/dashboard');
// });

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

        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    });

});