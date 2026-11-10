@extends('layouts.app')

@section('title', 'Lapor Harga Aktual')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Request Saya', route('pengajuan.index')], [$pengajuan->kode_pengajuan, route('pengajuan.show', $pengajuan)], ['Lapor Harga Aktual']]" />
@endsection

@php $p = $pengajuan; @endphp

@section('content')
    <x-page-header judul="Lapor Harga Aktual" subjudul="Laporkan realisasi pembelian dan data nota untuk request {{ $p->kode_pengajuan }}." />

    @if ($revisi)
        <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm">
            <p class="font-bold text-danger">Dikembalikan oleh {{ $revisi->aktor->role->label() }} ({{ $revisi->aktor->name }})</p>
            @if ($revisi->jenisMasalahLabel())
                <p class="mt-1"><span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-danger">{{ $revisi->jenisMasalahLabel() }}</span></p>
            @endif
            <p class="mt-2 whitespace-pre-line text-slate-700">{{ $revisi->catatan }}</p>
        </div>
    @endif

    {{-- Ringkasan request --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Kode request</p><p class="mt-1 font-bold text-primary">{{ $p->kode_pengajuan }}</p></div>
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Barang</p><p class="mt-1 font-bold">{{ $p->nama_barang }}</p><p class="text-xs text-slate-500">{{ $p->kategori->label() }}</p></div>
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Estimasi awal</p><p class="mt-1 font-bold">@rupiah($p->total_estimasi_biaya)</p><p class="text-xs text-slate-500">{{ $p->kuantitas }} {{ $p->satuan->label() }} × @rupiah($p->harga_estimasi)</p></div>
        <div class="card p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Dana telah dicairkan</p><p class="mt-1 font-bold text-primary">@rupiah($p->danaDicairkanEfektif())</p><p class="text-xs text-slate-500">{{ $waktuPencairan ? \Illuminate\Support\Carbon::parse($waktuPencairan)->translatedFormat('d M Y, H:i').' WITA' : '—' }}</p></div>
    </div>

    <form id="form-lapor-kirim" method="POST" action="{{ route('pengajuan.lapor.simpan', $p) }}" class="grid gap-6 lg:grid-cols-5"
          x-data="laporHarga({
              kuantitas: @js(old('kuantitas_terbeli', $p->kuantitas_terbeli ?? $p->kuantitas)),
              harga: @js(old('harga_aktual', $p->harga_aktual !== null ? (float) $p->harga_aktual : null)),
              dana: @js($p->danaDicairkanEfektif()),
              maxKuantitas: @js($p->kuantitas),
              maxHarga: @js((float) $p->harga_estimasi),
          })"
          @submit="setTimeout(() => kirim = true, 10)">
        @csrf

        {{-- Formulir --}}
        <section class="card p-6 lg:col-span-3">
            <h2 class="font-display text-xl font-bold text-slate-900">Formulir Laporan Realisasi</h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="kuantitas_terbeli" class="label">Kuantitas terbeli <span class="text-danger">*</span></label>
                    <div class="relative">
                        <input id="kuantitas_terbeli" name="kuantitas_terbeli" type="number" min="1" max="{{ $p->kuantitas }}" required x-model.number="kuantitas"
                               @class(['input pr-16', 'input-error' => $errors->has('kuantitas_terbeli')])>
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-bold text-slate-500">{{ $p->satuan->label() }}</span>
                    </div>
                    <p class="field-hint">Maksimal {{ $p->kuantitas }} {{ $p->satuan->value }} sesuai persetujuan.</p>
                    <p class="field-error" x-show="melebihiKuantitas" x-cloak>Kuantitas terbeli tidak boleh melebihi kuantitas yang diminta.</p>
                    @error('kuantitas_terbeli')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="harga_aktual" class="label">Harga aktual per satuan (Rp) <span class="text-danger">*</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-slate-500">Rp</span>
                        <input id="harga_aktual" name="harga_aktual" type="number" min="1" step="any" required x-model.number="harga"
                               @class(['input pl-10', 'input-error' => $errors->has('harga_aktual')])>
                    </div>
                    <p class="field-hint">Maksimal @rupiah($p->harga_estimasi) per {{ $p->satuan->value }}. Isi angka saja.</p>
                    <p class="field-error" x-show="melebihiHarga" x-cloak>Harga aktual tidak boleh melebihi harga estimasi.</p>
                    @error('harga_aktual')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nama_toko" class="label">Nama toko / penyedia <span class="text-danger">*</span></label>
                    <input id="nama_toko" name="nama_toko" type="text" maxlength="255" required value="{{ old('nama_toko', $p->nama_toko) }}"
                           @class(['input', 'input-error' => $errors->has('nama_toko')])>
                    @error('nama_toko')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nomor_nota" class="label">Nomor nota <span class="text-danger">*</span></label>
                    <input id="nomor_nota" name="nomor_nota" type="text" maxlength="100" required value="{{ old('nomor_nota', $p->nomor_nota) }}"
                           @class(['input', 'input-error' => $errors->has('nomor_nota')])>
                    @error('nomor_nota')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="tanggal_nota" class="label">Tanggal nota <span class="text-danger">*</span></label>
                    <input id="tanggal_nota" name="tanggal_nota" type="date" max="{{ now()->toDateString() }}" required
                           value="{{ old('tanggal_nota', $p->tanggal_nota?->toDateString()) }}"
                           @class(['input', 'input-error' => $errors->has('tanggal_nota')])>
                    @error('tanggal_nota')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="mt-6 flex items-start gap-3 rounded-lg border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-slate-700">
                <x-icon name="info" class="mt-0.5 size-5 shrink-0 text-primary" />
                <p><strong>Instruksi fisik dokumen.</strong> Serahkan nota dan barang fisik langsung ke Divisi Keuangan untuk proses verifikasi.</p>
            </div>

            <div class="mt-6 flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-5">
                <a href="{{ route('pengajuan.show', $p) }}" class="btn btn-ghost">Batal</a>
                <button type="button" class="btn btn-primary" :disabled="kirim" @click="$dispatch('buka-konfirmasi-laporan')">Kirim Laporan</button>
            </div>
        </section>

        {{-- Perhitungan --}}
        <aside class="space-y-4 lg:col-span-2">
            <section class="card p-6">
                <h2 class="font-display text-xl font-bold text-slate-900">Perhitungan Realisasi</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-600">Dana dicairkan</dt><dd class="font-bold" x-text="rupiah(dana)"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-600">Kuantitas terbeli</dt><dd class="font-bold" x-text="(Number(kuantitas) || 0) + ' {{ $p->satuan->value }}'"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-600">Harga aktual satuan</dt><dd class="font-bold" x-text="rupiah(harga)"></dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-3"><dt class="text-slate-600">Total biaya aktual</dt><dd class="font-bold text-primary" x-text="rupiah(total)"></dd></div>
                </dl>
                <div class="mt-4 flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3">
                    <span class="text-xs font-bold uppercase tracking-wide text-slate-600">Sisa dana</span>
                    <span class="font-display text-2xl font-bold text-primary" x-text="rupiah(sisa)"></span>
                </div>
            </section>

            <div x-show="sisa > 0" x-cloak class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <x-icon name="dompet" class="mt-0.5 size-5 shrink-0" />
                <p><strong>Pengembalian sisa dana.</strong> Sisa dana <span class="font-bold" x-text="rupiah(sisa)"></span> wajib dikembalikan ke Bendahara.</p>
            </div>
            <div x-show="sisa <= 0" x-cloak class="flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                <x-icon name="centang" class="mt-0.5 size-5 shrink-0" />
                <p><strong>Tidak ada sisa dana.</strong> Dana terpakai seluruhnya.</p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Alur selanjutnya</p>
                <p class="mt-1" x-show="sisa > 0">Setelah laporan dikirim, status menjadi <strong>Menunggu Pengembalian Dana</strong>. Setelah Bendahara mengonfirmasi pengembalian, request masuk ke <strong>Cek Fisik</strong> oleh Divisi Keuangan.</p>
                <p class="mt-1" x-show="sisa <= 0" x-cloak>Setelah laporan dikirim, request langsung masuk ke <strong>Cek Fisik</strong> oleh Divisi Keuangan.</p>
            </div>
        </aside>
    </form>

    <x-modal id="konfirmasi-laporan" judul="Kirim laporan?">
        Setelah dikirim, laporan tidak dapat diubah kecuali dikembalikan oleh Divisi Keuangan.
        <x-slot:aksi>
            <button type="submit" form="form-lapor-kirim" class="btn btn-primary">Ya, Kirim</button>
        </x-slot:aksi>
    </x-modal>
@endsection
