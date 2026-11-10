<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PengajuanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Kepala Divisi (UC-01 s/d UC-03)
    Route::middleware('role:kepala_divisi')->group(function () {
        Route::resource('pengajuan', PengajuanController::class);
        Route::post('pengajuan/{pengajuan}/ajukan', [PengajuanController::class, 'ajukan'])->name('pengajuan.ajukan');
        Route::get('pengajuan/{pengajuan}/lapor', [PengajuanController::class, 'lapor'])->name('pengajuan.lapor');
        Route::post('pengajuan/{pengajuan}/lapor', [PengajuanController::class, 'simpanLaporan'])->name('pengajuan.lapor.simpan');
    });

    // Modul berikutnya ditambahkan di sini, contoh:
    // Route::middleware('role:pejabat_pengadaan')->group(function () { ... });
});
