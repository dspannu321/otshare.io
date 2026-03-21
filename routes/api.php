<?php

use App\Http\Controllers\Api\V1\DownloadController;
use App\Http\Controllers\Api\V1\RedeemController;
use App\Http\Controllers\Api\V1\ShareController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('security.headers')->group(function () {
    Route::post('share', [ShareController::class, 'store'])->middleware('throttle:shares_create');
    Route::post('redeem', [RedeemController::class, 'redeem'])->middleware('throttle:redeem');
    Route::get('download', [DownloadController::class, 'download'])->middleware('throttle:download');
    Route::post('download/confirm', [DownloadController::class, 'confirm'])->middleware('throttle:download');
});
