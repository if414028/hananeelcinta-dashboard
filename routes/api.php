<?php

use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\ConfigController;
use App\Http\Controllers\Api\V1\FamilyAltarController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\MobileSessionController;
use App\Http\Controllers\Api\V1\PastorMessageController;
use App\Http\Controllers\Api\V1\PrayerRequestController;
use App\Support\ApiResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:public-api')->group(function (): void {
    Route::get('/health', fn () => ApiResponse::success(['version' => 'v1'], 'API is available.'));
    Route::get('/config', ConfigController::class)->middleware('cache.headers:public;max_age=300;etag')->name('api.v1.config');
    Route::get('/home', HomeController::class)->middleware('cache.headers:public;max_age=300;etag')->name('api.v1.home');
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('api.v1.announcements.index');
    Route::get('/announcements/{announcement:slug}', [AnnouncementController::class, 'show'])->name('api.v1.announcements.show');
    Route::get('/pastor-messages', [PastorMessageController::class, 'index'])->name('api.v1.pastor-messages.index');
    Route::get('/pastor-messages/{pastorMessage:slug}', [PastorMessageController::class, 'show'])->name('api.v1.pastor-messages.show');
    Route::get('/family-altars', [FamilyAltarController::class, 'index'])->name('api.v1.family-altars.index');
    Route::get('/family-altars/{familyAltar}', [FamilyAltarController::class, 'show'])->name('api.v1.family-altars.show');
});

Route::prefix('v1')->middleware('throttle:prayer-request')->group(function (): void {
    Route::post('/prayer-requests', PrayerRequestController::class)->name('api.v1.prayer-requests.store');
});

Route::prefix('v1')->middleware(['throttle:mobile-auth', 'auth.firebase'])->group(function (): void {
    Route::post('/auth/session', [MobileSessionController::class, 'store'])->name('api.v1.auth.session');
    Route::get('/me', [MobileSessionController::class, 'show'])->name('api.v1.me');
});
