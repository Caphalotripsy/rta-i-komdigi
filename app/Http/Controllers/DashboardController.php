<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\StatusBatch;
use App\Enums\StatusPengajuan;
use App\Models\AsetInventaris;
use App\Models\BatchPengadaan;
use App\Models\Inventaris;
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

        if ($user->hasRole(Role::PejabatPengadaan)) {
            return view('dashboard.pejabat-pengadaan', $this->dataPejabatPengadaan());
        }

        if ($user->hasRole(Role::Ppk)) {
            return view('dashboard.ppk', $this->dataPpk());
        }

        if ($user->hasRole(Role::Bendahara)) {
            return view('dashboard.bendahara', $this->dataBendahara());
        }

        if ($user->hasRole(Role::Keuangan)) {
            return view('dashboard.keuangan', $this->dataKeuangan());
        }

        if ($user->hasRole(Role::Inventaris)) {
            return view('dashboard.inventaris', $this->dataInventaris());
        }

        // Dashboard peran lain dibuat bersama modulnya.
        return view('dashboard');
    }

    /** @return array<string, mixed> */
    private function dataInventaris(): array
    {
        $antrean = fn (string $kategori) => PengajuanPengadaan::with('pemohon')
            ->where('status', StatusPengajuan::Selesai->value)->where('kategori', $kategori)->whereNull('diproses_inventaris_at')->oldest('updated_at');

        return [
            'statistik' => [
                'antreanAset' => $antrean('aset')->count(),
                'antreanStok' => $antrean('habis_pakai')->count(),
                'asetTerdaftar' => AsetInventaris::count(),
                'jenisStok' => Inventaris::where('kategori', 'habis_pakai')->get()->groupBy(fn ($i) => \Illuminate\Support\Str::lower(trim($i->nama_barang)).'|'.$i->satuan)->count(),
            ],
            'antreanAset' => $antrean('aset')->limit(5)->get(),
            'antreanStok' => $antrean('habis_pakai')->limit(5)->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function dataKeuangan(): array
    {
        $antrean = PengajuanPengadaan::where('status', StatusPengajuan::CekFisik->value);
        $selesai = PengajuanPengadaan::where('status', StatusPengajuan::Selesai->value);

        return [
            'statistik' => [
                'menungguVerifikasi' => (clone $antrean)->count(),
                'dikembalikan' => PengajuanPengadaan::where('status', StatusPengajuan::Direvisi->value)->where('dikembalikan_oleh', 'keuangan')->count(),
                'selesaiBulanIni' => (clone $selesai)->where('updated_at', '>=', now()->startOfMonth())->count(),
                'totalTerverifikasi' => (float) (clone $selesai)->sum('total_biaya_aktual'),
            ],
            'antrean' => (clone $antrean)->with('pemohon')->oldest('updated_at')->limit(5)->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function dataBendahara(): array
    {
        $pencairan = PengajuanPengadaan::where('status', StatusPengajuan::Disetujui->value);
        $pengembalian = PengajuanPengadaan::where('status', StatusPengajuan::MenungguPengembalian->value);

        return [
            'statistik' => [
                'menungguPencairan' => (clone $pencairan)->count(),
                'menungguPengembalian' => (clone $pengembalian)->count(),
                'danaMenunggu' => (float) (clone $pencairan)->sum('total_estimasi_biaya'),
                'sisaBelumKembali' => (float) (clone $pengembalian)->sum('sisa_dana'),
            ],
            'antreanPencairan' => (clone $pencairan)->with('pemohon', 'batch')->oldest('updated_at')->limit(5)->get(),
            'antreanPengembalian' => (clone $pengembalian)->with('pemohon')->oldest('updated_at')->limit(5)->get(),
        ];
    }

    /** @return array<string, mixed> */
    private function dataPpk(): array
    {
        $menunggu = BatchPengadaan::with('pembuat')->withCount('pengajuan')->withSum('pengajuan', 'total_estimasi_biaya')
            ->where('status', StatusBatch::PendingPpk->value)->oldest('updated_at')->get();

        return [
            'statistik' => [
                'batchMenunggu' => $menunggu->count(),
                'requestMenunggu' => PengajuanPengadaan::where('status', StatusPengajuan::PendingPpk->value)->count(),
                'batchDisetujui' => BatchPengadaan::where('status', StatusBatch::Disetujui->value)->count(),
                'totalMenunggu' => (float) PengajuanPengadaan::where('status', StatusPengajuan::PendingPpk->value)->sum('total_estimasi_biaya'),
            ],
            'batchMenunggu' => $menunggu->take(5),
        ];
    }

    /** @return array<string, mixed> */
    private function dataPejabatPengadaan(): array
    {
        $batchRevisi = BatchPengadaan::with('pembuat', 'pemutus')->withCount('pengajuan')->withSum('pengajuan', 'total_estimasi_biaya')
            ->where('status', StatusBatch::Direvisi->value)->latest('id')->get();

        return [
            'statistik' => [
                'perluDitinjau' => PengajuanPengadaan::where('status', StatusPengajuan::Diajukan->value)->count(),
                'siapBatching' => PengajuanPengadaan::where('status', StatusPengajuan::DisetujuiPengadaan->value)->whereNull('batch_id')->count(),
                'batchDikembalikan' => $batchRevisi->count(),
                'batchMenungguPpk' => BatchPengadaan::where('status', StatusBatch::PendingPpk->value)->count(),
            ],
            'menungguTinjauan' => PengajuanPengadaan::with('pemohon')->where('status', StatusPengajuan::Diajukan->value)->oldest('id')->limit(5)->get(),
            'batchRevisi' => $batchRevisi->take(5),
            'batchBanner' => $batchRevisi->first(),
        ];
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
