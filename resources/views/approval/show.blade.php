@extends('layouts.app')

@section('title', 'Tinjau Request')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Approval Tahap 1', route('approval.index')], [$pengajuan->kode_pengajuan]]" />
@endsection

@php
    $p = $pengajuan;
    $bisaDiputuskan = $p->status === \App\Enums\StatusPengajuan::Diajukan;
@endphp

@section('content')
    <a href="{{ route('approval.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-primary">&larr; Kembali ke Approval Tahap 1</a>

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-bold text-slate-900">Tinjau Request {{ $p->kode_pengajuan }}</h1>
            <p class="mt-1 text-sm text-slate-500">Periksa kelayakan teknis, kuantitas, dan estimasi biaya sebelum dimasukkan ke batch.</p>
        </div>
        <x-status-badge :status="$p->status" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card p-6 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-bold text-slate-900">Rincian Permohonan Barang</h2>
                <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ $p->kategori->label() }}</span>
            </div>
            <dl class="mt-4 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-3">
                <div><dt class="text-xs text-slate-500">Pemohon</dt><dd class="font-bold">{{ $p->pemohon->name }}</dd></div>
                <div><dt class="text-xs text-slate-500">Divisi</dt><dd class="font-bold">{{ $p->pemohon->divisi ?? '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Waktu pengajuan</dt><dd class="font-bold">{{ $p->created_at->translatedFormat('d M Y, H:i') }} WITA</dd></div>
                <div class="sm:col-span-3"><dt class="text-xs text-slate-500">Nama barang</dt><dd class="text-lg font-bold">{{ $p->nama_barang }}</dd></div>
                <div class="sm:col-span-3"><dt class="text-xs text-slate-500">Spesifikasi &amp; keperluan</dt><dd class="whitespace-pre-line font-medium">{{ $p->spesifikasi ?: '—' }}</dd></div>
                <div><dt class="text-xs text-slate-500">Kuantitas</dt><dd class="font-bold">{{ $p->kuantitas }} {{ $p->satuan->label() }}</dd></div>
                <div><dt class="text-xs text-slate-500">Harga estimasi satuan</dt><dd class="font-bold">@rupiah($p->harga_estimasi)</dd></div>
                <div><dt class="text-xs text-slate-500">Total estimasi biaya</dt><dd class="font-display text-xl font-bold text-primary">@rupiah($p->total_estimasi_biaya)</dd></div>
            </dl>
        </section>

        <aside class="card h-fit p-6">
            <h2 class="font-display text-xl font-bold text-slate-900">Riwayat &amp; Status</h2>
            <ol class="mt-4 space-y-5 border-l-2 border-secondary/40 pl-5">
                @foreach ($p->riwayat->reverse() as $r)
                    <li class="relative">
                        <span class="absolute -left-[1.62rem] top-1 size-3 rounded-full border-2 border-white bg-secondary"></span>
                        <p class="text-sm font-bold text-slate-900">{{ \App\Enums\StatusPengajuan::tryFrom($r->status_ke)?->label() ?? $r->status_ke }}</p>
                        <p class="text-xs text-slate-500">{{ $r->aktor->name }} &middot; {{ $r->aktor->role->label() }}</p>
                        <p class="text-xs text-slate-500">{{ $r->created_at->translatedFormat('d M Y, H:i') }} WITA</p>
                        @if ($r->catatan)<p class="mt-1 whitespace-pre-line rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-700">{{ $r->catatan }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>

    {{-- Keputusan --}}
    <section class="card mt-6 p-6">
        @if ($bisaDiputuskan)
            <h2 class="font-display text-xl font-bold text-slate-900">Keputusan Tahap 1</h2>
            <p class="mt-1 text-sm text-slate-500">Setujui untuk meneruskan ke batching, atau kembalikan ke pemohon bila rincian perlu diperbaiki.</p>

            <form id="form-kembalikan" method="POST" action="{{ route('approval.kembalikan', $p) }}"
                  x-data="{ catatan: @js(old('catatan', '')), kirim: false }" @submit="setTimeout(() => kirim = true, 10)" class="mt-4">
                @csrf
                <label for="catatan" class="label">Catatan revisi (wajib jika dikembalikan)</label>
                <textarea id="catatan" name="catatan" rows="3" maxlength="1000" x-model="catatan"
                          placeholder="Tuliskan alasan dan perbaikan yang diperlukan..."
                          @class(['input', 'input-error' => $errors->has('catatan')])></textarea>
                @error('catatan')<p class="field-error" role="alert">{{ $message }}</p>@enderror

                <div class="mt-5 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-5">
                    <a href="{{ route('approval.index') }}" class="btn btn-ghost">Batal</a>
                    <button type="submit" class="btn btn-danger" :disabled="kirim || catatan.trim() === ''">Kembalikan untuk Revisi</button>
                    <button type="button" class="btn btn-primary" @click="$dispatch('buka-konfirmasi-setujui')">Setujui Request</button>
                </div>
            </form>

            <x-modal id="konfirmasi-setujui" judul="Setujui request ini?">
                Request <strong>{{ $p->kode_pengajuan }}</strong> akan masuk daftar <strong>Siap Batching</strong> untuk digabungkan ke batch.
                <div class="mt-3 rounded-lg bg-slate-50 p-3 text-xs text-slate-700">
                    <p class="font-bold">{{ $p->kuantitas }} {{ $p->satuan->label() }} {{ $p->nama_barang }}</p>
                    <p>Pemohon: {{ $p->pemohon->name }} ({{ $p->pemohon->divisi ?? '—' }})</p>
                    <p class="mt-1 font-display text-lg font-bold text-primary">@rupiah($p->total_estimasi_biaya)</p>
                </div>
                <x-slot:aksi>
                    <form method="POST" action="{{ route('approval.setujui', $p) }}" x-data="{ kirim: false }" @submit="setTimeout(() => kirim = true, 10)">
                        @csrf
                        <button class="btn btn-primary" :disabled="kirim">Ya, Setujui</button>
                    </form>
                </x-slot:aksi>
            </x-modal>
        @else
            <div class="flex items-center gap-2 text-sm text-slate-600">
                <x-icon name="info" class="size-5 shrink-0 text-primary" />
                Request ini sudah diproses dan berstatus <strong>{{ $p->status->label() }}</strong>.
            </div>
        @endif
    </section>
@endsection
