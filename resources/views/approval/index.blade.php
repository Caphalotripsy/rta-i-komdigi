@extends('layouts.app')

@section('title', 'Approval Tahap 1')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Approval Tahap 1']]" />
@endsection

@section('content')
    <x-page-header judul="Approval Tahap 1" subjudul="Request baru berstatus Diajukan yang perlu ditinjau kelayakan teknis dan estimasi biayanya sebelum dibatch." />

    <div class="card mb-4 p-4">
        <form method="GET" action="{{ route('approval.index') }}" class="flex flex-wrap items-center gap-3">
            <label class="sr-only" for="q">Cari request</label>
            <input id="q" name="q" type="search" value="{{ $filter['q'] }}" placeholder="Cari kode request, pemohon, atau nama barang..." class="input min-w-64 flex-1">
            @if ($filter['kategori'])<input type="hidden" name="kategori" value="{{ $filter['kategori'] }}">@endif
            <button class="btn btn-outline">Cari</button>
        </form>

        <div class="mt-3 flex flex-wrap gap-2 text-xs font-medium">
            @foreach ([['', 'Semua', $jumlah['semua']], ['aset', 'Aset', $jumlah['aset']], ['habis_pakai', 'Habis Pakai', $jumlah['habis_pakai']]] as [$nilai, $nama, $n])
                <a href="{{ route('approval.index', array_filter(['kategori' => $nilai, 'q' => $filter['q']])) }}"
                   @class([
                       'rounded-full px-3 py-1.5 transition',
                       'bg-primary text-white' => ($filter['kategori'] ?? '') === $nilai,
                       'bg-slate-100 text-slate-700 hover:bg-slate-200' => ($filter['kategori'] ?? '') !== $nilai,
                   ])>{{ $nama }} <span class="ml-1 font-bold">{{ $n }}</span></a>
            @endforeach
        </div>
    </div>

    <div class="card overflow-hidden">
        @if ($pengajuan->isEmpty())
            <x-empty-state judul="Tidak ada request yang menunggu tinjauan." teks="Request baru dari Kepala Divisi akan muncul di sini." />
        @else
            @include('approval._tabel', ['daftar' => $pengajuan])
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                <span>Menampilkan {{ $pengajuan->firstItem() }}&ndash;{{ $pengajuan->lastItem() }} dari {{ $pengajuan->total() }} request</span>
                @if ($pengajuan->hasPages())<div>{{ $pengajuan->links() }}</div>@endif
            </div>
        @endif
    </div>
@endsection
