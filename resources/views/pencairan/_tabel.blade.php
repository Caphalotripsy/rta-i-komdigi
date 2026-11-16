<div class="overflow-x-auto">
    <table class="w-full min-w-[48rem] text-[13px]">
        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-3 py-3">Kode</th><th class="px-3 py-3">Batch</th><th class="px-3 py-3">Pemohon &amp; Divisi</th>
                <th class="min-w-44 px-3 py-3">Barang</th><th class="px-3 py-3">Kategori</th>
                <th class="px-3 py-3 text-right">Dana dicairkan</th><th class="px-3 py-3">Disetujui</th><th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($daftar as $p)
                <tr class="bg-white">
                    <td class="whitespace-nowrap px-3 py-4 font-bold text-primary"><a href="{{ route('pencairan.show', $p) }}" class="hover:underline">{{ $p->kode_pengajuan }}</a></td>
                    <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $p->batch?->kode_batch ?? '—' }}</td>
                    <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->pemohon->name }}</p><p class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</p></td>
                    <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->nama_barang }}</p><p class="text-xs text-slate-500">{{ $p->kuantitas }} {{ $p->satuan->label() }}</p></td>
                    <td class="whitespace-nowrap px-3 py-4"><span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $p->kategori->label() }}</span></td>
                    <td class="whitespace-nowrap px-3 py-4 text-right font-bold">@rupiah($p->total_estimasi_biaya)</td>
                    <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $p->updated_at->translatedFormat('d M Y') }}<span class="block text-xs text-slate-500">{{ $p->updated_at->format('H:i') }} WITA</span></td>
                    <td class="sticky right-0 bg-white px-3 py-4 text-right"><a href="{{ route('pencairan.show', $p) }}" class="inline-flex whitespace-nowrap rounded-md bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 transition hover:bg-amber-100">Catat Pencairan</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
