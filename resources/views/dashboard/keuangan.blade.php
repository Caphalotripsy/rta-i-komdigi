@extends('layouts.app')

@section('title', 'Dashboard Divisi Keuangan')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Dashboard']]" />
@endsection

@section('content')
    <x-page-header judul="Dashboard Divisi Keuangan" subjudul="Ringkasan verifikasi fisik dan nota." />

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Menunggu verifikasi" :nilai="$statistik['menungguVerifikasi']" keterangan="Request berstatus Cek Fisik" ikon="berkas" :nada="$statistik['menungguVerifikasi'] > 0 ? 'bahaya' : 'biasa'" />
        <x-stat-card label="Dikembalikan ke divisi" :nilai="$statistik['dikembalikan']" keterangan="Menunggu perbaikan pemohon" ikon="balik" />
        <x-stat-card label="Selesai bulan ini" :nilai="$statistik['selesaiBulanIni']" keterangan="Request ditutup" ikon="centang" nada="sukses" />
        <x-stat-card label="Total biaya aktual" :nilai="'Rp '.number_format($statistik['totalTerverifikasi'], 0, ',', '.')" keterangan="Request yang sudah ditutup" ikon="dompet" />
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-end justify-between gap-3 p-5 pb-3">
            <div><h2 class="font-display text-xl font-bold text-slate-900">Antrean Verifikasi</h2><p class="text-xs text-slate-500">Request yang paling lama menunggu di atas.</p></div>
            <a href="{{ route('verifikasi.index') }}" class="text-sm font-bold text-secondary hover:underline">Lihat semua &rarr;</a>
        </div>
        @if ($antrean->isEmpty())
            <x-empty-state judul="Belum ada request untuk diverifikasi." />
        @else
            @include('verifikasi._tabel', ['daftar' => $antrean, 'aksi' => 'Verifikasi', 'gaya' => 'bg-sky-50 text-primary hover:bg-sky-100'])
        @endif
    </div>
@endsection
