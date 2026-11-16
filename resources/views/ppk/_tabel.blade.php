{{-- Tabel batch menunggu keputusan PPK. Variabel: $daftar (batch dengan pembuat, pengajuan_count, pengajuan_sum_total_estimasi_biaya). --}}
<div class="overflow-x-auto">
    <table class="w-full min-w-[44rem] text-[13px]">
        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-3 py-3">Kode batch</th><th class="px-3 py-3">Periode</th><th class="px-3 py-3">Dibuat oleh</th>
                <th class="px-3 py-3 text-right">Request</th><th class="px-3 py-3 text-right">Total estimasi</th>
                <th class="px-3 py-3">Tanggal dikirim</th><th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($daftar as $b)
                <tr class="bg-white">
                    <td class="whitespace-nowrap px-3 py-4 font-bold text-primary"><a href="{{ route('ppk.batch.show', $b) }}" class="hover:underline">{{ $b->kode_batch }}</a></td>
                    <td class="whitespace-nowrap px-3 py-4">{{ $b->labelPeriode() }}</td>
                    <td class="px-3 py-4"><p class="font-medium">{{ $b->pembuat->name }}</p><p class="text-xs text-slate-500">{{ $b->pembuat->role->label() }}</p></td>
                    <td class="whitespace-nowrap px-3 py-4 text-right">{{ $b->jumlahRequest() }} request</td>
                    <td class="whitespace-nowrap px-3 py-4 text-right font-bold">@rupiah($b->totalEstimasi())</td>
                    <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $b->updated_at->translatedFormat('d M Y') }}<span class="block text-xs text-slate-500">{{ $b->updated_at->format('H:i') }} WITA</span></td>
                    <td class="sticky right-0 bg-white px-3 py-4 text-right"><a href="{{ route('ppk.batch.show', $b) }}" class="inline-flex whitespace-nowrap rounded-md bg-sky-50 px-3 py-1.5 text-xs font-bold text-primary transition hover:bg-sky-100">Tinjau</a></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
