<?php

namespace App\Models;

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
        return ['status' => StatusBatch::class];
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

    public function pengajuan(): HasMany
    {
        return $this->hasMany(PengajuanPengadaan::class, 'batch_id');
    }
}
