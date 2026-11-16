@extends('layouts.app')

@section('title', 'Verifikasi & Closing')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Verifikasi & Closing']]" />
@endsection

@section('content')
    <x-page-header judul="Verifikasi & Closing" subjudul="Request berstatus Cek Fisik: periksa barang dan nota, lalu tutup atau kembalikan ke pemohon." />

    <div class="card mb-4 p-4">
        <form method="GET" action="{{ route('verifikasi.index') }}" class="flex flex-wrap items-center gap-3">
            <label class="sr-only" for="q">Cari request</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="Cari kode, pemohon, barang, atau nama toko..." class="input min-w-64 flex-1">
            <button class="btn btn-outline">Cari</button>
        </form>
    </div>

    <div class="card overflow-hidden">
        @if ($pengajuan->isEmpty())
            <x-empty-state judul="Belum ada request untuk diverifikasi." teks="Request yang pemohonnya sudah melapor dan sisa dananya terkonfirmasi akan muncul di sini." />
        @else
            @include('verifikasi._tabel', ['daftar' => $pengajuan, 'aksi' => 'Verifikasi', 'gaya' => 'bg-sky-50 text-primary hover:bg-sky-100'])
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                <span>Menampilkan {{ $pengajuan->firstItem() }}&ndash;{{ $pengajuan->lastItem() }} dari {{ $pengajuan->total() }} request</span>
                @if ($pengajuan->hasPages())<div>{{ $pengajuan->links() }}</div>@endif
            </div>
        @endif
    </div>
@endsection
