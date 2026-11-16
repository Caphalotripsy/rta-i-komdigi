<?php

namespace Database\Seeders;

use App\Enums\PeriodeBatch;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use App\Services\BatchService;
use App\Services\PengajuanService;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk pengembangan dan demo (BUKAN bagian DatabaseSeeder).
 * Jalankan setelah UserSeeder: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $service = app(PengajuanService::class);
        $batchService = app(BatchService::class);

        $budi = User::where('username', 'kadiv.pelatihan')->firstOrFail();
        $rahmat = User::where('username', 'kadiv.umum')->firstOrFail();
        $pp = User::where('username', 'pengadaan')->firstOrFail();
        $ppk = User::where('username', 'ppk')->firstOrFail();

        $buat = fn (User $u, string $nama, string $kategori, int $qty, string $satuan, int $harga, string $spek, bool $setujui = false) => tap(
            $service->buat($u, ['nama_barang' => $nama, 'kategori' => $kategori, 'kuantitas' => $qty, 'satuan' => $satuan, 'harga_estimasi' => $harga, 'spesifikasi' => $spek], true),
            fn ($p) => $setujui ? $service->setujuiPengadaan($p, $pp) : null,
        )->refresh();

        // Menunggu tinjauan (Approval Tahap 1)
        $buat($budi, 'Laptop Operasional', 'aset', 2, 'unit', 10000000, 'RAM 16 GB, SSD 512 GB NVMe, layar 14 inci FHD. Untuk penggantian laptop pelatihan digital.');
        $buat($rahmat, 'Kertas A4 80 gsm', 'habis_pakai', 20, 'rim', 55000, 'Kertas HVS putih terang, 500 lembar per rim.');
        $buat($rahmat, 'Router Gigabit Dual Band', 'aset', 2, 'unit', 1800000, 'WiFi 6, 4 port LAN Gigabit, mendukung PoE.');

        // Siap batching
        $buat($budi, 'Proyektor', 'aset', 1, 'unit', 6500000, 'Resolusi WUXGA, input HDMI.', true);
        $buat($rahmat, 'Kursi Kerja Ergonomis', 'aset', 5, 'pcs', 1200000, 'Sandaran jaring, tinggi dapat diatur.', true);
        $buat($budi, 'Tinta Printer', 'habis_pakai', 4, 'botol', 120000, 'Tinta botol hitam 100 ml.', true);

        // Batch menunggu PPK
        $d = $buat($rahmat, 'Spidol Whiteboard', 'habis_pakai', 12, 'box', 85000, 'Hitam dan biru, isi 12 per box.', true);
        $e = $buat($budi, 'Monitor 24 inci', 'aset', 3, 'unit', 1800000, 'Panel IPS Full HD 75Hz.', true);
        $batchService->buat($pp, PeriodeBatch::Bulanan, [$d->id, $e->id]);

        // Batch yang dikembalikan PPK
        $f = $buat($rahmat, 'Kamera Mirrorless', 'aset', 1, 'unit', 14000000, 'Body dan lensa kit, untuk dokumentasi kegiatan.', true);
        $g = $buat($budi, 'Flashdisk 64 GB', 'habis_pakai', 10, 'pcs', 90000, 'USB 3.0.', true);
        $batchDikembalikan = $batchService->buat($pp, PeriodeBatch::Mingguan, [$f->id, $g->id]);
        $batchService->kembalikan($batchDikembalikan, $ppk, "{$f->kode_pengajuan} spesifikasi dan keperluan belum jelas.");

        // Batch yang sudah disetujui PPK (siap dilanjutkan ke pencairan)
        $h = $buat($budi, 'Printer Multifungsi', 'aset', 1, 'unit', 3400000, 'Cetak, pindai, salin; WiFi.', true);
        $i = $buat($rahmat, 'Hard Disk Eksternal 2 TB', 'aset', 2, 'unit', 1450000, 'USB 3.0, 2,5 inci.', true);
        $batchDisetujui = $batchService->buat($pp, PeriodeBatch::Bulanan, [$h->id, $i->id]);
        $batchService->setujui($batchDisetujui, $ppk);

        // Satu request sudah dicairkan lalu dilaporkan (menunggu pengembalian sisa dana)
        $bendahara = User::where('username', 'bendahara')->firstOrFail();
        $service->catatPencairan($i->refresh(), $bendahara, ['metode_pencairan' => 'transfer', 'tanggal_pencairan' => now()->toDateString(), 'referensi_pencairan' => 'BKU-089']);
        $service->laporHarga($i->refresh(), ['kuantitas_terbeli' => 2, 'harga_aktual' => 1400000, 'nama_toko' => 'Toko Maju Jaya', 'nomor_nota' => 'NT-0457/XI/2026', 'tanggal_nota' => now()->toDateString()], $rahmat);

        // Dua request lagi sampai Cek Fisik; satu langsung ditutup Keuangan (untuk Riwayat Verifikasi dan antrean Inventaris)
        $keuangan = User::where('username', 'keuangan')->firstOrFail();
        $j = $buat($budi, 'Headset', 'aset', 4, 'unit', 450000, 'Headset USB dengan mikrofon.', true);
        $k = $buat($rahmat, 'Mouse Nirkabel', 'aset', 5, 'pcs', 150000, 'Mouse nirkabel 2,4 GHz.', true);
        $batchCek = $batchService->buat($pp, PeriodeBatch::Bulanan, [$j->id, $k->id]);
        $batchService->setujui($batchCek, $ppk);

        foreach ([[$j, $budi, 4, 450000, 'NT-0501/XI/2026'], [$k, $rahmat, 5, 140000, 'NT-0502/XI/2026']] as [$req, $pemohon, $qty, $harga, $nota]) {
            $service->catatPencairan($req->refresh(), $bendahara, ['metode_pencairan' => 'transfer', 'tanggal_pencairan' => now()->toDateString()]);
            $service->laporHarga($req->refresh(), ['kuantitas_terbeli' => $qty, 'harga_aktual' => $harga, 'nama_toko' => 'Toko Mega Pratama', 'nomor_nota' => $nota, 'tanggal_nota' => now()->toDateString()], $pemohon);
        }
        $k->refresh();
        $service->konfirmasiPengembalian($k, $bendahara, now()->toDateString());
        $service->validasiKeuangan($j->refresh(), $keuangan);

        // Dua request lagi sampai Selesai: satu aset (didaftarkan, mengisi Master Inventory), satu habis pakai (menunggu dicatat)
        $inventaris = User::where('username', 'inventaris')->firstOrFail();
        $m = $buat($budi, 'Monitor Kerja 24 inci', 'aset', 2, 'unit', 1800000, 'Panel IPS Full HD 75Hz.', true);
        $n = $buat($rahmat, 'Kertas HVS F4', 'habis_pakai', 10, 'rim', 60000, 'Kertas 75 gsm, 500 lembar per rim.', true);
        $batchSelesai = $batchService->buat($pp, PeriodeBatch::Bulanan, [$m->id, $n->id]);
        $batchService->setujui($batchSelesai, $ppk);

        foreach ([[$m, $budi, 2, 1800000, 'NT-0601/XI/2026'], [$n, $rahmat, 10, 60000, 'NT-0602/XI/2026']] as [$req, $pemohon, $qty, $harga, $nota]) {
            $service->catatPencairan($req->refresh(), $bendahara, ['metode_pencairan' => 'tunai', 'tanggal_pencairan' => now()->toDateString()]);
            $service->laporHarga($req->refresh(), ['kuantitas_terbeli' => $qty, 'harga_aktual' => $harga, 'nama_toko' => 'Toko Mega Pratama', 'nomor_nota' => $nota, 'tanggal_nota' => now()->toDateString()], $pemohon);
            $service->validasiKeuangan($req->refresh(), $keuangan);
        }

        app(\App\Services\InventarisService::class)->registrasiAset($m->refresh(), $inventaris, [
            ['nama_penanggung_jawab' => 'Rahmat Hidayat (Kepala Divisi Umum)', 'lokasi' => 'Ruang Divisi Umum', 'kondisi' => 'baik'],
            ['nama_penanggung_jawab' => 'Lina Marlina (Kepala Divisi TI)', 'lokasi' => 'Ruang Server', 'kondisi' => 'baik'],
        ]);

        $this->command?->info('Data contoh dibuat: '.PengajuanPengadaan::count().' request.');
    }
}
