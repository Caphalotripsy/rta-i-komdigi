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

class PejabatPengadaanTest extends TestCase
{
    use RefreshDatabase;

    private User $pp;
    private User $kadiv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pp = User::factory()->create(['role' => Role::PejabatPengadaan, 'name' => 'Siti Rahma']);
        $this->kadiv = User::factory()->create(['role' => Role::KepalaDivisi, 'divisi' => 'Divisi Pelatihan', 'name' => 'Budi Santoso']);
    }

    private function request(StatusPengajuan $status, int $total = 1000000, array $o = []): PengajuanPengadaan
    {
        return PengajuanPengadaan::factory()->status($status)->create($o + [
            'pemohon_id' => $this->kadiv->id, 'kuantitas' => 1, 'harga_estimasi' => $total, 'total_estimasi_biaya' => $total,
        ]);
    }

    /** Batch yang dikembalikan PPK, berisi dua request berstatus Siap Batching. */
    private function batchDikembalikan(): BatchPengadaan
    {
        $batch = BatchPengadaan::create([
            'periode' => PeriodeBatch::Mingguan, 'status' => StatusBatch::Direvisi,
            'dibuat_oleh' => $this->pp->id, 'catatan_revisi' => 'REQ-A spesifikasi belum jelas.',
        ])->refresh();

        foreach ([1500000, 900000] as $total) {
            $this->request(StatusPengajuan::DisetujuiPengadaan, $total, ['batch_id' => $batch->id]);
        }

        return $batch;
    }

    // ---------- Akses ----------

    public function test_hanya_pejabat_pengadaan_yang_boleh_mengakses(): void
    {
        $p = $this->request(StatusPengajuan::Diajukan);

        foreach (['/approval', "/approval/{$p->id}", '/batching', '/batch', '/batch/dikembalikan'] as $url) {
            $this->actingAs($this->kadiv)->get($url)->assertForbidden();
        }

        $this->actingAs($this->kadiv)->post("/approval/{$p->id}/setujui")->assertForbidden();
        $this->actingAs($this->kadiv)->post('/batching', ['periode' => 'bulanan', 'ids' => [$p->id]])->assertForbidden();
    }

    public function test_menu_sidebar_pejabat_pengadaan(): void
    {
        $this->actingAs($this->pp)->get('/dashboard')->assertOk()
            ->assertSee('Approval Tahap 1')->assertSee('Batching')->assertSee('Batch Dikembalikan')->assertSee('Riwayat Batch')
            ->assertDontSee('Tambah Request');
    }

    // ---------- Approval Tahap 1 ----------

    public function test_daftar_approval_hanya_request_diajukan_dengan_pencarian(): void
    {
        $this->request(StatusPengajuan::Diajukan, 100, ['nama_barang' => 'Laptop Dosen']);
        $this->request(StatusPengajuan::Draft, 100, ['nama_barang' => 'Draft Rahasia']);
        $this->request(StatusPengajuan::PendingPpk, 100, ['nama_barang' => 'Sudah Jalan']);

        $this->actingAs($this->pp)->get('/approval')->assertOk()
            ->assertSee('Laptop Dosen')->assertDontSee('Draft Rahasia')->assertDontSee('Sudah Jalan');

        $this->get('/approval?q=budi')->assertSee('Laptop Dosen');           // cari nama pemohon
        $this->get('/approval?q=tidak-ada')->assertSee('Tidak ada request yang menunggu tinjauan.');
    }

    public function test_setujui_request_menjadi_siap_batching_dan_tercatat(): void
    {
        $p = $this->request(StatusPengajuan::Diajukan);

        $this->actingAs($this->pp)->get("/approval/{$p->id}")->assertOk()->assertSee('Setujui Request')->assertSee('Budi Santoso');
        $this->post("/approval/{$p->id}/setujui")->assertRedirect('/approval');

        $p->refresh();
        $this->assertSame(StatusPengajuan::DisetujuiPengadaan, $p->status);
        $this->assertSame($this->pp->id, $p->riwayat->last()->aktor_id);
    }

    public function test_kembalikan_wajib_catatan_dan_pemohon_bisa_merevisi(): void
    {
        $p = $this->request(StatusPengajuan::Diajukan);
        $this->actingAs($this->pp);

        $this->post("/approval/{$p->id}/kembalikan", ['catatan' => '  '])->assertSessionHasErrors('catatan');
        $this->assertSame(StatusPengajuan::Diajukan, $p->fresh()->status);

        $this->post("/approval/{$p->id}/kembalikan", ['catatan' => 'Lengkapi spesifikasi'])->assertRedirect('/approval');

        $p->refresh();
        $this->assertSame(StatusPengajuan::Direvisi, $p->status);
        $this->assertSame('pengadaan', $p->dikembalikan_oleh);

        // Pemohon melihat catatan dan bisa mengirim ulang; request kembali ke Approval Tahap 1.
        $this->actingAs($this->kadiv)->get("/pengajuan/{$p->id}/edit")->assertOk()->assertSee('Lengkapi spesifikasi');
        $this->post("/pengajuan/{$p->id}/ajukan")->assertRedirect();
        $this->assertSame(StatusPengajuan::Diajukan, $p->fresh()->status);
    }

    public function test_request_yang_sudah_diproses_tidak_bisa_diputuskan_lagi(): void
    {
        $p = $this->request(StatusPengajuan::Diajukan);
        $this->actingAs($this->pp)->post("/approval/{$p->id}/setujui");

        $this->post("/approval/{$p->id}/setujui")->assertRedirect('/approval')->assertSessionHasErrors('status');
        $this->post("/approval/{$p->id}/kembalikan", ['catatan' => 'x'])->assertSessionHasErrors('status');
        $this->assertSame(StatusPengajuan::DisetujuiPengadaan, $p->fresh()->status);
        $this->get("/approval/{$p->id}")->assertSee('sudah diproses')->assertDontSee('Kembalikan untuk Revisi');
    }

    public function test_draft_milik_orang_lain_tidak_bisa_dilihat(): void
    {
        $draft = $this->request(StatusPengajuan::Draft);

        $this->actingAs($this->pp)->get("/approval/{$draft->id}")->assertForbidden();
    }

    // ---------- Batching ----------

    public function test_batching_hanya_menampilkan_request_siap_dan_belum_dibatch(): void
    {
        $this->request(StatusPengajuan::DisetujuiPengadaan, 100, ['nama_barang' => 'Siap Satu']);
        $batch = BatchPengadaan::create(['periode' => 'bulanan', 'dibuat_oleh' => $this->pp->id]);
        $this->request(StatusPengajuan::DisetujuiPengadaan, 100, ['nama_barang' => 'Sudah Punya Batch', 'batch_id' => $batch->id]);
        $this->request(StatusPengajuan::Diajukan, 100, ['nama_barang' => 'Belum Disetujui']);

        $this->actingAs($this->pp)->get('/batching')->assertOk()
            ->assertSee('Siap Satu')->assertDontSee('Sudah Punya Batch')->assertDontSee('Belum Disetujui');
    }

    public function test_buat_batch_meneruskan_request_ke_ppk(): void
    {
        $a = $this->request(StatusPengajuan::DisetujuiPengadaan, 6500000);
        $b = $this->request(StatusPengajuan::DisetujuiPengadaan, 6000000);
        $c = $this->request(StatusPengajuan::DisetujuiPengadaan, 777);   // tidak dipilih

        $res = $this->actingAs($this->pp)->post('/batching', ['periode' => 'bulanan', 'ids' => [$a->id, $b->id]]);

        $batch = BatchPengadaan::firstOrFail();
        $res->assertRedirect("/batch/{$batch->id}");

        $this->assertMatchesRegularExpression('/^BTC-\d{6}-\d{4}$/', $batch->kode_batch);
        $this->assertSame(StatusBatch::PendingPpk, $batch->status);
        $this->assertSame(PeriodeBatch::Bulanan, $batch->periode);
        $this->assertSame($this->pp->id, $batch->dibuat_oleh);
        $this->assertEquals(12500000, $batch->totalEstimasi());

        foreach ([$a, $b] as $p) {
            $p->refresh();
            $this->assertSame(StatusPengajuan::PendingPpk, $p->status);
            $this->assertSame($batch->id, $p->batch_id);
            $this->assertStringContainsString($batch->kode_batch, $p->riwayat->last()->catatan);
        }
        $this->assertSame(StatusPengajuan::DisetujuiPengadaan, $c->fresh()->status);
        $this->assertNull($c->fresh()->batch_id);
    }

    public function test_batching_validasi_dan_penolakan_request_tidak_layak(): void
    {
        $siap = $this->request(StatusPengajuan::DisetujuiPengadaan);
        $diajukan = $this->request(StatusPengajuan::Diajukan);
        $this->actingAs($this->pp);

        $this->post('/batching', ['periode' => 'bulanan'])->assertSessionHasErrors('ids');
        $this->post('/batching', ['periode' => 'tahunan', 'ids' => [$siap->id]])->assertSessionHasErrors('periode');

        // Satu id tidak layak -> seluruh batch ditolak, tidak ada yang berubah.
        $this->post('/batching', ['periode' => 'bulanan', 'ids' => [$siap->id, $diajukan->id]])->assertSessionHasErrors('status');
        $this->post('/batching', ['periode' => 'bulanan', 'ids' => [$siap->id, 99999]])->assertSessionHasErrors('status');

        $this->assertDatabaseCount('batch_pengadaan', 0);
        $this->assertSame(StatusPengajuan::DisetujuiPengadaan, $siap->fresh()->status);
    }

    public function test_request_tidak_bisa_masuk_dua_batch(): void
    {
        $p = $this->request(StatusPengajuan::DisetujuiPengadaan);
        $service = app(BatchService::class);

        $service->buat($this->pp, PeriodeBatch::Mingguan, [$p->id]);

        $this->expectException(\App\Exceptions\StatusTidakValidException::class);
        $service->buat($this->pp, PeriodeBatch::Mingguan, [$p->id]);
    }

    // ---------- Batch dikembalikan PPK ----------

    public function test_halaman_batch_dikembalikan_dan_detail(): void
    {
        $batch = $this->batchDikembalikan();

        $this->actingAs($this->pp)->get('/batch/dikembalikan')->assertOk()->assertSee($batch->kode_batch)->assertSee('REQ-A spesifikasi belum jelas.');
        $this->get("/batch/{$batch->id}")->assertOk()
            ->assertSee('Dikembalikan oleh PPK')->assertSee('Keluarkan')->assertSee('Kirim Ulang ke PPK')->assertSee('Batalkan Batch');
    }

    public function test_keluarkan_request_mengembalikannya_ke_pemohon(): void
    {
        $batch = $this->batchDikembalikan();
        $p = $batch->pengajuan()->first();

        $this->actingAs($this->pp)->post("/batch/{$batch->id}/keluarkan/{$p->id}", ['catatan' => ''])->assertSessionHasErrors('catatan');
        $this->post("/batch/{$batch->id}/keluarkan/{$p->id}", ['catatan' => 'Spesifikasi belum jelas'])->assertRedirect("/batch/{$batch->id}");

        $p->refresh();
        $this->assertSame(StatusPengajuan::Direvisi, $p->status);
        $this->assertNull($p->batch_id);
        $this->assertSame('pengadaan', $p->dikembalikan_oleh);
        $this->assertSame(1, $batch->pengajuan()->count());
        $this->assertSame(StatusBatch::Direvisi, $batch->fresh()->status);   // masih ada isi
    }

    public function test_batch_otomatis_batal_bila_semua_request_dikeluarkan(): void
    {
        $batch = $this->batchDikembalikan();
        $this->actingAs($this->pp);

        foreach ($batch->pengajuan()->get() as $p) {
            $this->post("/batch/{$batch->id}/keluarkan/{$p->id}", ['catatan' => 'Perbaiki']);
        }

        $this->assertSame(StatusBatch::Dibatalkan, $batch->fresh()->status);
    }

    public function test_kirim_ulang_mengembalikan_batch_dan_isinya_ke_ppk(): void
    {
        $batch = $this->batchDikembalikan();

        $this->actingAs($this->pp)->post("/batch/{$batch->id}/kirim-ulang")->assertRedirect("/batch/{$batch->id}");

        $batch->refresh();
        $this->assertSame(StatusBatch::PendingPpk, $batch->status);
        $this->assertNull($batch->catatan_revisi);
        $this->assertSame(2, $batch->pengajuan()->where('status', StatusPengajuan::PendingPpk->value)->count());
    }

    public function test_batalkan_batch_mengembalikan_request_ke_antrean_batching(): void
    {
        $batch = $this->batchDikembalikan();

        $this->actingAs($this->pp)->post("/batch/{$batch->id}/batalkan")->assertRedirect('/batch/dikembalikan');

        $this->assertSame(StatusBatch::Dibatalkan, $batch->fresh()->status);
        $this->assertSame(2, PengajuanPengadaan::where('status', StatusPengajuan::DisetujuiPengadaan->value)->whereNull('batch_id')->count());
        $this->get('/batching')->assertOk()->assertSee('2 request');
    }

    public function test_batch_yang_tidak_perlu_revisi_tidak_bisa_diubah(): void
    {
        $batch = BatchPengadaan::create(['periode' => 'bulanan', 'status' => StatusBatch::PendingPpk, 'dibuat_oleh' => $this->pp->id]);
        $p = $this->request(StatusPengajuan::PendingPpk, 100, ['batch_id' => $batch->id]);
        $this->actingAs($this->pp);

        $this->post("/batch/{$batch->id}/kirim-ulang")->assertSessionHasErrors('status');
        $this->post("/batch/{$batch->id}/batalkan")->assertSessionHasErrors('status');
        $this->post("/batch/{$batch->id}/keluarkan/{$p->id}", ['catatan' => 'x'])->assertSessionHasErrors('status');
        $this->assertSame(StatusPengajuan::PendingPpk, $p->fresh()->status);

        $this->get("/batch/{$batch->id}")->assertOk()->assertDontSee('Batalkan Batch');
    }

    public function test_keluarkan_request_dari_batch_lain_ditolak(): void
    {
        $batch = $this->batchDikembalikan();
        $lain = $this->request(StatusPengajuan::DisetujuiPengadaan);

        $this->actingAs($this->pp)->post("/batch/{$batch->id}/keluarkan/{$lain->id}", ['catatan' => 'x'])->assertSessionHasErrors('status');
        $this->assertSame(StatusPengajuan::DisetujuiPengadaan, $lain->fresh()->status);
    }

    // ---------- Riwayat batch & dashboard ----------

    public function test_riwayat_batch_filter_dan_hitungan(): void
    {
        $this->batchDikembalikan();
        BatchPengadaan::create(['periode' => 'bulanan', 'status' => StatusBatch::Disetujui, 'dibuat_oleh' => $this->pp->id]);
        BatchPengadaan::create(['periode' => 'bulanan', 'status' => StatusBatch::PendingPpk, 'dibuat_oleh' => $this->pp->id]);

        $r = $this->actingAs($this->pp)->get('/batch')->assertOk();
        $this->assertSame(3, $r->viewData('jumlah')['semua']);
        $this->assertSame(1, $r->viewData('jumlah')['direvisi']);

        $this->assertCount(1, $this->get('/batch?status=disetujui')->viewData('batch'));
        $this->get('/batch?q=siti')->assertSee('Siti Rahma');
    }

    public function test_dashboard_pejabat_pengadaan_menghitung_antrean(): void
    {
        $this->request(StatusPengajuan::Diajukan, 100, ['nama_barang' => 'Antre Tinjau']);
        $this->request(StatusPengajuan::DisetujuiPengadaan);
        $batch = $this->batchDikembalikan();   // + 2 request siap batching (sudah di batch -> tidak dihitung)
        BatchPengadaan::create(['periode' => 'bulanan', 'status' => StatusBatch::PendingPpk, 'dibuat_oleh' => $this->pp->id]);

        $r = $this->actingAs($this->pp)->get('/dashboard')->assertOk()
            ->assertSee('Dashboard Pejabat Pengadaan')->assertSee('Antre Tinjau')
            ->assertSee("Batch {$batch->kode_batch} dikembalikan oleh PPK");

        $this->assertSame(1, $r->viewData('statistik')['perluDitinjau']);
        $this->assertSame(1, $r->viewData('statistik')['siapBatching']);
        $this->assertSame(1, $r->viewData('statistik')['batchDikembalikan']);
        $this->assertSame(1, $r->viewData('statistik')['batchMenungguPpk']);
    }
}
