<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/anggaran', [DashboardController::class, 'budgets'])->name('budgets.index');
Route::get('/kualitas-data', [DashboardController::class, 'quality'])->name('quality');
Route::get('/tentang', [DashboardController::class, 'about'])->name('about');
Route::get('/ekspor', [DashboardController::class, 'export'])->name('export');
Route::get('/kelola/impor/template', [DashboardController::class, 'template'])->name('admin.import.template');
Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware(['auth', 'can:manage-data'])->prefix('kelola')->group(function (): void {
    Route::get('/', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/anggaran', [AdminController::class, 'store'])->name('admin.budgets.store');
    Route::patch('/anggaran/{budget}', [AdminController::class, 'update'])->name('admin.budgets.update');
    Route::delete('/anggaran/{budget}', [AdminController::class, 'destroy'])->name('admin.budgets.destroy');
    Route::post('/impor', [AdminController::class, 'import'])->middleware('throttle:20,1')->name('admin.import');
});
