<?php

namespace App\Enums;

enum Role: string
{
    case KepalaDivisi = 'kepala_divisi';
    case PejabatPengadaan = 'pejabat_pengadaan';
    case Ppk = 'ppk';
    case Bendahara = 'bendahara';
    case Keuangan = 'keuangan';
    case Inventaris = 'inventaris';

    public function label(): string
    {
        return match ($this) {
            self::KepalaDivisi => 'Kepala Divisi',
            self::PejabatPengadaan => 'Pejabat Pengadaan',
            self::Ppk => 'PPK',
            self::Bendahara => 'Bendahara',
            self::Keuangan => 'Divisi Keuangan',
            self::Inventaris => 'Inventaris',
        };
    }
}
