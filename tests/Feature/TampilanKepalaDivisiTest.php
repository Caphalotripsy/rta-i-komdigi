<?php

namespace Tests\Feature;

use App\Enums\Kategori;
use App\Enums\Role;
use App\Enums\StatusPengajuan;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TampilanKepalaDivisiTest extends TestCase
{
    use RefreshDatabase;

    private User $kadiv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kadiv = User::factory()->create(['role' => Role::KepalaDivisi, 'divisi' => 'Divisi Pelatihan', 'name' => 'Budi Santoso']);
    }

    private function buat(StatusPengajuan $status, int $total, array $o = []): PengajuanPengadaan
    {
        return PengajuanPengadaan::factory()->status($status)->create($o + [
            'pemohon_id' => $this->kadiv->id, 'kuantitas' => 1, 'harga_estimasi' => $total, 'total_estimasi_biaya' => $total,
        ]);
    }

    public function test_dashboard_menghitung_statistik_dan_menampilkan_banner_revisi(): void
    {
        $this->buat(StatusPengajuan::Diajukan, 1000000);
        $this->buat(StatusPengajuan::Selesai, 250000);
        $this->buat(StatusPengajuan::Draft, 999999);
        PengajuanPengadaan::factory()->dikembalikanOleh('pengadaan', 'Lengkapi spesifikasi kemasan')
            ->create(['pemohon_id' => $this->kadiv->id, 'kuantitas' => 1, 'harga_estimasi' => 500000, 'total_estimasi_biaya' => 500000]);
        $this->buat(StatusPengajuan::Diajukan, 777, ['pemohon_id' => User::factory()->create()->id]); // milik orang lain

        $r = $this->actingAs($this->kadiv)->get('/dashboard')->assertOk();

        $r->assertSee('Dashboard Kepala Divisi')
            ->assertSee('Rp 1.750.000')            // tanpa draft dan tanpa milik orang lain
            ->assertSee('Tindakan diperlukan')
            ->assertSee('Lengkapi spesifikasi kemasan')
            ->assertSee('Buka Form Revisi')
            ->assertSee('Rp 999.999'); // draft tetap tampil di tabel, tetapi tidak dihitung di statistik

        $this->assertSame(2, $r->viewData('statistik')['berjalan']);   // diajukan + direvisi
        $this->assertSame(1, $r->viewData('statistik')['selesai']);
        $this->assertSame(1, $r->viewData('statistik')['perluRevisi']);
        $this->assertEquals(1750000, $r->viewData('statistik')['totalEstimasi']);
        $this->assertSame(4, $r->viewData('chip')['semua']);
    }

    public function test_dashboard_tanpa_data_menampilkan_empty_state(): void
    {
        $this->actingAs($this->kadiv)->get('/dashboard')->assertOk()->assertSee('Belum ada request.')->assertDontSee('Tindakan diperlukan');
    }

    public function test_dashboard_peran_lain_dan_menu_sidebar(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Ppk]))->get('/dashboard')
            ->assertOk()->assertSee('sedang disiapkan')->assertDontSee('Tambah Request');

        $this->actingAs($this->kadiv)->get('/dashboard')->assertSee('Tambah Request')->assertSee('Request Saya');
    }

    public function test_request_saya_pencarian_filter_dan_hitungan(): void
    {
        $this->buat(StatusPengajuan::Draft, 100, ['nama_barang' => 'Kertas HVS', 'kategori' => Kategori::HabisPakai]);
        $this->buat(StatusPengajuan::DanaCair, 200, ['nama_barang' => 'Monitor', 'kategori' => Kategori::Aset]);
        PengajuanPengadaan::factory()->dikembalikanOleh('pengadaan')->create(['pemohon_id' => $this->kadiv->id, 'nama_barang' => 'Printer', 'kategori' => Kategori::Aset]);
        $this->buat(StatusPengajuan::Selesai, 300, ['nama_barang' => 'Proyektor', 'kategori' => Kategori::Aset]);

        $this->actingAs($this->kadiv);

        $semua = $this->get('/pengajuan')->assertOk();
        $this->assertSame(4, $semua->viewData('jumlah')['semua']);
        $this->assertSame(2, $semua->viewData('jumlah')['menunggu_tindakan']); // revisi + dana cair

        $this->get('/pengajuan?q=hvs')->assertSee('Kertas HVS')->assertDontSee('Monitor');
        $this->get('/pengajuan?kategori=habis_pakai')->assertSee('Kertas HVS')->assertDontSee('Proyektor');
        $this->get('/pengajuan?status=menunggu_tindakan')->assertSee('Monitor')->assertSee('Printer')->assertDontSee('Proyektor')->assertDontSee('Kertas HVS');
        $this->get('/pengajuan?q=tidak-ada')->assertSee('Tidak ada request yang cocok.');
    }

    public function test_pencarian_tidak_menganggap_persen_sebagai_wildcard(): void
    {
        $this->buat(StatusPengajuan::Draft, 100, ['nama_barang' => 'Kertas']);

        $this->actingAs($this->kadiv)->get('/pengajuan?q=%25')->assertSee('Tidak ada request yang cocok.');
    }

    public function test_tombol_kontekstual_per_status(): void
    {
        $draft = $this->buat(StatusPengajuan::Draft, 100);
        $danaCair = PengajuanPengadaan::factory()->danaCair()->create(['pemohon_id' => $this->kadiv->id]);
        $revisi = PengajuanPengadaan::factory()->dikembalikanOleh('pengadaan')->create(['pemohon_id' => $this->kadiv->id]);

        $this->actingAs($this->kadiv)->get('/pengajuan')
            ->assertSee('Lanjutkan')->assertSee('Lapor Harga')->assertSee('Revisi')
            ->assertSee(route('pengajuan.edit', $draft), false)
            ->assertSee(route('pengajuan.lapor', $danaCair), false)
            ->assertSee(route('pengajuan.edit', $revisi), false);
    }

    public function test_halaman_detail_menampilkan_aksi_sesuai_status(): void
    {
        $draft = $this->buat(StatusPengajuan::Draft, 100);
        $diajukan = $this->buat(StatusPengajuan::Diajukan, 100);
        $danaCair = PengajuanPengadaan::factory()->danaCair()->create(['pemohon_id' => $this->kadiv->id]);
        $selesai = $this->buat(StatusPengajuan::Selesai, 100);

        $this->actingAs($this->kadiv);

        $this->get("/pengajuan/{$draft->id}")->assertSee('Hapus Draft')->assertSee('Ubah');
        $this->get("/pengajuan/{$diajukan->id}")->assertSee('Menunggu tinjauan Pejabat Pengadaan.')->assertDontSee('Hapus Draft')->assertDontSee('Lapor Harga Aktual');
        $this->get("/pengajuan/{$danaCair->id}")->assertSee('Lapor Harga Aktual')->assertSee('Dana Dicairkan');
        $this->get("/pengajuan/{$selesai->id}")->assertSee('Proses selesai.');
    }

    public function test_form_tambah_menampilkan_pilihan_kategori_kosong_dan_satuan(): void
    {
        $this->actingAs($this->kadiv)->get('/pengajuan/create')
            ->assertOk()->assertSee('Pilih kategori')->assertSee('Satuan')->assertSee('Rim')
            ->assertDontSee('type="file"', false);
    }
}
