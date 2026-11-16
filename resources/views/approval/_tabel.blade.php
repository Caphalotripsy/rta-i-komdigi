{{-- Tabel request menunggu tinjauan. Variabel: $daftar (koleksi PengajuanPengadaan dengan relasi pemohon). --}}
<div class="overflow-x-auto">
    <table class="w-full min-w-[48rem] text-[13px]">
        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-3 py-3">Kode</th>
                <th class="px-3 py-3">Tanggal diajukan</th>
                <th class="px-3 py-3">Pemohon &amp; Divisi</th>
                <th class="min-w-48 px-3 py-3">Barang &amp; Spesifikasi</th>
                <th class="px-3 py-3">Kategori</th>
                <th class="px-3 py-3 text-right">Total estimasi</th>
                <th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($daftar as $p)
                <tr class="bg-white">
                    <td class="whitespace-nowrap px-3 py-4 font-bold text-primary">
                        <a href="{{ route('approval.show', $p) }}" class="hover:underline">{{ $p->kode_pengajuan }}</a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-slate-600">
                        {{ $p->updated_at->translatedFormat('d M Y') }}
                        <span class="block text-xs text-slate-500">{{ $p->updated_at->format('H:i') }} WITA</span>
                    </td>
                    <td class="px-3 py-4">
                        <p class="font-bold text-slate-900">{{ $p->pemohon->name }}</p>
                        <p class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</p>
                    </td>
                    <td class="px-3 py-4">
                        <p class="font-bold text-slate-900">{{ $p->nama_barang }}</p>
                        <p class="text-xs text-slate-500">{{ $p->kuantitas }} {{ $p->satuan->label() }}@if ($p->spesifikasi) &middot; {{ \Illuminate\Support\Str::limit($p->spesifikasi, 50) }}@endif</p>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4"><span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $p->kategori->label() }}</span></td>
                    <td class="whitespace-nowrap px-3 py-4 text-right">
                        <p class="font-bold text-slate-900">@rupiah($p->total_estimasi_biaya)</p>
                        <p class="text-xs text-slate-500">@rupiah($p->harga_estimasi) / {{ $p->satuan->value }}</p>
                    </td>
                    <td class="sticky right-0 bg-white px-3 py-4 text-right">
                        <a href="{{ route('approval.show', $p) }}" class="inline-flex whitespace-nowrap rounded-md bg-sky-50 px-3 py-1.5 text-xs font-bold text-primary transition hover:bg-sky-100">Tinjau</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
