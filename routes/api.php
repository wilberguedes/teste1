<?php

use App\Http\Controllers\Api\SystemToolsController;
use App\Http\Controllers\Api\Twilio\TwilioAppController;
use App\Http\Controllers\Api\Twilio\TwilioController;
use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\Api\VoIPController;

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('permission:use voip')->group(function () {
        Route::get('/voip/token', [VoIPController::class, 'newToken']);
    });

    Route::middleware('admin')->group(function () {
        // Twilio integration routes
        Route::prefix('twilio')->group(function () {
            Route::delete('/', [TwilioController::class, 'destroy']);
            Route::get('numbers', [TwilioController::class, 'index']);

            Route::get('app/{id}', [TwilioAppController::class, 'show']);
            Route::post('app', [TwilioAppController::class, 'create']);
            Route::put('app/{id}', [TwilioAppController::class, 'update']);
            Route::delete('app/{sid}', [TwilioAppController::class, 'destroy']);
        });

        // Tools
        Route::prefix('tools')->group(function () {
            Route::get('finalize-update', [SystemToolsController::class, 'finalizeUpdate']);
            Route::get('json-language', [SystemToolsController::class, 'i18n']);
            Route::get('storage-link', [SystemToolsController::class, 'storageLink']);
            Route::get('clear-cache', [SystemToolsController::class, 'clearCache']);
            Route::get('migrate', [SystemToolsController::class, 'migrate']);
            Route::get('optimize', [SystemToolsController::class, 'optimize']);
            Route::get('seed-mailables', [SystemToolsController::class, 'seedMailableTemplates']);
        });
    });
});
