@extends('layouts.app')

@section('title', 'Dashboard Pejabat Pengadaan')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Dashboard']]" />
@endsection

@section('content')
    <x-page-header judul="Dashboard Pejabat Pengadaan" subjudul="Ringkasan tinjauan request dan pengelolaan batch." />

    @if ($batchBanner)
        <div role="alert" class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-red-200 bg-red-50 p-5">
            <div class="flex items-start gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-red-100 text-danger"><x-icon name="peringatan" /></span>
                <div class="text-sm">
                    <p class="font-bold text-danger">Batch {{ $batchBanner->kode_batch }} dikembalikan oleh PPK @if ($batchBanner->pemutus) ({{ $batchBanner->pemutus->name }})@endif</p>
                    @if ($batchBanner->catatan_revisi)<p class="mt-1 text-slate-700">Catatan: <em>&ldquo;{{ $batchBanner->catatan_revisi }}&rdquo;</em></p>@endif
                </div>
            </div>
            <a href="{{ route('batch.show', $batchBanner) }}" class="btn btn-danger">Buka Batch</a>
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Perlu ditinjau" :nilai="$statistik['perluDitinjau']" keterangan="Request baru menunggu verifikasi" ikon="berkas" />
        <x-stat-card label="Siap batching" :nilai="$statistik['siapBatching']" keterangan="Disetujui, menunggu dibatch" ikon="arsip" />
        <x-stat-card label="Batch dikembalikan" :nilai="$statistik['batchDikembalikan']" keterangan="Perlu ditindaklanjuti" ikon="peringatan" :nada="$statistik['batchDikembalikan'] > 0 ? 'bahaya' : 'biasa'" />
        <x-stat-card label="Batch menunggu PPK" :nilai="$statistik['batchMenungguPpk']" keterangan="Dalam peninjauan PPK" ikon="jam" />
    </div>

    <div class="card mb-6 overflow-hidden">
        <div class="flex flex-wrap items-end justify-between gap-3 p-5 pb-3">
            <div><h2 class="font-display text-xl font-bold text-slate-900">Menunggu Tinjauan</h2><p class="text-xs text-slate-500">Request berstatus Diajukan, yang paling lama menunggu di atas.</p></div>
            <a href="{{ route('approval.index') }}" class="text-sm font-bold text-secondary hover:underline">Lihat semua &rarr;</a>
        </div>
        @if ($menungguTinjauan->isEmpty())
            <x-empty-state judul="Tidak ada request yang menunggu tinjauan." />
        @else
            @include('approval._tabel', ['daftar' => $menungguTinjauan])
        @endif
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-end justify-between gap-3 p-5 pb-3">
            <div><h2 class="font-display text-xl font-bold text-slate-900">Batch Perlu Revisi</h2><p class="text-xs text-slate-500">Batch yang dikembalikan PPK untuk diperbaiki.</p></div>
            <a href="{{ route('batch.dikembalikan') }}" class="text-sm font-bold text-secondary hover:underline">Lihat semua &rarr;</a>
        </div>
        @if ($batchRevisi->isEmpty())
            <x-empty-state judul="Tidak ada batch yang dikembalikan." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[40rem] text-[13px]">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr><th class="px-3 py-3">Kode batch</th><th class="px-3 py-3">Periode</th><th class="px-3 py-3 text-right">Request</th><th class="px-3 py-3 text-right">Total estimasi</th><th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($batchRevisi as $b)
                            <tr class="bg-white">
                                <td class="whitespace-nowrap px-3 py-4 font-bold text-primary">{{ $b->kode_batch }}</td>
                                <td class="whitespace-nowrap px-3 py-4">{{ $b->labelPeriode() }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right">{{ $b->jumlahRequest() }} request</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right font-bold">@rupiah($b->totalEstimasi())</td>
                                <td class="sticky right-0 bg-white px-3 py-4 text-right"><a href="{{ route('batch.show', $b) }}" class="inline-flex rounded-md bg-red-50 px-3 py-1.5 text-xs font-bold text-danger hover:bg-red-100">Buka</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
