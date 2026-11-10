<?php

namespace Database\Factories;

use App\Enums\Kategori;
use App\Enums\StatusPengajuan;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PengajuanPengadaan> */
class PengajuanPengadaanFactory extends Factory
{
    public function definition(): array
    {
        $kuantitas = fake()->numberBetween(1, 5);
        $harga = fake()->numberBetween(50, 5000) * 1000;

        return [
            'pemohon_id' => User::factory(),
            'nama_barang' => fake()->words(2, true),
            'kategori' => fake()->randomElement(Kategori::cases()),
            'spesifikasi' => fake()->sentence(),
            'kuantitas' => $kuantitas,
            'harga_estimasi' => $harga,
            'total_estimasi_biaya' => $kuantitas * $harga,
            'status' => StatusPengajuan::Draft,
        ];
    }

    public function status(StatusPengajuan $status): static
    {
        return $this->state(['status' => $status]);
    }

    /** Dikembalikan oleh tahap tertentu ('pengadaan' atau 'keuangan'), lengkap dengan catatan di riwayat. */
    public function dikembalikanOleh(string $tahap, string $catatan = 'Mohon diperbaiki'): static
    {
        return $this->state(['status' => StatusPengajuan::Direvisi, 'dikembalikan_oleh' => $tahap])
            ->afterCreating(function (PengajuanPengadaan $p) use ($catatan) {
                $p->riwayat()->create([
                    'aktor_id' => User::factory()->create()->id,
                    'status_dari' => StatusPengajuan::Diajukan->value,
                    'status_ke' => StatusPengajuan::Direvisi->value,
                    'catatan' => $catatan,
                ]);
            });
    }
}
