<?php

namespace App\Http\Controllers;

use App\Enums\Kategori;
use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Models\PengajuanPengadaan;
use App\Services\PengajuanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Pejabat Pengadaan, UC-06: tinjau request (Approval Tahap 1). */
class ApprovalController extends Controller
{
    public function __construct(private PengajuanService $service)
    {
    }

    public function index(Request $request): View
    {
        $dasar = PengajuanPengadaan::where('status', StatusPengajuan::Diajukan->value);

        $perKategori = (clone $dasar)->selectRaw('kategori, count(*) as jumlah')->groupBy('kategori')->pluck('jumlah', 'kategori');
        $kategori = Kategori::tryFrom((string) $request->query('kategori'));
        $q = trim((string) $request->query('q'));

        $pengajuan = (clone $dasar)
            ->with('pemohon')
            ->when($kategori, fn ($w, $k) => $w->where('kategori', $k->value))
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $w->where(function ($x) use ($like) {
                    $x->where('nama_barang', 'like', $like)
                        ->orWhere('kode_pengajuan', 'like', $like)
                        ->orWhereHas('pemohon', fn ($u) => $u->where('name', 'like', $like));
                });
            })
            ->oldest('id')
            ->paginate(10)
            ->withQueryString();

        return view('approval.index', [
            'pengajuan' => $pengajuan,
            'filter' => ['kategori' => $kategori?->value, 'q' => $q],
            'jumlah' => [
                'semua' => (int) $perKategori->sum(),
                'aset' => (int) ($perKategori[Kategori::Aset->value] ?? 0),
                'habis_pakai' => (int) ($perKategori[Kategori::HabisPakai->value] ?? 0),
            ],
        ]);
    }

    public function show(PengajuanPengadaan $pengajuan): View
    {
        Gate::authorize('view', $pengajuan);

        $pengajuan->load('pemohon', 'riwayat.aktor', 'batch');

        return view('approval.show', ['pengajuan' => $pengajuan]);
    }

    public function setujui(Request $request, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        Gate::authorize('view', $pengajuan);

        try {
            $this->service->setujuiPengadaan($pengajuan, $request->user());
        } catch (StatusTidakValidException $e) {
            return redirect()->route('approval.index')->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('approval.index')
            ->with('status', "{$pengajuan->kode_pengajuan} disetujui dan masuk daftar Siap Batching.");
    }

    public function kembalikan(Request $request, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        Gate::authorize('view', $pengajuan);

        $data = $request->validate(
            ['catatan' => ['required', 'string', 'max:1000']],
            ['catatan.required' => 'Catatan revisi wajib diisi bila request dikembalikan.', 'catatan.max' => 'Catatan maksimal 1000 karakter.'],
        );

        try {
            $this->service->kembalikanPengadaan($pengajuan, $request->user(), $data['catatan']);
        } catch (StatusTidakValidException $e) {
            return redirect()->route('approval.index')->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('approval.index')
            ->with('status', "{$pengajuan->kode_pengajuan} dikembalikan ke pemohon untuk direvisi.");
    }
}
