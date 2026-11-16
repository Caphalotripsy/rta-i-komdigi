<?php

namespace App\Enums;

enum KondisiAset: string
{
    case Baik = 'baik';
    case Rusak = 'rusak';
    case Hilang = 'hilang';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function warna(): string
    {
        return match ($this) {
            self::Baik => 'green',
            self::Rusak => 'yellow',
            self::Hilang => 'red',
        };
    }
}
