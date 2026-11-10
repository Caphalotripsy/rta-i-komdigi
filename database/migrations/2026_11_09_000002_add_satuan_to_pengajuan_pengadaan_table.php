<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_pengadaan', function (Blueprint $table) {
            // String (bukan enum DB) agar daftar satuan bisa ditambah tanpa migrasi; divalidasi lewat App\Enums\Satuan.
            $table->string('satuan', 20)->default('unit')->after('kuantitas');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_pengadaan', function (Blueprint $table) {
            $table->dropColumn('satuan');
        });
    }
};
