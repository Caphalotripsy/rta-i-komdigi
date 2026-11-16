{{-- Variabel: $daftar (PengajuanPengadaan + pemohon), $aksi (label tombol), $gaya (kelas warna tombol). --}}
<div class="overflow-x-auto">
    <table class="w-full min-w-[48rem] text-[13px]">
        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-3 py-3">Kode</th><th class="px-3 py-3">Pemohon &amp; Divisi</th><th class="min-w-44 px-3 py-3">Barang</th>
                <th class="px-3 py-3">Toko &amp; nota</th><th class="px-3 py-3 text-right">Total aktual</th><th class="px-3 py-3 text-right">Sisa dana</th>
                <th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($daftar as $p)
                <tr class="bg-white">
                    <td class="whitespace-nowrap px-3 py-4 font-bold text-primary"><a href="{{ route('verifikasi.show', $p) }}" class="hover:underline">{{ $p->kode_pengajuan }}</a></td>
                    <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->pemohon->name }}</p><p class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</p></td>
                    <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->nama_barang }}</p><p class="text-xs text-slate-500">{{ $p->kuantitas_terbeli }} {{ $p->satuan->label() }} terbeli</p></td>
                    <td class="px-3 py-4"><p class="font-medium">{{ $p->nama_toko ?? '—' }}</p><p class="text-xs text-slate-500">{{ $p->nomor_nota ?? '—' }}</p></td>
                    <td class="whitespace-nowrap px-3 py-4 text-right font-bold">@rupiah($p->total_biaya_aktual)</td>
                    <td class="whitespace-nowrap px-3 py-4 text-right">@rupiah($p->sisa_dana)</td>
                    <td class="sticky right-0 bg-white px-3 py-4 text-right"><a href="{{ route('verifikasi.show', $p) }}" class="inline-flex whitespace-nowrap rounded-md px-3 py-1.5 text-xs font-bold transition {{ $gaya }}">{{ $aksi }}</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
