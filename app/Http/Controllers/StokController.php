<?php

namespace App\Http\Controllers;

use App\Enums\Kategori;
use App\Enums\Satuan;
use App\Exceptions\StatusTidakValidException;
use App\Models\Inventaris;
use App\Models\PengajuanPengadaan;
use App\Services\InventarisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Inventaris, UC-13: pembaruan stok barang habis pakai. */
class StokController extends Controller
{
    public function __construct(private InventarisService $service)
    {
    }

    public function index(Request $request): View
    {
        $antrean = app(AntreanAsetController::class, ['service' => $this->service])->antrean(Kategori::HabisPakai)->get();
        $terpilih = $antrean->firstWhere('id', (int) $request->query('pengajuan')) ?? $antrean->first();

        // Stok berjalan per nama (tanpa beda huruf besar/kecil) dan satuan, untuk pratinjau saldo.
        $stok = Inventaris::where('kategori', Kategori::HabisPakai->value)->get()
            ->groupBy(fn ($i) => Str::lower(trim($i->nama_barang)).'|'.$i->satuan)
            ->map(fn ($g) => (int) $g->sum('total_stok'));

        return view('stok.index', [
            'antrean' => $antrean,
            'terpilih' => $terpilih,
            'satuan' => Satuan::cases(),
            'stok' => $stok,
        ]);
    }

    public function store(Request $request, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        $data = $request->validate([
            'nama_barang' => ['required', 'string', 'max:255'],
            'satuan' => ['required', Rule::enum(Satuan::class)],
        ], [
            'nama_barang.required' => 'Nama barang wajib diisi.',
            'satuan.required' => 'Satuan wajib dipilih.',
        ]);

        try {
            $this->service->catatStok($pengajuan, $request->user(), $data['nama_barang'], Satuan::from($data['satuan']));
        } catch (StatusTidakValidException $e) {
            return redirect()->route('stok.index')->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('stok.index')->with('status', "Stok {$data['nama_barang']} dari {$pengajuan->kode_pengajuan} berhasil dicatat.");
    }
}
