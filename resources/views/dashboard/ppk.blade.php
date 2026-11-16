@extends('layouts.app')

@section('title', 'Dashboard PPK')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Dashboard']]" />
@endsection

@section('content')
    <x-page-header judul="Dashboard PPK" subjudul="Ringkasan batch yang menunggu keputusan Anda." />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Batch menunggu keputusan" :nilai="$statistik['batchMenunggu']" keterangan="Perlu disetujui atau dikembalikan" ikon="jam" :nada="$statistik['batchMenunggu'] > 0 ? 'bahaya' : 'biasa'" />
        <x-stat-card label="Request dalam batch" :nilai="$statistik['requestMenunggu']" keterangan="Menunggu keputusan PPK" ikon="berkas" />
        <x-stat-card label="Batch disetujui" :nilai="$statistik['batchDisetujui']" keterangan="Sepanjang waktu" ikon="centang" nada="sukses" />
        <x-stat-card label="Total estimasi menunggu" :nilai="'Rp '.number_format($statistik['totalMenunggu'], 0, ',', '.')" keterangan="Akumulasi batch yang menunggu" ikon="dompet" />
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-end justify-between gap-3 p-5 pb-3">
            <div><h2 class="font-display text-xl font-bold text-slate-900">Batch Menunggu Keputusan</h2><p class="text-xs text-slate-500">Keputusan PPK diambil per batch, bukan per request.</p></div>
            <a href="{{ route('ppk.batch.index') }}" class="text-sm font-bold text-secondary hover:underline">Lihat semua &rarr;</a>
        </div>
        @if ($batchMenunggu->isEmpty())
            <x-empty-state judul="Tidak ada batch yang menunggu keputusan." teks="Batch baru dari Pejabat Pengadaan akan muncul di sini." />
        @else
            @include('ppk._tabel', ['daftar' => $batchMenunggu])
        @endif
    </div>
@endsection
