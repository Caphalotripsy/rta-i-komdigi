<?php

use App\Http\Controllers\AntreanAsetController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\BatchingController;
use App\Http\Controllers\CetakController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PencairanController;
use App\Http\Controllers\MasterInventarisController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\PengembalianDanaController;
use App\Http\Controllers\PersetujuanBatchController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\VerifikasiController;
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

    // Pejabat Pengadaan (UC-05, UC-06 dan penanganan batch yang dikembalikan PPK)
    Route::middleware('role:pejabat_pengadaan')->group(function () {
        Route::get('approval', [ApprovalController::class, 'index'])->name('approval.index');
        Route::get('approval/{pengajuan}', [ApprovalController::class, 'show'])->name('approval.show');
        Route::post('approval/{pengajuan}/setujui', [ApprovalController::class, 'setujui'])->name('approval.setujui');
        Route::post('approval/{pengajuan}/kembalikan', [ApprovalController::class, 'kembalikan'])->name('approval.kembalikan');

        Route::get('batching', [BatchingController::class, 'create'])->name('batching.create');
        Route::post('batching', [BatchingController::class, 'store'])->name('batching.store');

        Route::get('batch', [BatchController::class, 'index'])->name('batch.index');
        Route::get('batch/dikembalikan', [BatchController::class, 'dikembalikan'])->name('batch.dikembalikan');
        Route::get('batch/{batch}', [BatchController::class, 'show'])->name('batch.show');
        Route::post('batch/{batch}/kirim-ulang', [BatchController::class, 'kirimUlang'])->name('batch.kirim-ulang');
        Route::post('batch/{batch}/batalkan', [BatchController::class, 'batalkan'])->name('batch.batalkan');
        Route::post('batch/{batch}/keluarkan/{pengajuan}', [BatchController::class, 'keluarkan'])->name('batch.keluarkan');
    });

    // PPK: keputusan per batch
    Route::middleware('role:ppk')->group(function () {
        Route::get('persetujuan-batch', [PersetujuanBatchController::class, 'index'])->name('ppk.batch.index');
        Route::get('persetujuan-batch/{batch}', [PersetujuanBatchController::class, 'show'])->name('ppk.batch.show');
        Route::post('persetujuan-batch/{batch}/setujui', [PersetujuanBatchController::class, 'setujui'])->name('ppk.batch.setujui');
        Route::post('persetujuan-batch/{batch}/kembalikan', [PersetujuanBatchController::class, 'kembalikan'])->name('ppk.batch.kembalikan');
        Route::get('riwayat-keputusan', [PersetujuanBatchController::class, 'riwayat'])->name('ppk.riwayat');
    });

    // Bendahara: pencairan dan pengembalian dana
    Route::middleware('role:bendahara')->group(function () {
        Route::get('pencairan', [PencairanController::class, 'index'])->name('pencairan.index');
        Route::get('pencairan/{pengajuan}', [PencairanController::class, 'show'])->name('pencairan.show');
        Route::post('pencairan/{pengajuan}', [PencairanController::class, 'store'])->name('pencairan.store');
        Route::get('pengembalian-dana', [PengembalianDanaController::class, 'index'])->name('pengembalian.index');
        Route::post('pengembalian-dana/{pengajuan}', [PengembalianDanaController::class, 'store'])->name('pengembalian.store');
    });

    // Divisi Keuangan: verifikasi fisik dan nota, closing
    Route::middleware('role:keuangan')->group(function () {
        Route::get('verifikasi', [VerifikasiController::class, 'index'])->name('verifikasi.index');
        Route::get('riwayat-verifikasi', [VerifikasiController::class, 'riwayat'])->name('verifikasi.riwayat');
        Route::get('verifikasi/{pengajuan}', [VerifikasiController::class, 'show'])->name('verifikasi.show');
        Route::post('verifikasi/{pengajuan}/valid', [VerifikasiController::class, 'valid'])->name('verifikasi.valid');
        Route::post('verifikasi/{pengajuan}/kembalikan', [VerifikasiController::class, 'kembalikan'])->name('verifikasi.kembalikan');
    });

    // Inventaris: registrasi aset, stok habis pakai, master inventaris, mutasi, cetak
    Route::middleware('role:inventaris')->group(function () {
        Route::get('antrean-aset', [AntreanAsetController::class, 'index'])->name('inventaris.antrean');
        Route::get('antrean-aset/{pengajuan}/registrasi', [AntreanAsetController::class, 'create'])->name('aset.registrasi');
        Route::post('antrean-aset/{pengajuan}/registrasi', [AntreanAsetController::class, 'store'])->name('aset.registrasi.simpan');

        Route::get('pembaruan-stok', [StokController::class, 'index'])->name('stok.index');
        Route::post('pembaruan-stok/{pengajuan}', [StokController::class, 'store'])->name('stok.simpan');

        Route::get('master-inventory', [MasterInventarisController::class, 'index'])->name('master.index');
        Route::get('master-inventory/aset/{aset}', [MasterInventarisController::class, 'show'])->name('master.aset');
        Route::post('master-inventory/aset/{aset}/mutasi', [MasterInventarisController::class, 'mutasi'])->name('master.aset.mutasi');

        Route::get('cetak/label', [CetakController::class, 'label'])->name('cetak.label');
        Route::get('cetak/laporan', [CetakController::class, 'laporan'])->name('cetak.laporan');
    });

    // Modul berikutnya ditambahkan di sini, contoh:
    // Route::middleware('role:pejabat_pengadaan')->group(function () { ... });
});
