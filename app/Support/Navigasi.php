<?php

namespace App\Support;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Menu sidebar per peran. Peran lain menambahkan itemnya di sini bersama modulnya;
 * item yang rutenya belum ada otomatis disembunyikan.
 */
class Navigasi
{
    /** @return list<array{label: string, url: string, aktif: bool, ikon: string}> */
    public static function untuk(User $user): array
    {
        // [label, nama rute, pola rute aktif (pisah dengan |), ikon]
        $daftar = match ($user->role) {
            Role::KepalaDivisi => [
                ['Dashboard', 'dashboard', 'dashboard', 'dashboard'],
                ['Tambah Request', 'pengajuan.create', 'pengajuan.create', 'tambah'],
                ['Request Saya', 'pengajuan.index', 'pengajuan.index|pengajuan.show|pengajuan.edit|pengajuan.lapor*', 'daftar'],
            ],
            Role::PejabatPengadaan => [
                ['Dashboard', 'dashboard', 'dashboard', 'dashboard'],
                ['Approval Tahap 1', 'approval.index', 'approval.*', 'centang'],
                ['Batching', 'batching.create', 'batching.*', 'arsip'],
                ['Batch Dikembalikan', 'batch.dikembalikan', 'batch.dikembalikan', 'balik'],
                ['Riwayat Batch', 'batch.index', 'batch.index|batch.show', 'jam'],
            ],
            Role::Ppk => [
                ['Dashboard', 'dashboard', 'dashboard', 'dashboard'],
                ['Persetujuan Batch', 'ppk.batch.index', 'ppk.batch.*', 'centang'],
                ['Riwayat Keputusan', 'ppk.riwayat', 'ppk.riwayat', 'jam'],
            ],
            Role::Bendahara => [
                ['Dashboard', 'dashboard', 'dashboard', 'dashboard'],
                ['Pencairan Dana', 'pencairan.index', 'pencairan.*', 'dompet'],
                ['Pengembalian Dana', 'pengembalian.index', 'pengembalian.*', 'balik'],
            ],
            Role::Keuangan => [
                ['Dashboard', 'dashboard', 'dashboard', 'dashboard'],
                ['Verifikasi & Closing', 'verifikasi.index', 'verifikasi.index|verifikasi.show', 'centang'],
                ['Riwayat Verifikasi', 'verifikasi.riwayat', 'verifikasi.riwayat', 'jam'],
            ],
            Role::Inventaris => [
                ['Dashboard', 'dashboard', 'dashboard', 'dashboard'],
                ['Draft Antrean Aset', 'inventaris.antrean', 'inventaris.antrean|aset.*', 'arsip'],
                ['Pembaruan Stok', 'stok.index', 'stok.*', 'tambah'],
                ['Master Inventory', 'master.index', 'master.*', 'daftar'],
            ],
            default => [
                ['Dashboard', 'dashboard', 'dashboard', 'dashboard'],
            ],
        };

        return collect($daftar)
            ->filter(fn ($item) => Route::has($item[1]))
            ->map(fn ($item) => [
                'label' => $item[0],
                'url' => route($item[1]),
                'aktif' => request()->routeIs(...explode('|', $item[2])),
                'ikon' => $item[3],
            ])
            ->values()
            ->all();
    }
}
