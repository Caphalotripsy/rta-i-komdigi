<?php

namespace App\Http\Controllers;

use App\Enums\MetodePencairan;
use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Models\PengajuanPengadaan;
use App\Services\PengajuanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Bendahara, UC-08: catat pencairan dana untuk request yang disetujui PPK. */
class PencairanController extends Controller
{
    public function __construct(private PengajuanService $service)
    {
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $pengajuan = PengajuanPengadaan::with('pemohon', 'batch')
            ->where('status', StatusPengajuan::Disetujui->value)
            ->when($q !== '', function ($w) use ($q) {
                $like = '%'.addcslashes($q, '%_\\').'%';
                $w->where(fn ($x) => $x->where('nama_barang', 'like', $like)->orWhere('kode_pengajuan', 'like', $like)
                    ->orWhereHas('pemohon', fn ($u) => $u->where('name', 'like', $like)));
            })
            ->oldest('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('pencairan.index', ['pengajuan' => $pengajuan, 'q' => $q]);
    }

    public function show(PengajuanPengadaan $pengajuan): View
    {
        $pengajuan->load('pemohon', 'batch', 'riwayat');

        return view('pencairan.form', ['pengajuan' => $pengajuan, 'metode' => MetodePencairan::cases()]);
    }

    public function store(Request $request, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        $data = $request->validate([
            'metode_pencairan' => ['required', Rule::enum(MetodePencairan::class)],
            'tanggal_pencairan' => ['required', 'date', 'before_or_equal:today'],
            'referensi_pencairan' => ['nullable', 'string', 'max:100'],
        ], [
            'metode_pencairan.required' => 'Metode penyerahan dana wajib dipilih.',
            'tanggal_pencairan.required' => 'Tanggal pencairan wajib diisi.',
            'tanggal_pencairan.before_or_equal' => 'Tanggal pencairan tidak boleh di masa depan.',
            'referensi_pencairan.max' => 'Nomor referensi maksimal 100 karakter.',
        ]);

        try {
            $this->service->catatPencairan($pengajuan, $request->user(), $data);
        } catch (StatusTidakValidException $e) {
            return redirect()->route('pencairan.index')->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('pencairan.index')->with('status', "Pencairan dana {$pengajuan->kode_pengajuan} berhasil dicatat.");
    }
}
