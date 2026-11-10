<?php

namespace App\Models;

use App\Enums\Kategori;
use App\Enums\Satuan;
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
            'satuan' => Satuan::class,
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

    /** Dikembalikan Keuangan atau dana sudah cair: pemohon melapor harga aktual lewat form Lapor Harga. */
    public function bisaLaporHarga(): bool
    {
        return $this->status === StatusPengajuan::DanaCair
            || ($this->status === StatusPengajuan::Direvisi && $this->dikembalikan_oleh === 'keuangan');
    }

    /** Catatan pengembalian terakhir beserta pelakunya (untuk banner revisi). */
    public function revisiTerakhir(): ?RiwayatPengajuan
    {
        return $this->riwayat()
            ->reorder('id', 'desc')
            ->where('status_ke', StatusPengajuan::Direvisi->value)
            ->with('aktor')
            ->first();
    }

    /** Waktu dana dicairkan, diambil dari riwayat (tidak butuh kolom tambahan). */
    public function waktuPencairan(): ?\Illuminate\Support\Carbon
    {
        return $this->riwayat()
            ->reorder('id', 'desc')
            ->where('status_ke', StatusPengajuan::DanaCair->value)
            ->value('created_at');
    }

    /** Dana yang dicairkan; sebelum modul Bendahara mengisinya, dianggap sama dengan total estimasi. */
    public function danaDicairkanEfektif(): float
    {
        return (float) ($this->dana_dicairkan ?? $this->total_estimasi_biaya);
    }

    public function labelPengembalianDana(): string
    {
        if ($this->sisa_dana === null) {
            return '—';
        }

        if ((float) $this->sisa_dana <= 0) {
            return 'Tidak ada sisa dana';
        }

        return $this->status_pengembalian_dana ? 'Sudah dikonfirmasi Bendahara' : 'Belum dikembalikan';
    }

    /** Tombol utama per baris tabel (Kepala Divisi). */
    public function aksiUtama(): array
    {
        return match (true) {
            $this->status === StatusPengajuan::Draft => [
                'label' => 'Lanjutkan', 'url' => route('pengajuan.edit', $this), 'gaya' => 'biru',
            ],
            $this->status === StatusPengajuan::Direvisi && $this->dikembalikan_oleh === 'keuangan' => [
                'label' => 'Perbaiki', 'url' => route('pengajuan.lapor', $this), 'gaya' => 'merah',
            ],
            $this->status === StatusPengajuan::Direvisi => [
                'label' => 'Revisi', 'url' => route('pengajuan.edit', $this), 'gaya' => 'merah',
            ],
            $this->status === StatusPengajuan::DanaCair => [
                'label' => 'Lapor Harga', 'url' => route('pengajuan.lapor', $this), 'gaya' => 'amber',
            ],
            default => [
                'label' => 'Detail', 'url' => route('pengajuan.show', $this), 'gaya' => 'biru',
            ],
        };
    }
}
