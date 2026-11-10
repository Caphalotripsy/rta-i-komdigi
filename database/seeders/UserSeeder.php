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
            ['Kepala Divisi Pelatihan', 'kadiv.pelatihan@rtai.test', Role::KepalaDivisi, 'Divisi Pelatihan'],
            ['Kepala Divisi Umum', 'kadiv.umum@rtai.test', Role::KepalaDivisi, 'Divisi Umum'],
            ['Pejabat Pengadaan', 'pengadaan@rtai.test', Role::PejabatPengadaan, 'Pengadaan'],
            ['PPK', 'ppk@rtai.test', Role::Ppk, 'PPK'],
            ['Bendahara', 'bendahara@rtai.test', Role::Bendahara, 'Keuangan'],
            ['Divisi Keuangan', 'keuangan@rtai.test', Role::Keuangan, 'Divisi Keuangan'],
            ['Pengelola Inventaris', 'inventaris@rtai.test', Role::Inventaris, 'Inventaris'],
        ];

        foreach ($akun as [$name, $email, $role, $divisi]) {
            User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => $password, 'role' => $role, 'divisi' => $divisi],
            );
        }
    }
}
