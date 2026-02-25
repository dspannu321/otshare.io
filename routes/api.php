<?php

use App\Http\Controllers\Api\V1\DownloadController;
use App\Http\Controllers\Api\V1\RedeemController;
use App\Http\Controllers\Api\V1\ShareController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('security.headers')->group(function () {
    Route::post('shares', [ShareController::class, 'store'])->middleware('throttle:shares_create');
    Route::post('shares/{share}/upload', [ShareController::class, 'upload'])->middleware('throttle:upload')->name('api.shares.upload');
    Route::post('redeem', [RedeemController::class, 'redeem'])->middleware('throttle:redeem');
    Route::get('download', [DownloadController::class, 'download'])->middleware('throttle:download')->name('api.download');
    Route::post('download/confirm', [DownloadController::class, 'confirm'])->middleware('throttle:confirm');
});
