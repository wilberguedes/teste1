<?php

use App\Http\Controllers\InstallController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InstallController::class, 'index']);
Route::get('permissions', [InstallController::class, 'permissions']);

Route::get('setup', [InstallController::class, 'setup']);
Route::post('setup', [InstallController::class, 'setupStore']);

Route::get('user', [InstallController::class, 'user']);
Route::post('user', [InstallController::class, 'userStore']);

Route::get('database', [InstallController::class, 'database']);

Route::get('finalize', [InstallController::class, 'finalize']);

Route::match(['get', 'post'], 'finished', [
    InstallController::class, 'finished',
])->name('install.finished');
