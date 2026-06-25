<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlueprintController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
   Route::post('/logout', [AuthController::class, 'logout']);
   Route::apiResource('blueprints', BlueprintController::class);
});
