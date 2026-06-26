<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BlueprintController;
use App\Http\Controllers\PostController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
   Route::post('/logout', [AuthController::class, 'logout']);
   //blueprints
   Route::apiResource('blueprints', BlueprintController::class);
  
   //content
    Route::post('/content/repurpose', [TextController::class, 'store']);
    Route::get('/content', [TextController::class, 'index']);
    Route::get('/content/{text}', [TextController::class, 'show']);

   //posts
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{post}', [PostController::class, 'show']);
    Route::patch('/posts/{post}/status', [PostController::class, 'updateStatus']);
});
