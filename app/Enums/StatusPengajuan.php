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
            self::DisetujuiPengadaan => 'Siap Batching',
            self::PendingPpk => 'Menunggu PPK',
            self::Direvisi => 'Perlu Revisi',
            self::Disetujui => 'Disetujui PPK',
            self::DanaCair => 'Dana Dicairkan',
            self::MenungguPengembalian => 'Menunggu Pengembalian Dana',
            self::CekFisik => 'Cek Fisik',
            self::Selesai => 'Selesai',
        };
    }

    /** Penjelasan singkat posisi request, dari sudut pandang pemohon (Kepala Divisi). */
    public function infoUntukPemohon(): string
    {
        return match ($this) {
            self::Draft => 'Request belum dikirim.',
            self::Diajukan => 'Menunggu tinjauan Pejabat Pengadaan.',
            self::DisetujuiPengadaan => 'Lolos tinjauan, menunggu dimasukkan ke batch.',
            self::PendingPpk => 'Menunggu keputusan PPK atas batch.',
            self::Direvisi => 'Request dikembalikan dan perlu diperbaiki.',
            self::Disetujui => 'Disetujui PPK, menunggu pencairan dana oleh Bendahara.',
            self::DanaCair => 'Dana telah dicairkan. Laporkan harga aktual pembelian.',
            self::MenungguPengembalian => 'Kembalikan sisa dana ke Bendahara.',
            self::CekFisik => 'Serahkan nota dan barang fisik ke Divisi Keuangan.',
            self::Selesai => 'Proses selesai.',
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
