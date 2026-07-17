<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\CongregationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FamilyAltarController;
use App\Http\Controllers\Admin\PastorMessageController;
use App\Http\Controllers\Admin\PrayerRequestController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\WebsiteSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:admin-login')->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:admin-login')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::redirect('/', '/admin/dashboard');
    Route::get('/dashboard', DashboardController::class)
        ->middleware('permission:dashboard.view')->name('dashboard');

    Route::resource('admin-users', AdminUserController::class)->parameters(['admin-users' => 'adminUser'])
        ->middlewareFor(['index', 'show'], 'permission:admins.view')->middlewareFor(['create', 'store'], 'permission:admins.create')->middlewareFor(['edit', 'update'], 'permission:admins.update')->middlewareFor('destroy', 'permission:admins.delete');

    Route::get('congregations/export', [CongregationController::class, 'export'])->middleware('permission:congregations.export')->name('congregations.export');
    Route::resource('congregations', CongregationController::class)
        ->middlewareFor(['index', 'show'], 'permission:congregations.view')->middlewareFor(['create', 'store'], 'permission:congregations.create')->middlewareFor(['edit', 'update'], 'permission:congregations.update')->middlewareFor('destroy', 'permission:congregations.delete');

    Route::post('announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->middleware('permission:announcements.publish')->name('announcements.publish');
    Route::resource('announcements', AnnouncementController::class)
        ->middlewareFor(['index', 'show'], 'permission:announcements.view')->middlewareFor(['create', 'store'], 'permission:announcements.create')->middlewareFor(['edit', 'update'], 'permission:announcements.update')->middlewareFor('destroy', 'permission:announcements.delete');

    Route::get('prayer-requests/export', [PrayerRequestController::class, 'export'])->middleware('permission:prayer_requests.export')->name('prayer-requests.export');
    Route::patch('prayer-requests/bulk-status', [PrayerRequestController::class, 'bulkUpdate'])->middleware('permission:prayer_requests.update')->name('prayer-requests.bulk-status');
    Route::resource('prayer-requests', PrayerRequestController::class)->parameters(['prayer-requests' => 'prayerRequest'])->only(['index', 'show', 'update', 'destroy'])
        ->middlewareFor(['index', 'show'], 'permission:prayer_requests.view')->middlewareFor('update', 'permission:prayer_requests.update')->middlewareFor('destroy', 'permission:prayer_requests.delete');

    Route::resource('family-altars', FamilyAltarController::class)->parameters(['family-altars' => 'familyAltar'])
        ->middlewareFor(['index', 'show'], 'permission:family_altars.view')->middlewareFor(['create', 'store'], 'permission:family_altars.create')->middlewareFor(['edit', 'update'], 'permission:family_altars.update')->middlewareFor('destroy', 'permission:family_altars.delete');

    Route::post('pastor-messages/{pastorMessage}/publish', [PastorMessageController::class, 'publish'])->middleware('permission:pastor_messages.publish')->name('pastor-messages.publish');
    Route::resource('pastor-messages', PastorMessageController::class)->parameters(['pastor-messages' => 'pastorMessage'])
        ->middlewareFor(['index', 'show'], 'permission:pastor_messages.view')->middlewareFor(['create', 'store'], 'permission:pastor_messages.create')->middlewareFor(['edit', 'update'], 'permission:pastor_messages.update')->middlewareFor('destroy', 'permission:pastor_messages.delete');

    Route::get('settings', [WebsiteSettingController::class, 'index'])->middleware('permission:settings.view')->name('settings.index');
    Route::put('settings', [WebsiteSettingController::class, 'update'])->middleware('permission:settings.update')->name('settings.update');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:audit_logs.view')->name('audit-logs.index');
    Route::get('roles', [RoleController::class, 'index'])->middleware('role:Super Admin')->name('roles.index');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->middleware('role:Super Admin')->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->middleware('role:Super Admin')->name('roles.update');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
