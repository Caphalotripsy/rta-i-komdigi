<?php

namespace App\Http\Controllers;

use App\Enums\Kategori;
use App\Enums\KondisiAset;
use App\Exceptions\StatusTidakValidException;
use App\Models\AsetInventaris;
use App\Models\Inventaris;
use App\Services\InventarisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Inventaris, UC-14: master inventaris, detail, dan mutasi aset. */
class MasterInventarisController extends Controller
{
    public function __construct(private InventarisService $service)
    {
    }

    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'stok' ? 'stok' : 'aset';
        $q = trim((string) $request->query('q'));
        $kondisi = (string) $request->query('kondisi');

        $aset = null;
        $stok = null;

        if ($tab === 'aset') {
            $aset = AsetInventaris::with('inventaris')->cari($q, $kondisi)->latest('id')->paginate(10)->withQueryString();
        } else {
            $stok = Inventaris::where('kategori', Kategori::HabisPakai->value)
                ->when($q !== '', fn ($w) => $w->where('nama_barang', 'like', '%'.addcslashes($q, '%_\\').'%'))
                ->get()
                ->groupBy(fn ($i) => \Illuminate\Support\Str::lower(trim($i->nama_barang)).'|'.$i->satuan)
                ->map(fn ($g) => (object) [
                    'nama' => $g->first()->nama_barang, 'satuan' => $g->first()->satuan,
                    'total' => (int) $g->sum('total_stok'), 'masuk' => $g->count(), 'terakhir' => $g->max('updated_at'),
                ])->sortBy('nama')->values();
        }

        return view('master.index', [
            'tab' => $tab, 'q' => $q, 'kondisi' => $kondisi, 'aset' => $aset, 'stok' => $stok,
            'semuaKondisi' => KondisiAset::cases(),
            'jumlah' => ['aset' => AsetInventaris::count(), 'stok' => Inventaris::where('kategori', Kategori::HabisPakai->value)->count()],
        ]);
    }

    public function show(AsetInventaris $aset): View
    {
        $aset->load('inventaris', 'pengajuan.pemohon', 'mutasi.pelaksana');

        return view('master.aset', [
            'aset' => $aset,
            'lokasiLama' => AsetInventaris::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi'),
        ]);
    }

    public function mutasi(Request $request, AsetInventaris $aset): RedirectResponse
    {
        $data = $request->validate([
            'lokasi' => ['required', 'string', 'max:150'],
            'nama_penanggung_jawab' => ['required', 'string', 'max:150'],
            'kondisi' => ['required', Rule::enum(KondisiAset::class)],
            'catatan' => ['nullable', 'string', 'max:500'],
        ], [
            'lokasi.required' => 'Lokasi wajib diisi.',
            'nama_penanggung_jawab.required' => 'Penanggung jawab wajib diisi.',
            'kondisi.required' => 'Kondisi wajib dipilih.',
        ]);

        try {
            $this->service->mutasi($aset, $request->user(), $data);
        } catch (StatusTidakValidException $e) {
            return back()->withInput()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('master.aset', $aset)->with('status', 'Perubahan aset berhasil disimpan dan tercatat di riwayat mutasi.');
    }
}
