<?php

use App\Http\Controllers\Api\Students\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('student')->middleware(['api', 'JsonRes'])
    ->group(function () {
        Route::get('/test', [MainController::class, 'test']);
    });