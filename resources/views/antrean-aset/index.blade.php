@extends('layouts.app')

@section('title', 'Draft Antrean Aset')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Draft Antrean Aset']]" />
@endsection

@section('content')
    <x-page-header judul="Draft Antrean Aset" subjudul="Barang kategori Aset dari request yang telah ditutup dan menunggu registrasi." />

    <div class="card overflow-hidden">
        @if ($antrean->isEmpty())
            <x-empty-state judul="Tidak ada aset yang menunggu registrasi." teks="Request aset yang ditutup Divisi Keuangan akan muncul di sini." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-[13px]">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr><th class="px-3 py-3">Kode</th><th class="min-w-44 px-3 py-3">Barang</th><th class="px-3 py-3">Pemohon &amp; Divisi</th><th class="px-3 py-3 text-right">Kuantitas terbeli</th><th class="px-3 py-3">Ditutup</th><th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($antrean as $p)
                            <tr class="bg-white">
                                <td class="whitespace-nowrap px-3 py-4 font-bold text-primary">{{ $p->kode_pengajuan }}</td>
                                <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->nama_barang }}</p>@if ($p->spesifikasi)<p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($p->spesifikasi, 60) }}</p>@endif</td>
                                <td class="px-3 py-4"><p class="font-bold">{{ $p->pemohon->name }}</p><p class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</p></td>
                                <td class="whitespace-nowrap px-3 py-4 text-right font-bold">{{ $p->kuantitas_terbeli }} {{ $p->satuan->label() }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $p->updated_at->translatedFormat('d M Y') }}<span class="block text-xs text-slate-500">{{ $p->updated_at->format('H:i') }} WITA</span></td>
                                <td class="sticky right-0 bg-white px-3 py-4 text-right"><a href="{{ route('aset.registrasi', $p) }}" class="inline-flex whitespace-nowrap rounded-md bg-sky-50 px-3 py-1.5 text-xs font-bold text-primary hover:bg-sky-100">Registrasi Aset</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($antrean->hasPages())<div class="border-t border-slate-100 px-5 py-3 text-xs">{{ $antrean->links() }}</div>@endif
        @endif
    </div>
@endsection
