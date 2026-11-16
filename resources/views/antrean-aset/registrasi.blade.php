@extends('layouts.app')

@section('title', 'Registrasi & Pelabelan Aset')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Draft Antrean Aset', route('inventaris.antrean')], ['Registrasi Aset']]" />
@endsection

@php
    $p = $pengajuan;
    $awalUnit = collect(range(0, $jumlah - 1))->map(fn ($i) => [
        'nama_penanggung_jawab' => old("unit.$i.nama_penanggung_jawab", ''),
        'lokasi' => old("unit.$i.lokasi", ''),
        'kondisi' => old("unit.$i.kondisi", 'baik'),
    ])->all();
@endphp

@section('content')
    <x-page-header judul="Registrasi & Pelabelan Aset" subjudul="Daftarkan setiap unit fisik dengan kode SKU unik, penanggung jawab, dan lokasi." />

    <section class="card mb-6 p-6">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Nomor request</p><p class="text-lg font-bold text-primary">{{ $p->kode_pengajuan }}</p></div>
            <p class="text-xs text-slate-500">Ditutup {{ $p->updated_at->translatedFormat('d M Y, H:i') }} WITA</p>
        </div>
        <dl class="mt-4 grid gap-4 border-t border-slate-100 pt-4 text-sm sm:grid-cols-3">
            <div><dt class="text-xs text-slate-500">Nama &amp; spesifikasi</dt><dd class="font-bold">{{ $p->nama_barang }}</dd><dd class="text-xs text-slate-500">{{ $p->spesifikasi ?: '—' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Pemohon &amp; divisi</dt><dd class="font-bold">{{ $p->pemohon->name }}</dd><dd class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</dd></div>
            <div><dt class="text-xs text-slate-500">Jumlah &amp; satuan</dt><dd class="font-bold">{{ $jumlah }} {{ $p->satuan->label() }}</dd></div>
        </dl>
    </section>

    <form id="form-registrasi" method="POST" action="{{ route('aset.registrasi.simpan', $p) }}"
          x-data="registrasiAset({ unit: @js($awalUnit) })" @submit="setTimeout(() => kirim = true, 10)">
        @csrf
        @error('unit')<p class="field-error mb-2" role="alert">{{ $message }}</p>@enderror

        <div class="card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-3">
                <h2 class="font-display text-lg font-bold text-slate-900">Registrasi per Unit</h2>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <span>Format SKU: <code class="rounded bg-slate-100 px-1.5 py-0.5 font-bold text-slate-800">AST-{{ now()->format('Y') }}-XXXX</code></span>
                    @if ($jumlah > 1)<button type="button" class="font-bold text-secondary hover:underline" @click="samakan()">Samakan semua dengan unit #1</button>@endif
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[52rem] text-[13px]">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        <tr><th class="px-3 py-3">Unit</th><th class="px-3 py-3">SKU</th><th class="px-3 py-3">Barcode</th><th class="min-w-56 px-3 py-3">Penanggung jawab</th><th class="min-w-48 px-3 py-3">Lokasi awal</th><th class="px-3 py-3">Kondisi</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($skuPratinjau as $i => $sku)
                            <tr class="bg-white align-top">
                                <td class="whitespace-nowrap px-3 py-4"><span class="rounded bg-sky-50 px-2 py-1 text-xs font-bold text-primary">Unit #{{ $i + 1 }}</span></td>
                                <td class="px-3 py-4"><span class="inline-block rounded-md bg-slate-100 px-3 py-2 font-mono text-sm font-bold">{{ $sku }}</span></td>
                                <td class="px-3 py-4"><svg data-barcode="{{ $sku }}" class="h-14 w-40" aria-label="Barcode {{ $sku }}"></svg></td>
                                <td class="px-3 py-4">
                                    <input type="text" name="unit[{{ $i }}][nama_penanggung_jawab]" x-model="unit[{{ $i }}].nama_penanggung_jawab" maxlength="150" required
                                           placeholder="Nama pejabat atau staf" aria-label="Penanggung jawab unit {{ $i + 1 }}" @class(['input', 'input-error' => $errors->has("unit.$i.nama_penanggung_jawab")])>
                                    @error("unit.$i.nama_penanggung_jawab")<p class="field-error" role="alert">{{ $message }}</p>@enderror
                                </td>
                                <td class="px-3 py-4">
                                    <input type="text" name="unit[{{ $i }}][lokasi]" x-model="unit[{{ $i }}].lokasi" maxlength="150" required list="daftar-lokasi"
                                           placeholder="Ruangan atau gedung" aria-label="Lokasi unit {{ $i + 1 }}" @class(['input', 'input-error' => $errors->has("unit.$i.lokasi")])>
                                    @error("unit.$i.lokasi")<p class="field-error" role="alert">{{ $message }}</p>@enderror
                                </td>
                                <td class="px-3 py-4">
                                    <select name="unit[{{ $i }}][kondisi]" x-model="unit[{{ $i }}].kondisi" aria-label="Kondisi unit {{ $i + 1 }}" class="input">
                                        <option value="baik">Baik</option><option value="rusak">Rusak</option>
                                    </select>
                                    @error("unit.$i.kondisi")<p class="field-error" role="alert">{{ $message }}</p>@enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <datalist id="daftar-lokasi">@foreach ($lokasiLama as $l)<option value="{{ $l }}">@endforeach</datalist>

            <div class="flex items-start gap-3 border-t border-slate-100 bg-sky-50 px-5 py-4 text-sm text-slate-700">
                <x-icon name="info" class="mt-0.5 size-5 shrink-0 text-primary" />
                <p>SKU final ditetapkan otomatis saat disimpan. Setelah itu cetak label dan tempelkan pada barang sebelum didistribusikan ke pengguna.</p>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap justify-end gap-2">
            <a href="{{ route('inventaris.antrean') }}" class="btn btn-ghost">Batal</a>
            <button type="button" class="btn btn-primary" :disabled="kirim" @click="$dispatch('buka-konfirmasi-registrasi')">Simpan Semua Aset</button>
        </div>
    </form>

    <x-modal id="konfirmasi-registrasi" judul="Simpan {{ $jumlah }} aset?">
        {{ $jumlah }} unit <strong>{{ $p->nama_barang }}</strong> akan didaftarkan dengan SKU otomatis. Request ini keluar dari antrean registrasi.
        <x-slot:aksi>
            <button type="submit" form="form-registrasi" class="btn btn-primary">Ya, Simpan Aset</button>
        </x-slot:aksi>
    </x-modal>
@endsection
