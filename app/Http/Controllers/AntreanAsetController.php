<?php

namespace App\Http\Controllers;

use App\Enums\Kategori;
use App\Enums\KondisiAset;
use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Models\PengajuanPengadaan;
use App\Services\InventarisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Inventaris, UC-12: registrasi dan pelabelan aset dari request yang sudah ditutup. */
class AntreanAsetController extends Controller
{
    public function __construct(private InventarisService $service)
    {
    }

    public function index(): View
    {
        return view('antrean-aset.index', ['antrean' => $this->antrean(Kategori::Aset)->paginate(10)]);
    }

    public function create(PengajuanPengadaan $pengajuan): View|RedirectResponse
    {
        if (! $this->layak($pengajuan)) {
            return redirect()->route('inventaris.antrean')->withErrors(['status' => 'Request ini tidak berada di antrean registrasi aset.']);
        }

        $jumlah = (int) $pengajuan->kuantitas_terbeli;
        $pengajuan->load('pemohon');

        return view('antrean-aset.registrasi', [
            'pengajuan' => $pengajuan,
            'jumlah' => $jumlah,
            'skuPratinjau' => $this->service->skuBerikutnya((int) now()->format('Y'), $jumlah, false),
            'lokasiLama' => \App\Models\AsetInventaris::whereNotNull('lokasi')->distinct()->orderBy('lokasi')->pluck('lokasi'),
        ]);
    }

    public function store(Request $request, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        $jumlah = (int) $pengajuan->kuantitas_terbeli;

        $data = $request->validate([
            'unit' => ['required', 'array', "size:{$jumlah}"],
            'unit.*.nama_penanggung_jawab' => ['required', 'string', 'max:150'],
            'unit.*.lokasi' => ['required', 'string', 'max:150'],
            'unit.*.kondisi' => ['required', Rule::in([KondisiAset::Baik->value, KondisiAset::Rusak->value])],
        ], [
            'unit.size' => "Jumlah unit harus {$jumlah}, sesuai kuantitas terbeli.",
            'unit.*.nama_penanggung_jawab.required' => 'Penanggung jawab wajib diisi.',
            'unit.*.nama_penanggung_jawab.max' => 'Penanggung jawab maksimal 150 karakter.',
            'unit.*.lokasi.required' => 'Lokasi wajib diisi.',
            'unit.*.lokasi.max' => 'Lokasi maksimal 150 karakter.',
            'unit.*.kondisi.required' => 'Kondisi wajib dipilih.',
            'unit.*.kondisi.in' => 'Kondisi awal hanya Baik atau Rusak.',
        ]);

        try {
            $aset = $this->service->registrasiAset($pengajuan, $request->user(), $data['unit']);
        } catch (StatusTidakValidException $e) {
            return redirect()->route('inventaris.antrean')->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('cetak.label', ['pengajuan' => $pengajuan->id])
            ->with('status', "{$aset->count()} aset berhasil didaftarkan. Cetak label untuk ditempel pada barang.");
    }

    public function antrean(Kategori $kategori)
    {
        return PengajuanPengadaan::with('pemohon')
            ->where('status', StatusPengajuan::Selesai->value)
            ->where('kategori', $kategori->value)
            ->whereNull('diproses_inventaris_at')
            ->oldest('updated_at');
    }

    private function layak(PengajuanPengadaan $p): bool
    {
        return $p->status === StatusPengajuan::Selesai && $p->kategori === Kategori::Aset && $p->diproses_inventaris_at === null;
    }
}
