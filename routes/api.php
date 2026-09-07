<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FeederReceiverController;
use App\Http\Controllers\Api\AuthApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// --- Mobile App Authentication (Laravel Sanctum) ---
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthApiController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/profile', [AuthApiController::class, 'profile']);
        Route::post('/auth/logout', [AuthApiController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Feeder Endpoint untuk Sync Data Dapodik dari sae-feeder
Route::post('/receive-data', [FeederReceiverController::class, 'receive'])->name('api.receive-data');
