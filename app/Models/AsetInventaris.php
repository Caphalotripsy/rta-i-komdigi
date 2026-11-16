<?php

namespace App\Models;

use App\Enums\KondisiAset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsetInventaris extends Model
{
    protected $table = 'aset_inventaris';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['kondisi' => KondisiAset::class];
    }

    public function inventaris(): BelongsTo
    {
        return $this->belongsTo(Inventaris::class, 'inventaris_id');
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanPengadaan::class, 'pengajuan_id');
    }

    public function mutasi(): HasMany
    {
        return $this->hasMany(MutasiAset::class, 'aset_inventaris_id')->orderByDesc('id');
    }

    /** Aset yang hilang dianggap tidak aktif. */
    public function aktif(): bool
    {
        return $this->kondisi !== KondisiAset::Hilang;
    }

    /** Pencarian SKU, nama barang, lokasi, atau penanggung jawab, plus filter kondisi. */
    public function scopeCari(Builder $query, ?string $q, ?string $kondisi): Builder
    {
        return $query
            ->when($q !== null && $q !== '', function ($w) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $w->where(fn ($x) => $x->where('sku_barcode', 'like', $like)->orWhere('lokasi', 'like', $like)
                    ->orWhere('nama_penanggung_jawab', 'like', $like)
                    ->orWhereHas('inventaris', fn ($i) => $i->where('nama_barang', 'like', $like)));
            })
            ->when(KondisiAset::tryFrom((string) $kondisi), fn ($w, $k) => $w->where('kondisi', $k->value));
    }
}
