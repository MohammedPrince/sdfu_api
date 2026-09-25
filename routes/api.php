<?php

use App\Http\Controllers\Api\Students\MainController;
use App\Http\Controllers\Api\Students\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public
Route::prefix('student')->middleware('JsonRes')->group(function () {

    // Public
    Route::post('/login', [MainController::class, 'login']);

    // Protected
    Route::middleware('auth:sanctum')->group(function () {

        //Main data. App launch
        Route::post('/mainData', [MainController::class, 'mainData']);
        Route::post('/profile', [MainController::class, 'getProfile']);
        Route::post('/result', [MainController::class, 'getResult']);
        Route::post('/fees', [MainController::class, 'getFees']);
        Route::post('/timetable', [MainController::class, 'getTimetable']);
        Route::post('/password', [MainController::class, 'updatePassword']);

        //Notification
        Route::get('/notifications', [NotificationController::class, 'getNotifications']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markNotificationAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllNotificationsAsRead']);
        Route::post('/notifications/token', [NotificationController::class, 'registerToken']);
        Route::post('/notifications/token/remove', [NotificationController::class, 'unregisterToken']);

        //Logout
        Route::post('/logout', [MainController::class, 'logout']);
    });

    //Test
    Route::get('/test', [MainController::class, 'test']);

});
