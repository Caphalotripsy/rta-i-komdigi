<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset_inventaris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventaris_id')->constrained('inventaris')->cascadeOnDelete();
            $table->foreignId('pengajuan_id')->constrained('pengajuan_pengadaan')->restrictOnDelete();
            $table->string('sku_barcode')->unique(); // satu baris = satu unit fisik
            $table->enum('kondisi', ['baik', 'rusak', 'hilang'])->default('baik');
            $table->string('nama_penanggung_jawab')->nullable();
            $table->string('lokasi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset_inventaris');
    }
};
