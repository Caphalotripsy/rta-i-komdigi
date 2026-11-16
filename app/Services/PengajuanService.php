<?php

namespace App\Services;

use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya tempat yang boleh mengubah status pengajuan.
 * Setiap perubahan: dalam transaksi, baris dikunci (anti klik ganda), dan tercatat di riwayat_pengajuan.
 * Modul berikutnya (batching, PPK, keuangan, dst.) memakai ubahStatus().
 */
class PengajuanService
{
    /** @param array<string, mixed> $data hasil $request->validated() */
    public function buat(User $pemohon, array $data, bool $ajukan = false): PengajuanPengadaan
    {
        return DB::transaction(function () use ($pemohon, $data, $ajukan) {
            $p = PengajuanPengadaan::create($this->atribut($data) + [
                'pemohon_id' => $pemohon->id,
                'status' => StatusPengajuan::Draft,
            ]);

            $this->catat($p, $pemohon, null, StatusPengajuan::Draft, 'Pengajuan dibuat');

            if ($ajukan) {
                $this->pindahStatus($p, StatusPengajuan::Diajukan, $pemohon, 'Pengajuan dikirim');
            }

            return $p->refresh();
        });
    }

    /** Ubah isi formulir (draft, atau dikembalikan Pejabat Pengadaan). Opsional langsung dikirim. */
    public function perbarui(PengajuanPengadaan $p, array $data, User $aktor, bool $ajukan = false): PengajuanPengadaan
    {
        return DB::transaction(function () use ($p, $data, $aktor, $ajukan) {
            $p = $this->kunci($p);
            $this->pastikanBisaDiedit($p);

            $p->update($this->atribut($data));

            if ($ajukan) {
                $this->kirim($p, $aktor);
            }

            return $p->refresh();
        });
    }

    /** UC-01 (draft -> diajukan) dan UC-02 (kirim ulang setelah revisi). */
    public function ajukan(PengajuanPengadaan $p, User $aktor): PengajuanPengadaan
    {
        return DB::transaction(function () use ($p, $aktor) {
            $p = $this->kunci($p);
            $this->pastikanBisaDiedit($p);
            $this->kirim($p, $aktor);

            return $p->refresh();
        });
    }

    /** UC-06 (setuju): Diajukan -> Siap Batching. */
    public function setujuiPengadaan(PengajuanPengadaan $p, User $aktor): PengajuanPengadaan
    {
        return $this->ubahStatus(
            $p, StatusPengajuan::DisetujuiPengadaan, $aktor, [StatusPengajuan::Diajukan],
            'Lolos tinjauan Pejabat Pengadaan',
        );
    }

    /** UC-06 (kembalikan): Diajukan -> Perlu Revisi. Catatan wajib; pemohon memperbaiki lalu kirim ulang ke tahap ini. */
    public function kembalikanPengadaan(PengajuanPengadaan $p, User $aktor, string $catatan): PengajuanPengadaan
    {
        return $this->ubahStatus(
            $p, StatusPengajuan::Direvisi, $aktor, [StatusPengajuan::Diajukan],
            $catatan, null, ['dikembalikan_oleh' => 'pengadaan'],
        );
    }

    /**
     * UC-08: Bendahara mencatat pencairan. Dana selalu sama dengan total estimasi (tidak bisa diubah dari input).
     *
     * @param  array{tanggal_pencairan: string, metode_pencairan: string, referensi_pencairan?: ?string}  $data
     */
    public function catatPencairan(PengajuanPengadaan $p, User $bendahara, array $data): PengajuanPengadaan
    {
        $metode = \App\Enums\MetodePencairan::from($data['metode_pencairan']);
        $dana = (float) $p->total_estimasi_biaya;

        return $this->ubahStatus(
            $p, StatusPengajuan::DanaCair, $bendahara, [StatusPengajuan::Disetujui],
            'Dana Rp '.number_format($dana, 0, ',', '.').' dicairkan via '.$metode->label(),
            null,
            [
                'dana_dicairkan' => $dana,
                'tanggal_pencairan' => $data['tanggal_pencairan'],
                'metode_pencairan' => $metode,
                'referensi_pencairan' => $data['referensi_pencairan'] ?? null,
            ],
        );
    }

