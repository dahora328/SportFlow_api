<?php

use App\Http\Controllers\AthletesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnterpriseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'Hello world!']);
});

// Rotas públicas (sem autenticação)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh'); 

// Rotas protegidas (requer token JWT)
Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'me'])->name('user');
    Route::put('/user', [AuthController::class, 'updateUser'])->name('user.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // /register agora é protegido: somente admins (Super Admin ou Gestor) criam usuários
    Route::post('/register', [AuthController::class, 'register'])->name('register');

    Route::apiResource('athletes', AthletesController::class)->names('athletes');
    Route::apiResource('enterprises', EnterpriseController::class)->names('enterprises');
});

// Fallback 404 para rotas não encontradas na API
Route::fallback(function () {
    return response()->json(['message' => 'Página não existe'], 404);
});