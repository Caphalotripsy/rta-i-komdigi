@extends('layouts.app')

@section('title', 'Dashboard Kepala Divisi')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Dashboard']]" />
@endsection

@section('content')
    <x-page-header judul="Dashboard Kepala Divisi" subjudul="Ringkasan permintaan pengadaan {{ $user->divisi ?? 'divisi Anda' }}.">
        <x-slot:aksi>
            <a href="{{ route('pengajuan.create') }}" class="btn btn-primary">+ Tambah Request</a>
        </x-slot:aksi>
    </x-page-header>

    {{-- Banner: request yang perlu direvisi --}}
    @if ($revisi)
        @php $akhir = $catatanRevisi; @endphp
        <div role="alert" class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-red-200 bg-red-50 p-5">
            <div class="flex items-start gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-red-100 text-danger"><x-icon name="peringatan" /></span>
                <div class="text-sm">
                    <p class="font-bold text-danger">
                        Tindakan diperlukan: {{ $statistik['perluRevisi'] }} request perlu diperbaiki
                    </p>
                    <p class="mt-0.5 text-slate-700">
                        <span class="font-bold">{{ $revisi->kode_pengajuan }}</span> &middot; {{ $revisi->nama_barang }}
                    </p>
                    @if ($akhir)
                        <p class="mt-1 text-slate-600">
                            Catatan {{ $akhir->aktor->role->label() }} ({{ $akhir->aktor->name }}):
                            <em>&ldquo;{{ $akhir->catatan }}&rdquo;</em>
                        </p>
                    @endif
                </div>
            </div>
            <a href="{{ $revisi->aksiUtama()['url'] }}" class="btn btn-danger">Buka Form Revisi</a>
        </div>
    @endif

    {{-- Kartu statistik --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Request berjalan" :nilai="$statistik['berjalan']" keterangan="Sedang dalam tahapan proses" ikon="berkas" />
        <x-stat-card label="Menunggu tindakan Anda" :nilai="$statistik['perluRevisi'] + $statistik['laporHarga']"
                     keterangan="Perlu revisi {{ $statistik['perluRevisi'] }} · Lapor harga {{ $statistik['laporHarga'] }}"
                     ikon="jam" :nada="($statistik['perluRevisi'] + $statistik['laporHarga']) > 0 ? 'bahaya' : 'biasa'" />
        <x-stat-card label="Selesai" :nilai="$statistik['selesai']" keterangan="Tuntas terverifikasi dan diterima" ikon="centang" nada="sukses" />
        <x-stat-card label="Total estimasi" :nilai="'Rp '.number_format($statistik['totalEstimasi'], 0, ',', '.')"
                     keterangan="Akumulasi usulan yang diajukan" ikon="dompet" />
    </div>

    {{-- Alur proses: ilustrasi tahapan umum, bukan posisi request tertentu --}}
    <div class="card mb-6 p-5">
        <p class="mb-4 text-xs font-bold uppercase tracking-wide text-slate-500">Alur proses</p>
        <ol class="grid grid-cols-2 gap-4 text-center text-xs font-medium text-slate-600 sm:grid-cols-4 lg:grid-cols-7">
            @foreach (['Diajukan', 'Tinjau Pengadaan', 'Batching & PPK', 'Pencairan Dana', 'Lapor Harga & Nota', 'Verifikasi Keuangan', 'Selesai'] as $i => $langkah)
                <li class="flex flex-col items-center gap-2">
                    <span class="flex size-8 items-center justify-center rounded-full border border-slate-300 bg-slate-50 text-xs font-bold text-slate-600">{{ $i + 1 }}</span>
                    {{ $langkah }}
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Request terbaru --}}
    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-end justify-between gap-3 p-5 pb-3">
            <div>
                <h2 class="font-display text-xl font-bold text-slate-900">Request Terbaru</h2>
                <p class="text-xs text-slate-500">Daftar permohonan terkini divisi Anda.</p>
            </div>
            <a href="{{ route('pengajuan.index') }}" class="text-sm font-bold text-secondary hover:underline">Lihat semua request &rarr;</a>
        </div>

        <div class="flex flex-wrap gap-2 px-5 pb-4 text-xs font-medium">
            @php
                $chipDaftar = [
                    ['', 'Semua', $chip['semua']],
                    ['diajukan', 'Diajukan', $chip['diajukan']],
                    ['disetujui_pengadaan', 'Siap Batching', $chip['disetujui_pengadaan']],
                    ['pending_ppk', 'Menunggu PPK', $chip['pending_ppk']],
                    ['direvisi', 'Perlu Revisi', $chip['direvisi']],
                    ['selesai', 'Selesai', $chip['selesai']],
                    ['draft', 'Draft', $chip['draft']],
                ];
            @endphp
            @foreach ($chipDaftar as [$nilai, $nama, $jumlahChip])
                <a href="{{ route('pengajuan.index', $nilai === '' ? [] : ['status' => $nilai]) }}"
                   @class([
                       'rounded-full px-3 py-1.5 transition',
                       'bg-primary text-white' => $nilai === '',
                       'bg-slate-100 text-slate-700 hover:bg-slate-200' => $nilai !== '',
                       'opacity-50' => $jumlahChip === 0 && $nilai !== '',
                   ])>
                    {{ $nama }} <span class="ml-1 font-bold">{{ $jumlahChip }}</span>
                </a>
            @endforeach
        </div>

        @if ($terbaru->isEmpty())
            <x-empty-state judul="Belum ada request." teks="Buat request pertama Anda untuk mulai melacak pengadaan.">
                <a href="{{ route('pengajuan.create') }}" class="btn btn-primary">+ Tambah Request</a>
            </x-empty-state>
        @else
            @include('pengajuan._tabel', ['daftar' => $terbaru])
            <p class="border-t border-slate-100 px-5 py-3 text-xs text-slate-500">Menampilkan {{ $terbaru->count() }} permohonan terbaru</p>
        @endif
    </div>
@endsection
