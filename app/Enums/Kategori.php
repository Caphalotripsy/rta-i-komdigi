<?php

namespace App\Enums;

enum Kategori: string
{
    case Aset = 'aset';
    case HabisPakai = 'habis_pakai';

    public function label(): string
    {
        return match ($this) {
            self::Aset => 'Aset',
            self::HabisPakai => 'Habis Pakai',
        };
    }
}
