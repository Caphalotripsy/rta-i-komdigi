@extends('layouts.app')

@section('title', 'Request Saya')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Request Saya']]" />
@endsection

@section('content')
    <x-page-header judul="Request Saya" subjudul="Daftar seluruh usulan pengadaan barang divisi Anda beserta tahapan prosesnya.">
        <x-slot:aksi>
            <a href="{{ route('pengajuan.create') }}" class="btn btn-primary">+ Tambah Request</a>
        </x-slot:aksi>
    </x-page-header>

    <div class="card mb-4 p-4">
        <form method="GET" action="{{ route('pengajuan.index') }}" class="grid gap-3 md:grid-cols-[1fr_12rem_12rem]">
            <label class="sr-only" for="q">Cari kode atau nama barang</label>
            <input id="q" name="q" type="search" value="{{ $filter['q'] }}" placeholder="Cari kode permohonan atau nama barang..." class="input">

            <label class="sr-only" for="status">Status</label>
            <select id="status" name="status" class="input" @change="$el.form.submit()">
                <option value="">Semua Status</option>
                <option value="menunggu_tindakan" @selected($filter['status'] === 'menunggu_tindakan')>Menunggu Tindakan Anda</option>
                @foreach ($semuaStatus as $s)
                    <option value="{{ $s->value }}" @selected($filter['status'] === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>

            <label class="sr-only" for="kategori">Kategori</label>
            <select id="kategori" name="kategori" class="input" @change="$el.form.submit()">
                <option value="">Semua Kategori</option>
                @foreach ($semuaKategori as $k)
                    <option value="{{ $k->value }}" @selected($filter['kategori'] === $k->value)>{{ $k->label() }}</option>
                @endforeach
            </select>
        </form>

        <div class="mt-3 flex flex-wrap gap-2 text-xs font-medium">
            @php
                $chipDaftar = [
                    ['', 'Semua', $jumlah['semua']],
                    ['draft', 'Draft', $jumlah['draft']],
                    ['direvisi', 'Perlu Revisi', $jumlah['direvisi']],
                    ['menunggu_tindakan', 'Menunggu Tindakan', $jumlah['menunggu_tindakan']],
                    ['selesai', 'Selesai', $jumlah['selesai']],
                ];
            @endphp
            @foreach ($chipDaftar as [$nilai, $nama, $jumlahChip])
                <a href="{{ route('pengajuan.index', $nilai === '' ? [] : ['status' => $nilai]) }}"
                   @class([
                       'rounded-full px-3 py-1.5 transition',
                       'bg-primary text-white' => $filter['status'] === $nilai,
                       'bg-slate-100 text-slate-700 hover:bg-slate-200' => $filter['status'] !== $nilai,
                   ])>
                    {{ $nama }} <span class="ml-1 font-bold">{{ $jumlahChip }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div class="card overflow-hidden">
        @if ($pengajuan->isEmpty())
            @if ($jumlah['semua'] === 0)
                <x-empty-state judul="Belum ada request. Buat request pertama Anda."
                               teks="Gunakan tombol di bawah untuk mengajukan kebutuhan inventaris maupun barang habis pakai {{ auth()->user()->divisi ?? 'divisi Anda' }}.">
                    <a href="{{ route('pengajuan.create') }}" class="btn btn-primary">+ Tambah Request</a>
                </x-empty-state>
            @else
                <x-empty-state judul="Tidak ada request yang cocok." teks="Ubah kata kunci atau filter yang Anda pakai.">
                    <a href="{{ route('pengajuan.index') }}" class="btn btn-outline">Hapus filter</a>
                </x-empty-state>
            @endif
        @else
            @include('pengajuan._tabel', ['daftar' => $pengajuan])
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-xs text-slate-500">
                <span>Menampilkan {{ $pengajuan->firstItem() }}&ndash;{{ $pengajuan->lastItem() }} dari {{ $pengajuan->total() }} request</span>
                @if ($pengajuan->hasPages())
                    <div>{{ $pengajuan->links() }}</div>
                @endif
            </div>
        @endif
    </div>
@endsection
