<?php

namespace App\Enums;

enum StatusPengajuan: string
{
    case Draft = 'draft';
    case Diajukan = 'diajukan';
    case DisetujuiPengadaan = 'disetujui_pengadaan';
    case PendingPpk = 'pending_ppk';
    case Direvisi = 'direvisi';
    case Disetujui = 'disetujui';
    case DanaCair = 'dana_cair';
    case MenungguPengembalian = 'menunggu_pengembalian';
    case CekFisik = 'cek_fisik';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Diajukan => 'Diajukan',
            self::DisetujuiPengadaan => 'Disetujui Pengadaan',
            self::PendingPpk => 'Menunggu PPK',
            self::Direvisi => 'Perlu Revisi',
            self::Disetujui => 'Disetujui PPK',
            self::DanaCair => 'Dana Dicairkan',
            self::MenungguPengembalian => 'Menunggu Pengembalian Sisa Dana',
            self::CekFisik => 'Pengecekan Fisik',
            self::Selesai => 'Selesai',
        };
    }

    /** Nama warna badge untuk frontend (kelas Tailwind dipetakan di Blade). */
    public function warna(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Diajukan, self::DisetujuiPengadaan, self::PendingPpk => 'blue',
            self::Direvisi => 'red',
            self::Disetujui, self::DanaCair, self::MenungguPengembalian, self::CekFisik => 'yellow',
            self::Selesai => 'green',
        };
    }
}
