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

    public function jenisMasalahLabel(): ?string
    {
        return match ($this->jenis_masalah) {
            'nominal' => 'Nominal tidak sesuai',
            'kuantitas' => 'Kuantitas tidak sesuai',
            'spesifikasi' => 'Barang tidak sesuai spesifikasi',
            'dokumen' => 'Dokumen tidak lengkap',
            default => null,
        };
    }
}
