<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Models\PengajuanPengadaan;
use App\Services\PengajuanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Bendahara, UC-09: konfirmasi pengembalian sisa dana. */
class PengembalianDanaController extends Controller
{
    public function __construct(private PengajuanService $service)
    {
    }

    public function index(): View
    {
        $pengajuan = PengajuanPengadaan::with('pemohon')
            ->where('status', StatusPengajuan::MenungguPengembalian->value)
            ->oldest('updated_at')
            ->paginate(10);

        return view('pengembalian.index', ['pengajuan' => $pengajuan]);
    }

    public function store(Request $request, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        $data = $request->validate(
            ['tanggal_pengembalian' => ['required', 'date', 'before_or_equal:today']],
            ['tanggal_pengembalian.required' => 'Tanggal diterima wajib diisi.', 'tanggal_pengembalian.before_or_equal' => 'Tanggal diterima tidak boleh di masa depan.'],
        );

        try {
            $this->service->konfirmasiPengembalian($pengajuan, $request->user(), $data['tanggal_pengembalian']);
        } catch (StatusTidakValidException $e) {
            return redirect()->route('pengembalian.index')->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('pengembalian.index')->with('status', "Pengembalian dana {$pengajuan->kode_pengajuan} dikonfirmasi. Request masuk ke Cek Fisik.");
    }
}
