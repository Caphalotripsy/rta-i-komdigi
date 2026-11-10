<?php

namespace App\Models;

use App\Enums\Kategori;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventaris extends Model
{
    protected $table = 'inventaris';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['kategori' => Kategori::class];
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanPengadaan::class, 'pengajuan_id');
    }

    public function aset(): HasMany
    {
        return $this->hasMany(AsetInventaris::class, 'inventaris_id');
    }
}
