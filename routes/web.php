<?php

use App\Http\Controllers\Web\AnnouncementController;
use App\Http\Controllers\Web\BrandAssetController;
use App\Http\Controllers\Web\FamilyAltarController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\PastorMessageController;
use App\Http\Controllers\Web\PrayerRequestController;
use App\Http\Controllers\Web\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/brand/logo.webp', [BrandAssetController::class, 'logo'])->name('brand.logo');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('terms');
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement:slug}', [AnnouncementController::class, 'show'])->name('announcements.show');
Route::get('/pastor-messages', [PastorMessageController::class, 'index'])->name('pastor-messages.index');
Route::get('/pastor-messages/{pastorMessage:slug}', [PastorMessageController::class, 'show'])->name('pastor-messages.show');
Route::get('/family-altars', FamilyAltarController::class)->name('family-altars.index');
Route::get('/prayer-request', [PrayerRequestController::class, 'create'])->name('prayer-request.create');
Route::post('/prayer-request', [PrayerRequestController::class, 'store'])->middleware('throttle:prayer-request')->name('prayer-request.store');
Route::get('/prayer-request/success/{reference}', [PrayerRequestController::class, 'success'])->name('prayer-request.success');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
