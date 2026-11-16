@extends('layouts.app')

@section('title', 'Pencairan Dana')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Pencairan Dana']]" />
@endsection

@section('content')
    <x-page-header judul="Pencairan Dana" subjudul="Request yang disetujui PPK dan menunggu pencairan dana." />

    <div class="card mb-4 p-4">
        <form method="GET" action="{{ route('pencairan.index') }}" class="flex flex-wrap items-center gap-3">
            <label class="sr-only" for="q">Cari request</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="Cari kode request, pemohon, atau nama barang..." class="input min-w-64 flex-1">
            <button class="btn btn-outline">Cari</button>
        </form>
    </div>

    <div class="card overflow-hidden">
        @if ($pengajuan->isEmpty())
            <x-empty-state judul="Tidak ada request yang menunggu pencairan." teks="Request yang disetujui PPK akan muncul di sini." />
        @else
            @include('pencairan._tabel', ['daftar' => $pengajuan])
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                <span>Menampilkan {{ $pengajuan->firstItem() }}&ndash;{{ $pengajuan->lastItem() }} dari {{ $pengajuan->total() }} request</span>
                @if ($pengajuan->hasPages())<div>{{ $pengajuan->links() }}</div>@endif
            </div>
        @endif
    </div>
@endsection
