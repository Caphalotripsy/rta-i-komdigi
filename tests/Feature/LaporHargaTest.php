<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatusPengajuan;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporHargaTest extends TestCase
{
    use RefreshDatabase;

    private User $kadiv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kadiv = User::factory()->create(['role' => Role::KepalaDivisi]);
    }

    /** 3 unit x Rp 1.800.000 = Rp 5.400.000, dana sudah dicairkan. */
    private function danaCair(array $override = []): PengajuanPengadaan
    {
        return PengajuanPengadaan::factory()->danaCair()->create($override + [
            'pemohon_id' => $this->kadiv->id,
            'nama_barang' => 'Monitor 24 inci',
            'kuantitas' => 3,
            'harga_estimasi' => 1800000,
            'total_estimasi_biaya' => 5400000,
        ]);
    }

    private function laporan(array $override = []): array
    {
        return $override + [
            'kuantitas_terbeli' => 3,
            'harga_aktual' => 1750000,
            'nama_toko' => 'CV Borneo Perkasa Komputer',
            'nomor_nota' => 'INV/BPK/2026/11/089',
            'tanggal_nota' => now()->toDateString(),
        ];
    }

    public function test_halaman_lapor_tampil_untuk_pemilik(): void
    {
        $p = $this->danaCair();

        $this->actingAs($this->kadiv)->get("/pengajuan/{$p->id}/lapor")
            ->assertOk()
            ->assertSee('Lapor Harga Aktual')
            ->assertSee('Monitor 24 inci');
    }

    public function test_sisa_dana_menjadi_menunggu_pengembalian(): void
    {
        $p = $this->danaCair();

        $this->actingAs($this->kadiv)->post("/pengajuan/{$p->id}/lapor", $this->laporan())
            ->assertRedirect("/pengajuan/{$p->id}");

        $p->refresh();
        $this->assertSame(StatusPengajuan::MenungguPengembalian, $p->status);
        $this->assertEquals(5250000, $p->total_biaya_aktual);
        $this->assertEquals(150000, $p->sisa_dana);
        $this->assertFalse($p->status_pengembalian_dana);
        $this->assertSame('CV Borneo Perkasa Komputer', $p->nama_toko);
        $this->assertSame('menunggu_pengembalian', $p->riwayat->last()->status_ke);
    }

    public function test_tanpa_sisa_dana_langsung_cek_fisik(): void
    {
        $p = $this->danaCair();

        $this->actingAs($this->kadiv)->post("/pengajuan/{$p->id}/lapor", $this->laporan(['harga_aktual' => 1800000]));

        $p->refresh();
        $this->assertSame(StatusPengajuan::CekFisik, $p->status);
        $this->assertEquals(0, $p->sisa_dana);
        $this->assertSame('Tidak ada sisa dana', $p->labelPengembalianDana());
    }

    public function test_kuantitas_lebih_sedikit_menambah_sisa_dana(): void
    {
        $p = $this->danaCair();

        $this->actingAs($this->kadiv)->post("/pengajuan/{$p->id}/lapor", $this->laporan(['kuantitas_terbeli' => 2, 'harga_aktual' => 1800000]));

        $p->refresh();
        $this->assertSame(StatusPengajuan::MenungguPengembalian, $p->status);
        $this->assertEquals(1800000, $p->sisa_dana);
    }

    public function test_validasi_batas_dan_kelengkapan(): void
    {
        $p = $this->danaCair();
        $this->actingAs($this->kadiv);

        $this->post("/pengajuan/{$p->id}/lapor", $this->laporan(['harga_aktual' => 1900000]))
            ->assertSessionHasErrors('harga_aktual');
        $this->post("/pengajuan/{$p->id}/lapor", $this->laporan(['kuantitas_terbeli' => 4]))
            ->assertSessionHasErrors('kuantitas_terbeli');
        $this->post("/pengajuan/{$p->id}/lapor", $this->laporan(['tanggal_nota' => now()->addDay()->toDateString()]))
            ->assertSessionHasErrors('tanggal_nota');
        $this->post("/pengajuan/{$p->id}/lapor", [])
            ->assertSessionHasErrors(['kuantitas_terbeli', 'harga_aktual', 'nama_toko', 'nomor_nota', 'tanggal_nota']);

        $this->assertSame(StatusPengajuan::DanaCair, $p->fresh()->status);
    }

    public function test_hanya_status_dana_cair_dan_hanya_pemilik(): void
    {
        $diajukan = PengajuanPengadaan::factory()->status(StatusPengajuan::Diajukan)->create(['pemohon_id' => $this->kadiv->id]);
        $milikLain = $this->danaCair(['pemohon_id' => User::factory()->create()->id]);

        $this->actingAs($this->kadiv);
        $this->get("/pengajuan/{$diajukan->id}/lapor")->assertForbidden();
        $this->post("/pengajuan/{$diajukan->id}/lapor", $this->laporan())->assertForbidden();
        $this->get("/pengajuan/{$milikLain->id}/lapor")->assertForbidden();
        $this->post("/pengajuan/{$milikLain->id}/lapor", $this->laporan())->assertForbidden();
    }

    public function test_dikembalikan_keuangan_bisa_dilapor_ulang_dan_bukan_lewat_form_utama(): void
    {
        $p = PengajuanPengadaan::factory()->dikembalikanOleh('keuangan', 'Nominal nota berbeda')->create([
            'pemohon_id' => $this->kadiv->id,
            'kuantitas' => 3, 'harga_estimasi' => 1800000, 'total_estimasi_biaya' => 5400000, 'dana_dicairkan' => 5400000,
            'sisa_dana' => 150000, 'status_pengembalian_dana' => true,
        ]);

        $this->actingAs($this->kadiv);
        $this->get("/pengajuan/{$p->id}")->assertSee('Perbaiki Laporan')->assertSee('Nominal nota berbeda');
        $this->get("/pengajuan/{$p->id}/edit")->assertForbidden();

        $this->post("/pengajuan/{$p->id}/lapor", $this->laporan(['harga_aktual' => 1780000]))->assertRedirect();

        $p->refresh();
        $this->assertSame(StatusPengajuan::MenungguPengembalian, $p->status);
        $this->assertNull($p->dikembalikan_oleh);
        $this->assertFalse($p->status_pengembalian_dana); // konfirmasi Bendahara diulang
        $this->assertEquals(60000, $p->sisa_dana);
    }

    public function test_dana_belum_tercatat_memakai_total_estimasi(): void
    {
        $p = $this->danaCair();
        $p->update(['dana_dicairkan' => null]);

        $this->actingAs($this->kadiv)->post("/pengajuan/{$p->id}/lapor", $this->laporan());

        $p->refresh();
        $this->assertEquals(5400000, $p->dana_dicairkan);
        $this->assertEquals(150000, $p->sisa_dana);
    }
}
