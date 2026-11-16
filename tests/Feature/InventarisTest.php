<?php

namespace Tests\Feature;

use App\Enums\Kategori;
use App\Enums\KondisiAset;
use App\Enums\Role;
use App\Enums\StatusPengajuan;
use App\Models\AsetInventaris;
use App\Models\Inventaris;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventarisTest extends TestCase
{
    use RefreshDatabase;

    private User $inv;
    private User $kadiv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inv = User::factory()->create(['role' => Role::Inventaris, 'name' => 'Maya Putri']);
        $this->kadiv = User::factory()->create(['role' => Role::KepalaDivisi, 'name' => 'Budi Santoso', 'divisi' => 'Divisi Pelatihan']);
    }

    private function selesai(Kategori $kategori, int $qty, array $o = []): PengajuanPengadaan
    {
        return PengajuanPengadaan::factory()->status(StatusPengajuan::Selesai)->create($o + [
            'pemohon_id' => $this->kadiv->id, 'nama_barang' => $kategori === Kategori::Aset ? 'Monitor 24 inci' : 'Kertas HVS F4',
            'kategori' => $kategori, 'kuantitas' => $qty, 'kuantitas_terbeli' => $qty, 'harga_estimasi' => 100000, 'total_estimasi_biaya' => $qty * 100000,
        ]);
    }

    private function unit(int $n, array $o = []): array
    {
        return ['unit' => collect(range(1, $n))->map(fn ($i) => $o + ['nama_penanggung_jawab' => "Staf {$i}", 'lokasi' => 'Ruang Server', 'kondisi' => 'baik'])->all()];
    }

    public function test_hanya_inventaris_yang_boleh_mengakses(): void
    {
        $p = $this->selesai(Kategori::Aset, 1);

        foreach (['/antrean-aset', "/antrean-aset/{$p->id}/registrasi", '/pembaruan-stok', '/master-inventory', '/cetak/laporan'] as $url) {
            $this->actingAs($this->kadiv)->get($url)->assertForbidden();
        }
        $this->actingAs($this->kadiv)->post("/antrean-aset/{$p->id}/registrasi", $this->unit(1))->assertForbidden();
    }

    public function test_dashboard_menu_dan_antrean_hanya_request_belum_diproses(): void
    {
        $this->selesai(Kategori::Aset, 2, ['nama_barang' => 'Antre Aset']);
        $this->selesai(Kategori::HabisPakai, 5, ['nama_barang' => 'Antre Stok']);
        $this->selesai(Kategori::Aset, 1, ['nama_barang' => 'Sudah Diproses', 'diproses_inventaris_at' => now()]);
        PengajuanPengadaan::factory()->status(StatusPengajuan::CekFisik)->create(['pemohon_id' => $this->kadiv->id, 'nama_barang' => 'Belum Ditutup', 'kategori' => Kategori::Aset]);

        $r = $this->actingAs($this->inv)->get('/dashboard')->assertOk()
            ->assertSee('Dashboard Inventaris')->assertSee('Draft Antrean Aset')->assertSee('Pembaruan Stok')->assertSee('Master Inventory');
        $this->assertSame(1, $r->viewData('statistik')['antreanAset']);
        $this->assertSame(1, $r->viewData('statistik')['antreanStok']);

        $this->get('/antrean-aset')->assertSee('Antre Aset')->assertDontSee('Sudah Diproses')->assertDontSee('Belum Ditutup')->assertDontSee('Antre Stok');
    }

    public function test_formulir_registrasi_satu_baris_per_unit_dengan_sku_berurutan(): void
    {
        $p = $this->selesai(Kategori::Aset, 3);

        $this->actingAs($this->inv)->get("/antrean-aset/{$p->id}/registrasi")->assertOk()
            ->assertSee('AST-'.now()->format('Y').'-0001')->assertSee('-0002')->assertSee('-0003')->assertSee('Unit #3')
            ->assertDontSee('type="file"', false);
    }

    public function test_registrasi_membuat_aset_per_unit_dengan_sku_unik_dan_riwayat(): void
    {
        $p = $this->selesai(Kategori::Aset, 3);
        $tahun = now()->format('Y');

        $this->actingAs($this->inv)->post("/antrean-aset/{$p->id}/registrasi", $this->unit(3))
            ->assertRedirect(route('cetak.label', ['pengajuan' => $p->id]));

        $this->assertSame(3, AsetInventaris::count());
        $this->assertSame(["AST-{$tahun}-0001", "AST-{$tahun}-0002", "AST-{$tahun}-0003"], AsetInventaris::orderBy('sku_barcode')->pluck('sku_barcode')->all());

        $inv = Inventaris::firstOrFail();
        $this->assertSame(3, $inv->total_stok);
        $this->assertSame($p->id, $inv->pengajuan_id);

        $aset = AsetInventaris::first();
        $this->assertSame(KondisiAset::Baik, $aset->kondisi);
        $this->assertSame('Registrasi awal', $aset->mutasi->last()->catatan);
        $this->assertNotNull($p->fresh()->diproses_inventaris_at);
        $this->assertStringContainsString('3 aset', $p->fresh()->riwayat->last()->catatan);
    }

    public function test_sku_melanjutkan_nomor_dari_registrasi_sebelumnya(): void
    {
        $a = $this->selesai(Kategori::Aset, 2);
        $b = $this->selesai(Kategori::Aset, 1);
        $tahun = now()->format('Y');
        $this->actingAs($this->inv);

        $this->post("/antrean-aset/{$a->id}/registrasi", $this->unit(2));
        $this->post("/antrean-aset/{$b->id}/registrasi", $this->unit(1));

        $this->assertTrue(AsetInventaris::where('sku_barcode', "AST-{$tahun}-0003")->exists());
    }

    public function test_validasi_registrasi(): void
    {
        $p = $this->selesai(Kategori::Aset, 2);
        $this->actingAs($this->inv);

        $this->post("/antrean-aset/{$p->id}/registrasi", $this->unit(1))->assertSessionHasErrors('unit');   // jumlah salah
        $this->post("/antrean-aset/{$p->id}/registrasi", $this->unit(2, ['nama_penanggung_jawab' => '']))->assertSessionHasErrors(['unit.0.nama_penanggung_jawab', 'unit.1.nama_penanggung_jawab']);
        $this->post("/antrean-aset/{$p->id}/registrasi", $this->unit(2, ['lokasi' => '']))->assertSessionHasErrors('unit.0.lokasi');
        $this->post("/antrean-aset/{$p->id}/registrasi", $this->unit(2, ['kondisi' => 'hilang']))->assertSessionHasErrors('unit.0.kondisi');

        $this->assertSame(0, AsetInventaris::count());
        $this->assertNull($p->fresh()->diproses_inventaris_at);
    }

    public function test_tidak_bisa_didaftarkan_dua_kali_atau_salah_kategori_atau_belum_selesai(): void
    {
        $aset = $this->selesai(Kategori::Aset, 1);
        $stok = $this->selesai(Kategori::HabisPakai, 4);
        $cek = PengajuanPengadaan::factory()->status(StatusPengajuan::CekFisik)->create(['pemohon_id' => $this->kadiv->id, 'kategori' => Kategori::Aset, 'kuantitas_terbeli' => 1]);
        $this->actingAs($this->inv);

        $this->post("/antrean-aset/{$aset->id}/registrasi", $this->unit(1));
        $this->post("/antrean-aset/{$aset->id}/registrasi", $this->unit(1))->assertSessionHasErrors('status');
        $this->get("/antrean-aset/{$aset->id}/registrasi")->assertRedirect('/antrean-aset');

        $this->post("/antrean-aset/{$stok->id}/registrasi", $this->unit(4))->assertSessionHasErrors('status');   // habis pakai
        $this->post("/antrean-aset/{$cek->id}/registrasi", $this->unit(1))->assertSessionHasErrors('status');    // belum selesai
        $this->assertSame(1, AsetInventaris::count());
    }

    public function test_catat_stok_dan_total_dihitung_per_nama_dan_satuan(): void
    {
        $a = $this->selesai(Kategori::HabisPakai, 10);
        $b = $this->selesai(Kategori::HabisPakai, 4);
        $this->actingAs($this->inv);

        $this->get('/pembaruan-stok')->assertOk()->assertSee('Kertas HVS F4')->assertSee('Pratinjau saldo stok');
        $this->post("/pembaruan-stok/{$a->id}", ['nama_barang' => 'Kertas HVS F4', 'satuan' => 'rim'])->assertRedirect('/pembaruan-stok');
        $this->post("/pembaruan-stok/{$b->id}", ['nama_barang' => '  kertas hvs f4 ', 'satuan' => 'rim']);   // nama sama (beda huruf/spasi)

        $this->assertSame(2, Inventaris::count());
        $this->assertNotNull($a->fresh()->diproses_inventaris_at);

        $r = $this->get('/master-inventory?tab=stok')->assertOk();
        $this->assertCount(1, $r->viewData('stok'));
        $this->assertSame(14, $r->viewData('stok')->first()->total);
        $this->assertSame(2, $r->viewData('stok')->first()->masuk);
    }

    public function test_stok_validasi_dan_tidak_dua_kali(): void
    {
        $p = $this->selesai(Kategori::HabisPakai, 10);
        $aset = $this->selesai(Kategori::Aset, 1);
        $this->actingAs($this->inv);

        $this->post("/pembaruan-stok/{$p->id}", ['nama_barang' => '', 'satuan' => 'meter'])->assertSessionHasErrors(['nama_barang', 'satuan']);
        $this->post("/pembaruan-stok/{$p->id}", ['nama_barang' => 'Kertas', 'satuan' => 'rim']);
        $this->post("/pembaruan-stok/{$p->id}", ['nama_barang' => 'Kertas', 'satuan' => 'rim'])->assertSessionHasErrors('status');
        $this->post("/pembaruan-stok/{$aset->id}", ['nama_barang' => 'X', 'satuan' => 'unit'])->assertSessionHasErrors('status');   // kategori aset
        $this->assertSame(1, Inventaris::count());
    }

    public function test_master_inventory_pencarian_filter_dan_detail(): void
    {
        $p = $this->selesai(Kategori::Aset, 2);
        $this->actingAs($this->inv)->post("/antrean-aset/{$p->id}/registrasi", ['unit' => [
            ['nama_penanggung_jawab' => 'Rahmat', 'lokasi' => 'Ruang Kerja', 'kondisi' => 'baik'],
            ['nama_penanggung_jawab' => 'Lina', 'lokasi' => 'Ruang Rapat', 'kondisi' => 'rusak'],
        ]]);

        $this->get('/master-inventory')->assertOk()->assertSee('Monitor 24 inci')->assertSee('Ruang Kerja')->assertSee('Detail / Mutasi');
        $this->get('/master-inventory?q=lina')->assertSee('Ruang Rapat')->assertDontSee('Ruang Kerja');
        $this->get('/master-inventory?kondisi=rusak')->assertSee('Ruang Rapat')->assertDontSee('Ruang Kerja');
        $this->get('/master-inventory?q=tidak-ada')->assertSee('Belum ada aset terdaftar.');

        $aset = AsetInventaris::first();
        $this->get("/master-inventory/aset/{$aset->id}")->assertOk()->assertSee($aset->sku_barcode)->assertSee('Registrasi awal')->assertSee('Cetak Label');
    }

    public function test_mutasi_mencatat_riwayat_dan_wajib_ada_perubahan(): void
    {
        $p = $this->selesai(Kategori::Aset, 1);
        $this->actingAs($this->inv)->post("/antrean-aset/{$p->id}/registrasi", $this->unit(1));
        $aset = AsetInventaris::firstOrFail();

        $this->post("/master-inventory/aset/{$aset->id}/mutasi", ['lokasi' => 'Ruang Server', 'nama_penanggung_jawab' => 'Staf 1', 'kondisi' => 'baik'])
            ->assertSessionHasErrors('status');                                   // tidak ada perubahan
        $this->post("/master-inventory/aset/{$aset->id}/mutasi", ['lokasi' => '', 'nama_penanggung_jawab' => 'X', 'kondisi' => 'baik'])->assertSessionHasErrors('lokasi');
        $this->post("/master-inventory/aset/{$aset->id}/mutasi", ['lokasi' => 'Ruang Rapat', 'nama_penanggung_jawab' => 'Lina', 'kondisi' => 'rusak', 'catatan' => 'Dipindah'])
            ->assertRedirect("/master-inventory/aset/{$aset->id}");

        $aset->refresh();
        $this->assertSame('Ruang Rapat', $aset->lokasi);
        $this->assertSame(KondisiAset::Rusak, $aset->kondisi);
        $this->assertCount(2, $aset->mutasi);
        $this->assertSame('Ruang Server', $aset->mutasi->first()->lokasi_lama);
        $this->get("/master-inventory/aset/{$aset->id}")->assertSee('Ruang Server')->assertSee('Dipindah');

        $this->post("/master-inventory/aset/{$aset->id}/mutasi", ['lokasi' => 'Ruang Rapat', 'nama_penanggung_jawab' => 'Lina', 'kondisi' => 'hilang']);
        $this->assertFalse($aset->fresh()->aktif());
    }

    public function test_halaman_cetak_label_dan_laporan(): void
    {
        $p = $this->selesai(Kategori::Aset, 2);
        $this->actingAs($this->inv)->post("/antrean-aset/{$p->id}/registrasi", $this->unit(2));
        $aset = AsetInventaris::orderBy('id')->first();

        $this->get("/cetak/label?pengajuan={$p->id}")->assertOk()->assertSee('data-barcode="'.$aset->sku_barcode.'"', false)->assertSee('Cetak / Simpan PDF');
        $this->get("/cetak/label?aset={$aset->id}")->assertOk()->assertSee($aset->sku_barcode, false);
        $this->get('/cetak/label?aset=99999')->assertNotFound();

        $this->get('/cetak/laporan')->assertOk()->assertSee('Laporan Inventaris Aset')->assertSee('Total 2 aset')->assertSee('Maya Putri');
        $this->get('/cetak/laporan?kondisi=hilang')->assertSee('Total 0 aset');
    }
}
