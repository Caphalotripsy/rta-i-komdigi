<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_inventaris_id')->constrained('aset_inventaris')->cascadeOnDelete();
            $table->foreignId('dilakukan_oleh')->constrained('users')->restrictOnDelete();
            $table->string('lokasi_lama')->nullable();
            $table->string('lokasi_baru')->nullable();
            $table->string('penanggung_jawab_lama')->nullable();
            $table->string('penanggung_jawab_baru')->nullable();
            $table->enum('kondisi_lama', ['baik', 'rusak', 'hilang'])->nullable();
            $table->enum('kondisi_baru', ['baik', 'rusak', 'hilang'])->nullable();
            $table->text('catatan')->nullable(); // "Registrasi awal" untuk entri pertama
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_aset');
    }
};
