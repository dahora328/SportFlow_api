<?php

use App\Http\Controllers\AthletesController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'Hello world!']);
});

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');

Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'getUser'])->name('user');
    Route::put('/user', [AuthController::class, 'updateUser'])->name('user.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::apiResource('athletes', AthletesController::class)->names('athletes');
});
