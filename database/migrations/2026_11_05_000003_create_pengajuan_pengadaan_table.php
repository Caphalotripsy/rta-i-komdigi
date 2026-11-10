<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_pengadaan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pengajuan')->nullable()->unique(); // diisi otomatis oleh model setelah dibuat
            $table->foreignId('pemohon_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batch_pengadaan')->nullOnDelete();

            // Satu pengajuan = satu item (dengan kuantitas)
            $table->string('nama_barang');
            $table->enum('kategori', ['aset', 'habis_pakai']);
            $table->text('spesifikasi')->nullable();
            $table->unsignedInteger('kuantitas');
            $table->decimal('harga_estimasi', 15, 2); // harga satuan
            $table->decimal('total_estimasi_biaya', 15, 2);

            $table->enum('status', [
                'draft', 'diajukan', 'disetujui_pengadaan', 'pending_ppk', 'direvisi',
                'disetujui', 'dana_cair', 'menunggu_pengembalian', 'cek_fisik', 'selesai',
            ])->default('draft');
            $table->enum('dikembalikan_oleh', ['pengadaan', 'keuangan'])->nullable();

            // Pencairan & realisasi
            $table->decimal('dana_dicairkan', 15, 2)->nullable();
            $table->unsignedInteger('kuantitas_terbeli')->nullable();
            $table->decimal('harga_aktual', 15, 2)->nullable(); // harga satuan
            $table->decimal('total_biaya_aktual', 15, 2)->nullable();
            $table->decimal('sisa_dana', 15, 2)->nullable();
            $table->boolean('status_pengembalian_dana')->default(false);

            // Data nota fisik (teks saja, tanpa upload)
            $table->string('nama_toko')->nullable();
            $table->string('nomor_nota')->nullable();
            $table->date('tanggal_nota')->nullable();

            $table->timestamp('diproses_inventaris_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_pengadaan');
    }
};
