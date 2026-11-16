<?php

namespace App\Policies;

use App\Enums\Role;
use App\Enums\StatusPengajuan;
use App\Models\PengajuanPengadaan;
use App\Models\User;

/**
 * Saat ini hanya pemilik (Kepala Divisi) yang punya akses.
 * Policy untuk Pejabat Pengadaan, PPK, Bendahara, Keuangan, dan Inventaris ditambahkan bersama modulnya.
 */
class PengajuanPengadaanPolicy
{
    public function view(User $user, PengajuanPengadaan $p): bool
    {
        if ($user->id === $p->pemohon_id) {
            return true;
        }

        // Pejabat Pengadaan meninjau request yang sudah dikirim (bukan draft milik orang lain).
        return $user->hasRole(Role::PejabatPengadaan) && $p->status !== StatusPengajuan::Draft;
    }

    public function update(User $user, PengajuanPengadaan $p): bool
    {
        return $user->id === $p->pemohon_id && $p->bisaDiedit();
    }

    public function delete(User $user, PengajuanPengadaan $p): bool
    {
        return $user->id === $p->pemohon_id && $p->status === StatusPengajuan::Draft;
    }

    public function lapor(User $user, PengajuanPengadaan $p): bool
    {
        return $user->id === $p->pemohon_id && $p->bisaLaporHarga();
    }
}
