<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Pemakaian di Blade: @rupiah($pengajuan->total_estimasi_biaya)  ->  Rp 1.500.000
        Blade::directive('rupiah', fn ($expression) => "<?php echo 'Rp ' . number_format((float) ({$expression}), 0, ',', '.'); ?>");
    }
}
