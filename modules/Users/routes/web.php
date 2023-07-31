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
use Modules\Core\Http\Middleware\PreventRequestsWhenMigrationNeeded;
use Modules\Core\Http\Middleware\PreventRequestsWhenUpdateNotFinished;
use Modules\Users\Http\Controllers\UserInvitationAcceptController;

Route::withoutMiddleware([
    PreventRequestsWhenMigrationNeeded::class,
    PreventRequestsWhenUpdateNotFinished::class,
])->group(function () {
    Route::get('/invitation/{token}', [UserInvitationAcceptController::class, 'show'])->name('invitation.show');
    Route::post('/invitation/{token}', [UserInvitationAcceptController::class, 'accept']);
});
