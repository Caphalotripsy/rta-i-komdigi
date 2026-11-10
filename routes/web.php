<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PengajuanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Kepala Divisi (UC-01 s/d UC-03)
    Route::middleware('role:kepala_divisi')->group(function () {
        Route::resource('pengajuan', PengajuanController::class);
        Route::post('pengajuan/{pengajuan}/ajukan', [PengajuanController::class, 'ajukan'])->name('pengajuan.ajukan');
    });

    // Modul berikutnya ditambahkan di sini, contoh:
    // Route::middleware('role:pejabat_pengadaan')->group(function () { ... });
});
