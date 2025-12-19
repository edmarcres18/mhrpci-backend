<?php

use App\Http\Controllers\CompanyPhoneController;
use App\Http\Controllers\ConsumableController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SiteInformationController;
use App\Http\Controllers\SiteSettingsController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureUserHasAdminPrivileges;
use App\Http\Middleware\EnsureUserIsSystemAdmin;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Public submission forms (no authentication)
Route::get('public/emails', [EmailController::class, 'publicForm'])->name('public.emails.form');
Route::post('public/emails', [EmailController::class, 'publicStore'])->middleware('throttle:20,1')->name('public.emails.store');

Route::get('public/company-phones', [CompanyPhoneController::class, 'publicForm'])->name('public.company-phones.form');
Route::post('public/company-phones', [CompanyPhoneController::class, 'publicStore'])->middleware('throttle:20,1')->name('public.company-phones.store');

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

        Route::get('/inventories-activity', [DashboardController::class, 'getInventoriesActivity'])
            ->name('api.dashboard.inventories-activity');
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
            Route::delete('/by-accountable/{accountable}', [InventoryController::class, 'destroyAccountable'])->name('api.inventories.destroy-accountable');
            Route::post('/import-excel', [InventoryController::class, 'importExcel'])->name('api.inventories.import-excel');
        });

        Route::get('/export-excel', [InventoryController::class, 'exportExcel'])->name('api.inventories.export-excel');
        Route::get('/export-summary-excel', [InventoryController::class, 'exportSummaryExcel'])->name('api.inventories.export-summary-excel');
        Route::get('/export-pdf', [InventoryController::class, 'exportPdfAll'])->name('api.inventories.export-pdf-all');
        Route::get('/export-pdf/{accountable}', [InventoryController::class, 'exportPdf'])->name('api.inventories.export-pdf');
    });

    // Consumables Pages
    Route::get('consumables', [ConsumableController::class, 'page'])->name('consumables.page');
    Route::get('consumables/create', [ConsumableController::class, 'createPage'])->name('consumables.create');
    Route::get('consumables/{consumable}/edit', [ConsumableController::class, 'editPage'])->name('consumables.edit');
    Route::get('consumables/usage-history', [ConsumableController::class, 'usageHistoryPage'])->name('consumables.usage-history');
    Route::get('consumables/logs', function () {
        return Inertia::render('Consumables/Logs');
    })->name('consumables.logs');
    Route::get('consumables/trash', function () {
        return Inertia::render('Consumables/Trash');
    })->name('consumables.trash');

    // Company Phones Pages
    Route::get('company-phones', [CompanyPhoneController::class, 'page'])->name('company-phones.page');
    Route::get('company-phones/{companyPhone}', [CompanyPhoneController::class, 'showPage'])->name('company-phones.show');
    Route::get('company-phones/create', [CompanyPhoneController::class, 'formPage'])->name('company-phones.create');
    Route::get('company-phones/{companyPhone}/edit', [CompanyPhoneController::class, 'formPage'])->name('company-phones.edit');
    Route::get('company-phones/import', [CompanyPhoneController::class, 'importPage'])->name('company-phones.import');

    // Emails Pages
    Route::get('emails', [EmailController::class, 'page'])->name('emails.page');
    Route::get('emails/{email}', [EmailController::class, 'showPage'])->middleware(EnsureUserHasAdminPrivileges::class)->name('emails.show');
    Route::get('emails/create', [EmailController::class, 'formPage'])->name('emails.create');
    Route::get('emails/{email}/edit', [EmailController::class, 'formPage'])->name('emails.edit');
    Route::get('emails/import', [EmailController::class, 'importPage'])->name('emails.import');

    // Consumables API
    Route::prefix('api/consumables')->group(function () {
        Route::get('/', [ConsumableController::class, 'index'])->name('api.consumables.index');
        Route::get('/usages', [ConsumableController::class, 'usages'])->name('api.consumables.usages');
        Route::get('/logs', [ConsumableController::class, 'logs'])->name('api.consumables.logs');
        Route::get('/trashed', [ConsumableController::class, 'trashed'])->name('api.consumables.trashed');
        Route::get('/{consumable}', [ConsumableController::class, 'show'])->whereNumber('consumable')->name('api.consumables.show');

        Route::middleware([EnsureUserHasAdminPrivileges::class])->group(function () {
            Route::post('/', [ConsumableController::class, 'store'])->name('api.consumables.store');
            Route::put('/{consumable}', [ConsumableController::class, 'update'])->whereNumber('consumable')->name('api.consumables.update');
            Route::delete('/{consumable}', [ConsumableController::class, 'destroy'])->whereNumber('consumable')->name('api.consumables.destroy');
            Route::post('/{consumable}/usage', [ConsumableController::class, 'usage'])->whereNumber('consumable')->name('api.consumables.usage');
            Route::post('/import-excel', [ConsumableController::class, 'importExcel'])->name('api.consumables.import-excel');
            Route::post('/{id}/restore', [ConsumableController::class, 'restore'])->whereNumber('id')->name('api.consumables.restore');
        });

        Route::delete('/{id}/force', [ConsumableController::class, 'forceDestroy'])
            ->whereNumber('id')
            ->middleware(EnsureUserIsSystemAdmin::class)
            ->name('api.consumables.force-destroy');

        Route::get('/export-excel', [ConsumableController::class, 'exportExcel'])->name('api.consumables.export-excel');
        Route::get('/export-pdf', [ConsumableController::class, 'exportPdf'])->name('api.consumables.export-pdf');
        Route::get('/export-usage-pdf', [ConsumableController::class, 'exportUsagePdf'])->name('api.consumables.export-usage-pdf');
    });

    // Emails API
    Route::prefix('api/emails')->group(function () {
        Route::get('/', [EmailController::class, 'index'])->name('api.emails.index');

        Route::middleware([EnsureUserHasAdminPrivileges::class])->group(function () {
            Route::post('/', [EmailController::class, 'store'])->name('api.emails.store');
            Route::put('/{email}', [EmailController::class, 'update'])->name('api.emails.update');
            Route::delete('/{email}', [EmailController::class, 'destroy'])->name('api.emails.destroy');
            Route::post('/import', [EmailController::class, 'importExcel'])->name('api.emails.import');
        });

        Route::get('/export/excel', [EmailController::class, 'exportExcel'])->middleware(EnsureUserHasAdminPrivileges::class)->name('api.emails.export.excel');
        Route::get('/export/pdf', [EmailController::class, 'exportPDF'])->middleware(EnsureUserHasAdminPrivileges::class)->name('api.emails.export.pdf');
    });

    // Company Phones API
    Route::prefix('api/company-phones')->group(function () {
        Route::get('/', [CompanyPhoneController::class, 'index'])->name('api.company-phones.index');

        Route::middleware([EnsureUserHasAdminPrivileges::class])->group(function () {
            Route::post('/', [CompanyPhoneController::class, 'store'])->name('api.company-phones.store');
            Route::put('/{companyPhone}', [CompanyPhoneController::class, 'update'])->name('api.company-phones.update');
            Route::delete('/{companyPhone}', [CompanyPhoneController::class, 'destroy'])->name('api.company-phones.destroy');
            Route::post('/import', [CompanyPhoneController::class, 'importExcel'])->name('api.company-phones.import');
        });

        Route::get('/export/excel', [CompanyPhoneController::class, 'exportExcel'])->name('api.company-phones.export.excel');
        Route::get('/export/pdf', [CompanyPhoneController::class, 'exportPDF'])->name('api.company-phones.export.pdf');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
