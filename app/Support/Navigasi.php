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
