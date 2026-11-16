@extends('layouts.app')

@section('title', 'Riwayat Verifikasi')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Riwayat Verifikasi']]" />
@endsection

@section('content')
    <x-page-header judul="Riwayat Verifikasi" subjudul="Request yang sudah diverifikasi dan ditutup." />

    <div class="card mb-4 p-4">
        <form method="GET" action="{{ route('verifikasi.riwayat') }}" class="flex flex-wrap items-center gap-3">
            <label class="sr-only" for="q">Cari request</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="Cari kode, pemohon, barang, atau nama toko..." class="input min-w-64 flex-1">
            <button class="btn btn-outline">Cari</button>
        </form>
    </div>

    <div class="card overflow-hidden">
        @if ($pengajuan->isEmpty())
            <x-empty-state judul="Belum ada request yang ditutup." />
        @else
            @include('verifikasi._tabel', ['daftar' => $pengajuan, 'aksi' => 'Lihat', 'gaya' => 'bg-sky-50 text-primary hover:bg-sky-100'])
            @if ($pengajuan->hasPages())<div class="border-t border-slate-100 px-5 py-3 text-xs">{{ $pengajuan->links() }}</div>@endif
        @endif
    </div>
@endsection
