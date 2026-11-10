<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use App\Services\PengajuanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengajuanTest extends TestCase
{
    use RefreshDatabase;

    private User $kadiv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kadiv = User::factory()->create(['role' => Role::KepalaDivisi]);
    }

    private function data(array $override = []): array
    {
        return $override + [
            'nama_barang' => 'Laptop',
            'kategori' => 'aset',
            'spesifikasi' => 'RAM 16GB',
            'kuantitas' => 2,
            'harga_estimasi' => 10000000,
        ];
    }

    public function test_simpan_draft(): void
    {
        $this->actingAs($this->kadiv)->post('/pengajuan', $this->data(['aksi' => 'draft']))->assertRedirect();

        $p = PengajuanPengadaan::firstOrFail();
        $this->assertSame(StatusPengajuan::Draft, $p->status);
        $this->assertSame($this->kadiv->id, $p->pemohon_id);
        $this->assertCount(1, $p->riwayat);
    }

    public function test_kirim_langsung_menghitung_total_dan_mencatat_riwayat(): void
    {
        $this->actingAs($this->kadiv)->post('/pengajuan', $this->data(['aksi' => 'ajukan']));

        $p = PengajuanPengadaan::firstOrFail();
        $this->assertSame(StatusPengajuan::Diajukan, $p->status);
        $this->assertEquals(20000000, $p->total_estimasi_biaya);
        $this->assertSame(['draft', 'diajukan'], $p->riwayat->pluck('status_ke')->map(fn ($s) => $s)->all());
    }

    public function test_total_tidak_bisa_dimanipulasi_dari_input(): void
    {
        $this->actingAs($this->kadiv)->post('/pengajuan', $this->data(['total_estimasi_biaya' => 1, 'status' => 'selesai', 'pemohon_id' => 999]));

        $p = PengajuanPengadaan::firstOrFail();
        $this->assertEquals(20000000, $p->total_estimasi_biaya);
        $this->assertSame(StatusPengajuan::Draft, $p->status);
        $this->assertSame($this->kadiv->id, $p->pemohon_id);
    }

    public function test_validasi(): void
    {
        $this->actingAs($this->kadiv)->post('/pengajuan', $this->data(['kuantitas' => 0, 'kategori' => 'lain']))
            ->assertSessionHasErrors(['kuantitas', 'kategori']);

        $this->assertDatabaseCount('pengajuan_pengadaan', 0);
    }

    public function test_role_lain_tidak_bisa_mengakses(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Ppk]))->get('/pengajuan')->assertForbidden();
    }

    public function test_hanya_menampilkan_dan_membuka_milik_sendiri(): void
    {
        $milikSaya = PengajuanPengadaan::factory()->create(['pemohon_id' => $this->kadiv->id, 'nama_barang' => 'Barang Saya']);
        $milikLain = PengajuanPengadaan::factory()->create(['nama_barang' => 'Barang Orang']);

        $this->actingAs($this->kadiv)->get('/pengajuan')->assertSee('Barang Saya')->assertDontSee('Barang Orang');
        $this->get("/pengajuan/{$milikSaya->id}")->assertOk();
        $this->get("/pengajuan/{$milikLain->id}")->assertForbidden();
    }

    public function test_ubah_draft_lalu_tidak_bisa_diubah_setelah_dikirim(): void
    {
        $p = PengajuanPengadaan::factory()->create(['pemohon_id' => $this->kadiv->id]);

        $this->actingAs($this->kadiv)->put("/pengajuan/{$p->id}", $this->data(['nama_barang' => 'Printer', 'aksi' => 'draft']));
        $this->assertSame('Printer', $p->fresh()->nama_barang);

        $this->post("/pengajuan/{$p->id}/ajukan")->assertRedirect();
        $this->assertSame(StatusPengajuan::Diajukan, $p->fresh()->status);

        $this->put("/pengajuan/{$p->id}", $this->data(['nama_barang' => 'Dicurangi']))->assertForbidden();
        $this->assertSame('Printer', $p->fresh()->nama_barang);
    }

    public function test_revisi_dari_pengadaan_bisa_diedit_dan_dikirim_ulang(): void
    {
        $p = PengajuanPengadaan::factory()->dikembalikanOleh('pengadaan', 'Cari vendor lebih murah')
            ->create(['pemohon_id' => $this->kadiv->id]);

        $this->actingAs($this->kadiv)->get("/pengajuan/{$p->id}")->assertSee('Cari vendor lebih murah');
        $this->get("/pengajuan/{$p->id}/edit")->assertOk()->assertSee('Cari vendor lebih murah');

        $this->put("/pengajuan/{$p->id}", $this->data(['harga_estimasi' => 8000000, 'aksi' => 'ajukan']))->assertRedirect();

        $p->refresh();
        $this->assertSame(StatusPengajuan::Diajukan, $p->status);
        $this->assertNull($p->dikembalikan_oleh);
        $this->assertEquals(16000000, $p->total_estimasi_biaya);
        $this->assertSame('Dikirim ulang setelah revisi', $p->riwayat->last()->catatan);
    }

    public function test_revisi_dari_keuangan_tidak_diedit_lewat_form_pengajuan(): void
    {
        $p = PengajuanPengadaan::factory()->dikembalikanOleh('keuangan')->create(['pemohon_id' => $this->kadiv->id]);

        $this->actingAs($this->kadiv)->get("/pengajuan/{$p->id}/edit")->assertForbidden();
        $this->post("/pengajuan/{$p->id}/ajukan")->assertForbidden();
    }

    public function test_klik_ganda_tidak_menggandakan_riwayat(): void
    {
        $p = PengajuanPengadaan::factory()->create(['pemohon_id' => $this->kadiv->id]);
        $service = app(PengajuanService::class);

        $service->ajukan($p, $this->kadiv);

        try {
            $service->ajukan($p, $this->kadiv); // $p masih berstatus draft di memori: mensimulasikan permintaan kedua
            $this->fail('Seharusnya ditolak');
        } catch (StatusTidakValidException) {
            $this->assertCount(1, $p->riwayat()->where('status_ke', 'diajukan')->get());
        }
    }

    public function test_hapus_hanya_draft(): void
    {
        $draft = PengajuanPengadaan::factory()->create(['pemohon_id' => $this->kadiv->id]);
        $terkirim = PengajuanPengadaan::factory()->status(StatusPengajuan::Diajukan)->create(['pemohon_id' => $this->kadiv->id]);

        $this->actingAs($this->kadiv)->delete("/pengajuan/{$terkirim->id}")->assertForbidden();
        $this->delete("/pengajuan/{$draft->id}")->assertRedirect('/pengajuan');

        $this->assertModelMissing($draft);
        $this->assertModelExists($terkirim);
    }

    public function test_filter_status_dan_halaman_tampil(): void
    {
        PengajuanPengadaan::factory()->create(['pemohon_id' => $this->kadiv->id, 'nama_barang' => 'Masih Draft']);
        PengajuanPengadaan::factory()->status(StatusPengajuan::Diajukan)->create(['pemohon_id' => $this->kadiv->id, 'nama_barang' => 'Sudah Dikirim']);

        $this->actingAs($this->kadiv)->get('/pengajuan?status=diajukan')
            ->assertSee('Sudah Dikirim')->assertDontSee('Masih Draft');
        $this->get('/pengajuan/create')->assertOk();
    }
}
