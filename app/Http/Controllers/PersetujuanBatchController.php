<?php

namespace App\Http\Controllers;

use App\Enums\StatusBatch;
use App\Exceptions\StatusTidakValidException;
use App\Models\BatchPengadaan;
use App\Services\BatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** PPK: keputusan per batch (setujui atau kembalikan) dan riwayat keputusan. */
class PersetujuanBatchController extends Controller
{
    public function __construct(private BatchService $service)
    {
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $batch = BatchPengadaan::with('pembuat')
            ->withCount('pengajuan')
            ->withSum('pengajuan', 'total_estimasi_biaya')
            ->where('status', StatusBatch::PendingPpk->value)
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $w->where(fn ($x) => $x->where('kode_batch', 'like', $like)->orWhereHas('pembuat', fn ($u) => $u->where('name', 'like', $like)));
            })
            ->oldest('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('ppk.index', ['batch' => $batch, 'q' => $q]);
    }

    public function show(BatchPengadaan $batch): View
    {
        $batch->load('pembuat', 'pemutus', 'pengajuan.pemohon')->loadCount('pengajuan')->loadSum('pengajuan', 'total_estimasi_biaya');

        return view('ppk.show', ['batch' => $batch]);
    }

    public function setujui(Request $request, BatchPengadaan $batch): RedirectResponse
    {
        try {
            $this->service->setujui($batch, $request->user());
        } catch (StatusTidakValidException $e) {
            return redirect()->route('ppk.batch.show', $batch)->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('ppk.batch.index')->with('status', "Batch {$batch->kode_batch} disetujui.");
    }

    public function kembalikan(Request $request, BatchPengadaan $batch): RedirectResponse
    {
        $data = $request->validate(
            ['catatan' => ['required', 'string', 'max:1000']],
            ['catatan.required' => 'Catatan wajib diisi bila batch dikembalikan. Sebutkan kode request yang bermasalah.', 'catatan.max' => 'Catatan maksimal 1000 karakter.'],
        );

        try {
            $this->service->kembalikan($batch, $request->user(), $data['catatan']);
        } catch (StatusTidakValidException $e) {
            return redirect()->route('ppk.batch.show', $batch)->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('ppk.batch.index')->with('status', "Batch {$batch->kode_batch} dikembalikan ke Pejabat Pengadaan.");
    }

    public function riwayat(Request $request): View
    {
        $keputusan = (string) $request->query('keputusan');   // '', 'disetujui', 'dikembalikan'
        $q = trim((string) $request->query('q'));

        $dasar = BatchPengadaan::whereNotNull('diputuskan_oleh');
        $jumlah = [
            'semua' => (clone $dasar)->count(),
            'disetujui' => (clone $dasar)->where('status', StatusBatch::Disetujui->value)->count(),
            'dikembalikan' => (clone $dasar)->where('status', '!=', StatusBatch::Disetujui->value)->count(),
        ];

        $batch = (clone $dasar)->with('pembuat', 'pemutus')
            ->withCount('pengajuan')
            ->withSum('pengajuan', 'total_estimasi_biaya')
            ->when($keputusan === 'disetujui', fn ($w) => $w->where('status', StatusBatch::Disetujui->value))
            ->when($keputusan === 'dikembalikan', fn ($w) => $w->where('status', '!=', StatusBatch::Disetujui->value))
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $w->where(fn ($x) => $x->where('kode_batch', 'like', $like)->orWhereHas('pembuat', fn ($u) => $u->where('name', 'like', $like)));
            })
            ->latest('diputuskan_pada')
            ->paginate(10)
            ->withQueryString();

        return view('ppk.riwayat', ['batch' => $batch, 'jumlah' => $jumlah, 'filter' => ['keputusan' => $keputusan, 'q' => $q]]);
    }
}
