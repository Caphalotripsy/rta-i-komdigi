<?php

namespace App\Http\Controllers;

use App\Enums\StatusBatch;
use App\Exceptions\StatusTidakValidException;
use App\Models\BatchPengadaan;
use App\Models\PengajuanPengadaan;
use App\Services\BatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Pejabat Pengadaan: riwayat batch, batch yang dikembalikan PPK, dan penanganannya. */
class BatchController extends Controller
{
    public function __construct(private BatchService $service)
    {
    }

    public function index(Request $request): View
    {
        $status = StatusBatch::tryFrom((string) $request->query('status'));
        $q = trim((string) $request->query('q'));

        $perStatus = BatchPengadaan::selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

        $batch = BatchPengadaan::with('pembuat')
            ->withCount('pengajuan')
            ->withSum('pengajuan', 'total_estimasi_biaya')
            ->when($status, fn ($w, $s) => $w->where('status', $s->value))
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $w->where(fn ($x) => $x->where('kode_batch', 'like', $like)->orWhereHas('pembuat', fn ($u) => $u->where('name', 'like', $like)));
            })
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('batch.index', [
            'batch' => $batch,
            'filter' => ['status' => $status?->value, 'q' => $q],
            'jumlah' => collect(StatusBatch::cases())->mapWithKeys(fn ($s) => [$s->value => (int) ($perStatus[$s->value] ?? 0)])
                ->put('semua', (int) $perStatus->sum()),
            'semuaStatus' => StatusBatch::cases(),
        ]);
    }

    public function dikembalikan(): View
    {
        $batch = BatchPengadaan::with('pembuat')
            ->withCount('pengajuan')
            ->withSum('pengajuan', 'total_estimasi_biaya')
            ->where('status', StatusBatch::Direvisi->value)
            ->latest('id')
            ->get();

        return view('batch.dikembalikan', ['batch' => $batch]);
    }

    public function show(BatchPengadaan $batch): View
    {
        $batch->load('pembuat', 'pemutus', 'pengajuan.pemohon')->loadCount('pengajuan')->loadSum('pengajuan', 'total_estimasi_biaya');

        return view('batch.show', ['batch' => $batch]);
    }

    public function kirimUlang(Request $request, BatchPengadaan $batch): RedirectResponse
    {
        return $this->jalankan($batch, fn () => $this->service->kirimUlang($batch, $request->user()),
            "Batch {$batch->kode_batch} dikirim ulang ke PPK.");
    }

    public function batalkan(Request $request, BatchPengadaan $batch): RedirectResponse
    {
        return $this->jalankan($batch, fn () => $this->service->batalkan($batch, $request->user()),
            "Batch {$batch->kode_batch} dibatalkan. Request di dalamnya kembali ke antrean Siap Batching.", true);
    }

    public function keluarkan(Request $request, BatchPengadaan $batch, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        $data = $request->validate(
            ['catatan' => ['required', 'string', 'max:1000']],
            ['catatan.required' => 'Catatan wajib diisi agar pemohon tahu apa yang harus diperbaiki.', 'catatan.max' => 'Catatan maksimal 1000 karakter.'],
        );

        return $this->jalankan($batch, fn () => $this->service->keluarkan($batch, $pengajuan, $request->user(), $data['catatan']),
            "{$pengajuan->kode_pengajuan} dikeluarkan dari batch dan dikembalikan ke pemohon.");
    }

    private function jalankan(BatchPengadaan $batch, \Closure $aksi, string $pesan, bool $kembaliKeDaftar = false): RedirectResponse
    {
        try {
            $aksi();
        } catch (StatusTidakValidException $e) {
            return redirect()->route('batch.show', $batch)->withErrors(['status' => $e->getMessage()]);
        }

        return $kembaliKeDaftar
            ? redirect()->route('batch.dikembalikan')->with('status', $pesan)
            : redirect()->route('batch.show', $batch)->with('status', $pesan);
    }
}
