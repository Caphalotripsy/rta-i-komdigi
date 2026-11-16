<?php

namespace App\Enums;

enum StatusBatch: string
{
    case PendingPpk = 'pending_ppk';
    case Disetujui = 'disetujui';
    case Direvisi = 'direvisi';
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::PendingPpk => 'Menunggu PPK',
            self::Disetujui => 'Disetujui',
            self::Direvisi => 'Perlu Revisi',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /** Nama warna badge (dipetakan ke kelas Tailwind di komponen x-batch-badge). */
    public function warna(): string
    {
        return match ($this) {
            self::PendingPpk => 'blue',
            self::Disetujui => 'green',
            self::Direvisi => 'red',
            self::Dibatalkan => 'gray',
        };
    }
}
