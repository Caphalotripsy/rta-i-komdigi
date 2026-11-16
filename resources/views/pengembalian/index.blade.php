@extends('layouts.app')

@section('title', 'Pengembalian Dana')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Pengembalian Dana']]" />
@endsection

@section('content')
    <x-page-header judul="Pengembalian Dana" subjudul="Sisa dana yang wajib dikembalikan pemohon. Konfirmasi setelah dana diterima." />

    <div class="card overflow-hidden">
        @if ($pengajuan->isEmpty())
            <x-empty-state judul="Tidak ada pengembalian yang menunggu." teks="Request dengan sisa dana akan muncul di sini setelah pemohon melaporkan harga aktual." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[48rem] text-[13px]">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Kode</th><th class="px-3 py-3">Pemohon &amp; Divisi</th><th class="min-w-40 px-3 py-3">Barang</th>
                            <th class="px-3 py-3 text-right">Dana dicairkan</th><th class="px-3 py-3 text-right">Total aktual</th><th class="px-3 py-3 text-right">Sisa dana</th>
                            <th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($pengajuan as $p)
                            <tr class="bg-white">
                                <td class="whitespace-nowrap px-3 py-4 font-bold text-primary">{{ $p->kode_pengajuan }}</td>
                                <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->pemohon->name }}</p><p class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</p></td>
                                <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->nama_barang }}</p><p class="text-xs text-slate-500">{{ $p->kuantitas_terbeli }} {{ $p->satuan->label() }} terbeli</p></td>
                                <td class="whitespace-nowrap px-3 py-4 text-right">@rupiah($p->dana_dicairkan)</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right">@rupiah($p->total_biaya_aktual)</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right font-bold text-amber-800">@rupiah($p->sisa_dana)</td>
                                <td class="sticky right-0 bg-white px-3 py-4 text-right"><button type="button" class="inline-flex whitespace-nowrap rounded-md bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100" @click="$dispatch('buka-pengembalian-{{ $p->id }}')">Konfirmasi Pengembalian</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($pengajuan->hasPages())<div class="border-t border-slate-100 px-5 py-3 text-xs">{{ $pengajuan->links() }}</div>@endif
        @endif
    </div>

    @foreach ($pengajuan as $p)
        <x-modal :id="'pengembalian-'.$p->id" :judul="'Konfirmasi pengembalian '.$p->kode_pengajuan">
            Sisa dana <strong>@rupiah($p->sisa_dana)</strong> dari <strong>{{ $p->pemohon->name }}</strong>.
            <form id="form-pengembalian-{{ $p->id }}" method="POST" action="{{ route('pengembalian.store', $p) }}" class="mt-3">
                @csrf
                <label for="tgl-{{ $p->id }}" class="label">Tanggal diterima</label>
                <input id="tgl-{{ $p->id }}" name="tanggal_pengembalian" type="date" required max="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" class="input">
            </form>
            <x-slot:aksi>
                <button type="submit" form="form-pengembalian-{{ $p->id }}" class="btn btn-primary">Ya, Konfirmasi</button>
            </x-slot:aksi>
        </x-modal>
    @endforeach
@endsection
