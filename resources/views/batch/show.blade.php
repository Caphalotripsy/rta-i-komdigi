@extends('layouts.app')

@section('title', 'Detail Batch')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Riwayat Batch', route('batch.index')], [$batch->kode_batch]]" />
@endsection

@php
    $perluRevisi = $batch->status === \App\Enums\StatusBatch::Direvisi;
    $jumlahRequest = $batch->pengajuan->count();
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <h1 class="font-display text-3xl font-bold text-slate-900">Detail Batch {{ $batch->kode_batch }}</h1>
        <x-batch-badge :status="$batch->status" />
    </div>

    @if ($perluRevisi)
        <div role="alert" class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-5 text-sm">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-red-100 text-danger"><x-icon name="peringatan" /></span>
            <div>
                <p class="font-bold text-danger">Dikembalikan oleh PPK @if ($batch->pemutus) ({{ $batch->pemutus->name }}, {{ $batch->diputuskan_pada?->translatedFormat('d M Y, H:i') }} WITA)@endif</p>
                <p class="mt-1 whitespace-pre-line text-slate-700">{{ $batch->catatan_revisi ?: 'Tidak ada catatan.' }}</p>
                <p class="mt-2 text-slate-600">Keluarkan request yang bermasalah agar dikembalikan ke pemohon, lalu kirim ulang batch ke PPK. Atau batalkan batch bila tidak diperlukan.</p>
            </div>
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Periode</p><p class="mt-1 font-bold">{{ $batch->periode->label() }}</p></div>
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal dibuat</p><p class="mt-1 font-bold">{{ $batch->created_at->translatedFormat('d M Y') }}</p><p class="text-xs text-slate-500">{{ $batch->created_at->format('H:i') }} WITA</p></div>
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Dibuat oleh</p><p class="mt-1 font-bold">{{ $batch->pembuat->name }}</p></div>
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Jumlah request</p><p class="mt-1 font-display text-2xl font-bold">{{ $jumlahRequest }}</p></div>
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total estimasi</p><p class="mt-1 font-display text-2xl font-bold text-primary">@rupiah($batch->totalEstimasi())</p></div>
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-3">
            <h2 class="font-display text-lg font-bold text-slate-900">Request dalam Batch</h2>
        </div>
        @if ($batch->pengajuan->isEmpty())
            <x-empty-state judul="Batch ini tidak berisi request." />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[44rem] text-[13px]">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Kode</th><th class="px-3 py-3">Pemohon &amp; Divisi</th><th class="min-w-48 px-3 py-3">Barang &amp; Spesifikasi</th>
                            <th class="px-3 py-3">Kategori</th><th class="px-3 py-3 text-right">Kuantitas</th><th class="px-3 py-3 text-right">Total estimasi</th>
                            <th class="px-3 py-3">Status</th>@if ($perluRevisi)<th class="sticky right-0 bg-slate-50 px-3 py-3 text-right">Aksi</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($batch->pengajuan as $p)
                            <tr class="bg-white">
                                <td class="whitespace-nowrap px-3 py-4 font-bold text-primary">{{ $p->kode_pengajuan }}</td>
                                <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->pemohon->name }}</p><p class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</p></td>
                                <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->nama_barang }}</p>@if ($p->spesifikasi)<p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($p->spesifikasi, 60) }}</p>@endif</td>
                                <td class="whitespace-nowrap px-3 py-4"><span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $p->kategori->label() }}</span></td>
                                <td class="whitespace-nowrap px-3 py-4 text-right">{{ $p->kuantitas }} {{ $p->satuan->label() }}</td>
                                <td class="whitespace-nowrap px-3 py-4 text-right font-bold">@rupiah($p->total_estimasi_biaya)</td>
                                <td class="px-3 py-4"><x-status-badge :status="$p->status" bungkus /></td>
                                @if ($perluRevisi)
                                    <td class="sticky right-0 bg-white px-3 py-4 text-right">
                                        <button type="button" class="inline-flex whitespace-nowrap rounded-md bg-red-50 px-3 py-1.5 text-xs font-bold text-danger hover:bg-red-100" @click="$dispatch('buka-keluarkan-{{ $p->id }}')">Keluarkan</button>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if ($perluRevisi)
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-5 py-4">
                <p class="text-sm"><strong>{{ $jumlahRequest }}</strong> request aktif &middot; <strong class="text-primary">@rupiah($batch->totalEstimasi())</strong></p>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-danger" @click="$dispatch('buka-batalkan-batch')">Batalkan Batch</button>
                    <button type="button" class="btn btn-primary" @click="$dispatch('buka-kirim-ulang')" @disabled($jumlahRequest === 0)>Kirim Ulang ke PPK</button>
                </div>
            </div>
        @endif
    </div>

    @if ($perluRevisi)
        @foreach ($batch->pengajuan as $p)
            <x-modal :id="'keluarkan-'.$p->id" :judul="'Keluarkan '.$p->kode_pengajuan.'?'">
                Request akan dikembalikan ke <strong>{{ $p->pemohon->name }}</strong> untuk diperbaiki dan keluar dari batch ini.
                <form id="form-keluarkan-{{ $p->id }}" method="POST" action="{{ route('batch.keluarkan', [$batch, $p]) }}" class="mt-3">
                    @csrf
                    <label for="catatan-{{ $p->id }}" class="label">Catatan untuk pemohon (wajib)</label>
                    <textarea id="catatan-{{ $p->id }}" name="catatan" rows="3" maxlength="1000" required class="input" placeholder="Apa yang perlu diperbaiki?"></textarea>
                </form>
                <x-slot:aksi>
                    <button type="submit" form="form-keluarkan-{{ $p->id }}" class="btn btn-danger">Keluarkan &amp; Kembalikan</button>
                </x-slot:aksi>
            </x-modal>
        @endforeach

        <x-modal id="kirim-ulang" judul="Kirim ulang batch ke PPK?">
            {{ $jumlahRequest }} request dengan total <strong>@rupiah($batch->totalEstimasi())</strong> akan dikirim ulang ke PPK.
            <x-slot:aksi>
                <form method="POST" action="{{ route('batch.kirim-ulang', $batch) }}" x-data="{ kirim: false }" @submit="setTimeout(() => kirim = true, 10)">@csrf<button class="btn btn-primary" :disabled="kirim">Ya, Kirim Ulang</button></form>
            </x-slot:aksi>
        </x-modal>

        <x-modal id="batalkan-batch" judul="Batalkan batch ini?">
            Batch dibatalkan dan seluruh request di dalamnya kembali ke antrean <strong>Siap Batching</strong> agar bisa dibatch ulang.
            <x-slot:aksi>
                <form method="POST" action="{{ route('batch.batalkan', $batch) }}" x-data="{ kirim: false }" @submit="setTimeout(() => kirim = true, 10)">@csrf<button class="btn btn-danger" :disabled="kirim">Ya, Batalkan</button></form>
            </x-slot:aksi>
        </x-modal>
    @endif
@endsection
