<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventaris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->nullable()->constrained('pengajuan_pengadaan')->nullOnDelete();
            $table->string('nama_barang');
            $table->enum('kategori', ['aset', 'habis_pakai']);
            $table->unsignedInteger('total_stok')->default(0);
            $table->string('satuan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventaris');
    }
};
