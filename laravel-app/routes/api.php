<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BuildController;
use App\Http\Controllers\TelegramController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Webhook routes для Codemagic
Route::prefix('build')->group(function () {
    Route::post('/start', [BuildController::class, 'start']);
    Route::post('/finish', [BuildController::class, 'finish']);
    Route::post('/publish', [BuildController::class, 'publish']);
    Route::post('/promote', [BuildController::class, 'promote']);
});

// Telegram webhook
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);
