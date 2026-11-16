<?php

namespace Tests\Feature;

use App\Enums\PeriodeBatch;
use App\Enums\Role;
use App\Enums\StatusBatch;
use App\Enums\StatusPengajuan;
use App\Models\BatchPengadaan;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use App\Services\BatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PpkTest extends TestCase
{
    use RefreshDatabase;

    private User $ppk;
    private User $pp;
    private User $kadiv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ppk = User::factory()->create(['role' => Role::Ppk, 'name' => 'Andi Pratama']);
        $this->pp = User::factory()->create(['role' => Role::PejabatPengadaan, 'name' => 'Siti Rahma']);
        $this->kadiv = User::factory()->create(['role' => Role::KepalaDivisi, 'divisi' => 'Divisi Pelatihan']);
    }

    /** Batch berstatus Menunggu PPK berisi dua request (Rp 6.500.000 dan Rp 6.000.000). */
    private function batchMenunggu(): BatchPengadaan
    {
        $ids = [];
        foreach ([6500000, 6000000] as $total) {
            $ids[] = PengajuanPengadaan::factory()->status(StatusPengajuan::DisetujuiPengadaan)->create([
                'pemohon_id' => $this->kadiv->id, 'kuantitas' => 1, 'harga_estimasi' => $total, 'total_estimasi_biaya' => $total,
            ])->id;
        }

        return app(BatchService::class)->buat($this->pp, PeriodeBatch::Bulanan, $ids);
    }

    public function test_hanya_ppk_yang_boleh_mengakses(): void
    {
        $batch = $this->batchMenunggu();

        foreach (['/persetujuan-batch', "/persetujuan-batch/{$batch->id}", '/riwayat-keputusan'] as $url) {
            $this->actingAs($this->pp)->get($url)->assertForbidden();
            $this->actingAs($this->kadiv)->get($url)->assertForbidden();
        }
        $this->actingAs($this->pp)->post("/persetujuan-batch/{$batch->id}/setujui")->assertForbidden();
        $this->actingAs($this->pp)->post("/persetujuan-batch/{$batch->id}/kembalikan", ['catatan' => 'x'])->assertForbidden();
    }

    public function test_dashboard_dan_menu_ppk(): void
    {
        $this->batchMenunggu();

        $r = $this->actingAs($this->ppk)->get('/dashboard')->assertOk()
            ->assertSee('Dashboard PPK')->assertSee('Persetujuan Batch')->assertSee('Riwayat Keputusan')->assertDontSee('Tambah Request');

        $this->assertSame(1, $r->viewData('statistik')['batchMenunggu']);
        $this->assertSame(2, $r->viewData('statistik')['requestMenunggu']);
        $this->assertEquals(12500000, $r->viewData('statistik')['totalMenunggu']);
    }

    public function test_daftar_hanya_batch_menunggu_dan_pencarian(): void
    {
        $batch = $this->batchMenunggu();
        BatchPengadaan::create(['periode' => 'bulanan', 'status' => StatusBatch::Disetujui, 'dibuat_oleh' => $this->pp->id])->refresh();

        $this->actingAs($this->ppk)->get('/persetujuan-batch')->assertOk()->assertSee($batch->kode_batch)->assertSee('2 request')
            ->assertSee('Siti Rahma');
        $this->assertCount(1, $this->get('/persetujuan-batch')->viewData('batch'));
        $this->get('/persetujuan-batch?q=tidak-ada')->assertSee('Tidak ada batch yang menunggu keputusan.');
    }

    public function test_halaman_detail_menampilkan_isi_batch_dan_lembar_keputusan(): void
    {
        $batch = $this->batchMenunggu();

        $this->actingAs($this->ppk)->get("/persetujuan-batch/{$batch->id}")->assertOk()
            ->assertSee("Tinjau Batch {$batch->kode_batch}")->assertSee('Keputusan per batch')
            ->assertSee('Rp 12.500.000')->assertSee('Setujui Batch')->assertSee('Kembalikan Batch')
            ->assertSee($this->kadiv->name)->assertSee('atas nama')->assertSee('Andi Pratama');
    }

    public function test_setujui_batch_menyetujui_seluruh_request_di_dalamnya(): void
    {
        $batch = $this->batchMenunggu();

        $this->actingAs($this->ppk)->post("/persetujuan-batch/{$batch->id}/setujui")->assertRedirect('/persetujuan-batch');

        $batch->refresh();
        $this->assertSame(StatusBatch::Disetujui, $batch->status);
        $this->assertSame($this->ppk->id, $batch->diputuskan_oleh);
        $this->assertNotNull($batch->diputuskan_pada);

        foreach ($batch->pengajuan as $p) {
            $this->assertSame(StatusPengajuan::Disetujui, $p->status);
            $this->assertSame($this->ppk->id, $p->riwayat->last()->aktor_id);
            $this->assertStringContainsString('disetujui PPK', $p->riwayat->last()->catatan);
        }
    }

    public function test_kembalikan_batch_wajib_catatan_dan_mengembalikan_request_ke_siap_batching(): void
    {
        $batch = $this->batchMenunggu();
        $this->actingAs($this->ppk);

        $this->post("/persetujuan-batch/{$batch->id}/kembalikan", ['catatan' => '   '])->assertSessionHasErrors('catatan');
        $this->assertSame(StatusBatch::PendingPpk, $batch->fresh()->status);

        $this->post("/persetujuan-batch/{$batch->id}/kembalikan", ['catatan' => 'REQ-A spesifikasi belum jelas'])
            ->assertRedirect('/persetujuan-batch');

        $batch->refresh();
        $this->assertSame(StatusBatch::Direvisi, $batch->status);
        $this->assertSame('REQ-A spesifikasi belum jelas', $batch->catatan_revisi);
        $this->assertSame($this->ppk->id, $batch->diputuskan_oleh);

        foreach ($batch->pengajuan as $p) {
            $this->assertSame(StatusPengajuan::DisetujuiPengadaan, $p->status);
            $this->assertSame($batch->id, $p->batch_id);   // tetap terhubung ke batch
        }
    }

    public function test_keputusan_hanya_bisa_diambil_satu_kali(): void
    {
        $batch = $this->batchMenunggu();
        $this->actingAs($this->ppk)->post("/persetujuan-batch/{$batch->id}/setujui");

        $this->post("/persetujuan-batch/{$batch->id}/setujui")->assertSessionHasErrors('status');
        $this->post("/persetujuan-batch/{$batch->id}/kembalikan", ['catatan' => 'x'])->assertSessionHasErrors('status');
        $this->assertSame(StatusBatch::Disetujui, $batch->fresh()->status);
        $this->get("/persetujuan-batch/{$batch->id}")->assertOk()->assertDontSee('Kembalikan Batch')->assertSee('Diputuskan oleh');
    }

    public function test_batch_dengan_status_lain_tidak_bisa_diputuskan(): void
    {
        $batch = BatchPengadaan::create(['periode' => 'bulanan', 'status' => StatusBatch::Direvisi, 'dibuat_oleh' => $this->pp->id]);

        $this->actingAs($this->ppk)->post("/persetujuan-batch/{$batch->id}/setujui")->assertSessionHasErrors('status');
        $this->assertSame(StatusBatch::Direvisi, $batch->fresh()->status);
    }

    public function test_riwayat_keputusan_dan_filter(): void
    {
        $disetujui = $this->batchMenunggu();
        $dikembalikan = $this->batchMenunggu();
        $this->batchMenunggu();   // belum diputuskan

        $this->actingAs($this->ppk);
        $this->post("/persetujuan-batch/{$disetujui->id}/setujui");
        $this->post("/persetujuan-batch/{$dikembalikan->id}/kembalikan", ['catatan' => 'Perlu diperbaiki']);

        $r = $this->get('/riwayat-keputusan')->assertOk();
        $this->assertSame(2, $r->viewData('jumlah')['semua']);
        $this->assertSame(1, $r->viewData('jumlah')['disetujui']);
        $this->assertSame(1, $r->viewData('jumlah')['dikembalikan']);

        $this->get('/riwayat-keputusan?keputusan=disetujui')->assertSee($disetujui->kode_batch)->assertDontSee($dikembalikan->kode_batch);
        $this->get('/riwayat-keputusan?keputusan=dikembalikan')->assertSee($dikembalikan->kode_batch)->assertSee('Perlu diperbaiki');
    }

    /** Alur lintas peran: PPK mengembalikan -> Pejabat Pengadaan menindaklanjuti -> PPK memutuskan lagi. */
    public function test_alur_lengkap_kembali_dan_kirim_ulang(): void
    {
        $batch = $this->batchMenunggu();
        $this->actingAs($this->ppk)->post("/persetujuan-batch/{$batch->id}/kembalikan", ['catatan' => 'Periksa harga']);

        // Pejabat Pengadaan melihat nama PPK dan catatannya.
        $this->actingAs($this->pp)->get("/batch/{$batch->id}")->assertSee('Dikembalikan oleh PPK')->assertSee('Andi Pratama')->assertSee('Periksa harga');
        $this->get('/dashboard')->assertSee("Batch {$batch->kode_batch} dikembalikan oleh PPK")->assertSee('Andi Pratama');

        // Kirim ulang: keputusan lama dikosongkan, batch kembali ke antrean PPK.
        $this->post("/batch/{$batch->id}/kirim-ulang")->assertRedirect();
        $batch->refresh();
        $this->assertSame(StatusBatch::PendingPpk, $batch->status);
        $this->assertNull($batch->diputuskan_oleh);
        $this->assertNull($batch->diputuskan_pada);

        $this->actingAs($this->ppk)->get('/persetujuan-batch')->assertSee($batch->kode_batch);
        $this->post("/persetujuan-batch/{$batch->id}/setujui")->assertRedirect();
        $this->assertSame(StatusBatch::Disetujui, $batch->fresh()->status);
        $this->assertSame(2, PengajuanPengadaan::where('status', StatusPengajuan::Disetujui->value)->count());
    }
}