    /** UC-09: Bendahara mengonfirmasi sisa dana sudah dikembalikan. Menunggu Pengembalian Dana -> Cek Fisik. */
    public function konfirmasiPengembalian(PengajuanPengadaan $p, User $bendahara, string $tanggal): PengajuanPengadaan
    {
        return $this->ubahStatus(
            $p, StatusPengajuan::CekFisik, $bendahara, [StatusPengajuan::MenungguPengembalian],
            'Pengembalian sisa dana Rp '.number_format((float) $p->sisa_dana, 0, ',', '.').' dikonfirmasi',
            null,
            ['status_pengembalian_dana' => true, 'tanggal_pengembalian' => $tanggal],
        );
    }

    /** UC-10/UC-11: verifikasi fisik dan nota valid, request ditutup (Cek Fisik -> Selesai; masuk antrean Inventaris). */
    public function validasiKeuangan(PengajuanPengadaan $p, User $keuangan): PengajuanPengadaan
    {
        return $this->ubahStatus(
            $p, StatusPengajuan::Selesai, $keuangan, [StatusPengajuan::CekFisik],
            'Verifikasi fisik dan nota valid. Request ditutup.',
        );
    }

    /** UC-10: tidak sesuai. Dikembalikan ke pemohon (Cek Fisik -> Perlu Revisi); pemohon memperbaiki lewat Lapor Harga. */
    public function kembalikanKeuangan(PengajuanPengadaan $p, User $keuangan, string $jenisMasalah, string $catatan): PengajuanPengadaan
    {
        return $this->ubahStatus(
            $p, StatusPengajuan::Direvisi, $keuangan, [StatusPengajuan::CekFisik],
            $catatan, $jenisMasalah, ['dikembalikan_oleh' => 'keuangan'],
        );
    }

    /**
     * UC-04: pemohon melaporkan harga aktual. Berlaku saat Dana Dicairkan, atau setelah dikembalikan Keuangan.
     * Sisa dana > 0  -> Menunggu Pengembalian Dana (Bendahara mengonfirmasi dulu).
     * Sisa dana = 0  -> langsung Cek Fisik (Divisi Keuangan).
     *
     * @param  array<string, mixed>  $data  hasil LaporHargaRequest::validated()
     */
    public function laporHarga(PengajuanPengadaan $p, array $data, User $aktor): PengajuanPengadaan
    {
        return DB::transaction(function () use ($p, $data, $aktor) {
            $p = $this->kunci($p);

            if (! $p->bisaLaporHarga()) {
                throw new StatusTidakValidException(
                    "Pengajuan berstatus \"{$p->status->label()}\" dan tidak dapat dilaporkan harga aktualnya."
                );
            }

            $kuantitas = (int) $data['kuantitas_terbeli'];
            $harga = (float) $data['harga_aktual'];

            if ($kuantitas < 1 || $kuantitas > $p->kuantitas || $harga <= 0 || $harga > (float) $p->harga_estimasi) {
                throw new StatusTidakValidException('Kuantitas atau harga aktual melebihi yang diajukan.');
            }

            $dana = $p->danaDicairkanEfektif();
            $total = round($kuantitas * $harga, 2);
            $sisa = round($dana - $total, 2);

            if ($sisa < 0) {
                throw new StatusTidakValidException('Total biaya aktual melebihi dana yang dicairkan.');
            }

            $this->pindahStatus(
                $p,
                $sisa > 0 ? StatusPengajuan::MenungguPengembalian : StatusPengajuan::CekFisik,
                $aktor,
                $sisa > 0
                    ? 'Harga aktual dilaporkan. Sisa dana wajib dikembalikan ke Bendahara.'
                    : 'Harga aktual dilaporkan. Tidak ada sisa dana.',
                null,
                [
                    'dana_dicairkan' => $dana,
                    'kuantitas_terbeli' => $kuantitas,
                    'harga_aktual' => $harga,
                    'total_biaya_aktual' => $total,
                    'sisa_dana' => $sisa,
                    'status_pengembalian_dana' => false,
                    'nama_toko' => $data['nama_toko'],
                    'nomor_nota' => $data['nomor_nota'],
                    'tanggal_nota' => $data['tanggal_nota'],
                    'dikembalikan_oleh' => null,
                ],
            );

            return $p->refresh();
        });
    }

