<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SiteInformationController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserHasAdminPrivileges;
use App\Http\Middleware\EnsureUserIsSystemAdmin;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard API Routes (JSON responses for authenticated users)
    Route::prefix('api/dashboard')->group(function () {
        Route::get('/stats', [DashboardController::class, 'getStats'])
            ->name('api.dashboard.stats');
        
        Route::get('/user-growth', [DashboardController::class, 'getUserGrowth'])
            ->name('api.dashboard.user-growth');
        
        Route::get('/recent-users', [DashboardController::class, 'getRecentUsers'])
            ->name('api.dashboard.recent-users');
        
        Route::get('/recent-invitations', [DashboardController::class, 'getRecentInvitations'])
            ->name('api.dashboard.recent-invitations');
        
        Route::get('/recent-backups', [DashboardController::class, 'getRecentBackups'])
            ->name('api.dashboard.recent-backups');
        
        Route::post('/clear-cache', [DashboardController::class, 'clearCache'])
            ->middleware(EnsureUserIsSystemAdmin::class)
            ->name('api.dashboard.clear-cache');
    });
    
    // User Management (Admin and System Admin only)
    Route::resource('users', UserController::class);

    
    // User invitation routes
    Route::get('users-invite', [UserController::class, 'inviteForm'])->name('users.invite.form');
    Route::post('users-invite', [UserController::class, 'sendInvitation'])->name('users.invite.send');
    Route::get('invitations', [UserController::class, 'invitations'])->name('invitations.index');
    Route::post('invitations/{invitation}/resend', [UserController::class, 'resendInvitation'])->name('invitations.resend');
    Route::delete('invitations/{invitation}', [UserController::class, 'cancelInvitation'])->name('invitations.cancel');
    
    // Site Information - Single record management (Admin only)
    Route::middleware([EnsureUserHasAdminPrivileges::class])->group(function () {
        Route::get('site-information', [SiteInformationController::class, 'index'])->name('site-information.index');
        Route::post('site-information', [SiteInformationController::class, 'store'])->name('site-information.store');
        Route::delete('site-information', [SiteInformationController::class, 'destroy'])->name('site-information.destroy');
    });

    // System Admin only routes
    Route::middleware([EnsureUserIsSystemAdmin::class])->group(function () {
        // Site Settings
        Route::get('site-settings', [SiteSettingsController::class, 'index'])->name('site-settings.index');
        Route::post('site-settings', [SiteSettingsController::class, 'update'])->name('site-settings.update');
        Route::post('site-settings/remove-logo', [SiteSettingsController::class, 'removeLogo'])->name('site-settings.remove-logo');
        Route::post('site-settings/reset', [SiteSettingsController::class, 'reset'])->name('site-settings.reset');
        
        // Database Backup Management (System Admin only)
        Route::get('database-backup', [DatabaseBackupController::class, 'index'])->name('database-backup.index');
        Route::post('database-backup/create', [DatabaseBackupController::class, 'backup'])->name('database-backup.create');
        Route::get('database-backup/download/{filename}', [DatabaseBackupController::class, 'download'])->name('database-backup.download');
        Route::delete('database-backup/{filename}', [DatabaseBackupController::class, 'destroy'])->name('database-backup.delete');
        Route::post('database-backup/restore', [DatabaseBackupController::class, 'restore'])->name('database-backup.restore');
        Route::post('database-backup/upload-restore', [DatabaseBackupController::class, 'uploadAndRestore'])->name('database-backup.upload-restore');
    });

    // Inventories Page
    Route::get('inventories', [InventoryController::class, 'page'])->name('inventories.page');
    Route::get('inventories/{accountable}', [InventoryController::class, 'showPage'])->name('inventories.show');

    // Inventories API
    Route::prefix('api/inventories')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('api.inventories.index');
        Route::get('/by-accountable/{accountable}', [InventoryController::class, 'byAccountable'])->name('api.inventories.by-accountable');

        Route::middleware([EnsureUserHasAdminPrivileges::class])->group(function () {
            Route::post('/', [InventoryController::class, 'store'])->name('api.inventories.store');
            Route::post('/batch', [InventoryController::class, 'batchStore'])->name('api.inventories.batch');
            Route::put('/{inventory}', [InventoryController::class, 'update'])->name('api.inventories.update');
            Route::delete('/{inventory}', [InventoryController::class, 'destroy'])->name('api.inventories.destroy');
            Route::post('/import-excel', [InventoryController::class, 'importExcel'])->name('api.inventories.import-excel');
        });

        Route::get('/export-excel', [InventoryController::class, 'exportExcel'])->name('api.inventories.export-excel');
        Route::get('/export-pdf/{accountable}', [InventoryController::class, 'exportPdf'])->name('api.inventories.export-pdf');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
