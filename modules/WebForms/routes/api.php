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
use Modules\WebForms\Http\Controllers\Api\WebFormController;

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('admin')->group(function () {
        Route::apiResource('/forms', WebFormController::class);
    });
});
