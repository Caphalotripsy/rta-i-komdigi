<?php

namespace App\Services;

use App\Enums\PeriodeBatch;
use App\Enums\StatusBatch;
use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Models\BatchPengadaan;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * UC-05 (batching) dan penanganan batch yang dikembalikan PPK (opsi A).
 *
 * Status request mengikuti batch:
 *   Siap Batching --buat batch--> Menunggu PPK --PPK kembalikan--> Siap Batching (batch Perlu Revisi)
 *   Perlu Revisi batch: keluarkan request -> request Perlu Revisi (kembali ke Kepala Divisi, batch_id dikosongkan)
 *                       kirim ulang       -> Menunggu PPK
 *                       batalkan          -> request kembali ke antrean Siap Batching
 */
class BatchService
{
    public function __construct(private PengajuanService $pengajuan)
    {
    }

    /** @param list<int|string> $idPengajuan */
    public function buat(User $pembuat, PeriodeBatch $periode, array $idPengajuan): BatchPengadaan
    {
        $ids = array_values(array_unique(array_map('intval', $idPengajuan)));

        if ($ids === []) {
            throw new StatusTidakValidException('Pilih minimal satu request untuk dibatch.');
        }

        return DB::transaction(function () use ($pembuat, $periode, $ids) {
            $daftar = PengajuanPengadaan::whereIn('id', $ids)->lockForUpdate()->get();

            $layak = $daftar->filter(fn ($p) => $p->status === StatusPengajuan::DisetujuiPengadaan && $p->batch_id === null);

            if ($daftar->count() !== count($ids) || $layak->count() !== count($ids)) {
                throw new StatusTidakValidException('Sebagian request sudah tidak berstatus Siap Batching. Muat ulang halaman lalu pilih kembali.');
            }

            $batch = BatchPengadaan::create([
                'periode' => $periode,
                'status' => StatusBatch::PendingPpk,
                'dibuat_oleh' => $pembuat->id,
            ])->refresh();

            foreach ($layak as $p) {
                $this->pengajuan->ubahStatus(
                    $p, StatusPengajuan::PendingPpk, $pembuat, [StatusPengajuan::DisetujuiPengadaan],
                    "Diteruskan dalam batch {$batch->kode_batch}", null, ['batch_id' => $batch->id],
                );
            }

            return $batch;
        });
    }

    /** Kirim ulang batch yang dikembalikan PPK, setelah request bermasalah dikeluarkan. */
    public function kirimUlang(BatchPengadaan $batch, User $aktor): BatchPengadaan
    {
        return DB::transaction(function () use ($batch, $aktor) {
            $batch = $this->kunci($batch);
            $this->pastikanPerluRevisi($batch);

            $isi = $batch->pengajuan()->get();

            if ($isi->isEmpty()) {
                throw new StatusTidakValidException('Batch tidak berisi request. Batalkan batch ini bila tidak diperlukan.');
            }

            foreach ($isi as $p) {
                $this->pengajuan->ubahStatus(
                    $p, StatusPengajuan::PendingPpk, $aktor, [StatusPengajuan::DisetujuiPengadaan],
                    "Batch {$batch->kode_batch} dikirim ulang ke PPK",
                );
            }

            $batch->update([
                'status' => StatusBatch::PendingPpk,
                'catatan_revisi' => null,
                'diputuskan_oleh' => null,
                'diputuskan_pada' => null,
            ]);

            return $batch->refresh();
        });
    }

    /** Keluarkan satu request dari batch Perlu Revisi dan kembalikan ke Kepala Divisi (catatan wajib). */
    public function keluarkan(BatchPengadaan $batch, PengajuanPengadaan $p, User $aktor, string $catatan): BatchPengadaan
    {
        return DB::transaction(function () use ($batch, $p, $aktor, $catatan) {
            $batch = $this->kunci($batch);
            $this->pastikanPerluRevisi($batch);

            if ($p->batch_id !== $batch->id) {
                throw new StatusTidakValidException('Request ini bukan bagian dari batch tersebut.');
            }

            $this->pengajuan->ubahStatus(
                $p, StatusPengajuan::Direvisi, $aktor, [StatusPengajuan::DisetujuiPengadaan],
                $catatan, null, ['batch_id' => null, 'dikembalikan_oleh' => 'pengadaan'],
            );

            // Batch kosong tidak berguna: batalkan otomatis.
            if (! $batch->pengajuan()->exists()) {
                $batch->update(['status' => StatusBatch::Dibatalkan]);
            }

            return $batch->refresh();
        });
    }

