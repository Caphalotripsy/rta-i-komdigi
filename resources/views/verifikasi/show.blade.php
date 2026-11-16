@extends('layouts.app')

@section('title', 'Verifikasi Fisik & Nota')

@section('breadcrumb')
    <x-breadcrumb :items="[['Beranda', route('dashboard')], ['Verifikasi & Closing', route('verifikasi.index')], [$pengajuan->kode_pengajuan]]" />
@endsection

@php
    $p = $pengajuan;
    $bisa = $p->status === \App\Enums\StatusPengajuan::CekFisik;
    $adaLaporan = $p->kuantitas_terbeli !== null;
    $selisihHarga = $adaLaporan ? (float) $p->harga_estimasi - (float) $p->harga_aktual : 0;
    $selisihTotal = $adaLaporan ? (float) $p->total_estimasi_biaya - (float) $p->total_biaya_aktual : 0;
    $selisihQty = $adaLaporan ? $p->kuantitas - $p->kuantitas_terbeli : 0;
    $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
@endphp

@section('content')
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-display text-3xl font-bold text-slate-900">Verifikasi Fisik &amp; Nota</h1>
            <p class="mt-1 text-sm text-slate-500">Cocokkan barang dan nota fisik dengan laporan pemohon, lalu tutup request atau kembalikan.</p>
        </div>
        <div class="flex items-center gap-2"><span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-bold text-primary">{{ $p->kode_pengajuan }}</span><x-status-badge :status="$p->status" /></div>
    </div>

    <div class="mb-5 flex items-start gap-3 rounded-lg border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-slate-700">
        <x-icon name="info" class="mt-0.5 size-5 shrink-0 text-primary" />
        <p><strong>Pemeriksaan fisik dilakukan di luar sistem.</strong> Pastikan barang, kuantitas, dan nota fisik sudah diterima dan dicek langsung sebelum menutup request.</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card p-6">
                <h2 class="font-display text-xl font-bold text-slate-900">Data Permohonan</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Pemohon &amp; divisi</dt><dd class="mt-1 font-bold">{{ $p->pemohon->name }}</dd><dd class="text-xs text-slate-500">{{ $p->pemohon->divisi ?? '—' }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Dilaporkan pada</dt><dd class="mt-1 font-bold">{{ $p->updated_at->translatedFormat('d M Y, H:i') }} WITA</dd></div>
                    <div class="rounded-lg bg-sky-50 p-4 sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Barang &amp; spesifikasi &middot; {{ $p->kategori->label() }}</dt><dd class="mt-1 text-lg font-bold">{{ $p->nama_barang }}</dd><dd class="text-xs text-slate-600">{{ $p->spesifikasi ?: '—' }}</dd></div>
                </dl>
            </section>

            <section class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h2 class="font-display text-xl font-bold text-slate-900">Rekonsiliasi: Diminta vs Dilaporkan</h2>
                    <span class="text-xs font-bold text-slate-500">Hanya baca</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[32rem] text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                            <tr><th class="px-6 py-3">Komponen</th><th class="px-3 py-3 text-right">Diminta</th><th class="px-3 py-3 text-right">Dilaporkan</th><th class="px-6 py-3 text-right">Selisih</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-6 py-3 font-bold">Kuantitas</td>
                                <td class="px-3 py-3 text-right">{{ $p->kuantitas }} {{ $p->satuan->label() }}</td>
                                <td class="px-3 py-3 text-right font-bold">{{ $adaLaporan ? $p->kuantitas_terbeli.' '.$p->satuan->label() : '—' }}</td>
                                <td class="px-6 py-3 text-right"><span @class(['rounded-md px-2 py-1 text-xs font-bold', 'bg-green-50 text-green-700' => $selisihQty === 0, 'bg-amber-50 text-amber-800' => $selisihQty !== 0])>{{ $selisihQty === 0 ? 'Sesuai' : 'Kurang '.$selisihQty }}</span></td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3 font-bold">Harga satuan</td>
                                <td class="px-3 py-3 text-right">{{ $rp($p->harga_estimasi) }}</td>
                                <td class="px-3 py-3 text-right font-bold">{{ $adaLaporan ? $rp($p->harga_aktual) : '—' }}</td>
                                <td class="px-6 py-3 text-right text-xs font-bold {{ $selisihHarga > 0 ? 'text-secondary' : 'text-slate-500' }}">{{ $selisihHarga > 0 ? 'Hemat '.$rp($selisihHarga).' / '.$p->satuan->value : 'Sesuai' }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3 font-bold">Total nilai belanja</td>
                                <td class="px-3 py-3 text-right">{{ $rp($p->total_estimasi_biaya) }}</td>
                                <td class="px-3 py-3 text-right font-bold">{{ $adaLaporan ? $rp($p->total_biaya_aktual) : '—' }}</td>
                                <td class="px-6 py-3 text-right text-xs font-bold {{ $selisihTotal > 0 ? 'text-secondary' : 'text-slate-500' }}">{{ $selisihTotal > 0 ? 'Selisih '.$rp($selisihTotal) : 'Sesuai' }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3 font-bold">Dana dicairkan</td>
                                <td class="px-3 py-3 text-right" colspan="2">{{ $rp($p->danaDicairkanEfektif()) }} <span class="text-xs text-slate-500">&middot; Batch {{ $p->batch?->kode_batch ?? '—' }}</span></td>
                                <td class="px-6 py-3"></td>
                            </tr>
                            <tr class="bg-amber-50/60">
                                <td class="px-6 py-3 font-bold text-amber-800">Sisa dana</td>
                                <td class="px-3 py-3 text-right font-bold text-amber-800" colspan="2">{{ $p->sisa_dana === null ? '—' : $rp($p->sisa_dana) }}</td>
                                <td class="px-6 py-3 text-right"><span class="rounded-md bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">{{ $p->labelPengembalianDana() }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card p-6">
                <h2 class="font-display text-xl font-bold text-slate-900">Data Nota</h2>
                <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Nama toko</dt><dd class="mt-1 font-bold">{{ $p->nama_toko ?? '—' }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Nomor nota</dt><dd class="mt-1 font-mono text-sm font-bold">{{ $p->nomor_nota ?? '—' }}</dd></div>
                    <div class="rounded-lg bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Tanggal nota</dt><dd class="mt-1 font-bold">{{ $p->tanggal_nota?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                    <div class="rounded-lg bg-sky-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Total biaya dilaporkan</dt><dd class="mt-1 font-display text-xl font-bold text-primary">{{ $adaLaporan ? $rp($p->total_biaya_aktual) : '—' }}</dd></div>
                </dl>
            </section>
        </div>

        <aside class="card h-fit p-6">
            <h2 class="font-display text-xl font-bold text-slate-900">Riwayat Request</h2>
            <ol class="mt-4 space-y-5 border-l-2 border-secondary/40 pl-5">
                @foreach ($p->riwayat->reverse() as $r)
                    <li class="relative">
                        <span class="absolute -left-[1.62rem] top-1 size-3 rounded-full border-2 border-white bg-secondary"></span>
                        <p class="text-sm font-bold text-slate-900">{{ \App\Enums\StatusPengajuan::tryFrom($r->status_ke)?->label() ?? $r->status_ke }}</p>
                        <p class="text-xs text-slate-500">{{ $r->aktor->name }} &middot; {{ $r->aktor->role->label() }}</p>
                        <p class="text-xs text-slate-500">{{ $r->created_at->translatedFormat('d M Y, H:i') }} WITA</p>
                        @if ($r->jenisMasalahLabel())<p class="mt-1"><span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-bold text-danger">{{ $r->jenisMasalahLabel() }}</span></p>@endif
                        @if ($r->catatan)<p class="mt-1 whitespace-pre-line rounded-md bg-slate-50 px-3 py-2 text-xs text-slate-700">{{ $r->catatan }}</p>@endif
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>

    <section class="card mt-6 p-6">
        @if ($bisa)
            <h2 class="font-display text-xl font-bold text-slate-900">Pemeriksaan &amp; Pengesahan Fisik</h2>
            <form id="form-valid" method="POST" action="{{ route('verifikasi.valid', $p) }}"
                  x-data="{ barang: false, nominal: false, kuantitas: false, kirim: false, get lengkap() { return this.barang && this.nominal && this.kuantitas } }"
                  @submit="setTimeout(() => kirim = true, 10)">
                @csrf
                <div class="mt-4 space-y-3">
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-slate-50 p-4"><input type="checkbox" name="cek_barang" value="1" x-model="barang" class="mt-0.5 size-5 rounded border-slate-300"><span><strong class="block text-sm">Barang fisik sesuai spesifikasi</strong><span class="text-xs text-slate-500">Jenis, spesifikasi, dan kondisi barang sudah dicek langsung.</span></span></label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-slate-50 p-4"><input type="checkbox" name="cek_nominal" value="1" x-model="nominal" class="mt-0.5 size-5 rounded border-slate-300"><span><strong class="block text-sm">Nominal nota sesuai laporan</strong><span class="text-xs text-slate-500">Nominal pada nota fisik sama dengan harga aktual yang dilaporkan.</span></span></label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-slate-50 p-4"><input type="checkbox" name="cek_kuantitas" value="1" x-model="kuantitas" class="mt-0.5 size-5 rounded border-slate-300"><span><strong class="block text-sm">Kuantitas fisik sesuai laporan</strong><span class="text-xs text-slate-500">Jumlah barang yang diterima sama dengan kuantitas terbeli.</span></span></label>
                </div>
                @if ($errors->has('cek_barang') || $errors->has('cek_nominal') || $errors->has('cek_kuantitas'))
                    <p class="field-error mt-2" role="alert">Seluruh pemeriksaan fisik harus dicentang sebelum request ditutup.</p>
                @endif
                <p class="mt-3 flex items-center gap-2 text-xs text-slate-500"><x-icon name="info" class="size-4 shrink-0" /> Data tidak dapat diedit di halaman ini. Bila ada yang tidak sesuai, kembalikan ke pemohon.</p>

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5">
                    <button type="button" class="btn btn-ghost !text-danger hover:!bg-red-50" @click="$dispatch('buka-kembalikan-keuangan')">&larr; Kembalikan ke Kepala Divisi</button>
                    <button type="button" class="btn btn-primary" :disabled="!lengkap || kirim" @click="$dispatch('buka-konfirmasi-valid')">Valid &amp; Tutup Request</button>
                </div>
            </form>

            <x-modal id="konfirmasi-valid" judul="Tutup request ini?">
                Request <strong>{{ $p->kode_pengajuan }}</strong> akan berstatus <strong>Selesai</strong> dan masuk antrean Inventaris. Tindakan ini tidak dapat dibatalkan.
                <x-slot:aksi>
                    <button type="submit" form="form-valid" class="btn btn-primary">Ya, Tutup Request</button>
                </x-slot:aksi>
            </x-modal>

            <x-modal id="kembalikan-keuangan" judul="Kembalikan ke Kepala Divisi?">
                <form id="form-kembalikan" method="POST" action="{{ route('verifikasi.kembalikan', $p) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label for="jenis_masalah" class="label">Jenis masalah <span class="text-danger">*</span></label>
                        <select id="jenis_masalah" name="jenis_masalah" required @class(['input', 'input-error' => $errors->has('jenis_masalah')])>
                            <option value="">Pilih jenis masalah</option>
                            @foreach ($jenisMasalah as $kode => $label)<option value="{{ $kode }}" @selected(old('jenis_masalah') === $kode)>{{ $label }}</option>@endforeach
                        </select>
                        @error('jenis_masalah')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="catatan" class="label">Catatan (wajib) <span class="text-danger">*</span></label>
                        <textarea id="catatan" name="catatan" rows="3" maxlength="1000" required @class(['input', 'input-error' => $errors->has('catatan')]) placeholder="Apa yang tidak sesuai dan perlu diperbaiki?">{{ old('catatan') }}</textarea>
                        @error('catatan')<p class="field-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                </form>
                <x-slot:aksi>
                    <button type="submit" form="form-kembalikan" class="btn btn-danger">Kembalikan</button>
                </x-slot:aksi>
            </x-modal>
        @else
            <div class="flex items-center gap-2 text-sm text-slate-600">
                <x-icon name="info" class="size-5 shrink-0 text-primary" />
                Request ini berstatus <strong>{{ $p->status->label() }}</strong> dan tidak menunggu verifikasi.
            </div>
        @endif
    </section>
@endsection
