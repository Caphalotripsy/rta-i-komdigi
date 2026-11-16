@extends('layouts.app')

@section('title', 'Dashboard Inventaris')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Dashboard']]" />
@endsection

@section('content')
    <x-page-header judul="Dashboard Inventaris" subjudul="Ringkasan antrean registrasi dan stok inventaris." />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Antrean aset" :nilai="$statistik['antreanAset']" keterangan="Menunggu registrasi" ikon="arsip" :nada="$statistik['antreanAset'] > 0 ? 'bahaya' : 'biasa'" />
        <x-stat-card label="Antrean habis pakai" :nilai="$statistik['antreanStok']" keterangan="Menunggu dicatat sebagai stok" ikon="berkas" :nada="$statistik['antreanStok'] > 0 ? 'bahaya' : 'biasa'" />
        <x-stat-card label="Aset terdaftar" :nilai="$statistik['asetTerdaftar']" keterangan="Seluruh unit aset" ikon="centang" nada="sukses" />
        <x-stat-card label="Jenis stok habis pakai" :nilai="$statistik['jenisStok']" keterangan="Per nama dan satuan" ikon="daftar" />
    </div>

    @foreach ([['Antrean Aset', $antreanAset, 'inventaris.antrean', 'aset.registrasi', 'Registrasi Aset'], ['Antrean Stok', $antreanStok, 'stok.index', 'stok.index', 'Perbarui Stok']] as [$judul, $daftar, $semua, $aksiRute, $aksi])
        <div class="card mb-6 overflow-hidden">
            <div class="flex flex-wrap items-end justify-between gap-3 p-5 pb-3">
                <div><h2 class="font-display text-xl font-bold text-slate-900">{{ $judul }}</h2><p class="text-xs text-slate-500">Hanya request yang belum diproses.</p></div>
                <a href="{{ route($semua) }}" class="text-sm font-bold text-secondary hover:underline">Lihat semua &rarr;</a>
            </div>
            @if ($daftar->isEmpty())
                <x-empty-state judul="Tidak ada request di antrean ini." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[36rem] text-[13px]">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-3 py-3">Kode</th><th class="px-3 py-3">Barang</th><th class="px-3 py-3 text-right">Kuantitas</th><th class="px-3 py-3">Ditutup</th><th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($daftar as $p)
                                <tr class="bg-white">
                                    <td class="whitespace-nowrap px-3 py-4 font-bold text-primary">{{ $p->kode_pengajuan }}</td>
                                    <td class="px-3 py-4 font-bold">{{ $p->nama_barang }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-right">{{ $p->kuantitas_terbeli }} {{ $p->satuan->label() }}</td>
                                    <td class="whitespace-nowrap px-3 py-4 text-slate-600">{{ $p->updated_at->translatedFormat('d M Y') }}</td>
                                    <td class="sticky right-0 bg-white px-3 py-4 text-right"><a href="{{ $aksiRute === 'aset.registrasi' ? route('aset.registrasi', $p) : route('stok.index', ['pengajuan' => $p->id]) }}" class="inline-flex whitespace-nowrap rounded-md bg-sky-50 px-3 py-1.5 text-xs font-bold text-primary hover:bg-sky-100">{{ $aksi }}</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endforeach
@endsection
