@extends('layouts.app')

@section('title', 'Detail & Mutasi Aset')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Master Inventory', route('master.index')], [$aset->sku_barcode]]" />
@endsection

@section('content')
    <x-page-header judul="Detail & Mutasi Aset" :subjudul="$aset->sku_barcode.' · '.$aset->inventaris->nama_barang">
        <x-slot:aksi>
            <a href="{{ route('cetak.label', ['aset' => $aset->id]) }}" target="_blank" class="btn btn-outline">Cetak Label</a>
        </x-slot:aksi>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <dl class="grid flex-1 gap-4 text-sm sm:grid-cols-2">
                        <div><dt class="text-xs text-slate-500">SKU</dt><dd class="font-mono font-bold">{{ $aset->sku_barcode }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Nama barang</dt><dd class="font-bold">{{ $aset->inventaris->nama_barang }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Asal request</dt><dd class="font-bold">{{ $aset->pengajuan->kode_pengajuan }} &middot; {{ $aset->pengajuan->pemohon->name }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Tanggal masuk</dt><dd class="font-bold">{{ $aset->created_at->translatedFormat('d M Y') }}</dd></div>
                        <div><dt class="text-xs text-slate-500">Kondisi</dt><dd><x-kondisi-badge :kondisi="$aset->kondisi" /></dd></div>
                        <div><dt class="text-xs text-slate-500">Status</dt><dd class="font-bold">{{ $aset->aktif() ? 'Aktif' : 'Tidak aktif' }}</dd></div>
                    </dl>
                    <div class="rounded-lg border border-slate-200 p-3"><svg data-barcode="{{ $aset->sku_barcode }}" class="h-16 w-44" aria-label="Barcode {{ $aset->sku_barcode }}"></svg></div>
                </div>
            </section>

            <section class="card p-6">
                <h2 class="font-display text-xl font-bold text-slate-900">Mutasi Aset</h2>
                <form id="form-mutasi" method="POST" action="{{ route('master.aset.mutasi', $aset) }}" x-data="{ kirim: false }" @submit="setTimeout(() => kirim = true, 10)" class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="lokasi" class="label">Lokasi <span class="text-danger">*</span></label>
                        <input id="lokasi" name="lokasi" type="text" maxlength="150" required list="daftar-lokasi" value="{{ old('lokasi', $aset->lokasi) }}" @class(['input', 'input-error' => $errors->has('lokasi')])>
                        <datalist id="daftar-lokasi">@foreach ($lokasiLama as $l)<option value="{{ $l }}">@endforeach</datalist>
                        @error('lokasi')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="nama_penanggung_jawab" class="label">Penanggung jawab <span class="text-danger">*</span></label>
                        <input id="nama_penanggung_jawab" name="nama_penanggung_jawab" type="text" maxlength="150" required value="{{ old('nama_penanggung_jawab', $aset->nama_penanggung_jawab) }}" @class(['input', 'input-error' => $errors->has('nama_penanggung_jawab')])>
                        @error('nama_penanggung_jawab')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="kondisi" class="label">Kondisi <span class="text-danger">*</span></label>
                        <select id="kondisi" name="kondisi" required class="input">
                            @foreach (\App\Enums\KondisiAset::cases() as $k)<option value="{{ $k->value }}" @selected(old('kondisi', $aset->kondisi->value) === $k->value)>{{ $k->label() }}</option>@endforeach
                        </select>
                        @error('kondisi')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="catatan" class="label">Catatan / alasan mutasi</label>
                        <input id="catatan" name="catatan" type="text" maxlength="500" value="{{ old('catatan') }}" class="input" placeholder="Opsional">
                    </div>
                    <div class="flex justify-end sm:col-span-2"><button class="btn btn-primary" :disabled="kirim">Simpan Mutasi</button></div>
                </form>
            </section>
        </div>

        <aside class="card h-fit p-6">
            <h2 class="font-display text-xl font-bold text-slate-900">Riwayat Mutasi</h2>
            <ol class="mt-4 space-y-5 border-l-2 border-secondary/40 pl-5">
                @foreach ($aset->mutasi as $m)
                    <li class="relative">
                        <span class="absolute -left-[1.62rem] top-1 size-3 rounded-full border-2 border-white bg-secondary"></span>
                        <p class="text-sm font-bold text-slate-900">{{ $m->catatan === 'Registrasi awal' ? 'Registrasi awal' : 'Mutasi' }}</p>
                        <p class="text-xs text-slate-500">{{ $m->created_at->translatedFormat('d M Y, H:i') }} WITA &middot; {{ $m->pelaksana->name }}</p>
                        <ul class="mt-1 space-y-0.5 text-xs text-slate-700">
                            @if ($m->lokasi_lama !== $m->lokasi_baru)<li>Lokasi: {{ $m->lokasi_lama ?? '—' }} &rarr; <strong>{{ $m->lokasi_baru }}</strong></li>@endif
                            @if ($m->penanggung_jawab_lama !== $m->penanggung_jawab_baru)<li>Penanggung jawab: {{ $m->penanggung_jawab_lama ?? '—' }} &rarr; <strong>{{ $m->penanggung_jawab_baru }}</strong></li>@endif
                            @if ($m->kondisi_lama !== $m->kondisi_baru)<li>Kondisi: {{ ucfirst($m->kondisi_lama ?? '—') }} &rarr; <strong>{{ ucfirst($m->kondisi_baru) }}</strong></li>@endif
                        </ul>
                        @if ($m->catatan && $m->catatan !== 'Registrasi awal')<p class="mt-1 rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-700">{{ $m->catatan }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>
@endsection
