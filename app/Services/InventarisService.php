<?php

namespace App\Services;

use App\Enums\Kategori;
use App\Enums\KondisiAset;
use App\Enums\Satuan;
use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Models\AsetInventaris;
use App\Models\Inventaris;
use App\Models\MutasiAset;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** UC-12 (registrasi aset), UC-13 (stok habis pakai), UC-14 (mutasi aset). */
class InventarisService
{
    public const AWALAN_SKU = 'AST';

    /**
     * Daftarkan aset per unit dari request Selesai. Satu baris data = satu unit fisik dengan SKU unik.
     *
     * @param  list<array{nama_penanggung_jawab: string, lokasi: string, kondisi: string}>  $unit
     * @return Collection<int, AsetInventaris>
     */
    public function registrasiAset(PengajuanPengadaan $p, User $aktor, array $unit): Collection
    {
        return DB::transaction(function () use ($p, $aktor, $unit) {
            $p = $this->kunci($p, Kategori::Aset);
            $jumlah = (int) $p->kuantitas_terbeli;

            if (count($unit) !== $jumlah) {
                throw new StatusTidakValidException("Jumlah baris unit ({$jumlah}) harus sama dengan kuantitas terbeli.");
            }

            $inventaris = Inventaris::create([
                'pengajuan_id' => $p->id, 'nama_barang' => $p->nama_barang, 'kategori' => Kategori::Aset,
                'total_stok' => $jumlah, 'satuan' => $p->satuan->value,
            ]);

            $sku = $this->skuBerikutnya((int) now()->format('Y'), $jumlah);
            $hasil = collect();

            foreach (array_values($unit) as $i => $data) {
                $aset = AsetInventaris::create([
                    'inventaris_id' => $inventaris->id, 'pengajuan_id' => $p->id, 'sku_barcode' => $sku[$i],
                    'kondisi' => KondisiAset::from($data['kondisi']),
                    'nama_penanggung_jawab' => $data['nama_penanggung_jawab'], 'lokasi' => $data['lokasi'],
                ]);

                MutasiAset::create([
                    'aset_inventaris_id' => $aset->id, 'dilakukan_oleh' => $aktor->id,
                    'lokasi_baru' => $data['lokasi'], 'penanggung_jawab_baru' => $data['nama_penanggung_jawab'],
                    'kondisi_baru' => $data['kondisi'], 'catatan' => 'Registrasi awal',
                ]);

                $hasil->push($aset);
            }

            $this->tandaiDiproses($p, $aktor, "Didaftarkan ke inventaris sebagai {$jumlah} aset ({$sku[0]}".($jumlah > 1 ? " s.d. {$sku[$jumlah - 1]}" : '').')');

            return $hasil;
        });
    }

    /** Catat stok masuk barang habis pakai. Setiap request menjadi satu baris stok masuk; total dihitung per nama dan satuan. */
    public function catatStok(PengajuanPengadaan $p, User $aktor, string $namaBarang, Satuan $satuan): Inventaris
    {
        return DB::transaction(function () use ($p, $aktor, $namaBarang, $satuan) {
            $p = $this->kunci($p, Kategori::HabisPakai);
            $jumlah = (int) $p->kuantitas_terbeli;

            $inventaris = Inventaris::create([
                'pengajuan_id' => $p->id, 'nama_barang' => trim($namaBarang), 'kategori' => Kategori::HabisPakai,
                'total_stok' => $jumlah, 'satuan' => $satuan->value,
            ]);

            $this->tandaiDiproses($p, $aktor, "Stok masuk dicatat: {$jumlah} {$satuan->label()} ".trim($namaBarang));

            return $inventaris;
        });
    }

    /**
     * Mutasi aset: ubah lokasi, penanggung jawab, atau kondisi. Minimal satu hal harus berubah.
     *
     * @param  array{lokasi: string, nama_penanggung_jawab: string, kondisi: string, catatan?: ?string}  $data
     */
    public function mutasi(AsetInventaris $aset, User $aktor, array $data): AsetInventaris
    {
        return DB::transaction(function () use ($aset, $aktor, $data) {
            $aset = AsetInventaris::whereKey($aset->getKey())->lockForUpdate()->firstOrFail();
            $kondisiBaru = KondisiAset::from($data['kondisi']);

            $berubah = $aset->lokasi !== $data['lokasi']
                || $aset->nama_penanggung_jawab !== $data['nama_penanggung_jawab']
                || $aset->kondisi !== $kondisiBaru;

            if (! $berubah) {
                throw new StatusTidakValidException('Tidak ada perubahan lokasi, penanggung jawab, atau kondisi.');
            }

            MutasiAset::create([
                'aset_inventaris_id' => $aset->id, 'dilakukan_oleh' => $aktor->id,
                'lokasi_lama' => $aset->lokasi, 'lokasi_baru' => $data['lokasi'],
                'penanggung_jawab_lama' => $aset->nama_penanggung_jawab, 'penanggung_jawab_baru' => $data['nama_penanggung_jawab'],
                'kondisi_lama' => $aset->kondisi->value, 'kondisi_baru' => $kondisiBaru->value,
                'catatan' => $data['catatan'] ?? null,
            ]);

            $aset->update([
                'lokasi' => $data['lokasi'], 'nama_penanggung_jawab' => $data['nama_penanggung_jawab'], 'kondisi' => $kondisiBaru,
            ]);

            return $aset->refresh();
        });
    }

    /** SKU berurutan per tahun, mis. AST-2026-0001. @return list<string> */
    public function skuBerikutnya(int $tahun, int $jumlah, bool $kunci = true): array
    {
        $awalan = self::AWALAN_SKU."-{$tahun}-";

        $query = AsetInventaris::where('sku_barcode', 'like', $awalan.'%')->orderByDesc('sku_barcode');
        $terakhir = ($kunci ? $query->lockForUpdate() : $query)->value('sku_barcode');
        $nomor = $terakhir ? (int) substr($terakhir, strlen($awalan)) : 0;

        return collect(range(1, $jumlah))->map(fn ($i) => $awalan.str_pad((string) ($nomor + $i), 4, '0', STR_PAD_LEFT))->all();
    }

    private function kunci(PengajuanPengadaan $p, Kategori $kategori): PengajuanPengadaan
    {
        $p = PengajuanPengadaan::whereKey($p->getKey())->lockForUpdate()->firstOrFail();

        if ($p->status !== StatusPengajuan::Selesai || $p->kategori !== $kategori || $p->diproses_inventaris_at !== null) {
            throw new StatusTidakValidException(
                'Request ini tidak berada di antrean inventaris (belum selesai, kategori berbeda, atau sudah diproses).'
            );
        }

        return $p;
    }

    private function tandaiDiproses(PengajuanPengadaan $p, User $aktor, string $catatan): void
    {
        $p->update(['diproses_inventaris_at' => now()]);

        $p->riwayat()->create([
            'aktor_id' => $aktor->id, 'status_dari' => StatusPengajuan::Selesai->value,
            'status_ke' => StatusPengajuan::Selesai->value, 'catatan' => $catatan,
        ]);
    }
}
