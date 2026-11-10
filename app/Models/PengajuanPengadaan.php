<?php

namespace App\Models;

use App\Enums\Kategori;
use App\Enums\StatusPengajuan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PengajuanPengadaan extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_pengadaan';

    // Selalu isi dari $request->validated(), jangan dari $request->all().
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'kategori' => Kategori::class,
            'status' => StatusPengajuan::class,
            'harga_estimasi' => 'decimal:2',
            'total_estimasi_biaya' => 'decimal:2',
            'dana_dicairkan' => 'decimal:2',
            'harga_aktual' => 'decimal:2',
            'total_biaya_aktual' => 'decimal:2',
            'sisa_dana' => 'decimal:2',
            'status_pengembalian_dana' => 'boolean',
            'tanggal_nota' => 'date',
            'diproses_inventaris_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $p) {
            $p->updateQuietly([
                'kode_pengajuan' => sprintf('REQ-%s-%05d', $p->created_at->format('Y'), $p->id),
            ]);
        });
    }

    public function pemohon(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pemohon_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BatchPengadaan::class, 'batch_id');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatPengajuan::class, 'pengajuan_id')->orderBy('id');
    }

    public function inventaris(): HasOne
    {
        return $this->hasOne(Inventaris::class, 'pengajuan_id');
    }

    public function asetInventaris(): HasMany
    {
        return $this->hasMany(AsetInventaris::class, 'pengajuan_id');
    }

    /** Draft, atau dikembalikan Pejabat Pengadaan (dikembalikan Keuangan diperbaiki lewat form harga aktual, bukan form ini). */
    public function bisaDiedit(): bool
    {
        return $this->status === StatusPengajuan::Draft
            || ($this->status === StatusPengajuan::Direvisi && $this->dikembalikan_oleh === 'pengadaan');
    }

    public function catatanRevisiTerakhir(): ?string
    {
        return $this->riwayat()
            ->reorder('id', 'desc')
            ->where('status_ke', StatusPengajuan::Direvisi->value)
            ->value('catatan');
    }
}
