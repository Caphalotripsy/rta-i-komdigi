@extends('layouts.app')

@section('title', 'Konfirmasi Pencairan Dana')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Pencairan Dana', route('pencairan.index')], [$pengajuan->kode_pengajuan]]" />
@endsection

@php
    $p = $pengajuan;
    $bisa = $p->status === \App\Enums\StatusPengajuan::Disetujui;
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-bold text-slate-900">Konfirmasi Pencairan Dana</h1>
            <p class="mt-1 text-sm text-slate-500">Catat penyerahan dana untuk request yang telah disetujui PPK.</p>
        </div>
        <x-status-badge :status="$p->status" />
    </div>

    <section class="card mb-6 p-6">
        <h2 class="font-display text-xl font-bold text-slate-900">Rincian Request</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Nomor request</p><p class="mt-1 font-bold text-primary">{{ $p->kode_pengajuan }}</p></div>
            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pemohon &amp; divisi</p><p class="mt-1 font-bold">{{ $p->pemohon->name }}</p><p class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</p></div>
            <div class="rounded-lg bg-slate-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Barang</p><p class="mt-1 font-bold">{{ $p->nama_barang }}</p><p class="text-xs text-slate-500">{{ $p->kuantitas }} {{ $p->satuan->label() }}@if ($p->spesifikasi) &middot; {{ \Illuminate\Support\Str::limit($p->spesifikasi, 70) }}@endif</p></div>
            <div class="rounded-lg bg-sky-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Total disetujui</p><p class="mt-1 font-display text-2xl font-bold text-primary">@rupiah($p->total_estimasi_biaya)</p><p class="text-xs text-slate-500">Batch {{ $p->batch?->kode_batch ?? '—' }}</p></div>
        </div>
    </section>

    @if ($bisa)
        <form id="form-pencairan" method="POST" action="{{ route('pencairan.store', $p) }}" class="card p-6"
              x-data="{ metode: @js(old('metode_pencairan', 'transfer')), kirim: false }" @submit="setTimeout(() => kirim = true, 10)">
            @csrf
            <h2 class="font-display text-xl font-bold text-slate-900">Formulir Pencairan</h2>

            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="label">Dana dicairkan</label>
                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                        <span class="font-display text-lg font-bold text-slate-900">@rupiah($p->total_estimasi_biaya)</span>
                        <span class="text-[11px] font-bold uppercase tracking-wide text-secondary">Sesuai estimasi yang disetujui</span>
                    </div>
                </div>
                <div>
                    <label class="label">Penerima dana</label>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-bold">{{ $p->pemohon->name }} &middot; {{ $p->pemohon->divisi ?? '—' }}</div>
                </div>

                <fieldset>
                    <legend class="label">Metode penyerahan dana <span class="text-danger">*</span></legend>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($metode as $m)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2.5 text-sm font-bold transition"
                                   :class="metode === '{{ $m->value }}' ? 'border-primary bg-primary text-white' : 'border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100'">
                                <input type="radio" name="metode_pencairan" value="{{ $m->value }}" x-model="metode" class="sr-only">
                                {{ $m->label() }}
                            </label>
                        @endforeach
                    </div>
                    @error('metode_pencairan')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </fieldset>

                <div>
                    <label for="tanggal_pencairan" class="label">Tanggal pencairan <span class="text-danger">*</span></label>
                    <input id="tanggal_pencairan" name="tanggal_pencairan" type="date" required max="{{ now()->toDateString() }}"
                           value="{{ old('tanggal_pencairan', now()->toDateString()) }}" @class(['input', 'input-error' => $errors->has('tanggal_pencairan')])>
                    @error('tanggal_pencairan')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label for="referensi_pencairan" class="label">Nomor referensi / keterangan (opsional)</label>
                    <input id="referensi_pencairan" name="referensi_pencairan" type="text" maxlength="100" value="{{ old('referensi_pencairan') }}"
                           placeholder="Contoh: nomor bukti transfer atau nomor kas" @class(['input', 'input-error' => $errors->has('referensi_pencairan')])>
                    @error('referensi_pencairan')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                <p class="flex items-center gap-2 text-sm text-slate-600"><x-icon name="centang" class="size-5 text-primary" /> Dicatat atas nama <strong>{{ auth()->user()->name }}</strong></p>
                <div class="flex gap-2">
                    <a href="{{ route('pencairan.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="button" class="btn btn-primary" :disabled="kirim" @click="$dispatch('buka-konfirmasi-pencairan')">Konfirmasi Pencairan</button>
                </div>
            </div>
        </form>

        <x-modal id="konfirmasi-pencairan" judul="Catat pencairan dana?">
            Dana <strong>@rupiah($p->total_estimasi_biaya)</strong> untuk <strong>{{ $p->pemohon->name }}</strong> akan dicatat sebagai dicairkan. Request berstatus <strong>Dana Dicairkan</strong> dan tindakan ini tidak dapat dibatalkan.
            <x-slot:aksi>
                <button type="submit" form="form-pencairan" class="btn btn-primary">Ya, Catat Pencairan</button>
            </x-slot:aksi>
        </x-modal>
    @else
        <div class="card flex items-center gap-2 p-6 text-sm text-slate-600">
            <x-icon name="info" class="size-5 shrink-0 text-primary" />
            Request ini berstatus <strong>{{ $p->status->label() }}</strong> dan tidak menunggu pencairan.
        </div>
    @endif
@endsection
