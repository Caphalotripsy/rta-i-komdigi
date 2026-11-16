<?php

namespace App\Models;

use App\Enums\PeriodeBatch;
use App\Enums\StatusBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchPengadaan extends Model
{
    protected $table = 'batch_pengadaan';

    // Selalu isi dari $request->validated(), jangan dari $request->all().
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => StatusBatch::class,
            'periode' => PeriodeBatch::class,
            'diputuskan_pada' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (self $batch) {
            $batch->updateQuietly([
                'kode_batch' => sprintf('BTC-%s-%04d', $batch->created_at->format('Ym'), $batch->id),
            ]);
        });
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** PPK yang memutuskan (menyetujui atau mengembalikan) batch ini. */
    public function pemutus(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh');
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(PengajuanPengadaan::class, 'batch_id');
    }

    /** Total estimasi isi batch; memakai hasil withSum() bila sudah dimuat agar tidak query ulang. */
    public function totalEstimasi(): float
    {
        return (float) ($this->pengajuan_sum_total_estimasi_biaya ?? $this->pengajuan()->sum('total_estimasi_biaya'));
    }

    public function jumlahRequest(): int
    {
        return (int) ($this->pengajuan_count ?? $this->pengajuan()->count());
    }

    /** Contoh: "Bulanan · November 2026". */
    public function labelPeriode(): string
    {
        return $this->periode->label().' · '.$this->created_at->translatedFormat('F Y');
    }
}
