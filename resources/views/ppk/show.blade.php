@extends('layouts.app')

@section('title', 'Tinjau Batch')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Persetujuan Batch', route('ppk.batch.index')], [$batch->kode_batch]]" />
@endsection

@php
    $menunggu = $batch->status === \App\Enums\StatusBatch::PendingPpk;
    $jumlahRequest = $batch->pengajuan->count();
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <h1 class="font-display text-3xl font-bold text-slate-900">Tinjau Batch {{ $batch->kode_batch }}</h1>
        <x-batch-badge :status="$batch->status" />
    </div>

    <div class="mb-5 flex items-start gap-3 rounded-lg border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-slate-700">
        <x-icon name="info" class="mt-0.5 size-5 shrink-0 text-primary" />
        <p><strong>Keputusan per batch.</strong> Persetujuan atau pengembalian oleh PPK berlaku untuk seluruh request dalam batch, bukan per request.</p>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Periode</p><p class="mt-1 font-bold">{{ $batch->periode->label() }}</p><p class="text-xs text-slate-500">{{ $batch->created_at->translatedFormat('F Y') }}</p></div>
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Dibuat oleh</p><p class="mt-1 font-bold">{{ $batch->pembuat->name }}</p><p class="text-xs text-slate-500">{{ $batch->pembuat->role->label() }}</p></div>
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal dikirim</p><p class="mt-1 font-bold">{{ $batch->updated_at->translatedFormat('d M Y') }}</p><p class="text-xs text-slate-500">{{ $batch->updated_at->format('H:i') }} WITA</p></div>
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Jumlah request</p><p class="mt-1 font-display text-2xl font-bold">{{ $jumlahRequest }}</p></div>
        <div class="rounded-xl bg-primary p-4 text-white shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-white/70">Total estimasi</p><p class="mt-1 font-display text-2xl font-bold">@rupiah($batch->totalEstimasi())</p></div>
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-3">
            <h2 class="font-display text-lg font-bold text-slate-900">Daftar Request dalam Batch</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[48rem] text-[13px]">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-3">Kode request</th><th class="px-3 py-3">Pemohon &amp; Divisi</th><th class="min-w-48 px-3 py-3">Barang &amp; Spesifikasi</th>
                        <th class="px-3 py-3">Kategori</th><th class="px-3 py-3 text-right">Kuantitas</th><th class="px-3 py-3 text-right">Harga satuan</th><th class="px-3 py-3 text-right">Total estimasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($batch->pengajuan as $p)
                        <tr class="bg-white">
                            <td class="whitespace-nowrap px-3 py-4 font-bold text-primary">{{ $p->kode_pengajuan }}</td>
                            <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->pemohon->name }}</p><p class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</p></td>
                            <td class="px-3 py-4"><p class="font-bold text-slate-900">{{ $p->nama_barang }}</p>@if ($p->spesifikasi)<p class="text-xs text-slate-500">{{ \Illuminate\Support\Str::limit($p->spesifikasi, 90) }}</p>@endif</td>
                            <td class="whitespace-nowrap px-3 py-4"><span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $p->kategori->label() }}</span></td>
                            <td class="whitespace-nowrap px-3 py-4 text-right">{{ $p->kuantitas }} {{ $p->satuan->label() }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-right">@rupiah($p->harga_estimasi)</td>
                            <td class="whitespace-nowrap px-3 py-4 text-right font-bold">@rupiah($p->total_estimasi_biaya)</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-sky-50">
                        <td colspan="6" class="px-3 py-4 font-bold">Akumulasi seluruh batch ({{ $jumlahRequest }} request)</td>
                        <td class="whitespace-nowrap px-3 py-4 text-right font-display text-lg font-bold text-primary">@rupiah($batch->totalEstimasi())</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <section class="card mt-6 p-6">
        @if ($menunggu)
            <h2 class="font-display text-xl font-bold text-slate-900">Lembar Keputusan PPK</h2>
            <form id="form-kembalikan" method="POST" action="{{ route('ppk.batch.kembalikan', $batch) }}"
                  x-data="{ catatan: @js(old('catatan', '')), kirim: false }" @submit="setTimeout(() => kirim = true, 10)" class="mt-4">
                @csrf
                <div class="flex items-end justify-between gap-3">
                    <label for="catatan" class="label !mb-0">Catatan (wajib jika dikembalikan; sebutkan kode request yang bermasalah)</label>
                    <span class="shrink-0 text-xs text-slate-500"><span x-text="catatan.length"></span>/1000</span>
                </div>
                <textarea id="catatan" name="catatan" rows="3" maxlength="1000" x-model="catatan"
                          placeholder="Tuliskan alasan pengembalian batch serta kode request yang memerlukan revisi spesifikasi atau anggaran..."
                          @class(['input mt-1.5', 'input-error' => $errors->has('catatan')])></textarea>
                @error('catatan')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                <p class="field-hint">Catatan wajib diisi sebelum menekan tombol Kembalikan Batch.</p>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                    <p class="flex items-center gap-2 text-sm text-slate-600"><x-icon name="centang" class="size-5 text-primary" /> Keputusan dicatat atas nama <strong>{{ auth()->user()->name }}</strong></p>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-danger" :disabled="kirim || catatan.trim() === ''" @click="$dispatch('buka-konfirmasi-kembalikan')">Kembalikan Batch</button>
                        <button type="button" class="btn btn-primary" @click="$dispatch('buka-konfirmasi-setujui')">Setujui Batch</button>
                    </div>
                </div>
            </form>

            <x-modal id="konfirmasi-setujui" judul="Setujui batch ini?">
                Batch <strong>{{ $batch->kode_batch }}</strong> berisi <strong>{{ $jumlahRequest }} request</strong> dengan total <strong>@rupiah($batch->totalEstimasi())</strong>. Seluruh request akan berstatus <strong>Disetujui PPK</strong> dan menunggu pencairan dana.
                <x-slot:aksi>
                    <form method="POST" action="{{ route('ppk.batch.setujui', $batch) }}" x-data="{ kirim: false }" @submit="setTimeout(() => kirim = true, 10)">@csrf<button class="btn btn-primary" :disabled="kirim">Ya, Setujui Batch</button></form>
                </x-slot:aksi>
            </x-modal>

            <x-modal id="konfirmasi-kembalikan" judul="Kembalikan batch ini?">
                Batch dikembalikan ke Pejabat Pengadaan beserta catatan Anda. Request di dalamnya kembali ke status <strong>Siap Batching</strong> sampai batch diperbaiki dan dikirim ulang.
                <x-slot:aksi>
                    <button type="submit" form="form-kembalikan" class="btn btn-danger">Ya, Kembalikan Batch</button>
                </x-slot:aksi>
            </x-modal>
        @else
            <h2 class="font-display text-xl font-bold text-slate-900">Keputusan</h2>
            @if ($batch->diputuskan_oleh)
                <p class="mt-2 text-sm text-slate-700">
                    Diputuskan oleh <strong>{{ $batch->pemutus->name }}</strong> pada {{ $batch->diputuskan_pada->translatedFormat('d M Y, H:i') }} WITA.
                    Status batch saat ini: <strong>{{ $batch->status->label() }}</strong>.
                </p>
                @if ($batch->catatan_revisi)
                    <p class="mt-3 whitespace-pre-line rounded-lg bg-red-50 px-4 py-3 text-sm text-slate-700">{{ $batch->catatan_revisi }}</p>
                @endif
            @else
                <p class="mt-2 text-sm text-slate-600">Batch berstatus <strong>{{ $batch->status->label() }}</strong> dan tidak menunggu keputusan PPK.</p>
            @endif
        @endif
    </section>
@endsection
