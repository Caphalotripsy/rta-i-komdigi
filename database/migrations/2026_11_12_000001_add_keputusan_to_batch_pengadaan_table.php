<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batch_pengadaan', function (Blueprint $table) {
            // Keputusan PPK terakhir atas batch ini. Dikosongkan saat batch dikirim ulang.
            $table->foreignId('diputuskan_oleh')->nullable()->after('catatan_revisi')->constrained('users')->nullOnDelete();
            $table->timestamp('diputuskan_pada')->nullable()->after('diputuskan_oleh');
        });
    }

    public function down(): void
    {
        Schema::table('batch_pengadaan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diputuskan_oleh');
            $table->dropColumn('diputuskan_pada');
        });
    }
};
