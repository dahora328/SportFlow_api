<?php

use App\Http\Controllers\AthletesController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EnterpriseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'Hello world!']);
});

// Rotas públicas (sem autenticação)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/refresh', [AuthController::class, 'refresh'])->name('refresh');
}); 

// Rotas protegidas (requer token JWT)
Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'me'])->name('user');
    Route::put('/user', [AuthController::class, 'updateUser'])->name('user.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // /register agora é protegido: somente admins (Super Admin ou Gestor) criam usuários
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1')
        ->name('register');

    // Export / Import da lista de atletas.
    // IMPORTANTE: declaradas ANTES do apiResource para não colidir com
    // a rota /athletes/{athlete} (show), que casaria com "export"/"import".
    Route::get('/athletes/export', [AthletesController::class, 'export'])->name('athletes.export');
    Route::get('/athletes/import/template', [AthletesController::class, 'importTemplate'])->name('athletes.import.template');
    Route::post('/athletes/import', [AthletesController::class, 'import'])->name('athletes.import');

    Route::apiResource('athletes', AthletesController::class)->names('athletes');
    Route::apiResource('enterprises', EnterpriseController::class)->names('enterprises');
    Route::apiResource('users', \App\Http\Controllers\UsersController::class)->names('users');
});

// Fallback 404 para rotas não encontradas na API
Route::fallback(function () {
    return response()->json(['message' => 'Página não existe'], 404);
});