    /**
     * Perpindahan status generik untuk modul lain.
     *
     * @param  list<StatusPengajuan>  $dariStatus  status yang diizinkan sebelum perpindahan
     * @param  array<string, mixed>  $ubah  kolom tambahan yang ikut disimpan (mis. dikembalikan_oleh)
     */
    public function ubahStatus(
        PengajuanPengadaan $p,
        StatusPengajuan $ke,
        User $aktor,
        array $dariStatus,
        ?string $catatan = null,
        ?string $jenisMasalah = null,
        array $ubah = [],
    ): PengajuanPengadaan {
        return DB::transaction(function () use ($p, $ke, $aktor, $dariStatus, $catatan, $jenisMasalah, $ubah) {
            $p = $this->kunci($p);

            if (! in_array($p->status, $dariStatus, true)) {
                throw new StatusTidakValidException(
                    "Pengajuan berstatus \"{$p->status->label()}\" dan tidak dapat diproses untuk aksi ini."
                );
            }

            $this->pindahStatus($p, $ke, $aktor, $catatan, $jenisMasalah, $ubah);

            return $p->refresh();
        });
    }

    private function kirim(PengajuanPengadaan $p, User $aktor): void
    {
        $ulang = $p->status === StatusPengajuan::Direvisi;

        $this->pindahStatus(
            $p,
            StatusPengajuan::Diajukan,
            $aktor,
            $ulang ? 'Dikirim ulang setelah revisi' : 'Pengajuan dikirim',
            null,
            ['dikembalikan_oleh' => null],
        );
    }

    private function kunci(PengajuanPengadaan $p): PengajuanPengadaan
    {
        return PengajuanPengadaan::whereKey($p->getKey())->lockForUpdate()->firstOrFail();
    }

    private function pastikanBisaDiedit(PengajuanPengadaan $p): void
    {
        if (! $p->bisaDiedit()) {
            throw new StatusTidakValidException(
                "Pengajuan berstatus \"{$p->status->label()}\" dan tidak dapat diubah atau dikirim."
            );
        }
    }

    private function pindahStatus(
        PengajuanPengadaan $p,
        StatusPengajuan $ke,
        User $aktor,
        ?string $catatan = null,
        ?string $jenisMasalah = null,
        array $ubah = [],
    ): void {
        $dari = $p->status;

        $p->fill($ubah);
        $p->status = $ke;
        $p->save();

        $this->catat($p, $aktor, $dari, $ke, $catatan, $jenisMasalah);
    }

    private function catat(
        PengajuanPengadaan $p,
        User $aktor,
        ?StatusPengajuan $dari,
        StatusPengajuan $ke,
        ?string $catatan = null,
        ?string $jenisMasalah = null,
    ): void {
        $p->riwayat()->create([
            'aktor_id' => $aktor->id,
            'status_dari' => $dari?->value,
            'status_ke' => $ke->value,
            'jenis_masalah' => $jenisMasalah,
            'catatan' => $catatan,
        ]);
    }

    /** Hanya kolom formulir yang boleh masuk; total dihitung server, bukan dari input. */
    private function atribut(array $data): array
    {
        $kuantitas = (int) $data['kuantitas'];
        $harga = (float) $data['harga_estimasi'];

        return [
            'nama_barang' => $data['nama_barang'],
            'kategori' => $data['kategori'],
            'spesifikasi' => $data['spesifikasi'] ?? null,
            'kuantitas' => $kuantitas,
            'satuan' => $data['satuan'],
            'harga_estimasi' => $harga,
            'total_estimasi_biaya' => round($kuantitas * $harga, 2),
        ];
    }
}
