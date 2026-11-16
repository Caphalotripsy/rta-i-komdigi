<?php

namespace App\Http\Controllers;

use App\Enums\StatusPengajuan;
use App\Exceptions\StatusTidakValidException;
use App\Models\PengajuanPengadaan;
use App\Services\PengajuanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Divisi Keuangan, UC-10 dan UC-11: verifikasi fisik dan nota, lalu tutup atau kembalikan. */
class VerifikasiController extends Controller
{
    public const JENIS_MASALAH = [
        'nominal' => 'Nominal tidak sesuai',
        'kuantitas' => 'Kuantitas tidak sesuai',
        'spesifikasi' => 'Barang tidak sesuai spesifikasi',
        'dokumen' => 'Dokumen tidak lengkap',
    ];

    public function __construct(private PengajuanService $service)
    {
    }

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $pengajuan = $this->cari(PengajuanPengadaan::where('status', StatusPengajuan::CekFisik->value), $q)
            ->oldest('updated_at')->paginate(10)->withQueryString();

        return view('verifikasi.index', ['pengajuan' => $pengajuan, 'q' => $q]);
    }

    public function riwayat(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $pengajuan = $this->cari(PengajuanPengadaan::where('status', StatusPengajuan::Selesai->value), $q)
            ->latest('updated_at')->paginate(10)->withQueryString();

        return view('verifikasi.riwayat', ['pengajuan' => $pengajuan, 'q' => $q]);
    }

    public function show(PengajuanPengadaan $pengajuan): View
    {
        $pengajuan->load('pemohon', 'batch', 'riwayat.aktor');

        return view('verifikasi.show', ['pengajuan' => $pengajuan, 'jenisMasalah' => self::JENIS_MASALAH]);
    }

    public function valid(Request $request, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        $request->validate(
            ['cek_barang' => ['accepted'], 'cek_nominal' => ['accepted'], 'cek_kuantitas' => ['accepted']],
            ['accepted' => 'Seluruh pemeriksaan fisik harus dicentang sebelum request ditutup.'],
        );

        try {
            $this->service->validasiKeuangan($pengajuan, $request->user());
        } catch (StatusTidakValidException $e) {
            return redirect()->route('verifikasi.index')->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('verifikasi.index')
            ->with('status', "{$pengajuan->kode_pengajuan} valid dan ditutup. Request masuk antrean Inventaris.");
    }

    public function kembalikan(Request $request, PengajuanPengadaan $pengajuan): RedirectResponse
    {
        $data = $request->validate([
            'jenis_masalah' => ['required', Rule::in(array_keys(self::JENIS_MASALAH))],
            'catatan' => ['required', 'string', 'max:1000'],
        ], [
            'jenis_masalah.required' => 'Jenis masalah wajib dipilih.',
            'jenis_masalah.in' => 'Jenis masalah tidak valid.',
            'catatan.required' => 'Catatan wajib diisi agar pemohon tahu apa yang harus diperbaiki.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
        ]);

        try {
            $this->service->kembalikanKeuangan($pengajuan, $request->user(), $data['jenis_masalah'], $data['catatan']);
        } catch (StatusTidakValidException $e) {
            return redirect()->route('verifikasi.index')->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('verifikasi.index')
            ->with('status', "{$pengajuan->kode_pengajuan} dikembalikan ke pemohon untuk diperbaiki.");
    }

    private function cari($query, string $q)
    {
        return $query->with('pemohon')->when($q !== '', function ($w) use ($q) {
            $like = '%'.addcslashes($q, '%_\\').'%';
            $w->where(fn ($x) => $x->where('nama_barang', 'like', $like)->orWhere('kode_pengajuan', 'like', $like)
                ->orWhere('nama_toko', 'like', $like)->orWhereHas('pemohon', fn ($u) => $u->where('name', 'like', $like)));
        });
    }
}
