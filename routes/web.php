<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/llms.txt', [SeoController::class, 'llms'])->name('seo.llms');

Route::get('/', LandingPageController::class)->name('landing');
Route::view('/app', 'welcome')->name('app');
Route::view('/download', 'welcome')->name('download');
Route::view('/privacy', 'legal-privacy')->name('privacy');
Route::view('/terms', 'legal-terms')->name('terms');

Route::permanentRedirect('/v2', '/app');
Route::permanentRedirect('/v2/download', '/download');
Route::permanentRedirect('/v2/api', '/app');
Route::permanentRedirect('/docs', '/');

Route::middleware('admin.web')->group(function () {
    Route::middleware(['admin.ip', 'admin.access'])->group(function () {
        Route::get('/admin/login', [AdminController::class, 'showLogin'])->name('admin.login.show');
        Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.post');
        Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');
    });

    Route::middleware(['admin.ip', 'admin.access', 'admin.password'])->group(function () {
        Route::get('/admin/mfa/setup', [AdminController::class, 'showTotpSetup'])->name('admin.mfa.setup.show');
        Route::post('/admin/mfa/setup', [AdminController::class, 'confirmTotpSetup'])->name('admin.mfa.setup.confirm');
        Route::get('/admin/mfa', [AdminController::class, 'showMfa'])->name('admin.mfa.show');
        Route::post('/admin/mfa', [AdminController::class, 'verifyMfa'])->name('admin.mfa.verify');
    });

    Route::middleware(['admin.ip', 'admin.access', 'admin.password', 'admin.totp'])->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
        Route::post('/admin/purge', [AdminController::class, 'purge'])->name('admin.purge');
    });
});
