<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.2.2
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2023 KONKORD DIGITAL
 */

use Illuminate\Support\Facades\Route;
use Modules\Deals\Http\Controllers\Api\DealBoardController;
use Modules\Deals\Http\Controllers\Api\DealStatusController;
use Modules\Deals\Http\Controllers\Api\PipelineStageController;
use Modules\Deals\Http\Controllers\Api\UpdatePipelineDisplayOrder;

Route::middleware('auth:sanctum')->group(function () {
    // Deals management
    Route::put('/deals/{deal}/status/{status}', [DealStatusController::class, 'handle']);

    Route::prefix('deals/board')->group(function () {
        Route::get('{pipeline}', [DealBoardController::class, 'board']);
        Route::post('{pipeline}', [DealBoardController::class, 'update']);
        Route::get('{pipeline}/summary', [DealBoardController::class, 'summary']);
        Route::get('{pipeline}/{stageId}', [DealBoardController::class, 'load']);
    });

    // Deal routes
    Route::get('/pipelines/{pipeline}/stages', [PipelineStageController::class, 'index']);
    Route::post('/pipelines/order', UpdatePipelineDisplayOrder::class);
});
