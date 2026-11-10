<?php

namespace App\Http\Controllers;

use App\Enums\Kategori;
use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Http\Requests\PengajuanRequest;
use App\Models\PengajuanPengadaan;
use App\Services\PengajuanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/** Modul Kepala Divisi: UC-01 (buat/ajukan), UC-02 (revisi), UC-03 (tracking). */
class PengajuanController extends Controller
{
    public function __construct(private PengajuanService $service)
    {
    }

    public function index(Request $request): View
    {
        $status = StatusPengajuan::tryFrom((string) $request->query('status'));

        $pengajuan = PengajuanPengadaan::where('pemohon_id', $request->user()->id)
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('pengajuan.index', [
            'pengajuan' => $pengajuan,
            'status' => $status,
            'semuaStatus' => StatusPengajuan::cases(),
        ]);
    }

    public function create(): View
    {
        return view('pengajuan.create', ['kategori' => Kategori::cases()]);
    }

    public function store(PengajuanRequest $request): RedirectResponse
    {
        $ajukan = $request->input('aksi') === 'ajukan';
        $p = $this->service->buat($request->user(), $request->validated(), $ajukan);

        return redirect()->route('pengajuan.show', $p)
            ->with('status', $ajukan ? 'Pengajuan berhasil dikirim.' : 'Draft berhasil disimpan.');
    }

    public function show(PengajuanPengadaan $pengajuan): View
    {
        Gate::authorize('view', $pengajuan);

        $pengajuan->load('riwayat.aktor', 'batch');

        return view('pengajuan.show', [
            'pengajuan' => $pengajuan,
            'catatanRevisi' => $pengajuan->status === StatusPengajuan::Direvisi
                ? $pengajuan->catatanRevisiTerakhir()
                : null,
        ]);
    }

    public function edit(PengajuanPengadaan $pengajuan): View
    {
        Gate::authorize('update', $pengajuan);

        return view('pengajuan.edit', [
            'pengajuan' => $pengajuan,
            'kategori' => Kategori::cases(),
            'catatanRevisi' => $pengajuan->status === StatusPengajuan::Direvisi
                ? $pengajuan->catatanRevisiTerakhir()
                : null,
        ]);
    }

    public function update(PengajuanRequest $request, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        Gate::authorize('update', $pengajuan);

        $ajukan = $request->input('aksi') === 'ajukan';

        try {
            $this->service->perbarui($pengajuan, $request->validated(), $request->user(), $ajukan);
        } catch (StatusTidakValidException $e) {
            return back()->withInput()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('pengajuan.show', $pengajuan)
            ->with('status', $ajukan ? 'Pengajuan berhasil dikirim.' : 'Perubahan berhasil disimpan.');
    }

    public function ajukan(Request $request, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        Gate::authorize('update', $pengajuan);

        try {
            $this->service->ajukan($pengajuan, $request->user());
        } catch (StatusTidakValidException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('pengajuan.show', $pengajuan)->with('status', 'Pengajuan berhasil dikirim.');
    }

    public function destroy(PengajuanPengadaan $pengajuan): RedirectResponse
    {
        Gate::authorize('delete', $pengajuan);

        $pengajuan->delete();

        return redirect()->route('pengajuan.index')->with('status', 'Draft berhasil dihapus.');
    }
}
