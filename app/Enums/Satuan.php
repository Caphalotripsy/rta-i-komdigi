<?php

namespace App\Enums;

enum Satuan: string
{
    case Unit = 'unit';
    case Pcs = 'pcs';
    case Rim = 'rim';
    case Box = 'box';
    case Botol = 'botol';
    case Lusin = 'lusin';
    case Set = 'set';
    case Pak = 'pak';
    case Roll = 'roll';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
