<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_pengadaan', function (Blueprint $table) {
            $table->date('tanggal_pencairan')->nullable()->after('dana_dicairkan');
            $table->string('metode_pencairan', 20)->nullable()->after('tanggal_pencairan');
            $table->string('referensi_pencairan', 100)->nullable()->after('metode_pencairan');
            $table->date('tanggal_pengembalian')->nullable()->after('status_pengembalian_dana');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_pengadaan', function (Blueprint $table) {
            $table->dropColumn(['tanggal_pencairan', 'metode_pencairan', 'referensi_pencairan', 'tanggal_pengembalian']);
        });
    }
};
