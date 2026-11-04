<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RealizationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

// Halaman publik untuk masyarakat (tanpa login).
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/profil', [DashboardController::class, 'profile'])->name('profile');
Route::get('/anggaran', [DashboardController::class, 'budgets'])->name('budgets.index');
Route::get('/pembiayaan', [DashboardController::class, 'financing'])->name('financing');
Route::get('/realisasi', [RealizationController::class, 'index'])->name('realizations.index');
Route::get('/grafik', [DashboardController::class, 'analytics'])->name('analytics');
Route::get('/laporan', [ReportController::class, 'index'])->name('reports.index');
Route::get('/laporan/unduh/{format}', [ReportController::class, 'download'])->middleware('throttle:30,1')->name('reports.download');
Route::get('/pengaduan', [ComplaintController::class, 'create'])->name('complaints.create');
Route::post('/pengaduan', [ComplaintController::class, 'store'])->middleware('throttle:complaints')->name('complaints.store');
Route::get('/pengaduan/cek', [ComplaintController::class, 'show'])->name('complaints.show');
Route::get('/kualitas-data', [DashboardController::class, 'quality'])->name('quality');
Route::get('/tentang', [DashboardController::class, 'about'])->name('about');
Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications');
Route::get('/ekspor', [DashboardController::class, 'export'])->name('export');
Route::get('/kelola/impor/template', [DashboardController::class, 'template'])->name('admin.import.template');

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

// Halaman perangkat desa.
Route::middleware(['auth', 'can:manage-data'])->prefix('kelola')->group(function (): void {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/anggaran', [AdminController::class, 'store'])->name('admin.budgets.store');
    Route::patch('/anggaran/{budget}', [AdminController::class, 'update'])->name('admin.budgets.update');
    Route::delete('/anggaran/{budget}', [AdminController::class, 'destroy'])->name('admin.budgets.destroy');
    Route::post('/impor', [AdminController::class, 'import'])->middleware('throttle:20,1')->name('admin.import');
    Route::get('/realisasi', [RealizationController::class, 'manage'])->name('admin.realizations.index');
    Route::post('/realisasi', [RealizationController::class, 'store'])->name('admin.realizations.store');
    Route::delete('/realisasi/{realization}', [RealizationController::class, 'destroy'])->name('admin.realizations.destroy');
    Route::get('/pengaduan', [ComplaintController::class, 'manage'])->name('admin.complaints.index');
    Route::patch('/pengaduan/{complaint}', [ComplaintController::class, 'update'])->name('admin.complaints.update');
    Route::get('/pengaturan', [SettingsController::class, 'edit'])->name('admin.settings');
    Route::put('/pengaturan/kata-sandi', [SettingsController::class, 'updatePassword'])->name('admin.settings.password');
});
