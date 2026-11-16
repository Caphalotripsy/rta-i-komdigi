<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatusPengajuan;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeuanganTest extends TestCase
{
    use RefreshDatabase;

    private User $keuangan;
    private User $kadiv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keuangan = User::factory()->create(['role' => Role::Keuangan, 'name' => 'Hendra Wijaya']);
        $this->kadiv = User::factory()->create(['role' => Role::KepalaDivisi, 'name' => 'Budi Santoso', 'divisi' => 'Divisi Pelatihan']);
    }

    /** Hard Disk 2 unit: estimasi 2 x 1.450.000, aktual 2 x 1.400.000, sisa dana Rp 100.000 sudah dikonfirmasi. */
    private function cekFisik(array $o = []): PengajuanPengadaan
    {
        return PengajuanPengadaan::factory()->status(StatusPengajuan::CekFisik)->create($o + [
            'pemohon_id' => $this->kadiv->id, 'nama_barang' => 'Hard Disk Eksternal 2 TB',
            'kuantitas' => 2, 'harga_estimasi' => 1450000, 'total_estimasi_biaya' => 2900000, 'dana_dicairkan' => 2900000,
            'kuantitas_terbeli' => 2, 'harga_aktual' => 1400000, 'total_biaya_aktual' => 2800000, 'sisa_dana' => 100000,
            'status_pengembalian_dana' => true, 'nama_toko' => 'Toko Mega Pratama', 'nomor_nota' => 'NOTA-2026/XI/8842', 'tanggal_nota' => '2026-11-13',
        ]);
    }

    private function cek(array $o = []): array
    {
        return $o + ['cek_barang' => 1, 'cek_nominal' => 1, 'cek_kuantitas' => 1];
    }

    public function test_hanya_divisi_keuangan_yang_boleh_mengakses(): void
    {
        $p = $this->cekFisik();

        foreach (['/verifikasi', "/verifikasi/{$p->id}", '/riwayat-verifikasi'] as $url) {
            $this->actingAs($this->kadiv)->get($url)->assertForbidden();
        }
        $this->actingAs($this->kadiv)->post("/verifikasi/{$p->id}/valid", $this->cek())->assertForbidden();
    }

    public function test_dashboard_dan_menu_keuangan(): void
    {
        $this->cekFisik();
        PengajuanPengadaan::factory()->dikembalikanOleh('keuangan')->create(['pemohon_id' => $this->kadiv->id]);
        PengajuanPengadaan::factory()->status(StatusPengajuan::Selesai)->create(['pemohon_id' => $this->kadiv->id, 'total_biaya_aktual' => 750000]);

        $r = $this->actingAs($this->keuangan)->get('/dashboard')->assertOk()
            ->assertSee('Dashboard Divisi Keuangan')->assertSee('Verifikasi & Closing')->assertSee('Riwayat Verifikasi')->assertDontSee('Tambah Request');

        $this->assertSame(1, $r->viewData('statistik')['menungguVerifikasi']);
        $this->assertSame(1, $r->viewData('statistik')['dikembalikan']);
        $this->assertSame(1, $r->viewData('statistik')['selesaiBulanIni']);
        $this->assertEquals(750000, $r->viewData('statistik')['totalTerverifikasi']);
    }

    public function test_daftar_hanya_cek_fisik_dengan_pencarian(): void
    {
        $this->cekFisik(['nama_barang' => 'Barang Cek']);
        PengajuanPengadaan::factory()->status(StatusPengajuan::DanaCair)->create(['pemohon_id' => $this->kadiv->id, 'nama_barang' => 'Belum Lapor']);

        $this->actingAs($this->keuangan)->get('/verifikasi')->assertOk()->assertSee('Barang Cek')->assertDontSee('Belum Lapor');
        $this->get('/verifikasi?q=mega')->assertSee('Barang Cek');                 // cari nama toko
        $this->get('/verifikasi?q=tidak-ada')->assertSee('Belum ada request untuk diverifikasi.');
    }

    public function test_halaman_rekonsiliasi_menampilkan_selisih(): void
    {
        $p = $this->cekFisik();

        $this->actingAs($this->keuangan)->get("/verifikasi/{$p->id}")->assertOk()
            ->assertSee('Rp 2.900.000')->assertSee('Rp 2.800.000')->assertSee('Hemat Rp 50.000 / unit')->assertSee('Selisih Rp 100.000')
            ->assertSee('Sudah dikonfirmasi Bendahara')->assertSee('Toko Mega Pratama')->assertSee('NOTA-2026/XI/8842')
            ->assertSee('Valid & Tutup Request');
    }

    public function test_valid_wajib_semua_checklist(): void
    {
        $p = $this->cekFisik();
        $this->actingAs($this->keuangan);

        $this->post("/verifikasi/{$p->id}/valid", $this->cek(['cek_nominal' => 0]))->assertSessionHasErrors('cek_nominal');
        $this->post("/verifikasi/{$p->id}/valid", [])->assertSessionHasErrors(['cek_barang', 'cek_nominal', 'cek_kuantitas']);
        $this->assertSame(StatusPengajuan::CekFisik, $p->fresh()->status);
    }

    public function test_valid_menutup_request_dan_masuk_antrean_inventaris(): void
    {
        $p = $this->cekFisik();

        $this->actingAs($this->keuangan)->post("/verifikasi/{$p->id}/valid", $this->cek())->assertRedirect('/verifikasi');

        $p->refresh();
        $this->assertSame(StatusPengajuan::Selesai, $p->status);
        $this->assertNull($p->diproses_inventaris_at);                         // belum diproses Inventaris
        $this->assertSame($this->keuangan->id, $p->riwayat->last()->aktor_id);
        $this->get('/riwayat-verifikasi')->assertSee('Hard Disk Eksternal 2 TB');
    }

    public function test_kembalikan_wajib_jenis_dan_catatan_lalu_pemohon_memperbaiki(): void
    {
        $p = $this->cekFisik();
        $this->actingAs($this->keuangan);

        $this->post("/verifikasi/{$p->id}/kembalikan", ['jenis_masalah' => 'nominal', 'catatan' => ''])->assertSessionHasErrors('catatan');
        $this->post("/verifikasi/{$p->id}/kembalikan", ['jenis_masalah' => 'lain', 'catatan' => 'x'])->assertSessionHasErrors('jenis_masalah');
        $this->assertSame(StatusPengajuan::CekFisik, $p->fresh()->status);

        $this->post("/verifikasi/{$p->id}/kembalikan", ['jenis_masalah' => 'nominal', 'catatan' => 'Nominal nota Rp 2.850.000'])->assertRedirect('/verifikasi');

        $p->refresh();
        $this->assertSame(StatusPengajuan::Direvisi, $p->status);
        $this->assertSame('keuangan', $p->dikembalikan_oleh);
        $this->assertSame('nominal', $p->riwayat->last()->jenis_masalah);

        // Pemohon melihat jenis masalah, tidak bisa memakai form utama, tetapi bisa Lapor Harga ulang.
        $this->actingAs($this->kadiv)->get("/pengajuan/{$p->id}")->assertSee('Nominal tidak sesuai')->assertSee('Nominal nota Rp 2.850.000')->assertSee('Perbaiki Laporan');
        $this->get("/pengajuan/{$p->id}/edit")->assertForbidden();
        $this->post("/pengajuan/{$p->id}/lapor", [
            'kuantitas_terbeli' => 2, 'harga_aktual' => 1425000, 'nama_toko' => 'Toko Mega Pratama', 'nomor_nota' => 'NOTA-2026/XI/8842', 'tanggal_nota' => now()->toDateString(),
        ])->assertRedirect();

        $p->refresh();
        $this->assertSame(StatusPengajuan::MenungguPengembalian, $p->status);   // sisa dana berubah, Bendahara konfirmasi ulang
        $this->assertEquals(50000, $p->sisa_dana);
        $this->assertFalse($p->status_pengembalian_dana);
    }

    public function test_hanya_status_cek_fisik_yang_bisa_diputuskan(): void
    {
        $p = PengajuanPengadaan::factory()->status(StatusPengajuan::DanaCair)->create(['pemohon_id' => $this->kadiv->id]);
        $this->actingAs($this->keuangan);

        $this->post("/verifikasi/{$p->id}/valid", $this->cek())->assertSessionHasErrors('status');
        $this->post("/verifikasi/{$p->id}/kembalikan", ['jenis_masalah' => 'nominal', 'catatan' => 'x'])->assertSessionHasErrors('status');
        $this->assertSame(StatusPengajuan::DanaCair, $p->fresh()->status);
        $this->get("/verifikasi/{$p->id}")->assertSee('tidak menunggu verifikasi');
    }

    public function test_keputusan_tidak_bisa_diulang(): void
    {
        $p = $this->cekFisik();
        $this->actingAs($this->keuangan)->post("/verifikasi/{$p->id}/valid", $this->cek());

        $this->post("/verifikasi/{$p->id}/valid", $this->cek())->assertSessionHasErrors('status');
        $this->assertSame(StatusPengajuan::Selesai, $p->fresh()->status);
    }

    /** Dari Dana Dicairkan sampai Selesai tanpa sisa dana. */
    public function test_alur_tanpa_sisa_dana_langsung_ke_keuangan(): void
    {
        $p = PengajuanPengadaan::factory()->danaCair()->create([
            'pemohon_id' => $this->kadiv->id, 'kuantitas' => 2, 'harga_estimasi' => 450000, 'total_estimasi_biaya' => 900000,
        ]);

        $this->actingAs($this->kadiv)->post("/pengajuan/{$p->id}/lapor", [
            'kuantitas_terbeli' => 2, 'harga_aktual' => 450000, 'nama_toko' => 'Toko A', 'nomor_nota' => 'N-1', 'tanggal_nota' => now()->toDateString(),
        ]);
        $this->assertSame(StatusPengajuan::CekFisik, $p->fresh()->status);

        $this->actingAs($this->keuangan)->post("/verifikasi/{$p->id}/valid", $this->cek());
        $this->assertSame(StatusPengajuan::Selesai, $p->fresh()->status);
    }
}
