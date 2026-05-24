<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\PublicCertificateVerificationController;
use App\Http\Controllers\BackupController;

// ---------------------------------------------------------------------------
// Resolve login slug from settings (gracefully handles unconfigured DB)
// ---------------------------------------------------------------------------
$loginSlug = 'login';
try {
    $slug = \App\Models\Setting::get('login_slug', 'login');
    if ($slug && preg_match('/^[a-zA-Z0-9\-_]+$/', $slug)) {
        $loginSlug = $slug;
    }
} catch (\Throwable) {}

// ---------------------------------------------------------------------------
// Root URL — accessible to everyone
// Guests: show 403/restricted page  |  Auth users: redirect to dashboard
// ---------------------------------------------------------------------------
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('persons.index');
    }
    $title   = 'Access Restricted';
    $message = 'You do not have permission to access this area.';
    try {
        $title   = \App\Models\Setting::get('root_access_title',   $title)   ?: $title;
        $message = \App\Models\Setting::get('root_access_message', $message) ?: $message;
    } catch (\Throwable) {}
    return response(view('errors.403', compact('title', 'message')), 403);
});

// ---------------------------------------------------------------------------
// Public certificate verification
// ---------------------------------------------------------------------------
Route::get('/verify', [PublicCertificateVerificationController::class, 'index'])->name('verify.index');
Route::get('/v/{token}', [PublicCertificateVerificationController::class, 'byToken'])->name('verify.token');

// ---------------------------------------------------------------------------
// Authentication routes (guests only)
// ---------------------------------------------------------------------------
Route::middleware('guest')->group(function () use ($loginSlug) {
    Route::get('/'  . $loginSlug, [AuthController::class, 'showLogin'])->name('login');
    Route::post('/' . $loginSlug, [AuthController::class, 'login'])->name('login.post');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// ---------------------------------------------------------------------------
// Protected routes — require authentication
// ---------------------------------------------------------------------------
Route::middleware('auth')->group(function () {

    // ── Members (main dashboard) ─────────────────────────────────────────────
    // URL prefix is "dashboard" instead of "persons"; route names stay persons.*

    // Bulk-destroy must be declared BEFORE resource to avoid {person} wildcard
    Route::post('dashboard/bulk-destroy', [PersonController::class, 'bulkDestroy'])
        ->name('persons.bulk-destroy');

    // Import / export / sample CSV
    Route::get('dashboard-import',  [PersonController::class, 'importForm'])->name('persons.import.form');
    Route::post('dashboard-import', [PersonController::class, 'importCsv'])->name('persons.import');
    Route::get('dashboard-export',  [PersonController::class, 'exportForm'])->name('persons.export');
    Route::post('dashboard-export', [PersonController::class, 'exportCsv'])->name('persons.export.download');
    Route::get('dashboard-sample',  [PersonController::class, 'sampleCsv'])->name('persons.sample');

    // Resource CRUD — bound to /dashboard, parameter kept as {person}
    Route::resource('dashboard', PersonController::class)
        ->parameters(['dashboard' => 'person'])
        ->names([
            'index'   => 'persons.index',
            'create'  => 'persons.create',
            'store'   => 'persons.store',
            'show'    => 'persons.show',
            'edit'    => 'persons.edit',
            'update'  => 'persons.update',
            'destroy' => 'persons.destroy',
        ]);

    // ── Media Library ────────────────────────────────────────────────────────
    Route::get ('media',               [MediaController::class, 'index'])->name('media.index');
    Route::post('media/upload',        [MediaController::class, 'upload'])->name('media.upload');
    Route::post('media/bulk-destroy',  [MediaController::class, 'bulkDestroy'])->name('media.bulk-destroy');
    Route::get ('media/{medium}',      [MediaController::class, 'show'])->name('media.show');
    Route::put ('media/{medium}',      [MediaController::class, 'update'])->name('media.update');
    Route::delete('media/{medium}',    [MediaController::class, 'destroy'])->name('media.destroy');
    Route::get ('media/{medium}/download', [MediaController::class, 'download'])->name('media.download');

    // ── Settings (admin only) ────────────────────────────────────────────────
    Route::get('settings',               [SettingController::class, 'index'])->name('settings.index');
    Route::put('settings',               [SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/test-captcha', [SettingController::class, 'testCaptcha'])->name('settings.test-captcha');

    // ── Backup & Restore (admin only) ────────────────────────────────────────
    Route::get('backups',                    [BackupController::class, 'index'])->name('backups.index');
    Route::post('backups/create',            [BackupController::class, 'create'])->name('backups.create');
    Route::get('backups/{backup}/download',  [BackupController::class, 'download'])->name('backups.download');
    Route::post('backups/{backup}/restore',  [BackupController::class, 'restore'])->name('backups.restore');
    Route::delete('backups/{backup}',        [BackupController::class, 'delete'])->name('backups.delete');
    Route::post('cache/clear',               [BackupController::class, 'clearCache'])->name('cache.clear');

    // ── User management (admin only) ─────────────────────────────────────────
    Route::resource('users', UserController::class)->except(['show']);

    // ── Certificates ───────────────────────────────────────────────────────────
    Route::get('certificates/{certificate}/qr', [CertificateController::class, 'qr'])->name('certificates.qr');
    Route::resource('certificates', CertificateController::class)->except(['show']);
    // ── Update system (admin only) ────────────────────────────────────────────
    Route::get ('update',       [UpdateController::class, 'index'])->name('update.index');
    Route::post('update/check', [UpdateController::class, 'check'])->name('update.check');
    Route::post('update/run',   [UpdateController::class, 'doUpdate'])->name('update.run');});

