<?php

namespace App\Http\Controllers;

use App\Enums\Kategori;
use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Enums\Satuan;
use App\Http\Requests\LaporHargaRequest;
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
        $dasar = PengajuanPengadaan::where('pemohon_id', $request->user()->id);

        $perStatus = (clone $dasar)
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $menungguTindakan = [StatusPengajuan::Direvisi->value, StatusPengajuan::DanaCair->value];
        $status = (string) $request->query('status');
        $kategori = Kategori::tryFrom((string) $request->query('kategori'));
        $q = trim((string) $request->query('q'));

        $pengajuan = (clone $dasar)
            ->when($status === 'menunggu_tindakan', fn ($w) => $w->whereIn('status', $menungguTindakan))
            ->when(StatusPengajuan::tryFrom($status), fn ($w, $s) => $w->where('status', $s->value))
            ->when($kategori, fn ($w, $k) => $w->where('kategori', $k->value))
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $w->where(fn ($x) => $x->where('nama_barang', 'like', $like)->orWhere('kode_pengajuan', 'like', $like));
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('pengajuan.index', [
            'pengajuan' => $pengajuan,
            'filter' => ['status' => $status, 'kategori' => $kategori?->value, 'q' => $q],
            'semuaStatus' => StatusPengajuan::cases(),
            'semuaKategori' => Kategori::cases(),
            'jumlah' => [
                'semua' => (int) $perStatus->sum(),
                'draft' => (int) ($perStatus[StatusPengajuan::Draft->value] ?? 0),
                'direvisi' => (int) ($perStatus[StatusPengajuan::Direvisi->value] ?? 0),
                'menunggu_tindakan' => (int) collect($menungguTindakan)->sum(fn ($v) => $perStatus[$v] ?? 0),
                'selesai' => (int) ($perStatus[StatusPengajuan::Selesai->value] ?? 0),
            ],
        ]);
    }

    public function create(): View
    {
        return view('pengajuan.create', ['kategori' => Kategori::cases(), 'satuan' => Satuan::cases()]);
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

        $pengajuan->load('riwayat.aktor', 'batch', 'pemohon');

        return view('pengajuan.show', [
            'pengajuan' => $pengajuan,
            'revisi' => $pengajuan->status === StatusPengajuan::Direvisi ? $pengajuan->revisiTerakhir() : null,
        ]);
    }

    public function edit(PengajuanPengadaan $pengajuan): View
    {
        Gate::authorize('update', $pengajuan);

        return view('pengajuan.edit', [
            'pengajuan' => $pengajuan,
            'kategori' => Kategori::cases(),
            'satuan' => Satuan::cases(),
            'revisi' => $pengajuan->status === StatusPengajuan::Direvisi ? $pengajuan->revisiTerakhir() : null,
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

    public function lapor(PengajuanPengadaan $pengajuan): View
    {
        Gate::authorize('lapor', $pengajuan);

        return view('pengajuan.lapor', [
            'pengajuan' => $pengajuan,
            'revisi' => $pengajuan->status === StatusPengajuan::Direvisi ? $pengajuan->revisiTerakhir() : null,
            'waktuPencairan' => $pengajuan->waktuPencairan(),
        ]);
    }

    public function simpanLaporan(LaporHargaRequest $request, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        try {
            $this->service->laporHarga($pengajuan, $request->validated(), $request->user());
        } catch (StatusTidakValidException $e) {
            return back()->withInput()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('pengajuan.show', $pengajuan)->with('status', 'Laporan harga aktual berhasil dikirim.');
    }

    public function destroy(PengajuanPengadaan $pengajuan): RedirectResponse
    {
        Gate::authorize('delete', $pengajuan);

        $pengajuan->delete();

        return redirect()->route('pengajuan.index')->with('status', 'Draft berhasil dihapus.');
    }
}
