<?php

namespace App\Http\Controllers;

use App\Enums\PeriodeBatch;
use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Models\PengajuanPengadaan;
use App\Services\BatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Pejabat Pengadaan, UC-05: kumpulkan request Siap Batching menjadi batch lalu teruskan ke PPK. */
class BatchingController extends Controller
{
    public function __construct(private BatchService $service)
    {
    }

    public function create(): View
    {
        $antrean = PengajuanPengadaan::with('pemohon')
            ->where('status', StatusPengajuan::DisetujuiPengadaan->value)
            ->whereNull('batch_id')
            ->oldest('id')
            ->get();

        return view('batching.create', [
            'antrean' => $antrean,
            'periode' => PeriodeBatch::cases(),
            'totalPerId' => $antrean->mapWithKeys(fn ($p) => [$p->id => (float) $p->total_estimasi_biaya]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'periode' => ['required', Rule::enum(PeriodeBatch::class)],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], [
            'periode.required' => 'Periode batch wajib dipilih.',
            'ids.required' => 'Pilih minimal satu request untuk dibatch.',
            'ids.min' => 'Pilih minimal satu request untuk dibatch.',
        ]);

        try {
            $batch = $this->service->buat($request->user(), PeriodeBatch::from($data['periode']), $data['ids']);
        } catch (StatusTidakValidException $e) {
            return back()->withInput()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('batch.show', $batch)
            ->with('status', "Batch {$batch->kode_batch} dibuat dan diteruskan ke PPK.");
    }
}