    /** Batalkan batch Perlu Revisi; request di dalamnya kembali ke antrean Siap Batching. */
    public function batalkan(BatchPengadaan $batch, User $aktor): BatchPengadaan
    {
        return DB::transaction(function () use ($batch, $aktor) {
            $batch = $this->kunci($batch);
            $this->pastikanPerluRevisi($batch);

            foreach ($batch->pengajuan()->get() as $p) {
                $this->pengajuan->ubahStatus(
                    $p, StatusPengajuan::DisetujuiPengadaan, $aktor, [StatusPengajuan::DisetujuiPengadaan],
                    "Batch {$batch->kode_batch} dibatalkan; kembali ke antrean Siap Batching", null, ['batch_id' => null],
                );
            }

            $batch->update(['status' => StatusBatch::Dibatalkan]);

            return $batch->refresh();
        });
    }

    /** Keputusan PPK per batch: setujui. Seluruh request di dalamnya menjadi Disetujui PPK. */
    public function setujui(BatchPengadaan $batch, User $ppk): BatchPengadaan
    {
        return DB::transaction(function () use ($batch, $ppk) {
            $batch = $this->kunci($batch);
            $this->pastikanMenungguPpk($batch);

            $isi = $this->isiBatch($batch);

            foreach ($isi as $p) {
                $this->pengajuan->ubahStatus(
                    $p, StatusPengajuan::Disetujui, $ppk, [StatusPengajuan::PendingPpk],
                    "Batch {$batch->kode_batch} disetujui PPK",
                );
            }

            $batch->update(['status' => StatusBatch::Disetujui, 'catatan_revisi' => null, ...$this->jejakKeputusan($ppk)]);

            return $batch->refresh();
        });
    }

    /** Keputusan PPK per batch: kembalikan (catatan wajib). Request kembali ke Siap Batching dan tetap terhubung ke batch. */
    public function kembalikan(BatchPengadaan $batch, User $ppk, string $catatan): BatchPengadaan
    {
        return DB::transaction(function () use ($batch, $ppk, $catatan) {
            $batch = $this->kunci($batch);
            $this->pastikanMenungguPpk($batch);

            foreach ($this->isiBatch($batch) as $p) {
                $this->pengajuan->ubahStatus(
                    $p, StatusPengajuan::DisetujuiPengadaan, $ppk, [StatusPengajuan::PendingPpk],
                    "Batch {$batch->kode_batch} dikembalikan oleh PPK",
                );
            }

            $batch->update(['status' => StatusBatch::Direvisi, 'catatan_revisi' => $catatan, ...$this->jejakKeputusan($ppk)]);

            return $batch->refresh();
        });
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, PengajuanPengadaan> */
    private function isiBatch(BatchPengadaan $batch)
    {
        $isi = $batch->pengajuan()->get();

        if ($isi->isEmpty()) {
            throw new StatusTidakValidException('Batch tidak berisi request.');
        }

        return $isi;
    }

    /** @return array{diputuskan_oleh: int, diputuskan_pada: \Illuminate\Support\Carbon} */
    private function jejakKeputusan(User $ppk): array
    {
        return ['diputuskan_oleh' => $ppk->id, 'diputuskan_pada' => now()];
    }

    private function pastikanMenungguPpk(BatchPengadaan $batch): void
    {
        if ($batch->status !== StatusBatch::PendingPpk) {
            throw new StatusTidakValidException(
                "Batch berstatus \"{$batch->status->label()}\" dan tidak menunggu keputusan PPK."
            );
        }
    }

    private function kunci(BatchPengadaan $batch): BatchPengadaan
    {
        return BatchPengadaan::whereKey($batch->getKey())->lockForUpdate()->firstOrFail();
    }

    private function pastikanPerluRevisi(BatchPengadaan $batch): void
    {
        if ($batch->status !== StatusBatch::Direvisi) {
            throw new StatusTidakValidException(
                "Batch berstatus \"{$batch->status->label()}\" dan tidak dapat diubah."
            );
        }
    }
}
