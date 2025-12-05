<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BattleController;
use App\Http\Controllers\MonsterController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/monsters', [MonsterController::class, 'index']);
Route::post('/monsters', [MonsterController::class, 'store']);

Route::post('/battles', [BattleController::class, 'store']);

Route::middleware('auth:api')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::get('/monsters', [MonsterController::class, 'index']);
Route::post('/monsters', [MonsterController::class, 'store']);

Route::post('/battles', [BattleController::class, 'store']);
