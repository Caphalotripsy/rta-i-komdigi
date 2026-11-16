<?php

namespace Tests\Feature;

use App\Enums\MetodePencairan;
use App\Enums\Role;
use App\Enums\StatusPengajuan;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BendaharaTest extends TestCase
{
    use RefreshDatabase;

    private User $bendahara;
    private User $kadiv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bendahara = User::factory()->create(['role' => Role::Bendahara, 'name' => 'Dewi Lestari']);
        $this->kadiv = User::factory()->create(['role' => Role::KepalaDivisi, 'name' => 'Rahmat Hidayat', 'divisi' => 'Divisi Umum']);
    }

    private function request(StatusPengajuan $status, array $o = []): PengajuanPengadaan
    {
        return PengajuanPengadaan::factory()->status($status)->create($o + [
            'pemohon_id' => $this->kadiv->id, 'nama_barang' => 'Spidol Whiteboard',
            'kuantitas' => 12, 'harga_estimasi' => 85000, 'total_estimasi_biaya' => 1020000,
        ]);
    }

    private function data(array $o = []): array
    {
        return $o + ['metode_pencairan' => 'transfer', 'tanggal_pencairan' => now()->toDateString(), 'referensi_pencairan' => 'BKU-089'];
    }

    public function test_hanya_bendahara_yang_boleh_mengakses(): void
    {
        $p = $this->request(StatusPengajuan::Disetujui);

        foreach (['/pencairan', "/pencairan/{$p->id}", '/pengembalian-dana'] as $url) {
            $this->actingAs($this->kadiv)->get($url)->assertForbidden();
        }
        $this->actingAs($this->kadiv)->post("/pencairan/{$p->id}", $this->data())->assertForbidden();
        $this->actingAs($this->kadiv)->post("/pengembalian-dana/{$p->id}", ['tanggal_pengembalian' => now()->toDateString()])->assertForbidden();
    }

    public function test_dashboard_dan_menu_bendahara(): void
    {
        $this->request(StatusPengajuan::Disetujui);
        $this->request(StatusPengajuan::MenungguPengembalian, ['sisa_dana' => 200000]);

        $r = $this->actingAs($this->bendahara)->get('/dashboard')->assertOk()
            ->assertSee('Dashboard Bendahara')->assertSee('Pencairan Dana')->assertSee('Pengembalian Dana')->assertDontSee('Tambah Request');

        $this->assertSame(1, $r->viewData('statistik')['menungguPencairan']);
        $this->assertSame(1, $r->viewData('statistik')['menungguPengembalian']);
        $this->assertEquals(1020000, $r->viewData('statistik')['danaMenunggu']);
        $this->assertEquals(200000, $r->viewData('statistik')['sisaBelumKembali']);
    }

    public function test_daftar_pencairan_hanya_request_disetujui(): void
    {
        $this->request(StatusPengajuan::Disetujui, ['nama_barang' => 'Siap Cair']);
        $this->request(StatusPengajuan::DanaCair, ['nama_barang' => 'Sudah Cair']);
        $this->request(StatusPengajuan::Diajukan, ['nama_barang' => 'Belum Disetujui']);

        $this->actingAs($this->bendahara)->get('/pencairan')->assertOk()
            ->assertSee('Siap Cair')->assertDontSee('Sudah Cair')->assertDontSee('Belum Disetujui');
        $this->get('/pencairan?q=rahmat')->assertSee('Siap Cair');
        $this->get('/pencairan?q=tidak-ada')->assertSee('Tidak ada request yang menunggu pencairan.');
    }

    public function test_formulir_menampilkan_dana_terkunci_dan_penerima(): void
    {
        $p = $this->request(StatusPengajuan::Disetujui);

        $this->actingAs($this->bendahara)->get("/pencairan/{$p->id}")->assertOk()
            ->assertSee('Rp 1.020.000')->assertSee('Rahmat Hidayat')->assertSee('Konfirmasi Pencairan')
            ->assertDontSee('name="dana_dicairkan"', false);
    }

    public function test_catat_pencairan_mengubah_status_dan_menyimpan_data(): void
    {
        $p = $this->request(StatusPengajuan::Disetujui);

        $this->actingAs($this->bendahara)->post("/pencairan/{$p->id}", $this->data())->assertRedirect('/pencairan');

        $p->refresh();
        $this->assertSame(StatusPengajuan::DanaCair, $p->status);
        $this->assertEquals(1020000, $p->dana_dicairkan);
        $this->assertSame(MetodePencairan::Transfer, $p->metode_pencairan);
        $this->assertSame(now()->toDateString(), $p->tanggal_pencairan->toDateString());
        $this->assertSame('BKU-089', $p->referensi_pencairan);
        $this->assertSame($this->bendahara->id, $p->riwayat->last()->aktor_id);
        $this->assertStringContainsString('Rp 1.020.000', $p->riwayat->last()->catatan);
    }

    public function test_nominal_dana_tidak_bisa_diubah_dari_input(): void
    {
        $p = $this->request(StatusPengajuan::Disetujui);

        $this->actingAs($this->bendahara)->post("/pencairan/{$p->id}", $this->data(['dana_dicairkan' => 1, 'total_estimasi_biaya' => 1]));

        $this->assertEquals(1020000, $p->fresh()->dana_dicairkan);
    }

    public function test_validasi_pencairan(): void
    {
        $p = $this->request(StatusPengajuan::Disetujui);
        $this->actingAs($this->bendahara);

        $this->post("/pencairan/{$p->id}", $this->data(['metode_pencairan' => 'cek']))->assertSessionHasErrors('metode_pencairan');
        $this->post("/pencairan/{$p->id}", $this->data(['tanggal_pencairan' => now()->addDay()->toDateString()]))->assertSessionHasErrors('tanggal_pencairan');
        $this->post("/pencairan/{$p->id}", [])->assertSessionHasErrors(['metode_pencairan', 'tanggal_pencairan']);
        $this->assertSame(StatusPengajuan::Disetujui, $p->fresh()->status);
    }

    public function test_hanya_request_disetujui_yang_bisa_dicairkan_dan_tidak_dua_kali(): void
    {
        $diajukan = $this->request(StatusPengajuan::Diajukan);
        $p = $this->request(StatusPengajuan::Disetujui);
        $this->actingAs($this->bendahara);

        $this->post("/pencairan/{$diajukan->id}", $this->data())->assertSessionHasErrors('status');
        $this->assertSame(StatusPengajuan::Diajukan, $diajukan->fresh()->status);

        $this->post("/pencairan/{$p->id}", $this->data());
        $this->post("/pencairan/{$p->id}", $this->data())->assertSessionHasErrors('status');
        $this->get("/pencairan/{$p->id}")->assertSee('tidak menunggu pencairan');
    }

    public function test_konfirmasi_pengembalian_masuk_ke_cek_fisik(): void
    {
        $p = $this->request(StatusPengajuan::MenungguPengembalian, ['sisa_dana' => 150000, 'dana_dicairkan' => 1020000]);

        $this->actingAs($this->bendahara)->get('/pengembalian-dana')->assertOk()->assertSee('Rp 150.000')->assertSee('Konfirmasi Pengembalian');
        $this->post("/pengembalian-dana/{$p->id}", ['tanggal_pengembalian' => now()->addDay()->toDateString()])->assertSessionHasErrors('tanggal_pengembalian');
        $this->post("/pengembalian-dana/{$p->id}", ['tanggal_pengembalian' => now()->toDateString()])->assertRedirect('/pengembalian-dana');

        $p->refresh();
        $this->assertSame(StatusPengajuan::CekFisik, $p->status);
        $this->assertTrue($p->status_pengembalian_dana);
        $this->assertSame(now()->toDateString(), $p->tanggal_pengembalian->toDateString());
        $this->assertSame('Sudah dikonfirmasi Bendahara', $p->labelPengembalianDana());
    }

    public function test_pengembalian_hanya_untuk_status_menunggu_pengembalian(): void
    {
        $p = $this->request(StatusPengajuan::DanaCair);

        $this->actingAs($this->bendahara)->post("/pengembalian-dana/{$p->id}", ['tanggal_pengembalian' => now()->toDateString()])->assertSessionHasErrors('status');
        $this->assertSame(StatusPengajuan::DanaCair, $p->fresh()->status);
    }

    /** Pencairan -> Lapor Harga memakai dana yang dicatat -> pengembalian dikonfirmasi. */
    public function test_alur_lengkap_pencairan_lapor_harga_pengembalian(): void
    {
        $p = $this->request(StatusPengajuan::Disetujui);

        $this->actingAs($this->bendahara)->post("/pencairan/{$p->id}", $this->data());
        $this->actingAs($this->kadiv)->post("/pengajuan/{$p->id}/lapor", [
            'kuantitas_terbeli' => 12, 'harga_aktual' => 80000, 'nama_toko' => 'Toko Maju', 'nomor_nota' => 'NT-1', 'tanggal_nota' => now()->toDateString(),
        ])->assertRedirect();

        $p->refresh();
        $this->assertSame(StatusPengajuan::MenungguPengembalian, $p->status);
        $this->assertEquals(60000, $p->sisa_dana);   // 1.020.000 - (12 x 80.000)

        $this->actingAs($this->bendahara)->post("/pengembalian-dana/{$p->id}", ['tanggal_pengembalian' => now()->toDateString()]);
        $this->assertSame(StatusPengajuan::CekFisik, $p->fresh()->status);
    }
}
