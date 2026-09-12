<?php

use App\Http\Controllers\Api\Students\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public
Route::prefix('student')->middleware('JsonRes')->group(function () {

    // Public
    Route::get('/test', [MainController::class, 'test']);
    Route::post('/checkIndex', [MainController::class, 'checkIndex']);
    Route::post('/login', [MainController::class, 'login']);

    // Protected
    Route::middleware('auth:sanctum')->group(function () {
        //Main data. App launch
        Route::post('/mainData', [MainController::class, 'mainData']);
        Route::post('/profile', [MainController::class, 'getProfile']);
        Route::post('/result', [MainController::class, 'getResult']);
        Route::post('/fees', [MainController::class, 'getFees']);
        //Logout
        Route::post('/logout', [MainController::class, 'logout']);
    });

});
