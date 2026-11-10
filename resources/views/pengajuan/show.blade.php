@extends('layouts.app')

@section('title', 'Detail Request')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Request Saya', route('pengajuan.index')], [$pengajuan->kode_pengajuan]]" />
@endsection

@php
    $p = $pengajuan;
    $nilai = fn ($v, $kosong = 'Belum dilaporkan') => ($v === null || $v === '') ? $kosong : $v;
    $rp = fn ($v) => $v === null ? 'Belum dilaporkan' : 'Rp '.number_format((float) $v, 0, ',', '.');
@endphp

@section('content')
    <a href="{{ route('pengajuan.index') }}" class="mb-3 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-primary">&larr; Kembali ke Request Saya</a>

    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-bold tracking-wide text-slate-500">{{ $p->kode_pengajuan }}</p>
            <h1 class="mt-1 font-display text-3xl font-bold text-slate-900">{{ $p->nama_barang }}</h1>
            <div class="mt-2"><x-status-badge :status="$p->status" /></div>
        </div>

        {{-- Aksi sesuai status --}}
        <div class="flex flex-wrap items-center gap-2">
            @can('update', $p)
                <a href="{{ route('pengajuan.edit', $p) }}" class="btn btn-outline">{{ $p->status === \App\Enums\StatusPengajuan::Direvisi ? 'Revisi' : 'Ubah' }}</a>
                @if ($p->status === \App\Enums\StatusPengajuan::Draft)
                    <form method="POST" action="{{ route('pengajuan.ajukan', $p) }}" x-data="{ kirim: false }" @submit="setTimeout(() => kirim = true, 10)">
                        @csrf
                        <button class="btn btn-primary" :disabled="kirim">Kirim</button>
                    </form>
                @endif
            @endcan
            @can('lapor', $p)
                <a href="{{ route('pengajuan.lapor', $p) }}" class="btn btn-primary">
                    {{ $p->status === \App\Enums\StatusPengajuan::Direvisi ? 'Perbaiki Laporan' : 'Lapor Harga Aktual' }}
                </a>
            @endcan
            @can('delete', $p)
                <form method="POST" action="{{ route('pengajuan.destroy', $p) }}" onsubmit="return confirm('Hapus draft ini?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger">Hapus Draft</button>
                </form>
            @endcan
        </div>
    </div>

    {{-- Info posisi request; tampil bila tidak ada tombol aksi yang relevan --}}
    @if (! $revisi && ! $p->bisaDiedit() && ! $p->bisaLaporHarga())
        <div class="mb-5 flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
            <x-icon name="info" class="size-5 shrink-0 text-primary" />
            {{ $p->status->infoUntukPemohon() }}
        </div>
    @endif

    @if ($revisi)
        <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm">
            <p class="font-bold text-danger">
                Dikembalikan oleh {{ $revisi->aktor->role->label() }} ({{ $revisi->aktor->name }}) &middot; {{ $revisi->created_at->translatedFormat('d M Y, H:i') }} WITA
            </p>
            @if ($revisi->jenisMasalahLabel())
                <p class="mt-1"><span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-danger">{{ $revisi->jenisMasalahLabel() }}</span></p>
            @endif
            <p class="mt-2 whitespace-pre-line text-slate-700">{{ $revisi->catatan }}</p>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card p-6">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-xl font-bold text-slate-900">Rincian Pengajuan</h2>
                    <span class="text-xs text-slate-500">Diperbarui {{ $p->updated_at->translatedFormat('d M Y') }}</span>
                </div>
                <dl class="mt-4 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">Pemohon</dt><dd class="font-bold">{{ $p->pemohon->name }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Divisi</dt><dd class="font-bold">{{ $p->pemohon->divisi ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Kategori</dt><dd class="font-bold">{{ $p->kategori->label() }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Kuantitas diajukan</dt><dd class="font-bold">{{ $p->kuantitas }} {{ $p->satuan->label() }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Spesifikasi &amp; keperluan</dt><dd class="font-bold whitespace-pre-line">{{ $nilai($p->spesifikasi, '—') }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Harga estimasi satuan</dt><dd class="font-bold">@rupiah($p->harga_estimasi)</dd></div>
                    <div><dt class="text-xs text-slate-500">Total estimasi</dt><dd class="font-bold">@rupiah($p->total_estimasi_biaya)</dd></div>
                </dl>
            </section>

            <section class="card p-6">
                <h2 class="font-display text-xl font-bold text-slate-900">Realisasi &amp; Pengadaan</h2>
                <dl class="mt-4 grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-xs text-slate-500">Batch</dt><dd class="font-bold">{{ $p->batch?->kode_batch ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Dana dicairkan</dt><dd class="font-bold">{{ $p->dana_dicairkan === null ? '—' : 'Rp '.number_format((float) $p->dana_dicairkan, 0, ',', '.') }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Kuantitas terbeli</dt><dd class="font-bold">{{ $p->kuantitas_terbeli === null ? 'Belum dilaporkan' : $p->kuantitas_terbeli.' '.$p->satuan->label() }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Harga aktual satuan</dt><dd class="font-bold">{{ $rp($p->harga_aktual) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Total biaya aktual</dt><dd class="font-bold">{{ $rp($p->total_biaya_aktual) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Sisa dana</dt><dd class="font-bold">{{ $p->sisa_dana === null ? '—' : 'Rp '.number_format((float) $p->sisa_dana, 0, ',', '.') }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-slate-500">Status pengembalian dana</dt><dd class="font-bold">{{ $p->labelPengembalianDana() }}</dd></div>
                </dl>

                <div class="mt-5 rounded-lg bg-sky-50 p-4">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-primary">Data nota</h3>
                    <dl class="mt-2 grid gap-4 text-sm sm:grid-cols-3">
                        <div><dt class="text-xs text-slate-500">Nama toko</dt><dd class="font-bold">{{ $nilai($p->nama_toko, '—') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Nomor nota</dt><dd class="font-bold">{{ $nilai($p->nomor_nota, '—') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Tanggal nota</dt><dd class="font-bold">{{ $p->tanggal_nota?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                    </dl>
                </div>
            </section>
        </div>

        {{-- Riwayat: terbaru di atas --}}
        <aside class="card h-fit p-6">
            <h2 class="font-display text-xl font-bold text-slate-900">Timeline Proses</h2>
            <ol class="mt-4 space-y-5 border-l-2 border-secondary/40 pl-5">
                @foreach ($p->riwayat->reverse() as $r)
                    <li class="relative">
                        <span class="absolute -left-[1.62rem] top-1 size-3 rounded-full border-2 border-white bg-secondary"></span>
                        <p class="text-sm font-bold text-slate-900">{{ \App\Enums\StatusPengajuan::tryFrom($r->status_ke)?->label() ?? $r->status_ke }}</p>
                        <p class="text-xs text-slate-500">{{ $r->aktor->name }} &middot; {{ $r->aktor->role->label() }}</p>
                        <p class="text-xs text-slate-500">{{ $r->created_at->translatedFormat('d M Y, H:i') }} WITA</p>
                        @if ($r->jenisMasalahLabel())
                            <p class="mt-1"><span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-bold text-danger">{{ $r->jenisMasalahLabel() }}</span></p>
                        @endif
                        @if ($r->catatan)
                            <p class="mt-1 rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-700 whitespace-pre-line">{{ $r->catatan }}</p>
                        @endif
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>
@endsection
