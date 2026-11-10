<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEED_PASSWORD', 'rtai-demo-2026'); // di-hash otomatis oleh cast pada model User

        $akun = [
            ['Kepala Divisi Pelatihan', 'kadiv.pelatihan', Role::KepalaDivisi, 'Divisi Pelatihan'],
            ['Kepala Divisi Umum', 'kadiv.umum', Role::KepalaDivisi, 'Divisi Umum'],
            ['Pejabat Pengadaan', 'pengadaan', Role::PejabatPengadaan, 'Pengadaan'],
            ['PPK', 'ppk', Role::Ppk, 'PPK'],
            ['Bendahara', 'bendahara', Role::Bendahara, 'Keuangan'],
            ['Divisi Keuangan', 'keuangan', Role::Keuangan, 'Divisi Keuangan'],
            ['Pengelola Inventaris', 'inventaris', Role::Inventaris, 'Inventaris'],
        ];

        foreach ($akun as [$name, $username, $role, $divisi]) {
            User::updateOrCreate(
                ['email' => $username.'@rtai.test'],
                ['name' => $name, 'username' => $username, 'password' => $password, 'role' => $role, 'divisi' => $divisi],
            );
        }
    }
}
