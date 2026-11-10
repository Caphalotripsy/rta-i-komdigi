<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan_pengadaan')->cascadeOnDelete();
            $table->foreignId('aktor_id')->constrained('users')->restrictOnDelete();
            $table->string('status_dari')->nullable();
            $table->string('status_ke');
            $table->enum('jenis_masalah', ['nominal', 'kuantitas', 'spesifikasi', 'dokumen'])->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_pengajuan');
    }
};
