<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StatusPengajuan;
use App\Models\PengajuanPengadaan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->hasRole(Role::KepalaDivisi)) {
            return view('dashboard.kepala-divisi', $this->dataKepalaDivisi($user));
        }

        // Dashboard peran lain dibuat bersama modulnya.
        return view('dashboard');
    }

    /** @return array<string, mixed> */
    private function dataKepalaDivisi(User $user): array
    {
        $dasar = PengajuanPengadaan::where('pemohon_id', $user->id);

        $perStatus = (clone $dasar)
            ->selectRaw('status, count(*) as jumlah, sum(total_estimasi_biaya) as nilai')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->status->value => ['jumlah' => (int) $r->jumlah, 'nilai' => (float) $r->nilai]]);

        $jumlah = fn (StatusPengajuan ...$status) => collect($status)->sum(fn ($s) => $perStatus[$s->value]['jumlah'] ?? 0);

        $semuaStatus = StatusPengajuan::cases();
        $berjalan = array_filter($semuaStatus, fn ($s) => ! in_array($s, [StatusPengajuan::Draft, StatusPengajuan::Selesai], true));
        $bukanDraft = array_filter($semuaStatus, fn ($s) => $s !== StatusPengajuan::Draft);

        $revisi = (clone $dasar)->where('status', StatusPengajuan::Direvisi->value)->latest('id')->first();

        return [
            'user' => $user,
            'statistik' => [
                'berjalan' => $jumlah(...$berjalan),
                'perluRevisi' => $jumlah(StatusPengajuan::Direvisi),
                'laporHarga' => $jumlah(StatusPengajuan::DanaCair),
                'selesai' => $jumlah(StatusPengajuan::Selesai),
                'totalEstimasi' => collect($bukanDraft)->sum(fn ($s) => $perStatus[$s->value]['nilai'] ?? 0),
            ],
            'chip' => [
                'semua' => collect($semuaStatus)->sum(fn ($s) => $perStatus[$s->value]['jumlah'] ?? 0),
                'draft' => $jumlah(StatusPengajuan::Draft),
                'diajukan' => $jumlah(StatusPengajuan::Diajukan),
                'disetujui_pengadaan' => $jumlah(StatusPengajuan::DisetujuiPengadaan),
                'pending_ppk' => $jumlah(StatusPengajuan::PendingPpk),
                'direvisi' => $jumlah(StatusPengajuan::Direvisi),
                'selesai' => $jumlah(StatusPengajuan::Selesai),
            ],
            'revisi' => $revisi,
            'catatanRevisi' => $revisi?->revisiTerakhir(),
            'terbaru' => (clone $dasar)->latest('id')->limit(5)->get(),
        ];
    }
}
