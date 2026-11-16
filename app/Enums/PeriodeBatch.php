<?php

namespace App\Enums;

enum PeriodeBatch: string
{
    case Mingguan = 'mingguan';
    case Bulanan = 'bulanan';
    case Triwulan = 'triwulan';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
