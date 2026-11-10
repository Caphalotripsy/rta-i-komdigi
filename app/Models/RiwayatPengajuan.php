<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPengajuan extends Model
{
    protected $table = 'riwayat_pengajuan';

    protected $guarded = ['id'];

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanPengadaan::class, 'pengajuan_id');
    }

    public function aktor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aktor_id');
    }
}
