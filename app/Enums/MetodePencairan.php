<?php

namespace App\Enums;

enum MetodePencairan: string
{
    case Transfer = 'transfer';
    case Tunai = 'tunai';

    public function label(): string
    {
        return match ($this) {
            self::Transfer => 'Transfer Rekening',
            self::Tunai => 'Tunai / Kas Langsung',
        };
    }
}
