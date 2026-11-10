<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/login')->assertOk();
    }

    public function test_login_berhasil_dan_menampilkan_role(): void
    {
        $user = User::factory()->create(['role' => Role::Ppk]);

        $this->post('/login', ['username' => $user->username, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->get('/dashboard')->assertOk()->assertSee('PPK');
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        $user = User::factory()->create();

        $this->from('/login')
            ->post('/login', ['username' => $user->username, 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_username_tidak_peka_huruf_besar_kecil(): void
    {
        $user = User::factory()->create(['username' => 'budi.santoso']);

        $this->post('/login', ['username' => '  Budi.Santoso ', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_dibatasi_setelah_lima_kali_salah(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $_) {
            $this->post('/login', ['username' => $user->username, 'password' => 'salah']);
        }

        $this->from('/login')
            ->post('/login', ['username' => $user->username, 'password' => 'password'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
        $this->get('/login')->assertSee('Terlalu banyak percobaan');
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_middleware_role_membatasi_akses(): void
    {
        Route::middleware(['web', 'auth', 'role:ppk,bendahara'])->get('/_uji/keuangan', fn () => 'ok');

        $this->actingAs(User::factory()->create(['role' => Role::KepalaDivisi]))
            ->get('/_uji/keuangan')->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => Role::Bendahara]))
            ->get('/_uji/keuangan')->assertOk();
    }

    public function test_seeder_membuat_enam_role(): void
    {
        $this->seed(UserSeeder::class);

        foreach (Role::cases() as $role) {
            $this->assertDatabaseHas('users', ['role' => $role->value]);
        }

        $this->assertDatabaseHas('users', ['username' => 'ppk', 'role' => Role::Ppk->value]);
    }
}
