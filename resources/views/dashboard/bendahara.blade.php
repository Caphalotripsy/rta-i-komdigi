@extends('layouts.app')

@section('title', 'Dashboard Bendahara')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Dashboard']]" />
@endsection

@section('content')
    <x-page-header judul="Dashboard Bendahara" subjudul="Ringkasan pencairan dan pengembalian dana." />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Menunggu pencairan" :nilai="$statistik['menungguPencairan']" keterangan="Request disetujui PPK" ikon="dompet" :nada="$statistik['menungguPencairan'] > 0 ? 'bahaya' : 'biasa'" />
        <x-stat-card label="Menunggu pengembalian" :nilai="$statistik['menungguPengembalian']" keterangan="Sisa dana belum dikonfirmasi" ikon="balik" />
        <x-stat-card label="Dana menunggu dicairkan" :nilai="'Rp '.number_format($statistik['danaMenunggu'], 0, ',', '.')" keterangan="Akumulasi antrean pencairan" ikon="berkas" />
        <x-stat-card label="Sisa dana belum kembali" :nilai="'Rp '.number_format($statistik['sisaBelumKembali'], 0, ',', '.')" keterangan="Akumulasi antrean pengembalian" ikon="jam" />
    </div>

    <div class="card mb-6 overflow-hidden">
        <div class="flex flex-wrap items-end justify-between gap-3 p-5 pb-3">
            <div><h2 class="font-display text-xl font-bold text-slate-900">Antrean Pencairan</h2><p class="text-xs text-slate-500">Request yang disetujui PPK dan menunggu dana dicairkan.</p></div>
            <a href="{{ route('pencairan.index') }}" class="text-sm font-bold text-secondary hover:underline">Lihat semua &rarr;</a>
        </div>
        @if ($antreanPencairan->isEmpty())
            <x-empty-state judul="Tidak ada request yang menunggu pencairan." />
        @else
            @include('pencairan._tabel', ['daftar' => $antreanPencairan])
        @endif
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-end justify-between gap-3 p-5 pb-3">
            <div><h2 class="font-display text-xl font-bold text-slate-900">Antrean Pengembalian</h2><p class="text-xs text-slate-500">Sisa dana yang menunggu konfirmasi.</p></div>
            <a href="{{ route('pengembalian.index') }}" class="text-sm font-bold text-secondary hover:underline">Lihat semua &rarr;</a>
        </div>
        @if ($antreanPengembalian->isEmpty())
            <x-empty-state judul="Tidak ada pengembalian yang menunggu." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-[13px]">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr><th class="px-3 py-3">Kode</th><th class="px-3 py-3">Pemohon &amp; Divisi</th><th class="px-3 py-3">Barang</th><th class="px-3 py-3 text-right">Sisa dana</th><th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($antreanPengembalian as $p)
                            <tr class="bg-white">
                                <td class="whitespace-nowrap px-3 py-4 font-bold text-primary">{{ $p->kode_pengajuan }}</td>
                                <td class="px-3 py-4"><p class="font-bold">{{ $p->pemohon->name }}</p><p class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</p></td>
                                <td class="px-3 py-4">{{ $p->nama_barang }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right font-bold text-amber-800">@rupiah($p->sisa_dana)</td>
                                <td class="sticky right-0 bg-white px-3 py-4 text-right"><a href="{{ route('pengembalian.index') }}" class="inline-flex rounded-md bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-100">Konfirmasi</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
