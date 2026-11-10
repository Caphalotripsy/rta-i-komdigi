<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_pengadaan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_batch')->nullable()->unique(); // diisi otomatis oleh model setelah dibuat
            $table->enum('periode', ['mingguan', 'bulanan', 'triwulan']);
            $table->enum('status', ['pending_ppk', 'disetujui', 'direvisi', 'dibatalkan'])->default('pending_ppk');
            $table->text('catatan_revisi')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_pengadaan');
    }
};
