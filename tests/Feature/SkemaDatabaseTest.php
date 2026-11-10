<?php

namespace Tests\Feature;

use App\Enums\Kategori;
use App\Enums\StatusPengajuan;
use App\Models\AsetInventaris;
use App\Models\BatchPengadaan;
use App\Models\Inventaris;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkemaDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengajuan(array $override = []): PengajuanPengadaan
    {
        return PengajuanPengadaan::create($override + [
            'pemohon_id' => User::factory()->create()->id,
            'nama_barang' => 'Laptop',
            'kategori' => Kategori::Aset,
            'kuantitas' => 2,
            'harga_estimasi' => 10000000,
            'total_estimasi_biaya' => 20000000,
        ]);
    }

    public function test_pengajuan_default_draft_tanpa_batch_dan_kode_otomatis(): void
    {
        $p = $this->buatPengajuan()->fresh();

        $this->assertSame(StatusPengajuan::Draft, $p->status);
        $this->assertNull($p->batch_id);
        $this->assertMatchesRegularExpression('/^REQ-\d{4}-\d{5}$/', $p->kode_pengajuan);
    }

    public function test_batch_memiliki_kode_dan_relasi_pengajuan(): void
    {
        $batch = BatchPengadaan::create([
            'periode' => 'bulanan',
            'dibuat_oleh' => User::factory()->create()->id,
        ])->fresh();

        $p = $this->buatPengajuan(['batch_id' => $batch->id]);

        $this->assertMatchesRegularExpression('/^BTC-\d{6}-\d{4}$/', $batch->kode_batch);
        $this->assertTrue($batch->pengajuan->contains($p));
    }

    public function test_satu_pengajuan_aset_menghasilkan_satu_baris_per_unit(): void
    {
        $p = $this->buatPengajuan();
        $inv = Inventaris::create([
            'pengajuan_id' => $p->id, 'nama_barang' => 'Laptop',
            'kategori' => Kategori::Aset, 'total_stok' => 2, 'satuan' => 'unit',
        ]);

        foreach (['AST-2026-0001', 'AST-2026-0002'] as $sku) {
            AsetInventaris::create(['inventaris_id' => $inv->id, 'pengajuan_id' => $p->id, 'sku_barcode' => $sku]);
        }

        $this->assertCount(2, $p->asetInventaris);
        $this->expectException(\Illuminate\Database\QueryException::class); // SKU harus unik
        AsetInventaris::create(['inventaris_id' => $inv->id, 'pengajuan_id' => $p->id, 'sku_barcode' => 'AST-2026-0001']);
    }
}
